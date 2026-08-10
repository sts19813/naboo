<div
    class="naboo-copilot"
    data-copilot
    data-history-url="{{ route('copilot.history') }}"
    data-reset-url="{{ route('copilot.reset') }}"
    data-chat-url="{{ route('copilot.chat') }}"
>
    <button type="button" class="naboo-copilot__launcher" data-copilot-toggle aria-label="Abrir Naboo Copilot">
        <span class="naboo-copilot__pulse"></span>
        <i class="bi bi-stars"></i>
        <span class="naboo-copilot__launcher-text">AI Copilot</span>
    </button>

    <section class="naboo-copilot__panel" data-copilot-panel aria-hidden="true">
        <header class="naboo-copilot__header">
            <div class="naboo-copilot__identity">
                <div class="naboo-copilot__avatar">
                    <i class="bi bi-stars"></i>
                </div>
                <div>
                    <div class="naboo-copilot__title">Naboo Copilot</div>
                    <div class="naboo-copilot__subtitle">
                        <span class="naboo-copilot__status-dot"></span>
                        Datos vivos del sistema
                    </div>
                </div>
            </div>
            <div class="naboo-copilot__header-actions">
                <button type="button" class="naboo-copilot__new-chat" data-copilot-reset>Nuevo chat</button>
                <button type="button" class="naboo-copilot__icon-btn" data-copilot-close aria-label="Cerrar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </header>

        <div class="naboo-copilot__body" data-copilot-messages>
            <div class="naboo-copilot__welcome">
                <div class="naboo-copilot__welcome-kicker">Asistente operativo</div>
                <div class="naboo-copilot__welcome-title">Pregunta por propiedades, cobranza, gastos, mantenimiento o expedientes.</div>
                <div class="naboo-copilot__suggestions" data-copilot-suggestions>
                    <button type="button" data-copilot-prompt="Dame un resumen ejecutivo del sistema hoy.">Resumen ejecutivo</button>
                    <button type="button" data-copilot-prompt="Que cobranza esta vencida o pendiente?">Cobranza pendiente</button>
                    <button type="button" data-copilot-prompt="Que tickets de mantenimiento urgentes siguen abiertos?">Tickets urgentes</button>
                    <button type="button" data-copilot-prompt="Que documentos vencen en los proximos 30 dias?">Documentos por vencer</button>
                </div>
            </div>
        </div>

        <div class="naboo-copilot__usage" data-copilot-usage hidden>
            <div>
                <span class="naboo-copilot__usage-label">Hoy</span>
                <strong data-copilot-usage-today>0 tokens</strong>
            </div>
            <div>
                <span class="naboo-copilot__usage-label">Mes</span>
                <strong data-copilot-usage-month>0 tokens</strong>
            </div>
            <div>
                <span class="naboo-copilot__usage-label">Costo est.</span>
                <strong data-copilot-usage-cost>$0.0000 USD</strong>
            </div>
        </div>

        <form class="naboo-copilot__composer" data-copilot-form>
            <textarea
                class="naboo-copilot__input"
                data-copilot-input
                rows="1"
                maxlength="2000"
                placeholder="Preguntale a Naboo Copilot..."
            ></textarea>
            <button type="submit" class="naboo-copilot__send" data-copilot-send aria-label="Enviar">
                <i class="bi bi-send-fill"></i>
            </button>
        </form>
    </section>
</div>

