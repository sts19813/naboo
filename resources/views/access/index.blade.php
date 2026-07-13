@extends('layouts.app')

@section('title', 'Usuarios y Permisos | SuWork')

@push('styles')
    <style>
        .access-module .access-stat {
            border: 1px solid var(--border-soft);
            border-radius: 8px;
            background: #fff;
            min-height: 86px;
        }

        .access-module .access-chip-list {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }

        .access-module .access-empty {
            border: 1px dashed #dce2ec;
            border-radius: 8px;
            background: #fbfcff;
        }

        .access-module .nav-line-tabs .nav-link {
            border: 0;
            border-bottom: 2px solid transparent;
            color: var(--text-muted);
        }

        .access-module .nav-line-tabs .nav-link.active {
            color: var(--sw-primary);
            border-bottom-color: var(--sw-primary);
        }

        .access-module .permission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: .75rem;
            max-height: 320px;
            overflow: auto;
            padding-right: .25rem;
        }
    </style>
@endpush

@section('content')
    @include('access.partials.module')
@endsection

@push('scripts')
    <script>
        (() => {
            const moduleSelector = '#access-module';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const showToast = (type, message) => {
                if (window.SuWorkToast?.fire) {
                    window.SuWorkToast.fire(type, message);
                    return;
                }
                console[type === 'danger' || type === 'error' ? 'error' : 'log'](message);
            };

            const activeTabKey = () => {
                return document.querySelector(`${moduleSelector} [data-bs-toggle="tab"].active`)?.dataset.tabKey || 'users';
            };

            const cssEscape = (value) => {
                return window.CSS?.escape ? CSS.escape(value) : String(value).replace(/["\\]/g, '\\$&');
            };

            const buildUrl = (url, tab = activeTabKey()) => {
                const nextUrl = new URL(url, window.location.origin);
                nextUrl.searchParams.set('tab', tab);
                return nextUrl;
            };

            const clearValidation = (form) => {
                form.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
                form.querySelectorAll('[data-error-for]').forEach((error) => error.textContent = '');
                form.querySelectorAll('[data-form-error]').forEach((error) => {
                    error.classList.add('d-none');
                    error.textContent = '';
                });
            };

            const fieldNameSelector = (name) => {
                return `[name="${cssEscape(name)}"], [name="${cssEscape(name)}[]"]`;
            };

            const setValidation = (form, errors, fallbackMessage) => {
                const entries = Object.entries(errors || {});
                if (!entries.length) {
                    const generalError = form.querySelector('[data-form-error]');
                    if (generalError) {
                        generalError.textContent = fallbackMessage || 'No fue posible guardar.';
                        generalError.classList.remove('d-none');
                    }
                    return;
                }

                entries.forEach(([name, messages]) => {
                    const message = Array.isArray(messages) ? messages[0] : messages;
                    const normalizedName = name.replace(/\.\d+$/, '');
                    form.querySelectorAll(fieldNameSelector(normalizedName)).forEach((field) => {
                        field.classList.add('is-invalid');
                    });
                    const feedback = form.querySelector(`[data-error-for="${cssEscape(normalizedName)}"]`);
                    if (feedback) {
                        feedback.textContent = message;
                    }
                });
            };

            const hideOpenModals = () => {
                document.querySelectorAll('.modal.show').forEach((modalEl) => {
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                });
                document.body.classList.remove('modal-open');
                document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
            };

            const initTables = () => {
                if (!window.jQuery || !$.fn.DataTable) return;

                document.querySelectorAll(`${moduleSelector} table[data-access-datatable]`).forEach((table) => {
                    if ($.fn.DataTable.isDataTable(table)) return;
                    const searchInputSelector = table.dataset.accessSearchInput;

                    const dataTable = $(table).DataTable({
                        pageLength: 10,
                        order: [],
                        responsive: true,
                        dom: searchInputSelector ? 'rt<"d-flex flex-wrap justify-content-between align-items-center gap-3 pt-5"ip>' : undefined,
                        language: {
                            url: '//cdn.datatables.net/plug-ins/2.3.2/i18n/es-MX.json'
                        }
                    });

                    const searchInput = searchInputSelector ? document.querySelector(searchInputSelector) : null;
                    if (searchInput) {
                        searchInput.addEventListener('input', (event) => {
                            dataTable.search(event.target.value || '').draw();
                        });
                    }
                });
            };

            const loadModule = async (url) => {
                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'text/html',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error('No fue posible refrescar el módulo.');
                }

                const html = await response.text();
                const moduleEl = document.querySelector(moduleSelector);
                if (moduleEl) {
                    moduleEl.outerHTML = html;
                }
                window.history.replaceState({}, '', url.toString());
                initTables();
            };

            document.addEventListener('DOMContentLoaded', initTables);

            document.addEventListener('shown.bs.tab', (event) => {
                const tab = event.target?.dataset?.tabKey;
                if (!tab || !event.target.closest(moduleSelector)) return;
                const nextUrl = buildUrl(window.location.href, tab);
                window.history.replaceState({}, '', nextUrl.toString());
            });

            document.addEventListener('submit', async (event) => {
                const form = event.target;
                if (!(form instanceof HTMLFormElement) || !form.closest(moduleSelector)) return;
                if (!form.matches('[data-access-form]')) return;

                event.preventDefault();
                clearValidation(form);

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
                        setValidation(form, payload.errors, payload.message);
                        showToast('danger', Object.values(payload.errors || {}).flat()[0] || payload.message || 'No fue posible guardar.');
                        return;
                    }

                    showToast(payload.type || 'success', payload.message || 'Guardado correctamente.');
                    hideOpenModals();
                    const currentTab = activeTabKey();
                    const nextTab = payload.tab === 'users' && currentTab === 'tenants'
                        ? 'tenants'
                        : (payload.tab || currentTab);
                    const nextUrl = buildUrl(window.location.href, nextTab);
                    await loadModule(nextUrl);
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

                const pageLink = event.target.closest(`${moduleSelector} a[data-access-page]`);
                if (pageLink) {
                    event.preventDefault();
                    try {
                        await loadModule(buildUrl(pageLink.href, 'users'));
                    } catch (error) {
                        showToast('danger', error.message || 'No fue posible cargar la página.');
                    }
                    return;
                }

                const button = event.target.closest('[data-access-delete]');
                if (!button || !button.closest(moduleSelector)) return;

                const form = button.closest('form');
                if (!form) return;

                event.preventDefault();
                const message = button.dataset.confirmMessage || 'Esta acción no se puede deshacer.';

                if (window.Swal?.fire) {
                    const result = await window.Swal.fire({
                        icon: 'warning',
                        title: '¿Confirmar eliminación?',
                        text: message,
                        showCancelButton: true,
                        confirmButtonText: 'Eliminar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#d9214e'
                    });

                    if (!result.isConfirmed) return;
                } else if (!window.confirm(message)) {
                    return;
                }

                form.requestSubmit(button);
            });
        })();
    </script>
@endpush
