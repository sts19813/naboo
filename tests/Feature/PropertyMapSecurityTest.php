<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use App\Services\PropertyMapLocationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PropertyMapSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_resolver_never_requests_private_or_redirected_external_hosts(): void
    {
        Http::fake(['maps.app.goo.gl/*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/admin'])]);
        $resolver = new PropertyMapLocationResolver;
        $this->assertNull($resolver->resolve('http://127.0.0.1/admin'));
        $this->assertNull($resolver->resolve('https://maps.app.goo.gl/short'));
        Http::assertSentCount(1);
    }

    public function test_map_resolver_follows_valid_google_redirects(): void
    {
        Http::fake([
            'maps.app.goo.gl/*' => Http::response('', 302, ['Location' => 'https://www.google.com/maps/@21.1234567,-89.1234567,16z']),
            'www.google.com/*' => Http::response('', 200),
        ]);
        $resolved = (new PropertyMapLocationResolver)->resolve('https://maps.app.goo.gl/short');
        $this->assertEquals(21.1234567, $resolved['latitude']);
        $this->assertEquals(-89.1234567, $resolved['longitude']);
    }

    public function test_advisor_sees_only_visible_map_markers_and_tenant_is_denied(): void
    {
        $advisor = User::factory()->create();
        $advisor->assignRole(Role::findOrCreate('asesores', 'web'));
        $other = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $mine = Property::create(['internal_name' => 'Visible mapa', 'property_type_id' => $type->id, 'full_address' => 'Calle 1', 'status' => Property::STATUS_AVAILABLE, 'created_by' => $advisor->id, 'map_url' => 'https://www.google.com/maps/@21,-89', 'map_latitude' => 21, 'map_longitude' => -89]);
        Property::create(['internal_name' => 'Privada mapa', 'property_type_id' => $type->id, 'full_address' => 'Calle 2', 'status' => Property::STATUS_AVAILABLE, 'created_by' => $other->id, 'map_url' => 'https://www.google.com/maps/@22,-90', 'map_latitude' => 22, 'map_longitude' => -90]);
        $pendingOther = Property::create(['internal_name' => 'Privada pendiente', 'property_type_id' => $type->id, 'full_address' => 'Calle 3', 'status' => Property::STATUS_AVAILABLE, 'created_by' => $other->id, 'map_url' => 'https://maps.app.goo.gl/private']);
        $this->actingAs($advisor)->get(route('properties.map'))->assertOk()
            ->assertViewHas('markers', fn ($markers) => $markers->count() === 1 && $markers->first()['id'] === $mine->id);
        $this->actingAs($advisor)->postJson(route('properties.map.sync-pending'))->assertOk()->assertJsonPath('processed', 0);
        $this->assertNull($pendingOther->fresh()->map_coordinates_checked_at);
        $tenant = User::factory()->create();
        $tenant->assignRole(Role::findOrCreate('inquilino', 'web'));
        $this->actingAs($tenant)->get(route('properties.map'))->assertForbidden();
        $this->actingAs($tenant)->postJson(route('properties.map.sync-pending'))->assertForbidden();
    }

    public function test_sync_does_not_overwrite_a_location_changed_during_resolution(): void
    {
        $user = User::factory()->create();
        $type = PropertyType::create(['name' => 'Casa', 'slug' => 'casa', 'is_active' => true]);
        $property = Property::create(['internal_name' => 'Casa', 'property_type_id' => $type->id, 'full_address' => 'Calle 1', 'status' => Property::STATUS_AVAILABLE, 'created_by' => $user->id, 'map_url' => 'https://maps.app.goo.gl/old']);
        $this->mock(PropertyMapLocationResolver::class, function ($mock) use ($property): void {
            $mock->shouldReceive('resolve')->once()->andReturnUsing(function () use ($property): array {
                $property->update(['map_url' => 'https://maps.app.goo.gl/new']);
                return ['latitude' => 21, 'longitude' => -89, 'resolved_url' => 'https://www.google.com/maps/@21,-89'];
            });
        });
        $this->actingAs($user)->postJson(route('properties.map.sync-pending'))->assertOk()->assertJsonPath('resolved', 0)->assertJsonPath('remaining', 1);
        $this->assertNull($property->fresh()->map_latitude);
        $this->assertSame('https://maps.app.goo.gl/new', $property->fresh()->map_url);
    }
}
