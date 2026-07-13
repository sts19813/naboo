<div id="notification-settings-module" class="py-10 notification-settings">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
        <div>
            <h1 class="mb-1 fw-bold">Configuración de notificaciones</h1>
            <div class="text-muted">Define qué correos se envían globalmente por tipo de usuario.</div>
        </div>
        <a href="{{ route('settings.dossiers.index') }}" class="btn btn-icon btn-light-primary" title="Configuración">
            <i class="bi bi-gear"></i>
        </a>
    </div>

    <form method="POST" action="{{ route('settings.notifications.update') }}" data-notification-settings-form>
        @csrf
        @method('PATCH')

        <div class="row g-5">
            @foreach ($notificationRoles as $roleKey => $role)
                @php
                    $activeCount = collect($notificationEvents)
                        ->keys()
                        ->filter(fn ($eventKey) => (bool) ($notificationMatrix[$roleKey][$eventKey] ?? true))
                        ->count();
                @endphp
                <div class="col-xl-6">
                    <div class="notification-role-card h-100 p-6">
                        <div class="d-flex align-items-start justify-content-between gap-4 mb-5">
                            <div class="d-flex align-items-center gap-3">
                                <span class="notification-role-icon">
                                    <i class="bi {{ $role['icon'] }} fs-3"></i>
                                </span>
                                <div>
                                    <h2 class="fs-4 fw-bold mb-1">{{ $role['label'] }}</h2>
                                    <div class="text-muted fs-7">{{ $role['description'] }}</div>
                                </div>
                            </div>
                            <span class="badge badge-light-primary text-primary">{{ $activeCount }} activos</span>
                        </div>

                        @foreach ($notificationEvents as $eventKey => $event)
                            @php
                                $inputId = 'notification_' . $roleKey . '_' . $eventKey;
                                $isChecked = (bool) ($notificationMatrix[$roleKey][$eventKey] ?? true);
                            @endphp
                            <div class="notification-event-row p-4">
                                <input type="hidden" name="settings[{{ $roleKey }}][{{ $eventKey }}]" value="0">
                                <label class="form-check form-switch form-check-custom form-check-solid align-items-start gap-3 mb-0" for="{{ $inputId }}">
                                    <input id="{{ $inputId }}"
                                        class="form-check-input mt-1"
                                        type="checkbox"
                                        name="settings[{{ $roleKey }}][{{ $eventKey }}]"
                                        value="1"
                                        {{ $isChecked ? 'checked' : '' }}>
                                    <span class="form-check-label">
                                        <span class="d-block fw-semibold text-gray-900">{{ $event['label'] }}</span>
                                        <span class="d-block text-muted fs-8">{{ $event['description'] }}</span>
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex flex-wrap justify-content-end align-items-center gap-3 mt-6">
            <div class="text-muted fs-7">Los cambios aplican a los correos enviados desde el sistema.</div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2 me-1"></i> Guardar notificaciones
            </button>
        </div>
    </form>
</div>
