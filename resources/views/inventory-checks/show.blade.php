@extends('layouts.app')

@section('title', ($check->type === 'entry' ? 'Check de Entrada' : 'Check de Salida') . ' | ' . $property->internal_name . ' | SuWork')

@section('content')
    <div class="py-10 inventory-check-show">
        <style>
            .inventory-thumb {
                max-width: 150px !important;
                max-height: 150px !important;
                object-fit: cover;
            }

            .inventory-photo-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1rem;
            }

            .check-item-card {
                border-width: 1px;
                border-style: solid;
                border-color: #e4e6ef;
                transition: all 0.2s ease;
            }

            .check-item-card.item-pending-save {
                border-color: #ffc700 !important;
                box-shadow: 0 0 0 0.2rem rgba(255, 199, 0, 0.2) !important;
            }

            .status-buttons .btn.active {
                color: #fff !important;
            }

            .status-buttons .btn[data-status="ok"].active {
                background: #17c653 !important;
                border-color: #17c653 !important;
            }

            .status-buttons .btn[data-status="damaged"].active {
                background: #f1416c !important;
                border-color: #f1416c !important;
            }

            .status-buttons .btn[data-status="missing"].active {
                background: #ffc700 !important;
                border-color: #ffc700 !important;
                color: #1e1e2d !important;
            }
        </style>
        <div class="mb-8">
            <a href="{{ route('inventory-checks.index', $property) }}" class="text-gray-600 text-hover-primary fw-semibold">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Volver al inventario
            </a>
        </div>

        <div class="row g-6 mb-8">
            <div class="col-lg-8">
                <h1 class="mb-1 fw-bold">
                    {{ $check->type === 'entry' ? 'Check de Entrada' : 'Check de Salida' }}
                </h1>
                <div class="d-flex gap-3 align-items-center">
                    <span class="badge {{ $check->status === 'completed' ? 'badge-success' : 'badge-warning' }}">
                        {{ $check->status === 'completed' ? 'Completado' : 'En progreso' }}
                    </span>
                    <span class="text-muted">{{ $check->created_at->format('d/m/Y H:i') }}</span>
                    @if ($check->tenant)
                        <span class="text-primary">Inquilino: <strong>{{ $check->tenant->full_name }}</strong></span>
                    @endif
                </div>
            </div>
            <div class="col-lg-4 text-end">
                @if ($check->status === 'draft')
                    <form method="POST" action="{{ route('inventory-checks.complete', [$property, $check]) }}"
                        style="display: inline;" onsubmit="return confirm('Confirmar que el check esta completo?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-success">
                            <i class="ki-outline ki-check-circle fs-4 me-2"></i> Completar Check
                        </button>
                    </form>
                @else
                    <span class="badge badge-success badge-lg">Completado</span>
                @endif
            </div>
        </div>

        @if ($check->notes)
            <div class="alert alert-light-info mb-8">
                <strong>Notas:</strong>
                <p class="mb-0 mt-2">{{ $check->notes }}</p>
            </div>
        @endif

        <div class="card">
            <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center">
                <h3 class="card-title fw-bold mb-0">
                    Elementos ({{ $check->items->count() }})
                    <span class="badge badge-success ms-2" id="count-ok">{{ $check->items->where('status', 'ok')->count() }} OK</span>
                    <span class="badge badge-danger ms-1" id="count-damaged">{{ $check->items->where('status', 'damaged')->count() }} Danado</span>
                    <span class="badge badge-warning ms-1" id="count-missing">{{ $check->items->where('status', 'missing')->count() }} Faltante</span>
                    <span class="badge badge-secondary ms-1" id="count-pending">{{ $check->items->where('status', 'pending')->count() }} Pendiente</span>
                </h3>
                @if ($check->status === 'draft')
                    <button type="button" class="btn btn-primary" id="save-all-items-btn">
                        <i class="ki-outline ki-check fs-4 me-1"></i> Guardar cambios del check
                    </button>
                @endif
            </div>
            <div class="card-body pt-0">
                @if ($check->status === 'draft')
                    <form id="bulk-items-form" method="POST" action="{{ route('inventory-checks.update-items', [$property, $check]) }}">
                        @csrf
                        @method('PATCH')
                @endif

                @forelse ($check->items->groupBy(fn($item) => $item->inventoryItem?->area->name ?? 'Sin area') as $areaName => $items)
                    <div class="mb-8">
                        <h5 class="mb-4 fw-bold text-primary">{{ $areaName }}</h5>

                        <div class="d-flex flex-column gap-4">
                            @foreach ($items as $checkItem)
                                @php
                                    $statusBgClass = match ($checkItem->status) {
                                        'ok' => 'bg-light-success border-success',
                                        'damaged' => 'bg-light-danger border-danger',
                                        'missing' => 'bg-light-warning border-warning',
                                        default => '',
                                    };
                                @endphp
                                <div class="rounded p-4 check-item-card {{ $statusBgClass }}" id="item-{{ $checkItem->id }}">
                                    <div class="row g-4">
                                        <div class="col-lg-auto">
                                            @if ($checkItem->photo_path)
                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($checkItem->photo_path) }}"
                                                    class="rounded inventory-thumb cursor-pointer"
                                                    alt="{{ $checkItem->item_name }}"
                                                    onclick="document.getElementById('modalPhotoImg').src='{{ \Illuminate\Support\Facades\Storage::url($checkItem->photo_path) }}';"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#photoModal">
                                            @elseif ($checkItem->inventoryItem?->photos->isNotEmpty())
                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($checkItem->inventoryItem->photos->first()->latestVersion->file_path) }}"
                                                    class="rounded inventory-thumb cursor-pointer opacity-50"
                                                    alt="{{ $checkItem->item_name }}"
                                                    title="Foto original (no validada)"
                                                    onclick="document.getElementById('modalPhotoImg').src='{{ \Illuminate\Support\Facades\Storage::url($checkItem->inventoryItem->photos->first()->latestVersion->file_path) }}';"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#photoModal">
                                            @else
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center inventory-thumb">
                                                    <span class="text-muted">Sin foto</span>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="col-lg">
                                            <div class="row g-3 align-items-center">
                                                <div class="col-lg-3">
                                                    <strong>{{ $checkItem->item_name }}</strong>
                                                </div>

                                                @if ($check->status === 'draft')
                                                    <div class="col-lg-5">
                                                        <div class="btn-group status-buttons w-100" role="group">
                                                            <button type="button"
                                                                class="btn btn-sm btn-light-success js-status-btn {{ $checkItem->status === 'ok' ? 'active' : '' }}"
                                                                data-item-id="{{ $checkItem->id }}" data-status="ok">
                                                                OK
                                                            </button>
                                                            <button type="button"
                                                                class="btn btn-sm btn-light-danger js-status-btn {{ $checkItem->status === 'damaged' ? 'active' : '' }}"
                                                                data-item-id="{{ $checkItem->id }}" data-status="damaged">
                                                                Danado
                                                            </button>
                                                            <button type="button"
                                                                class="btn btn-sm btn-light-warning js-status-btn {{ $checkItem->status === 'missing' ? 'active' : '' }}"
                                                                data-item-id="{{ $checkItem->id }}" data-status="missing">
                                                                Faltante
                                                            </button>
                                                        </div>
                                                        <input type="hidden" name="items[{{ $checkItem->id }}][status]"
                                                            id="status-input-{{ $checkItem->id }}" value="{{ $checkItem->status }}">
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <input type="text" name="items[{{ $checkItem->id }}][notes]"
                                                            class="form-control form-control-sm js-notes-input"
                                                            data-item-id="{{ $checkItem->id }}" placeholder="Notas"
                                                            value="{{ $checkItem->notes ?? '' }}">
                                                    </div>
                                                @else
                                                    <div class="col-lg-4">
                                                        <span class="badge {{ $checkItem->status === 'ok' ? 'badge-success' : ($checkItem->status === 'damaged' ? 'badge-danger' : ($checkItem->status === 'missing' ? 'badge-warning' : 'badge-secondary')) }}">
                                                            {{ $checkItem->status === 'ok' ? 'OK' : ($checkItem->status === 'damaged' ? 'Danado' : ($checkItem->status === 'missing' ? 'Faltante' : 'Pendiente')) }}
                                                        </span>
                                                    </div>
                                                    <div class="col-lg-5">
                                                        @if ($checkItem->notes)
                                                            <small class="text-muted">{{ $checkItem->notes }}</small>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="alert alert-light-warning mb-0">No hay elementos en este check.</div>
                @endforelse

                @if ($check->status === 'draft')
                    </form>
                @endif
            </div>
        </div>

        <div class="mt-8 d-flex gap-3">
            <a href="{{ route('inventory-checks.index', $property) }}" class="btn btn-light">
                Volver al inventario
            </a>
        </div>
    </div>

    <div class="modal fade" id="photoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Foto del elemento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalPhotoImg" src="" alt="Foto" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('bulk-items-form');
            if (!form) {
                return;
            }

            const topSaveBtn = document.getElementById('save-all-items-btn');

            const statusClassMap = {
                ok: ['bg-light-success', 'border-success'],
                damaged: ['bg-light-danger', 'border-danger'],
                missing: ['bg-light-warning', 'border-warning'],
                pending: []
            };

            const showToast = (type, message) => {
                if (window.toastr) {
                    toastr[type](message);
                    return;
                }
                alert(message);
            };

            const getCard = (itemId) => document.getElementById(`item-${itemId}`);

            const clearStatusClasses = (card) => {
                card.classList.remove(
                    'bg-light-success',
                    'border-success',
                    'bg-light-danger',
                    'border-danger',
                    'bg-light-warning',
                    'border-warning'
                );
            };

            const applySavedStyle = (itemId, status) => {
                const card = getCard(itemId);
                if (!card) {
                    return;
                }
                clearStatusClasses(card);
                (statusClassMap[status] || []).forEach((className) => card.classList.add(className));
                card.classList.remove('item-pending-save');
            };

            const markPendingSave = (itemId) => {
                const card = getCard(itemId);
                if (!card) {
                    return;
                }
                card.classList.add('item-pending-save');
            };

            const updateCounters = () => {
                const statuses = [...form.querySelectorAll('input[id^="status-input-"]')].map((input) => input.value);
                const count = (value) => statuses.filter((status) => status === value).length;

                const countOk = document.getElementById('count-ok');
                const countDamaged = document.getElementById('count-damaged');
                const countMissing = document.getElementById('count-missing');
                const countPending = document.getElementById('count-pending');

                if (countOk) countOk.textContent = `${count('ok')} OK`;
                if (countDamaged) countDamaged.textContent = `${count('damaged')} Danado`;
                if (countMissing) countMissing.textContent = `${count('missing')} Faltante`;
                if (countPending) countPending.textContent = `${count('pending')} Pendiente`;
            };

            document.querySelectorAll('.js-status-btn').forEach((button) => {
                button.addEventListener('click', () => {
                    const itemId = button.dataset.itemId;
                    const status = button.dataset.status;
                    const input = document.getElementById(`status-input-${itemId}`);
                    if (!input) {
                        return;
                    }

                    input.value = status;
                    const group = button.closest('.status-buttons');
                    group?.querySelectorAll('.js-status-btn').forEach((btn) => btn.classList.remove('active'));
                    button.classList.add('active');
                    markPendingSave(itemId);
                    updateCounters();
                });
            });

            document.querySelectorAll('.js-notes-input').forEach((input) => {
                input.addEventListener('input', () => {
                    markPendingSave(input.dataset.itemId);
                });
            });

            const setSaveButtonsState = (disabled, text = 'Guardar cambios del check') => {
                [topSaveBtn].forEach((btn) => {
                    if (!btn) {
                        return;
                    }
                    btn.disabled = disabled;
                    btn.textContent = text;
                });
            };

            const submitBulkSave = async () => {
                setSaveButtonsState(true, 'Guardando...');
                const formData = new FormData(form);

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    const payload = await response.json();
                    if (!response.ok || !payload.success) {
                        throw new Error(payload.message || 'No fue posible guardar el check.');
                    }

                    (payload.items || []).forEach((item) => {
                        applySavedStyle(item.id, item.status);
                    });

                    updateCounters();
                    showToast('success', payload.message || 'Check guardado correctamente.');
                    setSaveButtonsState(false);
                } catch (error) {
                    setSaveButtonsState(false);
                    showToast('error', error.message || 'No fue posible guardar el check.');
                }
            };

            topSaveBtn?.addEventListener('click', submitBulkSave);

            updateCounters();
        });
    </script>
@endpush
