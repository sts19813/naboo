                <div class="charges-list-table-card">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle mb-0" id="{{ $tableId }}">
                        <thead>
                            <tr class="fw-bold text-muted text-uppercase fs-8">
                                <th class="ps-7 min-w-220px">Concepto</th>
                                <th class="min-w-220px">Inquilino / Propiedad</th>
                                <th class="min-w-130px">Vencimiento</th>
                                <th class="min-w-140px">Monto</th>
                                <th class="min-w-120px">Estado</th>
                                <th class="min-w-320px text-end pe-7">Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($charges as $charge)
                                @php
                                    $canRegisterPayment = $canManageCharges && in_array(
                                        $charge->status,
                                        [\App\Models\Charge::STATUS_PENDING, \App\Models\Charge::STATUS_PARTIAL, \App\Models\Charge::STATUS_IN_VALIDATION],
                                        true,
                                    );
                                    $canEditCharge = $canManageCharges && in_array(
                                        $charge->status,
                                        [\App\Models\Charge::STATUS_PENDING, \App\Models\Charge::STATUS_PARTIAL, \App\Models\Charge::STATUS_IN_VALIDATION],
                                        true,
                                    );
                                    $canDeleteCharge = $canManageCharges
                                        && $charge->status !== \App\Models\Charge::STATUS_CANCELED
                                        && ($charge->status !== \App\Models\Charge::STATUS_PAID || $canDeletePaidCharges);
                                @endphp
                                <tr class="charges-list-row" data-charge-row>
                                    <td class="ps-7">
                                        <div class="charges-list-title">{{ $charge->concept }}</div>
                                        <div class="charges-list-meta">{{ $charge->type_label }}</div>
                                    </td>
                                    <td>
                                        <div class="charges-list-value">{{ $charge->customer_display_name }}</div>
                                        <div class="charges-list-meta">
                                            {{ $charge->property?->internal_reference ?: $charge->property?->internal_name ?: '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="charges-list-value">{{ $charge->due_date?->format('d M Y') ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <div class="charges-list-value">${{ number_format((float) $charge->amount, 2) }}
                                        </div>
                                        @if ($charge->outstanding_amount > 0 && $charge->status !== \App\Models\Charge::STATUS_CANCELED)
                                            <div class="charges-list-meta">Saldo: ${{ number_format($charge->outstanding_amount, 2) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $charge->status_badge_class }}">
                                            {{ $charge->display_status_label }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-7">
                                        <div class="charges-list-actions">
                                            <a href="{{ route('charges.show', array_filter([
                                                'charge' => $charge,
                                                'property' => $selectedProperty?->uuid,
                                                'return_to' => request()->fullUrl(),
                                            ])) }}"
                                                class="btn btn-sm btn-primary">
                                                Ver
                                            </a>

                                            @if ($canEditCharge)
                                                <button type="button" class="btn btn-sm btn-success" data-edit-charge
                                                    data-action="{{ route('charges.update', $charge) }}"
                                                    data-charge="{{ $charge->uuid }}" data-type="{{ $charge->type }}"
                                                    data-due-date="{{ $charge->due_date?->format('Y-m-d') }}"
                                                    data-amount="{{ number_format((float) $charge->amount, 2, '.', '') }}"
                                                    data-period-month="{{ $charge->period_month }}"
                                                    data-period-year="{{ $charge->period_year }}"
                                                    data-concept="{{ $charge->concept }}" data-notes="{{ $charge->notes }}">
                                                    Editar cargo
                                                </button>
                                            @endif

                                            @if ($canDeleteCharge)
                                                <form method="POST" action="{{ route('charges.destroy', $charge) }}"
                                                    class="d-inline js-delete-charge-form"
                                                    data-charge-concept="{{ $charge->concept }}"
                                                    data-charge-paid="{{ $charge->status === \App\Models\Charge::STATUS_PAID ? 'true' : 'false' }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="deletion_note" value="">
                                                    <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
                                                    <button type="submit" class="btn btn-sm btn-secondary">Eliminar cargo</button>
                                                </form>
                                            @endif

                                            @if ($canRegisterPayment)
                                                <button type="button" class="btn btn-sm btn-secondary" data-register-payment
                                                    data-charge="{{ $charge->uuid }}"
                                                    data-action="{{ route('charges.payments.store', $charge) }}"
                                                    data-concept="{{ $charge->concept }}"
                                                    data-outstanding="{{ number_format($charge->outstanding_amount, 2, '.', '') }}">
                                                    Registrar pago
                                                </button>
                                            @endif

                                            <a href="{{ route('charges.public.show', ['token' => $charge->payment_token]) }}"
                                                target="_blank" class="btn btn-sm btn-secondary">
                                                Abrir link
                                            </a>
                                            <button type="button" class="btn btn-sm btn-light"
                                                data-copy-link="{{ route('charges.public.show', ['token' => $charge->payment_token]) }}">
                                                Copiar link
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-16 text-muted" data-empty-row="true">No hay cargos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