<style>
    .naboo-copilot {
        --copilot-bg: #101828;
        --copilot-panel: #ffffff;
        --copilot-ink: #111827;
        --copilot-muted: #667085;
        --copilot-line: rgba(15, 23, 42, .1);
        --copilot-brand: #2563eb;
        --copilot-brand-2: #14b8a6;
        position: fixed;
        right: 26px;
        bottom: 24px;
        z-index: 1055;
        font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .naboo-copilot__launcher {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-height: 52px;
        padding: 0 18px;
        border: 0;
        border-radius: 999px;
        color: #fff;
        background: linear-gradient(135deg, #111827 0%, #1d4ed8 54%, #0f766e 100%);
        box-shadow: 0 18px 45px rgba(15, 23, 42, .28);
        font-weight: 800;
        letter-spacing: 0;
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .naboo-copilot__launcher:hover {
        transform: translateY(-2px);
        box-shadow: 0 22px 54px rgba(15, 23, 42, .34);
    }

    .naboo-copilot__launcher i {
        font-size: 20px;
    }

    .naboo-copilot__pulse {
        position: absolute;
        inset: -4px;
        border-radius: inherit;
        border: 1px solid rgba(37, 99, 235, .36);
        animation: nabooCopilotPulse 2.4s ease-out infinite;
    }

    .naboo-copilot__panel {
        position: absolute;
        right: 0;
        bottom: 70px;
        display: flex;
        flex-direction: column;
        width: min(440px, calc(100vw - 28px));
        height: min(680px, calc(100vh - 110px));
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 22px;
        background: var(--copilot-panel);
        box-shadow: 0 28px 80px rgba(15, 23, 42, .34);
        opacity: 0;
        pointer-events: none;
        transform: translateY(18px) scale(.98);
        transition: opacity .18s ease, transform .18s ease;
    }

    .naboo-copilot.is-open .naboo-copilot__panel {
        opacity: 1;
        pointer-events: auto;
        transform: translateY(0) scale(1);
    }

    .naboo-copilot__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 16px 15px;
        color: #fff;
        background: linear-gradient(135deg, #0f172a 0%, #1e40af 58%, #0f766e 100%);
    }

    .naboo-copilot__identity {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .naboo-copilot__avatar {
        display: grid;
        width: 40px;
        height: 40px;
        place-items: center;
        border-radius: 12px;
        background: rgba(255, 255, 255, .14);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .2);
    }

    .naboo-copilot__avatar i {
        font-size: 21px;
    }

    .naboo-copilot__title {
        font-size: 15px;
        font-weight: 800;
    }

    .naboo-copilot__subtitle {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-top: 2px;
        color: rgba(255, 255, 255, .78);
        font-size: 12px;
        font-weight: 600;
    }

    .naboo-copilot__status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #22c55e;
        box-shadow: 0 0 0 4px rgba(34, 197, 94, .16);
    }

    .naboo-copilot__icon-btn,
    .naboo-copilot__send {
        display: inline-grid;
        place-items: center;
        border: 0;
        color: inherit;
        background: transparent;
    }

    .naboo-copilot__icon-btn {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        color: rgba(255, 255, 255, .82);
    }

    .naboo-copilot__header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .naboo-copilot__new-chat {
        min-height: 30px;
        padding: 0 10px;
        border: 1px solid rgba(255, 255, 255, .2);
        border-radius: 999px;
        color: rgba(255, 255, 255, .86);
        background: rgba(255, 255, 255, .1);
        font-size: 11px;
        font-weight: 800;
    }

    .naboo-copilot__new-chat:hover {
        color: #fff;
        background: rgba(255, 255, 255, .16);
    }

    .naboo-copilot__icon-btn:hover {
        background: rgba(255, 255, 255, .12);
    }

    .naboo-copilot__body {
        flex: 1;
        overflow-y: auto;
        padding: 18px;
        background:
            linear-gradient(180deg, rgba(248, 250, 252, .96), rgba(255, 255, 255, .96)),
            radial-gradient(circle at top left, rgba(37, 99, 235, .1), transparent 35%);
    }

    .naboo-copilot__welcome {
        padding: 16px;
        border: 1px solid var(--copilot-line);
        border-radius: 16px;
        background: #fff;
    }

    .naboo-copilot__welcome-kicker {
        color: var(--copilot-brand);
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .naboo-copilot__welcome-title {
        margin-top: 6px;
        color: var(--copilot-ink);
        font-size: 15px;
        font-weight: 800;
        line-height: 1.3;
    }

    .naboo-copilot__suggestions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-top: 14px;
    }

    .naboo-copilot__suggestions button {
        min-height: 38px;
        padding: 8px 10px;
        border: 1px solid rgba(37, 99, 235, .14);
        border-radius: 10px;
        color: #1d4ed8;
        background: #eff6ff;
        font-size: 12px;
        font-weight: 700;
        text-align: left;
    }

    .naboo-copilot__message {
        display: flex;
        margin-top: 14px;
    }

    .naboo-copilot__message.is-user {
        justify-content: flex-end;
    }

    .naboo-copilot__bubble {
        max-width: 86%;
        padding: 12px 13px;
        border-radius: 16px;
        font-size: 13px;
        line-height: 1.48;
        white-space: pre-wrap;
    }

    .naboo-copilot__message.is-assistant .naboo-copilot__bubble {
        color: #344054;
        background: #fff;
        border: 1px solid var(--copilot-line);
        box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
    }

    .naboo-copilot__message.is-user .naboo-copilot__bubble {
        color: #fff;
        background: linear-gradient(135deg, #1d4ed8, #0f766e);
        border-bottom-right-radius: 6px;
    }

    .naboo-copilot__meta {
        margin-top: 8px;
        color: #98a2b3;
        font-size: 11px;
        font-weight: 700;
    }

    .naboo-copilot__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
    }

    .naboo-copilot__action-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 32px;
        padding: 7px 10px;
        border: 1px solid rgba(37, 99, 235, .16);
        border-radius: 999px;
        color: #1d4ed8;
        background: #eff6ff;
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
    }

    .naboo-copilot__action-link:hover {
        color: #0f766e;
        background: #ecfeff;
    }

    .naboo-copilot__typing {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 12px 13px;
        border: 1px solid var(--copilot-line);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
    }

    .naboo-copilot__typing span {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #94a3b8;
        animation: nabooCopilotTyping 1s ease-in-out infinite;
    }

    .naboo-copilot__typing span:nth-child(2) {
        animation-delay: .15s;
    }

    .naboo-copilot__typing span:nth-child(3) {
        animation-delay: .3s;
    }

    .naboo-copilot__usage {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        padding: 10px 12px;
        border-top: 1px solid var(--copilot-line);
        background: #f8fafc;
    }

    .naboo-copilot__usage > div {
        min-width: 0;
        padding: 8px 9px;
        border: 1px solid rgba(148, 163, 184, .18);
        border-radius: 10px;
        background: #fff;
    }

    .naboo-copilot__usage-label {
        display: block;
        color: #98a2b3;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .naboo-copilot__usage strong {
        display: block;
        overflow: hidden;
        margin-top: 2px;
        color: var(--copilot-ink);
        font-size: 11px;
        font-weight: 800;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .naboo-copilot__composer {
        display: flex;
        align-items: flex-end;
        gap: 10px;
        padding: 12px;
        border-top: 1px solid var(--copilot-line);
        background: #fff;
    }

    .naboo-copilot__input {
        flex: 1;
        max-height: 118px;
        min-height: 44px;
        resize: none;
        padding: 12px 13px;
        border: 1px solid rgba(148, 163, 184, .34);
        border-radius: 14px;
        color: var(--copilot-ink);
        background: #f8fafc;
        font-size: 13px;
        outline: 0;
    }

    .naboo-copilot__input:focus {
        border-color: rgba(37, 99, 235, .5);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .08);
    }

    .naboo-copilot__send {
        width: 44px;
        height: 44px;
        flex: 0 0 44px;
        border-radius: 14px;
        color: #fff;
        background: linear-gradient(135deg, #1d4ed8, #0f766e);
        box-shadow: 0 12px 22px rgba(29, 78, 216, .24);
    }

    .naboo-copilot__send:disabled {
        opacity: .6;
        cursor: wait;
    }

    [data-bs-theme="dark"] .naboo-copilot {
        --copilot-panel: #111827;
        --copilot-ink: #f9fafb;
        --copilot-muted: #cbd5e1;
        --copilot-line: rgba(255, 255, 255, .1);
    }

    [data-bs-theme="dark"] .naboo-copilot__body {
        background: #0b1120;
    }

    [data-bs-theme="dark"] .naboo-copilot__welcome,
    [data-bs-theme="dark"] .naboo-copilot__message.is-assistant .naboo-copilot__bubble,
    [data-bs-theme="dark"] .naboo-copilot__typing,
    [data-bs-theme="dark"] .naboo-copilot__composer {
        background: #111827;
    }

    [data-bs-theme="dark"] .naboo-copilot__message.is-assistant .naboo-copilot__bubble {
        color: #e5e7eb;
    }

    [data-bs-theme="dark"] .naboo-copilot__usage {
        background: #0f172a;
    }

    [data-bs-theme="dark"] .naboo-copilot__usage > div {
        background: #111827;
        border-color: rgba(255, 255, 255, .1);
    }

    [data-bs-theme="dark"] .naboo-copilot__input {
        color: #f9fafb;
        background: #0f172a;
        border-color: rgba(255, 255, 255, .13);
    }

    [data-bs-theme="dark"] .naboo-copilot__action-link {
        color: #bfdbfe;
        background: rgba(37, 99, 235, .18);
        border-color: rgba(147, 197, 253, .24);
    }

    @media (max-width: 575.98px) {
        .naboo-copilot {
            right: 14px;
            bottom: 14px;
        }

        .naboo-copilot__launcher-text {
            display: none;
        }

        .naboo-copilot__launcher {
            width: 56px;
            min-height: 56px;
            justify-content: center;
            padding: 0;
        }

        .naboo-copilot__panel {
            right: -2px;
            bottom: 68px;
            width: calc(100vw - 24px);
            height: min(650px, calc(100vh - 92px));
            border-radius: 18px;
        }

        .naboo-copilot__suggestions {
            grid-template-columns: 1fr;
        }
    }

    @keyframes nabooCopilotPulse {
        0% {
            opacity: .75;
            transform: scale(1);
        }

        100% {
            opacity: 0;
            transform: scale(1.18);
        }
    }

    @keyframes nabooCopilotTyping {
        0%, 80%, 100% {
            transform: translateY(0);
            opacity: .45;
        }

        40% {
            transform: translateY(-4px);
            opacity: 1;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const root = document.querySelector('[data-copilot]');
        if (!root) return;

        const panel = root.querySelector('[data-copilot-panel]');
        const toggle = root.querySelector('[data-copilot-toggle]');
        const close = root.querySelector('[data-copilot-close]');
        const reset = root.querySelector('[data-copilot-reset]');
        const form = root.querySelector('[data-copilot-form]');
        const input = root.querySelector('[data-copilot-input]');
        const send = root.querySelector('[data-copilot-send]');
        const messages = root.querySelector('[data-copilot-messages]');
        const usagePanel = root.querySelector('[data-copilot-usage]');
        const usageToday = root.querySelector('[data-copilot-usage-today]');
        const usageMonth = root.querySelector('[data-copilot-usage-month]');
        const usageCost = root.querySelector('[data-copilot-usage-cost]');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const storageKey = 'naboo_copilot_conversation_id';
        let conversationId = localStorage.getItem(storageKey) || null;
        let isLoading = false;
        const minTypingMs = 500;

        const openPanel = () => {
            root.classList.add('is-open');
            panel.setAttribute('aria-hidden', 'false');
            setTimeout(() => input.focus(), 120);
        };

        const closePanel = () => {
            root.classList.remove('is-open');
            panel.setAttribute('aria-hidden', 'true');
        };

        const scrollToBottom = () => {
            messages.scrollTop = messages.scrollHeight;
        };

        const escapeHtml = (value) => String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        const formatNumber = (value) => new Intl.NumberFormat('es-MX').format(Number(value || 0));
        const formatUsd = (value) => `$${Number(value || 0).toFixed(4)} USD`;

        const renderUsage = (summary) => {
            if (!usagePanel || !summary) return;

            const today = summary.today || {};
            const month = summary.month || {};
            usageToday.textContent = `${formatNumber(today.total_tokens)} tokens`;
            usageMonth.textContent = `${formatNumber(month.total_tokens)} tokens`;
            usageCost.textContent = formatUsd(month.estimated_cost_usd);
            usagePanel.hidden = false;
        };

        const addMessage = (role, content, meta = {}) => {
            const wrapper = document.createElement('div');
            wrapper.className = `naboo-copilot__message ${role === 'user' ? 'is-user' : 'is-assistant'}`;
            const usageTokens = meta.usage?.total_tokens ? ` · ${formatNumber(meta.usage.total_tokens)} tokens` : '';
            const toolMeta = meta.tool_call_count > 0 || usageTokens
                ? `<div class="naboo-copilot__meta">${meta.tool_call_count || 0} consulta${meta.tool_call_count === 1 ? '' : 's'} al sistema${usageTokens}</div>`
                : '';
            const actions = Array.isArray(meta.actions) ? meta.actions : [];
            const actionsHtml = actions.length > 0
                ? `<div class="naboo-copilot__actions">${actions.map((action) => {
                    const label = escapeHtml(action.label || 'Ir a la vista');
                    const url = escapeHtml(action.url || '#');
                    return `<a href="${url}" class="naboo-copilot__action-link"><i class="bi bi-arrow-up-right"></i>${label}</a>`;
                }).join('')}</div>`
                : '';
            wrapper.innerHTML = `<div class="naboo-copilot__bubble">${escapeHtml(content)}${actionsHtml}${toolMeta}</div>`;
            messages.appendChild(wrapper);
            scrollToBottom();
        };

        const addTyping = () => {
            const wrapper = document.createElement('div');
            wrapper.className = 'naboo-copilot__message is-assistant';
            wrapper.setAttribute('data-copilot-typing', 'true');
            wrapper.innerHTML = '<div class="naboo-copilot__typing"><span></span><span></span><span></span></div>';
            messages.appendChild(wrapper);
            scrollToBottom();
        };

        const removeTyping = () => {
            messages.querySelector('[data-copilot-typing]')?.remove();
        };

        const setLoading = (value) => {
            isLoading = value;
            send.disabled = value;
            input.disabled = value;
        };

        const resizeInput = () => {
            input.style.height = 'auto';
            input.style.height = `${Math.min(input.scrollHeight, 118)}px`;
        };

        const sendMessage = async (text) => {
            const message = text.trim();
            if (!message || isLoading) return;

            addMessage('user', message);
            input.value = '';
            resizeInput();
            setLoading(true);
            const typingStartedAt = Date.now();
            addTyping();

            try {
                const response = await fetch(root.dataset.chatUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        message,
                        conversation_id: conversationId,
                    }),
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'No se pudo consultar el Copilot.');
                }

                conversationId = data.conversation_id;
                localStorage.setItem(storageKey, conversationId);
                renderUsage(data.usage_summary);
                const remainingTypingMs = Math.max(0, minTypingMs - (Date.now() - typingStartedAt));
                if (remainingTypingMs > 0) {
                    await new Promise((resolve) => setTimeout(resolve, remainingTypingMs));
                }
                removeTyping();
                addMessage('assistant', data.message.content, data.message.meta || {});
            } catch (error) {
                const remainingTypingMs = Math.max(0, minTypingMs - (Date.now() - typingStartedAt));
                if (remainingTypingMs > 0) {
                    await new Promise((resolve) => setTimeout(resolve, remainingTypingMs));
                }
                removeTyping();
                addMessage('assistant', error.message || 'Ocurrio un error al consultar el Copilot.');
            } finally {
                setLoading(false);
                input.focus();
            }
        };

        const resetConversation = async () => {
            if (isLoading) return;
            let data = null;

            try {
                const response = await fetch(root.dataset.resetUrl, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                });
                data = await response.json();
            } catch (error) {
                // Si falla el borrado remoto, al menos limpiamos la experiencia local.
            }

            conversationId = null;
            localStorage.removeItem(storageKey);
            renderUsage(data?.usage_summary);
            messages.innerHTML = `
                <div class="naboo-copilot__welcome">
                    <div class="naboo-copilot__welcome-kicker">Asistente operativo</div>
                    <div class="naboo-copilot__welcome-title">Pregunta por propiedades, cobranza, gastos, mantenimiento o expedientes.</div>
                    <div class="naboo-copilot__suggestions" data-copilot-suggestions>
                        <button type="button" data-copilot-prompt="Dame un resumen ejecutivo del sistema hoy.">Resumen ejecutivo</button>
                        <button type="button" data-copilot-prompt="Que cobranza esta vencida o pendiente?">Cobranza pendiente</button>
                        <button type="button" data-copilot-prompt="Que tickets de mantenimiento urgentes siguen abiertos?">Tickets urgentes</button>
                        <button type="button" data-copilot-prompt="Que documentos vencen en los proximos 30 dias?">Documentos por vencer</button>
                    </div>
                </div>
            `;
        };

        const loadHistory = async () => {
            try {
                const response = await fetch(root.dataset.historyUrl, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) return;
                const data = await response.json();
                renderUsage(data.usage_summary);
                if (!data.conversation_id || !Array.isArray(data.messages) || data.messages.length === 0) return;

                conversationId = data.conversation_id;
                localStorage.setItem(storageKey, conversationId);
                messages.querySelector('.naboo-copilot__welcome')?.remove();
                data.messages.slice(-12).forEach((message) => addMessage(message.role, message.content, message.meta || {}));
            } catch (error) {
                // El historial no bloquea el uso del Copilot.
            }
        };

        toggle.addEventListener('click', () => {
            root.classList.contains('is-open') ? closePanel() : openPanel();
        });

        close.addEventListener('click', closePanel);
        reset?.addEventListener('click', resetConversation);

        input.addEventListener('input', resizeInput);

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                form.requestSubmit();
            }
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            sendMessage(input.value);
        });

        messages.addEventListener('click', (event) => {
            const button = event.target.closest('[data-copilot-prompt]');
            if (!button) return;
            openPanel();
            sendMessage(button.dataset.copilotPrompt || '');
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && root.classList.contains('is-open')) {
                closePanel();
            }
        });

        loadHistory();
    });
</script>
