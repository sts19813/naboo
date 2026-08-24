@extends('layouts.app')

@section('title', 'Buzón | Naboo')

@push('styles')
    <style>
        .mailbox-module {
            --mailbox-surface: #ffffff;
            --mailbox-ink: #172033;
            --mailbox-text: #334155;
            --mailbox-muted: #7b879d;
            --mailbox-line: #e5eaf3;
            color: var(--mailbox-text);
        }

        .mailbox-table-card {
            border: 1px solid var(--mailbox-line);
            border-radius: 20px;
            overflow: hidden;
            background: var(--mailbox-surface);
        }

        .mailbox-table-card thead th {
            padding-top: 20px;
            padding-bottom: 20px;
            background: #f8fafc;
            border-bottom: 1px solid var(--mailbox-line) !important;
            color: #94a3b8 !important;
            font-size: 0.76rem;
            letter-spacing: 0.08em;
        }

        .mailbox-row td {
            padding-top: 18px;
            padding-bottom: 18px;
            border-top: 1px solid var(--mailbox-line) !important;
            vertical-align: middle;
            background: #fff;
            transition: background-color 0.2s ease;
        }

        .mailbox-row:hover td {
            background: #fcf8f6;
        }

        .mailbox-person {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .mailbox-avatar {
            display: grid;
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            place-items: center;
            border-radius: 50%;
            background: #fff1e8;
            color: #b54708;
            font-size: 0.95rem;
            font-weight: 800;
        }

        .mailbox-title {
            color: var(--mailbox-ink);
            font-size: 0.95rem;
            font-weight: 800;
            line-height: 1.35;
        }

        .mailbox-meta,
        .mailbox-preview {
            color: var(--mailbox-muted);
            font-size: 0.88rem;
            line-height: 1.45;
        }

        .mailbox-preview {
            margin-top: 4px;
        }

        .mailbox-actions .btn {
            min-width: 76px;
            border-radius: 12px;
            font-weight: 700;
        }

        .mailbox-message-full {
            color: var(--mailbox-text);
            font-size: 1rem;
            line-height: 1.7;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        @media (max-width: 767.98px) {
            .mailbox-table-card {
                border: 0;
                border-radius: 0;
                overflow: visible;
                background: transparent;
            }

            .mailbox-table-card .table-responsive {
                overflow: visible;
            }

            .mailbox-table-card table,
            .mailbox-table-card tbody {
                display: block;
                width: 100%;
            }

            .mailbox-table-card thead {
                display: none;
            }

            .mailbox-table-card tbody {
                display: grid;
                gap: 14px;
            }

            .mailbox-row {
                display: block;
                padding: 18px;
                border: 1px solid #e8eef7;
                border-radius: 8px;
                background: #fff;
                box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
            }

            .mailbox-row td {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 10px 0 !important;
                border-top: 1px solid #f0f3f8 !important;
                background: transparent !important;
                text-align: right !important;
            }

            .mailbox-row td::before {
                flex: 0 0 86px;
                color: #8b96b2;
                font-size: 0.66rem;
                font-weight: 800;
                letter-spacing: 0.04em;
                text-align: left;
                text-transform: uppercase;
            }

            .mailbox-row td:nth-child(1) {
                display: block;
                padding-top: 0 !important;
                padding-bottom: 14px !important;
                border-top: 0 !important;
                text-align: left !important;
            }

            .mailbox-row td:nth-child(1)::before,
            .mailbox-row td:nth-child(5)::before {
                content: none;
            }

            .mailbox-row td:nth-child(2)::before {
                content: 'Sugerencia';
            }

            .mailbox-row td:nth-child(3)::before {
                content: 'Propiedad';
            }

            .mailbox-row td:nth-child(4)::before {
                content: 'Enviado';
            }

            .mailbox-row td:nth-child(2) > div,
            .mailbox-row td:nth-child(3) > div {
                max-width: 64%;
            }

            .mailbox-row td:nth-child(5) {
                padding-top: 14px !important;
            }

            .mailbox-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="py-10 mailbox-module">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8">
            <div>
                <h1 class="mb-1 fw-bold text-dark">Buzón</h1>
                <div class="text-muted fs-6">{{ $suggestions->total() }} mensajes enviados por los inquilinos</div>
            </div>
        </div>

        <div class="mailbox-table-card">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle mb-0">
                    <thead>
                        <tr class="fw-bold text-muted text-uppercase fs-8">
                            <th class="ps-7 min-w-240px">Inquilino</th>
                            <th class="min-w-320px">Sugerencia</th>
                            <th class="min-w-220px">Propiedades</th>
                            <th class="min-w-160px">Enviado</th>
                            <th class="text-end pe-7 min-w-110px">Opciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($suggestions as $suggestion)
                            @php
                                $tenantName = $suggestion->tenant?->full_name ?? $suggestion->sender?->name ?? 'Inquilino eliminado';
                                $tenantEmail = $suggestion->tenant?->email ?? $suggestion->sender?->email ?? '-';
                                $initial = mb_strtoupper(mb_substr($tenantName, 0, 1));
                            @endphp
                            <tr class="mailbox-row">
                                <td class="ps-7">
                                    <div class="mailbox-person">
                                        <div class="mailbox-avatar">{{ $initial }}</div>
                                        <div class="min-w-0">
                                            <div class="mailbox-title text-break">{{ $tenantName }}</div>
                                            <div class="mailbox-meta text-break">{{ $tenantEmail }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div class="mailbox-title">{{ str($suggestion->title)->limit(80) }}</div>
                                        <div class="mailbox-preview">{{ str($suggestion->message)->limit(120) }}</div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        @forelse ($suggestion->tenant?->properties ?? [] as $property)
                                            <div class="mailbox-title">
                                                {{ $property->internal_name }}{{ $property->internal_reference ? ' · ' . $property->internal_reference : '' }}
                                            </div>
                                        @empty
                                            <span class="mailbox-meta">Sin propiedad asignada</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="text-nowrap">
                                    <div class="mailbox-title">{{ $suggestion->created_at->translatedFormat('d M Y') }}</div>
                                    <div class="mailbox-meta">{{ $suggestion->created_at->format('H:i') }}</div>
                                </td>
                                <td class="text-end pe-7 mailbox-actions">
                                    <button type="button" class="btn btn-sm btn-light-primary"
                                        data-bs-toggle="modal" data-bs-target="#suggestionModal{{ $suggestion->id }}">
                                        <i class="bi bi-eye me-1"></i> Ver
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-16 text-muted">
                                    No hay mensajes en el buzón.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($suggestions->hasPages())
            <div class="mt-6">
                {{ $suggestions->links() }}
            </div>
        @endif
    </div>

    @foreach ($suggestions as $suggestion)
        @php
            $tenantName = $suggestion->tenant?->full_name ?? $suggestion->sender?->name ?? 'Inquilino eliminado';
            $tenantEmail = $suggestion->tenant?->email ?? $suggestion->sender?->email ?? '-';
        @endphp
        <div class="modal fade" id="suggestionModal{{ $suggestion->id }}" tabindex="-1"
            aria-labelledby="suggestionModalLabel{{ $suggestion->id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title fs-3 fw-bold" id="suggestionModalLabel{{ $suggestion->id }}">
                                {{ $suggestion->title }}
                            </h2>
                            <div class="text-muted fs-7 mt-1">
                                {{ $tenantName }} · {{ $tenantEmail }} · {{ $suggestion->created_at->translatedFormat('d M Y, H:i') }}
                            </div>
                        </div>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary"
                            data-bs-dismiss="modal" aria-label="Cerrar">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body py-8">
                        <div class="mailbox-message-full">{{ $suggestion->message }}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
