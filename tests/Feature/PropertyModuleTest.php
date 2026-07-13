<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\DossierDocumentRequirement;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PropertyModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_properties_index_is_displayed_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Montebello', 'slug' => 'montebello', 'is_active' => true]);

        Property::create([
            'internal_name' => 'Casa Montebello 101',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 1 #101',
            'status' => Property::STATUS_AVAILABLE,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('properties.index'));

        $response->assertOk();
        $response->assertSee('Casa Montebello 101');
    }

    public function test_property_create_page_is_displayed(): void
    {
        $user = User::factory()->create();
        PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        Zone::create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);

        $response = $this
            ->actingAs($user)
            ->get(route('properties.create'));

        $response->assertOk();
        $response->assertSee('Nueva Propiedad');
        $response->assertDontSee('Cuota de mantenimiento');
        $response->assertSee('Terreno');
        $this->assertDatabaseHas('property_types', [
            'name' => 'Terreno',
            'slug' => 'terreno',
            'is_active' => true,
        ]);
    }

    public function test_advisor_role_sees_all_properties_by_default(): void
    {
        $advisorRole = Role::query()->create(['name' => 'asesores', 'guard_name' => 'web']);
        $advisor = User::factory()->create();
        $advisor->assignRole($advisorRole);
        $otherUser = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);

        $assignedProperty = Property::create([
            'internal_name' => 'Casa Asignada',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle Asignada',
            'status' => Property::STATUS_AVAILABLE,
            'created_by' => $otherUser->id,
        ]);
        $assignedProperty->advisors()->attach($advisor->id);

        Property::create([
            'internal_name' => 'Casa General',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle General',
            'status' => Property::STATUS_AVAILABLE,
            'created_by' => $otherUser->id,
        ]);

        $this->actingAs($advisor)
            ->get(route('properties.index'))
            ->assertOk()
            ->assertSee('Casa Asignada')
            ->assertSee('Casa General')
            ->assertSee('Nueva Propiedad');

        $this->actingAs($advisor)
            ->get(route('properties.index', ['property_scope' => 'mine']))
            ->assertOk()
            ->assertSee('Casa Asignada')
            ->assertSee('Casa General');
    }

    public function test_advisor_role_can_create_edit_and_assign_property_to_another_advisor(): void
    {
        Storage::fake('public');

        $advisorRole = Role::query()->create(['name' => 'asesores', 'guard_name' => 'web']);
        $advisor = User::factory()->create(['name' => 'Asesor Creador']);
        $advisor->assignRole($advisorRole);
        $otherAdvisor = User::factory()->create(['name' => 'Asesor Responsable']);
        $otherAdvisor->assignRole($advisorRole);
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);

        $this->actingAs($advisor)
            ->get(route('properties.create'))
            ->assertOk()
            ->assertSee('Nueva Propiedad')
            ->assertSee('Asesor Responsable');

        $createResponse = $this->actingAs($advisor)
            ->post(route('properties.store'), [
                'internal_name' => 'Casa Creada por Asesor',
                'property_type_id' => $type->id,
                'zone_id' => $zone->id,
                'full_address' => 'Calle Asesor 100',
                'status' => Property::STATUS_AVAILABLE,
                'advisor_user_id' => $otherAdvisor->id,
                'facade_photo' => UploadedFile::fake()->image('fachada.jpg'),
                'new_owners' => [
                    [
                        'name' => 'Propietario Asesor',
                        'phone' => '9991112233',
                    ],
                ],
            ]);

        $property = Property::query()->where('internal_name', 'Casa Creada por Asesor')->firstOrFail();
        $owner = $property->owners()->firstOrFail();

        $createResponse
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('properties.show', $property));
        $this->assertSame($otherAdvisor->id, $property->advisor_user_id);
        $this->assertDatabaseHas('property_advisor', [
            'property_id' => $property->id,
            'user_id' => $otherAdvisor->id,
        ]);

        $this->actingAs($advisor)
            ->get(route('properties.edit', $property))
            ->assertOk()
            ->assertSee('Editar Propiedad')
            ->assertSee('Asesor Responsable');

        $this->actingAs($advisor)
            ->put(route('properties.update', $property), [
                'internal_name' => 'Casa Editada por Asesor',
                'property_type_id' => $type->id,
                'zone_id' => $zone->id,
                'full_address' => 'Calle Asesor 200',
                'status' => Property::STATUS_AVAILABLE,
                'advisor_user_id' => $otherAdvisor->id,
                'owner_ids' => [$owner->id],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('properties.show', $property));

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'internal_name' => 'Casa Editada por Asesor',
            'advisor_user_id' => $otherAdvisor->id,
        ]);

        $this->actingAs($advisor)
            ->putJson(route('properties.update.advisors', $property), [
                'advisor_user_ids' => [$advisor->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame($advisor->id, $property->fresh()->advisor_user_id);
    }

    public function test_admin_can_assign_responsible_advisors_from_properties_index(): void
    {
        $adminRole = Role::query()->create(['name' => 'administrador', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);
        $advisor = User::factory()->create(['name' => 'Asesor Demo']);
        $advisor->assignRole(Role::query()->create(['name' => 'asesores', 'guard_name' => 'web']));
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);
        $property = Property::create([
            'internal_name' => 'Casa con Asesor',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle Asesor',
            'status' => Property::STATUS_AVAILABLE,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->putJson(route('properties.update.advisors', $property), [
                'advisor_user_ids' => [$advisor->id],
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('property_advisor', [
            'property_id' => $property->id,
            'user_id' => $advisor->id,
        ]);
        $this->assertSame($advisor->id, $property->fresh()->advisor_user_id);
    }

    public function test_property_advisor_dropdown_only_shows_advisors_and_admins(): void
    {
        $adminRole = Role::query()->create(['name' => 'administrador', 'guard_name' => 'web']);
        $advisorRole = Role::query()->create(['name' => 'asesores', 'guard_name' => 'web']);
        $tenantRole = Role::query()->create(['name' => 'inquilino', 'guard_name' => 'web']);
        $admin = User::factory()->create(['name' => 'Admin Visible']);
        $admin->assignRole($adminRole);
        $advisor = User::factory()->create(['name' => 'Asesor Visible']);
        $advisor->assignRole($advisorRole);
        $tenantUser = User::factory()->create(['name' => 'Inquilino Oculto']);
        $tenantUser->assignRole($tenantRole);
        $permissionUser = User::factory()->create(['name' => 'Permiso Oculto']);
        Permission::findOrCreate('propiedades.ver_propias', 'web');
        $permissionUser->givePermissionTo('propiedades.ver_propias');
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);
        Property::create([
            'internal_name' => 'Casa Filtro Asesores',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle Filtro',
            'status' => Property::STATUS_AVAILABLE,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('properties.index'))
            ->assertOk()
            ->assertSee('Admin Visible')
            ->assertSee('Asesor Visible')
            ->assertDontSee('Inquilino Oculto')
            ->assertDontSee('Permiso Oculto');
    }

    public function test_property_advisor_assignment_rejects_users_without_advisor_or_admin_role(): void
    {
        $adminRole = Role::query()->create(['name' => 'administrador', 'guard_name' => 'web']);
        $tenantRole = Role::query()->create(['name' => 'inquilino', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);
        $tenantUser = User::factory()->create();
        $tenantUser->assignRole($tenantRole);
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);
        $property = Property::create([
            'internal_name' => 'Casa Rechazo Asesor',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle Rechazo',
            'status' => Property::STATUS_AVAILABLE,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->putJson(route('properties.update.advisors', $property), [
                'advisor_user_ids' => [$tenantUser->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('advisor_user_ids');

        $this->assertDatabaseMissing('property_advisor', [
            'property_id' => $property->id,
            'user_id' => $tenantUser->id,
        ]);
    }

    public function test_property_advisor_assignment_requires_specific_permission(): void
    {
        $manager = User::factory()->create();
        Permission::findOrCreate('usuarios.gestionar', 'web');
        Permission::findOrCreate('propiedades.asignar_asesores', 'web');
        $manager->givePermissionTo('usuarios.gestionar');
        $advisor = User::factory()->create(['name' => 'Asesor Demo']);
        $advisor->assignRole(Role::query()->create(['name' => 'asesores', 'guard_name' => 'web']));
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);
        $property = Property::create([
            'internal_name' => 'Casa Permiso Asesor',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle Permiso',
            'status' => Property::STATUS_AVAILABLE,
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->putJson(route('properties.update.advisors', $property), [
                'advisor_user_ids' => [$advisor->id],
            ])
            ->assertForbidden();

        $manager->givePermissionTo('propiedades.asignar_asesores');

        $this->actingAs($manager)
            ->putJson(route('properties.update.advisors', $property), [
                'advisor_user_ids' => [$advisor->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_property_can_be_created_with_new_owner(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Terreno', 'slug' => 'terreno', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Centro', 'slug' => 'centro', 'is_active' => true]);

        $response = $this
            ->actingAs($user)
            ->post(route('properties.store'), [
                'internal_name' => 'Terreno Centro 201',
                'internal_reference' => 'TC-201',
                'property_type_id' => $type->id,
                'zone_id' => $zone->id,
                'full_address' => 'Calle 20 #201',
                'status' => Property::STATUS_AVAILABLE,
                'facade_photo' => UploadedFile::fake()->image('facade.jpg'),
                'new_owners' => [
                    [
                        'name' => 'Juan Perez',
                        'phone' => '9991234567',
                        'email' => 'juan@example.com',
                        'owner_type' => Owner::OWNER_INDIVIDUAL,
                        'payment_method' => Owner::PAYMENT_METHOD_TRANSFER,
                    ],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('properties', [
            'internal_name' => 'Terreno Centro 201',
            'property_type_id' => $type->id,
        ]);
        $this->assertDatabaseHas('owners', ['email' => 'juan@example.com']);
        $this->assertDatabaseCount('property_documents', DossierDocumentRequirement::query()->where('entity_type', 'property')->where('is_active', true)->count());
        $this->assertDatabaseCount('owner_property', 1);
    }

    public function test_property_route_uses_uuid(): void
    {
        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Playa', 'slug' => 'playa', 'is_active' => true]);

        $property = Property::create([
            'internal_name' => 'Casa Playa 9',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 9',
            'status' => Property::STATUS_AVAILABLE,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('properties.show', $property));

        $response->assertOk();
        $response->assertSee('Asignar inquilino');
        $response->assertSee('suwork:property-tab-restore:', false);
        $this->assertNotNull($property->uuid);
        $this->assertStringContainsString('/propiedades/' . $property->uuid, route('properties.show', $property));
    }

    public function test_property_can_be_updated_from_edit_flow(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $type2 = PropertyType::create(['name' => 'Terreno', 'slug' => 'terreno', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Playa', 'slug' => 'playa', 'is_active' => true]);
        $zone2 = Zone::create(['name' => 'Montebello', 'slug' => 'montebello', 'is_active' => true]);
        $owner = Owner::create([
            'name' => 'Laura Gomez',
            'phone' => '9993332211',
            'email' => 'laura@example.com',
            'owner_type' => Owner::OWNER_INDIVIDUAL,
            'payment_method' => Owner::PAYMENT_METHOD_TRANSFER,
            'is_active' => true,
        ]);

        $property = Property::create([
            'internal_name' => 'Casa Playa 9',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 9',
            'status' => Property::STATUS_AVAILABLE,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('properties.update', $property), [
                'internal_name' => 'Terreno Montebello 20',
                'internal_reference' => 'TM-20',
                'property_type_id' => $type2->id,
                'zone_id' => $zone2->id,
                'full_address' => 'Calle 20',
                'status' => Property::STATUS_BLOCKED,
                'facade_photo' => UploadedFile::fake()->image('facade.jpg'),
                'owner_ids' => [$owner->id],
            ]);

        $response->assertRedirect(route('properties.show', $property));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'internal_name' => 'Terreno Montebello 20',
            'property_type_id' => $type2->id,
            'status' => Property::STATUS_BLOCKED,
        ]);

        $editResponse = $this->actingAs($user)
            ->get(route('properties.edit', $property))
            ->assertOk();
        $this->assertMatchesRegularExpression(
            '/<option\s+value="' . $type2->id . '"\s+selected>\s*Terreno\s*<\/option>/',
            $editResponse->getContent(),
        );
        $this->assertDatabaseHas('owner_property', [
            'property_id' => $property->id,
            'owner_id' => $owner->id,
        ]);
    }

    public function test_inventory_area_and_item_ids_are_preserved_in_inventory_update(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Playa', 'slug' => 'playa', 'is_active' => true]);

        $property = Property::create([
            'internal_name' => 'Casa Bug 23',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 23',
            'status' => Property::STATUS_AVAILABLE,
            'onboarding_step' => 5,
            'created_by' => $user->id,
        ]);

        $area = $property->inventoryAreas()->create([
            'name' => 'Sala',
            'notes' => 'Principal',
        ]);

        $item = $area->items()->create([
            'name' => 'Sillon',
            'condition' => 'bueno',
            'notes' => 'Ninguna',
            'entry_checklist' => 'OK',
            'exit_checklist' => 'OK',
        ]);

        $areaResponse = $this
            ->actingAs($user)
            ->patchJson(route('inventory.areas.update', [$property, $area]), [
                'name' => 'Sala',
                'notes' => 'Principal actualizado',
            ]);

        $areaResponse->assertOk();

        $itemResponse = $this
            ->actingAs($user)
            ->patchJson(route('inventory.items.update', [$property, $area, $item]), [
                'name' => 'Sillon',
                'condition' => 'bueno',
                'notes' => 'Ninguna actualizada',
            ]);

        $itemResponse->assertOk();

        $this->assertDatabaseHas('property_inventory_areas', [
            'id' => $area->id,
            'name' => 'Sala',
            'notes' => 'Principal actualizado',
        ]);

        $this->assertDatabaseHas('property_inventory_items', [
            'id' => $item->id,
            'name' => 'Sillon',
            'notes' => 'Ninguna actualizada',
        ]);

        $this->assertEquals(1, $property->fresh()->inventoryAreas()->count());
        $this->assertEquals(1, $property->fresh()->inventoryAreas->first()->items->count());
    }

    public function test_inventory_photos_are_saved_from_inventory_endpoints(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Playa', 'slug' => 'playa', 'is_active' => true]);

        $property = Property::create([
            'internal_name' => 'Casa Fotos',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle Fotos',
            'status' => Property::STATUS_AVAILABLE,
            'created_by' => $user->id,
        ]);

        $area = $property->inventoryAreas()->create([
            'name' => 'Cocina',
            'notes' => 'Principal',
        ]);

        $item = $area->items()->create([
            'name' => 'Parrilla',
            'condition' => 'bueno',
        ]);

        $this
            ->actingAs($user)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('inventory.areas.update', [$property, $area]), [
                '_method' => 'PATCH',
                'name' => 'Cocina',
                'notes' => 'Principal',
                'photos' => [UploadedFile::fake()->image('area.jpg')],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $areaPhoto = $area->photos()->first();
        $this->assertNotNull($areaPhoto);
        Storage::disk('public')->assertExists($areaPhoto->file_path);

        $this
            ->actingAs($user)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('inventory.items.update', [$property, $area, $item]), [
                '_method' => 'PATCH',
                'name' => 'Parrilla',
                'condition' => 'bueno',
                'notes' => 'Sin detalle',
                'photos' => [UploadedFile::fake()->image('item.jpg')],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $itemPhoto = $item->photos()->with('latestVersion')->first();
        $this->assertNotNull($itemPhoto?->latestVersion);
        Storage::disk('public')->assertExists($itemPhoto->latestVersion->file_path);
    }

    public function test_occupied_status_requires_tenant_selection(): void
    {
        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Playa', 'slug' => 'playa', 'is_active' => true]);

        $response = $this
            ->actingAs($user)
            ->from(route('properties.create'))
            ->post(route('properties.store'), [
                'internal_name' => 'Casa Ocupada',
                'property_type_id' => $type->id,
                'zone_id' => $zone->id,
                'full_address' => 'Calle 10',
                'status' => Property::STATUS_OCCUPIED,
                'new_owners' => [
                    [
                        'name' => 'Owner Uno',
                        'phone' => '9990001111',
                    ],
                ],
            ]);

        $response->assertRedirect(route('properties.create'));
        $response->assertSessionHasErrors('tenant_id');
    }

    public function test_property_can_be_saved_as_occupied_with_tenant(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Playa', 'slug' => 'playa', 'is_active' => true]);
        $tenant = Tenant::create([
            'full_name' => 'Ana Lucia Torres',
            'phone_primary' => '9991112233',
            'dossier_status' => Tenant::DOSSIER_COMPLETE,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('properties.store'), [
                'internal_name' => 'Casa Ocupada 2',
                'property_type_id' => $type->id,
                'zone_id' => $zone->id,
                'full_address' => 'Calle 20',
                'status' => Property::STATUS_OCCUPIED,
                'facade_photo' => UploadedFile::fake()->image('facade.jpg'),
                'tenant_id' => $tenant->id,
                'new_owners' => [
                    [
                        'name' => 'Owner Dos',
                        'phone' => '9991112222',
                    ],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('properties', [
            'internal_name' => 'Casa Ocupada 2',
            'status' => Property::STATUS_OCCUPIED,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_remove_tenant_option_is_shown_when_property_has_no_pending_charges(): void
    {
        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Playa', 'slug' => 'playa', 'is_active' => true]);
        $tenant = Tenant::create([
            'full_name' => 'Inquilino Pagado',
            'phone_primary' => '9991112233',
            'dossier_status' => Tenant::DOSSIER_COMPLETE,
            'is_active' => true,
        ]);
        $property = Property::create([
            'internal_name' => 'Casa Sin Pendientes',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 30',
            'status' => Property::STATUS_OCCUPIED,
            'tenant_id' => $tenant->id,
            'current_tenant_name' => $tenant->full_name,
            'created_by' => $user->id,
        ]);
        Charge::create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'type' => Charge::TYPE_RENT,
            'due_date' => now()->subMonth()->toDateString(),
            'amount' => 1000,
            'paid_amount' => 1000,
            'period_month' => now()->subMonth()->month,
            'period_year' => now()->subMonth()->year,
            'concept' => 'Renta pagada',
            'status' => Charge::STATUS_PAID,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('properties.show', $property))
            ->assertOk()
            ->assertSee('data-current-tenant-name="Inquilino Pagado"', false);

        $this->assertGreaterThanOrEqual(2, substr_count($response->getContent(), 'js-remove-tenant-form'));

        $this->actingAs($user)
            ->from(route('properties.show', $property))
            ->put(route('properties.update.tenant', $property), ['tenant_id' => ''])
            ->assertRedirect(route('properties.show', $property))
            ->assertSessionHas('success', 'Inquilino quitado correctamente.');

        $this->assertNull($property->fresh()->tenant_id);
    }

    public function test_tenant_controls_remain_visible_and_block_changes_when_property_has_pending_charges(): void
    {
        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $zone = Zone::create(['name' => 'Playa', 'slug' => 'playa', 'is_active' => true]);
        $tenant = Tenant::create([
            'full_name' => 'Inquilino Pendiente',
            'phone_primary' => '9991112233',
            'dossier_status' => Tenant::DOSSIER_COMPLETE,
            'is_active' => true,
        ]);
        $property = Property::create([
            'internal_name' => 'Casa Con Pendientes',
            'property_type_id' => $type->id,
            'zone_id' => $zone->id,
            'full_address' => 'Calle 31',
            'status' => Property::STATUS_OCCUPIED,
            'tenant_id' => $tenant->id,
            'current_tenant_name' => $tenant->full_name,
            'created_by' => $user->id,
        ]);
        Charge::create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'type' => Charge::TYPE_RENT,
            'due_date' => now()->addWeek()->toDateString(),
            'amount' => 1000,
            'paid_amount' => 0,
            'period_month' => now()->month,
            'period_year' => now()->year,
            'concept' => 'Renta pendiente',
            'status' => Charge::STATUS_PENDING,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('properties.show', $property))
            ->assertOk()
            ->assertSee('Cambiar inquilino')
            ->assertSee('data-current-tenant-name="Inquilino Pendiente"', false)
            ->assertSee('data-tenant-change-allowed="false"', false)
            ->assertSee('No es posible cambiar el inquilino', false);

        $this->assertGreaterThanOrEqual(2, substr_count($response->getContent(), 'js-remove-tenant-form'));

        $this->actingAs($user)
            ->from(route('properties.show', $property))
            ->put(route('properties.update.tenant', $property), ['tenant_id' => ''])
            ->assertRedirect(route('properties.show', $property))
            ->assertSessionHas(
                'warning',
                'No es posible cambiar o quitar el inquilino mientras existan cargos pendientes, en validación o vencidos.'
            );

        $this->assertSame($tenant->id, $property->fresh()->tenant_id);
    }
}
