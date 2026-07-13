@php
    $suworkFlashMessages = [
        'success' => session('success'),
        'danger' => session('error'),
        'warning' => session('warning'),
        'info' => session('info') ?? session('status'),
    ];
@endphp
<script type="application/json" id="suwork-flash-messages">{!! json_encode($suworkFlashMessages) !!}</script>

<script>
    window.SuWorkToast = {
        fire(type, message) {
            if (!message) return;
            const icon = type === 'danger' ? 'error' : (type || 'info');
            if (window.Swal?.fire) {
                window.Swal.fire({
                    toast: true,
                    icon,
                    title: message,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3500,
                    timerProgressBar: true,
                });
                return;
            }
            console[type === 'danger' || type === 'error' ? 'error' : 'log'](message);
        },
    };

    document.addEventListener('DOMContentLoaded', () => {
        const flashEl = document.getElementById('suwork-flash-messages');
        let flashMessages = {};

        try {
            flashMessages = flashEl ? JSON.parse(flashEl.textContent || '{}') : {};
        } catch (error) {
            flashMessages = {};
        }

        Object.entries(flashMessages).forEach(([type, message]) => {
            if (!message) return;
            window.SuWorkToast.fire(type, message);

            const selectors = [`.alert-${type}`];
            if (type === 'danger') selectors.push('.alert-danger');
            document.querySelectorAll(selectors.join(','))
                .forEach((alert) => {
                    if (alert.textContent.trim().includes(String(message).trim())) {
                        alert.classList.add('d-none');
                        alert.setAttribute('aria-hidden', 'true');
                    }
                });
        });

        const bindSuWorkAjaxForms = () => document.addEventListener('submit', async (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
            if (form.matches('[data-no-ajax], [data-ajax="false"], .js-no-ajax')) return;

            const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;
            const method = (submitter?.getAttribute('formmethod') || form.getAttribute('method') || 'GET').toUpperCase();
            if (method === 'GET' || method === 'DIALOG') return;
            if (form.target && form.target !== '_self') return;
            if (!form.hasAttribute('action')) return;

            const action = submitter?.getAttribute('formaction') || form.getAttribute('action') || window.location.href;
            const url = new URL(action, window.location.href);
            if (url.origin !== window.location.origin) return;
            if (/\/checkout\b|\/stripe\/webhook\b/.test(url.pathname)) return;

            event.preventDefault();

            const previousDisabled = submitter?.hasAttribute('disabled') ?? false;
            if (submitter) submitter.setAttribute('disabled', 'disabled');

            try {
                const formData = submitter ? new FormData(form, submitter) : new FormData(form);
                const response = await fetch(url.toString(), {
                    method,
                    headers: {
                        'Accept': 'text/html, application/json',
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                const contentType = response.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    const payload = await response.json();
                    if (!response.ok || payload.success === false) {
                        const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                        window.SuWorkToast.fire('danger', errors[0] || payload.message || 'No fue posible guardar.');
                        return;
                    }
                    window.SuWorkToast.fire(payload.type || 'success', payload.message || 'Guardado correctamente.');
                    if (payload.redirect) window.location.href = payload.redirect;
                    if (payload.reload) window.location.reload();
                    return;
                }

                const html = await response.text();
                if (response.url && response.url !== window.location.href) {
                    window.history.replaceState({}, '', response.url);
                }
                document.open();
                document.write(html);
                document.close();
            } catch (error) {
                window.SuWorkToast.fire('danger', error.message || 'No fue posible enviar la solicitud.');
            } finally {
                if (submitter && !previousDisabled) submitter.removeAttribute('disabled');
            }
        });

        setTimeout(bindSuWorkAjaxForms, 0);
    });
</script>
