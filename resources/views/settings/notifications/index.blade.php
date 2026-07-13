@extends('layouts.app')

@section('title', 'Configuración de Notificaciones | SuWork')

@push('styles')
    <style>
        .notification-settings .notification-role-card {
            border: 1px solid var(--border-soft);
            border-radius: 8px;
            background: #fff;
        }

        .notification-settings .notification-event-row {
            border: 1px solid #edf0f5;
            border-radius: 8px;
            background: #fbfcff;
        }

        .notification-settings .notification-event-row + .notification-event-row {
            margin-top: 12px;
        }

        .notification-settings .notification-role-icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eef6ff;
            color: var(--sw-primary);
        }
    </style>
@endpush

@section('content')
    @include('settings.notifications.partials.module')
@endsection

@push('scripts')
    <script>
        (() => {
            const moduleSelector = '#notification-settings-module';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const showToast = (type, message) => {
                if (window.SuWorkToast?.fire) {
                    window.SuWorkToast.fire(type, message);
                    return;
                }
                console[type === 'danger' || type === 'error' ? 'error' : 'log'](message);
            };

            const setValidation = (form, payload) => {
                form.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
                form.querySelectorAll('[data-error-for]').forEach((error) => error.textContent = '');

                Object.entries(payload.errors || {}).forEach(([name, messages]) => {
                    const message = Array.isArray(messages) ? messages[0] : messages;
                    form.querySelectorAll(`[name="${name}"]`).forEach((field) => field.classList.add('is-invalid'));
                    const error = form.querySelector(`[data-error-for="${name}"]`);
                    if (error) error.textContent = message;
                });
            };

            document.addEventListener('submit', async (event) => {
                const form = event.target;
                if (!(form instanceof HTMLFormElement) || !form.closest(moduleSelector)) return;
                if (!form.matches('[data-notification-settings-form]')) return;

                event.preventDefault();
                setValidation(form, {errors: {}});

                const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;
                const previousLabel = submitter?.innerHTML;
                if (submitter) {
                    submitter.setAttribute('disabled', 'disabled');
                    submitter.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando';
                }

                try {
                    const response = await fetch(form.action, {
                        method: form.method || 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: new FormData(form),
                        credentials: 'same-origin'
                    });
                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok || payload.success === false) {
                        setValidation(form, payload);
                        showToast('danger', Object.values(payload.errors || {}).flat()[0] || payload.message || 'No fue posible guardar.');
                        return;
                    }

                    showToast(payload.type || 'success', payload.message || 'Guardado correctamente.');
                    const moduleEl = document.querySelector(moduleSelector);
                    if (moduleEl && payload.html) {
                        moduleEl.outerHTML = payload.html;
                    }
                } catch (error) {
                    showToast('danger', error.message || 'No fue posible enviar la solicitud.');
                } finally {
                    if (submitter) {
                        submitter.removeAttribute('disabled');
                        submitter.innerHTML = previousLabel;
                    }
                }
            }, true);
        })();
    </script>
@endpush
