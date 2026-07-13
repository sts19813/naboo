@extends('layouts.app')

@section('title', 'Configuración de Expedientes | SuWork')

@push('styles')
    <style>
        .dossier-settings .settings-stat {
            border: 1px solid var(--border-soft);
            border-radius: 8px;
            background: #fff;
            min-height: 88px;
        }

        .dossier-settings .settings-empty {
            border: 1px dashed #dce2ec;
            border-radius: 8px;
            background: #fbfcff;
        }

        .dossier-settings .document-row {
            border: 1px solid #edf0f5;
            border-radius: 8px;
            background: #fff;
        }

        .dossier-settings .document-row.is-inactive {
            background: #fafafa;
        }

        .dossier-settings .nav-line-tabs .nav-link {
            border: 0;
            border-bottom: 2px solid transparent;
            color: var(--text-muted);
        }

        .dossier-settings .nav-line-tabs .nav-link.active {
            color: var(--sw-primary);
            border-bottom-color: var(--sw-primary);
        }

    </style>
@endpush

@section('content')
    @include('settings.dossiers.partials.module')
@endsection

@push('scripts')
    <script>
        (() => {
            const moduleSelector = '#dossier-settings-module';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const showToast = (type, message) => {
                if (window.SuWorkToast?.fire) {
                    window.SuWorkToast.fire(type, message);
                    return;
                }
                console[type === 'danger' || type === 'error' ? 'error' : 'log'](message);
            };

            const activeEntity = () => {
                return document.querySelector(`${moduleSelector} [data-bs-toggle="tab"].active`)?.dataset.entity || 'property';
            };

            const refreshModule = (html) => {
                const moduleEl = document.querySelector(moduleSelector);
                if (moduleEl && html) {
                    moduleEl.outerHTML = html;
                }
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

            document.addEventListener('shown.bs.tab', (event) => {
                const entity = event.target?.dataset?.entity;
                if (!entity || !event.target.closest(moduleSelector)) return;

                const url = new URL(window.location.href);
                url.searchParams.set('entity', entity);
                window.history.replaceState({}, '', url.toString());
            });

            document.addEventListener('submit', async (event) => {
                const form = event.target;
                if (!(form instanceof HTMLFormElement) || !form.closest(moduleSelector)) return;
                if (!form.matches('[data-dossier-settings-form]')) return;

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
                    refreshModule(payload.html);
                } catch (error) {
                    showToast('danger', error.message || 'No fue posible enviar la solicitud.');
                } finally {
                    if (submitter) {
                        submitter.removeAttribute('disabled');
                        submitter.innerHTML = previousLabel;
                    }
                }
            }, true);

            document.addEventListener('click', async (event) => {
                if (!(event.target instanceof Element)) return;

                const deleteButton = event.target.closest('[data-dossier-delete]');
                if (!deleteButton || !deleteButton.closest(moduleSelector)) return;

                event.preventDefault();
                const form = deleteButton.closest('form');
                if (!form) return;

                if (window.Swal?.fire) {
                    const result = await window.Swal.fire({
                        icon: 'warning',
                        title: '¿Eliminar documento?',
                        text: 'Los archivos ya cargados no se eliminan; este documento dejará de ser requisito inicial.',
                        showCancelButton: true,
                        confirmButtonText: 'Eliminar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#d9214e'
                    });
                    if (!result.isConfirmed) return;
                } else if (!window.confirm('¿Eliminar documento de la configuración?')) {
                    return;
                }

                form.requestSubmit(deleteButton);
            });
        })();
    </script>
@endpush
