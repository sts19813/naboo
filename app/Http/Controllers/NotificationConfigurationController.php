<?php

namespace App\Http\Controllers;

use App\Support\NotificationSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationConfigurationController extends Controller
{
    private const CONFIGURE_PERMISSION = 'notificaciones.configurar';

    public function index(Request $request): View
    {
        $this->ensureAccess($request);

        return view('settings.notifications.index', $this->viewData());
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureAccess($request);

        $validated = $request->validate([
            'settings' => ['nullable', 'array'],
            'settings.*' => ['nullable', 'array'],
            'settings.*.*' => ['nullable', 'boolean'],
        ]);

        NotificationSettings::setMatrix($validated['settings'] ?? []);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'type' => 'success',
                'message' => 'Configuración de notificaciones actualizada.',
                'html' => view('settings.notifications.partials.module', $this->viewData())->render(),
            ]);
        }

        return redirect()
            ->route('settings.notifications.index')
            ->with('success', 'Configuración de notificaciones actualizada.');
    }

    private function viewData(): array
    {
        return [
            'notificationRoles' => NotificationSettings::roles(),
            'notificationEvents' => NotificationSettings::events(),
            'notificationMatrix' => NotificationSettings::matrix(),
        ];
    }

    private function ensureAccess(Request $request): void
    {
        $user = $request->user();
        $isAdmin = $user?->hasRole('administrador') || $user?->hasRole('admin');

        if (!$isAdmin && !$user?->can(self::CONFIGURE_PERMISSION)) {
            abort(403);
        }
    }
}
