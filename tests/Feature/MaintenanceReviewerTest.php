<?php
namespace Tests\Feature;

use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceReviewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewer_can_only_see_and_validate_tickets_in_authorized_range(): void
    {
        $user = User::factory()->create();
        $user->assignRole('revisor de tickets');
        $user->maintenanceReviewerAccess()->create(['range_type' => 'relative', 'days_before' => 30, 'days_after' => 0]);
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $property = Property::create(['internal_name' => 'Casa', 'property_type_id' => $type->id, 'full_address' => 'Calle 1', 'status' => Property::STATUS_AVAILABLE]);
        $create = fn ($title, $date) => MaintenanceTicket::create(['property_id' => $property->id, 'reported_by_role' => 'administrador', 'reported_by_name' => 'Admin', 'category' => 'plomeria', 'priority' => 'media', 'status' => 'completado', 'title' => $title, 'exact_location' => 'Cocina', 'description' => 'Trabajo', 'reported_at' => $date]);
        $inside = $create('Ticket visible', now()->subDays(2));
        $outside = $create('Ticket fuera de rango', now()->subDays(40));
        $unfinished = $create('Ticket sin completar', now()->subDays(2));
        $unfinished->update(['status' => 'en_proceso']);
        $this->actingAs($user)->get(route('maintenance.index'))->assertOk()->assertSee('Ticket visible')->assertDontSee('Ticket fuera de rango');
        $this->actingAs($user)->get(route('maintenance.show', $unfinished))->assertForbidden();
        $this->actingAs($user)->patch(route('maintenance.validate'), ['ticket_uuids' => [$unfinished->uuid]])->assertForbidden();
        $this->actingAs($user)->get(route('maintenance.show', $inside))->assertOk()->assertSee('Validar ticket')->assertDontSee('Subir');
        $this->actingAs($user)->get(route('maintenance.show', $outside))->assertForbidden();
        $this->actingAs($user)->patch(route('maintenance.validate'), ['ticket_uuids' => [$inside->uuid, $outside->uuid]])->assertForbidden();
        $this->assertDatabaseCount('maintenance_ticket_validations', 0);
        $this->actingAs($user)->patch(route('maintenance.validate'), ['ticket_uuids' => [$inside->uuid]])->assertRedirect();
        $this->actingAs($user)->patch(route('maintenance.validate'), ['ticket_uuids' => [$inside->uuid]])->assertRedirect();
        $this->assertDatabaseCount('maintenance_ticket_validations', 1);
        $this->assertDatabaseHas('maintenance_ticket_validations', ['ticket_id' => $inside->id, 'validated_by_user_id' => $user->id]);
        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($user)->patch(route('maintenance.status', $inside), ['status' => 'reabierto'])->assertForbidden();
    }

    public function test_reviewer_without_range_sees_nothing_and_login_ignores_intended_url(): void
    {
        $user = User::factory()->create();
        $user->assignRole('revisor de tickets');
        $this->actingAs($user)->get(route('maintenance.index'))->assertOk()->assertSee('No tienes un rango configurado');
        auth()->logout();
        $this->withSession(['url.intended' => route('dashboard')])->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('maintenance.index'));
    }

    public function test_admin_configures_custom_reviewer_dates_and_invalid_range_does_not_create_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(\Spatie\Permission\Models\Role::findOrCreate('administrador', 'web'));
        $data = ['name' => 'Revisor', 'email' => 'revisor@example.test', 'password' => 'password123', 'is_active' => 1, 'role_names' => ['revisor de tickets'], 'reviewer_range_type' => 'custom', 'reviewer_starts_on' => '2026-01-01', 'reviewer_ends_on' => '2026-02-01'];
        $this->actingAs($admin)->post(route('access.users.store'), $data)->assertSessionHasNoErrors();
        $user = User::where('email', $data['email'])->firstOrFail();
        $this->assertSame('custom', $user->maintenanceReviewerAccess->range_type);
        $this->assertSame('2026-01-01', $user->maintenanceReviewerAccess->starts_on->format('Y-m-d'));
        $this->assertSame('2026-02-01', $user->maintenanceReviewerAccess->ends_on->format('Y-m-d'));
        $this->actingAs($admin)->get(route('access.index'))->assertOk()->assertSee('Rango para revisor de tickets')->assertSee('2026-01-01');
        $data['email'] = 'invalido@example.test';
        $data['reviewer_ends_on'] = null;
        $this->actingAs($admin)->post(route('access.users.store'), $data)->assertSessionHasErrors('reviewer_starts_on');
        $this->assertDatabaseMissing('users', ['email' => 'invalido@example.test']);
    }
}
