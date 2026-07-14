<?php

namespace Tests\Feature;

use App\Mail\MaintenanceTicketEventMail;
use App\Models\Expense;
use App\Models\MaintenanceProvider;
use App\Models\MaintenanceTicket;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Zone;
use App\Support\NotificationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MaintenanceModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_maintenance_index_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('maintenance.index'));

        $response->assertOk();
        $response->assertSee('Mantenimiento');
    }

    public function test_ticket_can_be_created_with_multiple_files(): void
    {
        Storage::fake('public');
        Mail::fake();

        $user = User::factory()->create();
        $property = $this->createPropertyFixture($user);

        $response = $this
            ->actingAs($user)
            ->post(route('maintenance.store'), [
                'property_id' => $property->id,
                'category' => 'plomeria',
                'priority' => 'alta',
                'title' => 'Fuga en baño principal',
                'reference' => 'REP-001',
                'exact_location' => 'Baño principal',
                'description' => 'Se detecta fuga constante en lavabo',
                'additional_notes' => 'Urgente por filtración',
                'reported_at' => now()->format('Y-m-d H:i:s'),
                'files' => [
                    UploadedFile::fake()->image('foto1.jpg'),
                    UploadedFile::fake()->create('video.mp4', 1024, 'video/mp4'),
                ],
            ]);

        $ticket = MaintenanceTicket::query()->where('title', 'Fuga en baño principal')->first();
        $this->assertNotNull($ticket);
        $response->assertRedirect(route('maintenance.show', $ticket));
        $this->assertDatabaseHas('maintenance_tickets', [
            'id' => $ticket->id,
            'property_id' => $property->id,
            'status' => 'pendiente',
        ]);
        $this->assertDatabaseCount('maintenance_ticket_files', 2);
        $this->assertDatabaseHas('maintenance_ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'to_status' => 'pendiente',
        ]);
    }

    public function test_new_ticket_notifies_only_technician_advisor_and_tenant(): void
    {
        Mail::fake();

        $reporter = User::factory()->create(['email' => 'reporter@example.com']);
        Role::query()->create(['name' => 'administrador', 'guard_name' => 'web']);
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->assignRole('administrador');
        $advisor = User::factory()->create(['email' => 'advisor@example.com']);
        $technicianUser = User::factory()->create(['email' => 'technician-user@example.com']);
        $tenant = Tenant::create([
            'full_name' => 'Inquilino Notificado',
            'phone_primary' => '9991112233',
            'email' => 'tenant@example.com',
            'dossier_status' => Tenant::DOSSIER_INCOMPLETE,
            'is_active' => true,
        ]);
        $property = $this->createPropertyFixture($reporter);
        $property->update(['tenant_id' => $tenant->id]);
        $property->advisors()->attach($advisor->id);
        $owner = Owner::create([
            'name' => 'Propietario No Notificado',
            'phone' => '9992223344',
            'email' => 'owner@example.com',
            'is_active' => true,
        ]);
        $property->owners()->attach($owner->id);
        $provider = MaintenanceProvider::create([
            'type' => 'tecnico_interno',
            'name' => 'Técnico asignado',
            'email' => 'technician-provider@example.com',
            'specialty' => 'Plomería',
            'user_id' => $technicianUser->id,
            'is_active' => true,
        ]);

        $this
            ->actingAs($reporter)
            ->post(route('maintenance.store'), [
                'property_id' => $property->id,
                'category' => 'plomeria',
                'priority' => 'media',
                'title' => 'Fuga reportada sin aviso al propietario',
                'exact_location' => 'Baño',
                'description' => 'Validar destinatarios del nuevo reporte',
                'reported_at' => now()->format('Y-m-d H:i:s'),
                'provider_id' => $provider->id,
            ])
            ->assertRedirect();

        $ticket = MaintenanceTicket::query()
            ->where('title', 'Fuga reportada sin aviso al propietario')
            ->firstOrFail();

        foreach (['technician-provider@example.com', 'technician-user@example.com', 'advisor@example.com', 'tenant@example.com'] as $recipient) {
            $this->assertDatabaseHas('maintenance_ticket_notifications', [
                'ticket_id' => $ticket->id,
                'event' => 'nuevo_reporte',
                'recipient' => $recipient,
            ]);
        }

        foreach (['owner@example.com', 'reporter@example.com', 'admin@example.com'] as $recipient) {
            $this->assertDatabaseMissing('maintenance_ticket_notifications', [
                'ticket_id' => $ticket->id,
                'event' => 'nuevo_reporte',
                'recipient' => $recipient,
            ]);
        }

        Mail::assertSent(MaintenanceTicketEventMail::class, 4);
    }

    public function test_new_ticket_does_not_notify_tenant_when_switch_is_disabled(): void
    {
        Mail::fake();
        NotificationSettings::setMatrix([
            NotificationSettings::ROLE_TENANT => [
                NotificationSettings::EVENT_MAINTENANCE_CREATED => false,
            ],
        ]);

        $reporter = User::factory()->create(['email' => 'reporter@example.com']);
        $advisor = User::factory()->create(['email' => 'advisor@example.com']);
        $tenant = Tenant::create([
            'full_name' => 'Inquilino Sin Aviso',
            'phone_primary' => '9991112233',
            'email' => 'tenant@example.com',
            'dossier_status' => Tenant::DOSSIER_INCOMPLETE,
            'is_active' => true,
        ]);
        $property = $this->createPropertyFixture($reporter);
        $property->update(['tenant_id' => $tenant->id]);
        $property->advisors()->attach($advisor->id);

        $this
            ->actingAs($reporter)
            ->post(route('maintenance.store'), [
                'property_id' => $property->id,
                'category' => 'plomeria',
                'priority' => 'media',
                'title' => 'Reporte con switch de inquilino apagado',
                'exact_location' => 'Baño',
                'description' => 'Validar configuración de notificaciones',
                'reported_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $ticket = MaintenanceTicket::query()
            ->where('title', 'Reporte con switch de inquilino apagado')
            ->firstOrFail();

        $this->assertDatabaseMissing('maintenance_ticket_notifications', [
            'ticket_id' => $ticket->id,
            'event' => 'nuevo_reporte',
            'recipient' => 'tenant@example.com',
        ]);
        $this->assertDatabaseHas('maintenance_ticket_notifications', [
            'ticket_id' => $ticket->id,
            'event' => 'nuevo_reporte',
            'recipient' => 'advisor@example.com',
        ]);
        Mail::assertSent(MaintenanceTicketEventMail::class, 1);
    }

    public function test_tenant_can_create_ticket_with_minimal_fields(): void
    {
        Storage::fake('public');
        Mail::fake();

        Role::query()->firstOrCreate(['name' => 'inquilino', 'guard_name' => 'web']);
        $tenantUser = User::factory()->create(['email' => 'inquilino@example.com']);
        $tenantUser->assignRole('inquilino');
        $tenant = Tenant::create([
            'full_name' => 'Inquilino Prueba',
            'phone_primary' => '9991112233',
            'email' => 'inquilino@example.com',
            'dossier_status' => Tenant::DOSSIER_INCOMPLETE,
            'is_active' => true,
        ]);
        $property = $this->createPropertyFixture($tenantUser);
        $property->update([
            'tenant_id' => $tenant->id,
            'current_tenant_name' => $tenant->full_name,
        ]);

        $response = $this
            ->actingAs($tenantUser)
            ->post(route('maintenance.store'), [
                'property_id' => $property->id,
                'title' => 'No hay agua caliente',
                'files' => [
                    UploadedFile::fake()->image('evidencia.jpg'),
                ],
            ]);

        $ticket = MaintenanceTicket::query()->where('title', 'No hay agua caliente')->first();
        $this->assertNotNull($ticket);
        $response->assertRedirect(route('maintenance.show', $ticket));
        $this->assertSame('sin_asignar', $ticket->priority);
        $this->assertSame('pendiente', $ticket->status);
        $this->assertDatabaseCount('maintenance_ticket_files', 1);
    }

    public function test_maintenance_ticket_detail_displays_operational_sections(): void
    {
        $user = User::factory()->create();
        $property = $this->createPropertyFixture($user);
        $ticket = MaintenanceTicket::create([
            'property_id' => $property->id,
            'reported_by_user_id' => $user->id,
            'reported_by_role' => 'administrador',
            'reported_by_name' => $user->name,
            'category' => 'plomeria',
            'priority' => 'alta',
            'status' => 'pendiente',
            'title' => 'Filtro de agua con fuga',
            'exact_location' => 'Cocina',
            'description' => 'Goteo constante en filtro bajo tarja',
            'reported_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('maintenance.show', $ticket));

        $response->assertOk();
        $response->assertSee('Evidencias y archivos del incidente');
        $response->assertSee('Evidencias y archivos del trabajo finalizado');
        $response->assertSee('Chat');
        $response->assertSee('Costos y gastos del incidente');
        $response->assertSeeInOrder([
            'Costos y gastos del incidente',
            'Evidencias y archivos del trabajo finalizado',
        ]);
    }

    public function test_ticket_detail_uses_relative_file_urls_and_hides_chat_card(): void
    {
        config(['app.url' => 'http://localhost']);

        $user = User::factory()->create();
        $property = $this->createPropertyFixture($user);
        $ticket = MaintenanceTicket::create([
            'property_id' => $property->id,
            'reported_by_user_id' => $user->id,
            'reported_by_role' => 'administrador',
            'reported_by_name' => $user->name,
            'category' => 'plomeria',
            'priority' => 'alta',
            'status' => 'pendiente',
            'title' => 'Evidencias visibles',
            'exact_location' => 'Cocina',
            'description' => 'Archivos de prueba',
            'reported_at' => now(),
        ]);
        $ticket->files()->create([
            'uploaded_by_user_id' => $user->id,
            'kind' => 'evidencia',
            'path' => 'maintenance/' . $ticket->id . '/evidencia/foto.jpg',
            'original_name' => 'foto.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 123,
            'is_compressed' => false,
        ]);
        $ticket->files()->create([
            'uploaded_by_user_id' => $user->id,
            'kind' => 'video',
            'path' => 'maintenance/' . $ticket->id . '/video/video.mp4',
            'original_name' => 'video.mp4',
            'mime_type' => 'video/mp4',
            'size' => 456,
            'is_compressed' => false,
        ]);
        $ticket->files()->create([
            'uploaded_by_user_id' => $user->id,
            'kind' => 'documento',
            'path' => 'maintenance/' . $ticket->id . '/documento/reporte.pdf',
            'original_name' => 'reporte.pdf',
            'mime_type' => 'application/pdf',
            'size' => 789,
            'is_compressed' => false,
        ]);
        $ticket->files()->create([
            'uploaded_by_user_id' => $user->id,
            'kind' => 'trabajo_finalizado',
            'path' => 'maintenance/' . $ticket->id . '/trabajo_finalizado/final.jpg',
            'original_name' => 'final.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 101,
            'is_compressed' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('maintenance.show', $ticket));

        $response->assertOk();
        $response->assertSee('/storage/maintenance/' . $ticket->id . '/evidencia/foto.jpg', false);
        $response->assertSee('/storage/maintenance/' . $ticket->id . '/video/video.mp4', false);
        $response->assertSee('/storage/maintenance/' . $ticket->id . '/documento/reporte.pdf', false);
        $response->assertSee('/storage/maintenance/' . $ticket->id . '/trabajo_finalizado/final.jpg', false);
        $response->assertSee('<video', false);
        $response->assertSee('ticketFilePreviewModal', false);
        $response->assertSee('application/pdf', false);
        $response->assertSee('js-delete-ticket-file', false);
        $response->assertSee('data-file-delete-url=', false);
        $response->assertSee('Evidencias y archivos del trabajo finalizado');
        $response->assertSee('name="kind" value="reporte"', false);
        $response->assertSee('name="kind" value="trabajo_finalizado"', false);
        $response->assertSee('Evidencia · ' . $ticket->files()->where('original_name', 'foto.jpg')->first()->created_at?->format('d/m/Y H:i'), false);
        $response->assertSee('Trabajo finalizado · ' . $ticket->files()->where('original_name', 'final.jpg')->first()->created_at?->format('d/m/Y H:i'), false);
        $response->assertDontSee('ticket-file-list', false);
        $response->assertDontSee('http://localhost/storage/maintenance/', false);
        $response->assertDontSee('href="#ticket-chat-section"', false);
        $response->assertSee('<section class="ticket-panel d-none" id="ticket-chat-section" hidden>', false);
    }

    public function test_multiple_costs_create_expenses_and_tenant_costs_are_excluded_from_totals(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $property = $this->createPropertyFixture($user);
        $ticket = MaintenanceTicket::create([
            'property_id' => $property->id,
            'reported_by_user_id' => $user->id,
            'reported_by_role' => 'administrador',
            'reported_by_name' => $user->name,
            'category' => 'plomeria',
            'priority' => 'alta',
            'status' => 'pendiente',
            'title' => 'Reparación de tubería',
            'exact_location' => 'Cocina',
            'description' => 'Cambio de tubería dañada',
            'reported_at' => now(),
        ]);

        $this->actingAs($user)
            ->from(route('maintenance.show', $ticket))
            ->put(route('maintenance.costs', $ticket), [
                'labor_cost' => 300,
                'material_cost' => 200,
                'final_cost' => 9999,
                'currency' => 'MXN',
                'payer' => 'inquilino',
                'payment_rule' => 'mal_uso',
                'is_paid' => '1',
                'notes' => 'Daño atribuible al inquilino',
                'invoice_files' => [UploadedFile::fake()->create('factura-inquilino.pdf', 100, 'application/pdf')],
            ])
            ->assertRedirect(route('maintenance.show', $ticket));

        $this->actingAs($user)
            ->from(route('maintenance.show', $ticket))
            ->put(route('maintenance.costs', $ticket), [
                'labor_cost' => 400,
                'material_cost' => 200,
                'currency' => 'MXN',
                'payer' => 'administracion',
                'payment_rule' => 'preventivo',
            ])
            ->assertRedirect(route('maintenance.show', $ticket));

        $this->assertSame(2, $ticket->costs()->count());
        $this->assertSame(2, Expense::query()->where('property_id', $property->id)->count());
        $this->assertSame(600.0, (float) Expense::query()->includedInTotals()->sum('amount'));
        $this->assertDatabaseHas('expenses', [
            'property_id' => $property->id,
            'amount' => 500,
            'excluded_from_totals' => true,
        ]);
        $this->assertNotNull(Expense::query()->where('amount', 500)->value('paid_at'));
        $this->assertDatabaseCount('expense_files', 1);

        $this->actingAs($user)
            ->get(route('maintenance.show', $ticket))
            ->assertOk()
            ->assertSee('No contabiliza')
            ->assertSee('factura-inquilino.pdf')
            ->assertSee('js-maintenance-invoice-preview', false);

        $this->actingAs($user)
            ->get(route('expenses.index', ['property' => $property->uuid]))
            ->assertOk()
            ->assertViewHas('expenses', fn($expenses): bool => $expenses->count() === 2)
            ->assertViewHas('summary', fn(array $summary): bool => $summary['overdue_total'] + $summary['pending_total'] === 600.0);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('profitabilitySummary', fn(array $summary): bool => $summary['expense_total'] === 600.0);
    }

    public function test_ticket_file_can_be_deleted_from_detail(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $property = $this->createPropertyFixture($user);
        $ticket = MaintenanceTicket::create([
            'property_id' => $property->id,
            'reported_by_user_id' => $user->id,
            'reported_by_role' => 'administrador',
            'reported_by_name' => $user->name,
            'category' => 'plomeria',
            'priority' => 'alta',
            'status' => 'pendiente',
            'title' => 'Eliminar evidencia',
            'exact_location' => 'Cocina',
            'description' => 'Archivo para borrar',
            'reported_at' => now(),
        ]);
        $path = 'maintenance/' . $ticket->id . '/evidencia/foto.jpg';
        Storage::disk('public')->put($path, 'fake-image');
        $file = $ticket->files()->create([
            'uploaded_by_user_id' => $user->id,
            'kind' => 'evidencia',
            'path' => $path,
            'original_name' => 'foto.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 10,
            'is_compressed' => false,
        ]);

        $this->actingAs($user)
            ->delete(route('maintenance.files.destroy', [$ticket, $file]))
            ->assertRedirect();

        $this->assertDatabaseMissing('maintenance_ticket_files', ['id' => $file->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_ticket_can_be_assigned_and_completed(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $property = $this->createPropertyFixture($user);
        $ticket = MaintenanceTicket::create([
            'property_id' => $property->id,
            'reported_by_user_id' => $user->id,
            'reported_by_role' => 'administrador',
            'reported_by_name' => $user->name,
            'category' => 'electricidad',
            'priority' => 'media',
            'status' => 'pendiente',
            'title' => 'Luminaria sin energía',
            'exact_location' => 'Sala',
            'description' => 'No enciende foco principal',
            'reported_at' => now(),
        ]);

        $provider = MaintenanceProvider::create([
            'type' => 'tecnico_interno',
            'name' => 'Técnico 01',
            'email' => 'tecnico01@example.com',
            'specialty' => 'Electricidad',
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $assignResponse = $this
            ->actingAs($user)
            ->post(route('maintenance.assign', $ticket), [
                'provider_id' => $provider->id,
                'notes' => 'Asignación inicial',
            ]);

        $assignResponse->assertRedirect();
        $this->assertDatabaseHas('maintenance_ticket_assignments', [
            'ticket_id' => $ticket->id,
            'provider_id' => $provider->id,
            'is_current' => true,
        ]);

        $statusResponse = $this
            ->actingAs($user)
            ->patch(route('maintenance.status', $ticket), [
                'status' => 'completado',
                'notes' => 'Trabajo finalizado',
            ]);

        $statusResponse->assertRedirect();
        $ticket->refresh();
        $this->assertSame('completado', $ticket->status);
        $this->assertNotNull($ticket->completed_at);
        $this->assertDatabaseHas('maintenance_ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'to_status' => 'completado',
        ]);
    }

    public function test_status_change_notifies_only_advisor_assigned_technician_and_tenant(): void
    {
        Mail::fake();

        Role::query()->create(['name' => 'administrador', 'guard_name' => 'web']);
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->assignRole('administrador');
        $advisor = User::factory()->create(['email' => 'advisor@example.com']);
        $technicianUser = User::factory()->create(['email' => 'technician-user@example.com']);
        $tenant = Tenant::create([
            'full_name' => 'Inquilino Notificado',
            'phone_primary' => '9991112233',
            'email' => 'tenant@example.com',
            'dossier_status' => Tenant::DOSSIER_INCOMPLETE,
            'is_active' => true,
        ]);
        $owner = Owner::create([
            'name' => 'Propietario No Notificado',
            'phone' => '9992223344',
            'email' => 'owner@example.com',
            'is_active' => true,
        ]);
        $property = $this->createPropertyFixture($admin);
        $property->update(['tenant_id' => $tenant->id]);
        $property->advisors()->attach($advisor->id);
        $property->owners()->attach($owner->id);
        $provider = MaintenanceProvider::create([
            'type' => 'tecnico_interno',
            'name' => 'Técnico asignado',
            'email' => 'technician-provider@example.com',
            'specialty' => 'Electricidad',
            'user_id' => $technicianUser->id,
            'is_active' => true,
        ]);
        $ticket = MaintenanceTicket::create([
            'property_id' => $property->id,
            'reported_by_user_id' => $admin->id,
            'reported_by_role' => 'administrador',
            'reported_by_name' => $admin->name,
            'current_provider_id' => $provider->id,
            'category' => 'electricidad',
            'priority' => 'media',
            'status' => 'pendiente',
            'title' => 'Cambio de estado con destinatarios limitados',
            'exact_location' => 'Sala',
            'description' => 'Validar destinatarios',
            'reported_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('maintenance.status', $ticket), [
                'status' => 'completado',
                'notes' => 'Trabajo finalizado',
            ])
            ->assertRedirect();

        foreach (['advisor@example.com', 'technician-provider@example.com', 'technician-user@example.com', 'tenant@example.com'] as $recipient) {
            $this->assertDatabaseHas('maintenance_ticket_notifications', [
                'ticket_id' => $ticket->id,
                'event' => 'cierre',
                'recipient' => $recipient,
            ]);
        }
        Mail::assertSent(MaintenanceTicketEventMail::class, 4);
        Mail::assertSent(MaintenanceTicketEventMail::class, function (MaintenanceTicketEventMail $mail) use ($ticket): bool {
            return $mail->ticket->is($ticket)
                && $mail->event === 'cierre'
                && $mail->subjectLine === 'Ticket de mantenimiento completado';
        });

        foreach (['owner@example.com', 'admin@example.com'] as $recipient) {
            $this->assertDatabaseMissing('maintenance_ticket_notifications', [
                'ticket_id' => $ticket->id,
                'event' => 'cierre',
                'recipient' => $recipient,
            ]);
        }
    }

    public function test_ticket_priority_and_provider_can_be_changed_from_index_panel(): void
    {
        $user = User::factory()->create();
        $property = $this->createPropertyFixture($user);
        $ticket = MaintenanceTicket::create([
            'property_id' => $property->id,
            'reported_by_user_id' => $user->id,
            'reported_by_role' => 'administrador',
            'reported_by_name' => $user->name,
            'category' => 'plomeria',
            'priority' => 'media',
            'status' => 'pendiente',
            'title' => 'Llave con fuga',
            'exact_location' => 'Baño',
            'description' => 'Fuga constante',
            'reported_at' => now(),
        ]);
        $provider = MaintenanceProvider::create([
            'type' => 'tecnico_interno',
            'name' => 'Santos Tecnico',
            'email' => 'santos@example.com',
            'specialty' => 'Plomería',
            'is_active' => true,
        ]);

        $indexResponse = $this
            ->actingAs($user)
            ->get(route('maintenance.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('Cambiar urgencia de', false);
        $indexResponse->assertSee('Cambiar técnico de', false);
        $indexResponse->assertSee(route('maintenance.meta', $ticket), false);

        $response = $this
            ->actingAs($user)
            ->from(route('maintenance.index'))
            ->patch(route('maintenance.meta', $ticket), [
                'priority' => 'urgente',
                'provider_id' => $provider->id,
                'notes' => 'Asignación rápida desde panel',
            ]);

        $response->assertRedirect(route('maintenance.index'));
        $ticket->refresh();

        $this->assertSame('urgente', $ticket->priority);
        $this->assertSame($provider->id, $ticket->current_provider_id);
        $this->assertSame('asignado', $ticket->status);
        $this->assertDatabaseHas('maintenance_ticket_assignments', [
            'ticket_id' => $ticket->id,
            'provider_id' => $provider->id,
            'is_current' => true,
        ]);
    }

    public function test_user_without_technician_administration_permission_cannot_open_technicians_module(): void
    {
        Role::query()->create(['name' => 'asesores', 'guard_name' => 'web']);
        $advisor = User::factory()->create();
        $advisor->assignRole('asesores');

        $this->actingAs($advisor)
            ->get(route('maintenance.index'))
            ->assertOk()
            ->assertDontSee('Administración de técnicos');

        $this->actingAs($advisor)
            ->get(route('maintenance.technicians.index'))
            ->assertForbidden();
    }

    public function test_user_with_technician_administration_permission_can_create_provider_and_system_access(): void
    {
        Mail::fake();

        Permission::findOrCreate('administracion de tecnicos', 'web');
        Role::query()->create(['name' => 'asesores', 'guard_name' => 'web']);
        $advisor = User::factory()->create();
        $advisor->assignRole('asesores');
        $advisor->givePermissionTo('administracion de tecnicos');

        $this->actingAs($advisor)
            ->get(route('maintenance.index'))
            ->assertOk()
            ->assertSee('Administración de técnicos');

        $this->actingAs($advisor)
            ->get(route('maintenance.technicians.index'))
            ->assertOk()
            ->assertSee('Administración de técnicos');

        $response = $this->actingAs($advisor)
            ->post(route('maintenance.providers.store'), [
                'type' => 'tecnico_interno',
                'name' => 'Técnico Permiso',
                'email' => 'tecnico.permiso@example.com',
                'phone' => '9991234567',
                'specialty' => 'Electricidad',
                'is_active' => '1',
                'create_user_account' => '1',
                'account_name' => 'Técnico Permiso',
                'account_email' => 'acceso.tecnico@example.com',
                'account_password' => 'password-tecnico',
            ]);

        $response->assertRedirect();

        $linkedUser = User::query()->where('email', 'acceso.tecnico@example.com')->firstOrFail();
        $this->assertTrue($linkedUser->hasRole('tecnico'));
        $this->assertDatabaseHas('maintenance_providers', [
            'name' => 'Técnico Permiso',
            'email' => 'tecnico.permiso@example.com',
            'user_id' => $linkedUser->id,
            'is_active' => true,
        ]);
    }

    private function createPropertyFixture(User $user): Property
    {
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);

        return Property::create([
            'internal_name' => 'Casa 10',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle Principal 10',
            'status' => Property::STATUS_OCCUPIED,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);
    }
}
