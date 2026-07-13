<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Support\NotificationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationSettingsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_notification_settings_module(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(route('settings.notifications.index'))
            ->assertOk()
            ->assertSee('Configuración de notificaciones')
            ->assertSee('Pago confirmado');
    }

    public function test_admin_can_update_notification_matrix(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->patchJson(route('settings.notifications.update'), [
                'settings' => [
                    NotificationSettings::ROLE_TENANT => [
                        NotificationSettings::EVENT_PAYMENT_CONFIRMED => '0',
                        NotificationSettings::EVENT_PAYMENT_REMINDER => '1',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $stored = SystemSetting::query()
            ->where('key', 'notifications.email_matrix')
            ->value('value');
        $matrix = json_decode((string) $stored, true);

        $this->assertFalse($matrix[NotificationSettings::ROLE_TENANT][NotificationSettings::EVENT_PAYMENT_CONFIRMED]);
        $this->assertTrue($matrix[NotificationSettings::ROLE_TENANT][NotificationSettings::EVENT_PAYMENT_REMINDER]);
        $this->assertTrue($matrix[NotificationSettings::ROLE_ADMIN][NotificationSettings::EVENT_PAYMENT_CONFIRMED]);
    }

    public function test_user_without_permission_cannot_view_notification_settings_module(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.notifications.index'))
            ->assertForbidden();
    }

    private function adminUser(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
