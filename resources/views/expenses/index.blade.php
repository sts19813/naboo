@extends('layouts.app')

@section('title', 'Gastos | SuWork')

@push('styles')
    <style>
        .expenses-list-module {
            --el-surface: #ffffff;
            --el-ink: #172033;
            --el-text: #334155;
            --el-muted: #7b879d;
            --el-line: #e5eaf3;
            --el-accent: #b54708;
            --el-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
            color: var(--el-text);
        }

        .expenses-list-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 20px;
        }

        .expenses-list-search {
            position: relative;
            min-width: min(100%, 360px);
            flex: 1 1 300px;
        }

        .expenses-list-search i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--el-muted);
            font-size: 1rem;
            pointer-events: none;
        }

        .expenses-list-search .form-control {
            height: 52px;
            padding-left: 46px;
            border-radius: 16px;
            border: 1px solid var(--el-line);
            background: #fbfcfe;
            color: var(--el-ink);
            font-weight: 600;
            box-shadow: none;
        }

        .expenses-list-search .form-control:focus {
            border-color: rgba(181, 71, 8, 0.35);
            box-shadow: 0 0 0 4px rgba(181, 71, 8, 0.08);
        }

        .expenses-list-results {
            color: var(--el-muted);
            font-size: 1rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .expenses-list-table-card {
            margin-top: 20px;
            border: 1px solid var(--el-line);
            border-radius: 20px;
            overflow: hidden;
            background: var(--el-surface);
        }

        .expenses-list-table-card .table-responsive {
            overflow-x: auto;
        }

        .expenses-list-table-card table.dataTable {
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            border-collapse: separate !important;
            border-spacing: 0;
        }

        .expenses-list-table-card thead th {
            padding-top: 20px;
            padding-bottom: 20px;
            background: #f8fafc;
            border-bottom: 1px solid var(--el-line) !important;
            color: #94a3b8 !important;
            font-size: 0.76rem;
            letter-spacing: 0.08em;
        }

        .expenses-list-row {
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .expenses-list-row td {
            padding-top: 12px;
            padding-bottom: 12px;
            border-top: 1px solid var(--el-line) !important;
            vertical-align: middle;
            background: #fff;
        }

        .expenses-list-row:hover td {
            background: #fcf8f6;
        }

        .expenses-list-title {
            color: var(--el-ink);
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .expenses-list-meta {
            color: var(--el-muted);
            font-size: 0.88rem;
            margin-top: 4px;
            line-height: 1.4;
        }

        .expenses-list-value {
            color: var(--el-ink);
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .expenses-list-actions {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .expenses-list-actions .btn {
            border-radius: 12px;
            font-weight: 700;
        }

        .expenses-list-table-card .dataTables_info,
        .expenses-list-table-card .dataTables_paginate {
            padding: 18px 28px 0;
            color: var(--el-muted) !important;
            font-weight: 700;
        }

        .expenses-list-table-card .dataTables_paginate .pagination {
            gap: 6px;
        }

        .expenses-list-table-card .page-link {
            border-radius: 10px !important;
            border-color: var(--el-line) !important;
            color: var(--el-text) !important;
            min-width: 38px;
            text-align: center;
            font-weight: 700;
        }

        .expenses-list-table-card .page-item.active .page-link {
            background: var(--el-accent) !important;
            border-color: var(--el-accent) !important;
            color: #fff !important;
        }

        @media (max-width: 991px) {
            .expenses-list-table-card .dataTables_info,
            .expenses-list-table-card .dataTables_paginate {
                padding-left: 16px;
                padding-right: 16px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="py-10 expenses-module expenses-list-module">
        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-information-5 fs-2hx text-warning me-4"></i>
                <div class="fw-semibold">{{ session('warning') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-cross-circle fs-2hx text-danger me-4"></i>
                <div class="fw-semibold">{{ session('error') }}</div>
            </div>
        @endif

        @if ($errors->updateExpense->any())
            <div class="alert alert-danger d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-information-5 fs-2hx text-danger me-4"></i>
                <div class="fw-semibold">
                    No fue posible actualizar el gasto. Verifica los datos e inténtalo nuevamente.
                </div>
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8">
            <div>
                <h1 class="mb-1 fw-bold text-dark">Gastos</h1>
                <div class="text-muted fs-6">Control global de gastos por propiedad</div>
            </div>

            <div class="d-flex flex-wrap gap-3">
                {{-- Botón configuración --}}
                <button type="button"
                    class="btn btn-light-warning fw-bold"
                    data-bs-toggle="modal"
                    data-bs-target="#globalNotificationsModal">
                    <i class="ki-outline ki-setting-2 fs-4 me-1"></i>
                    Configuración
                </button>

                {{-- Nuevo gasto --}}
                <button type="button"
                    class="btn btn-primary fw-bold"
                    data-bs-toggle="modal"
                    data-bs-target="#createExpenseModal">
                    <i class="ki-outline ki-plus fs-4 me-1"></i>
                    Nuevo gasto
                </button>
            </div>
        </div>

        @include('expenses.partials.summary-cards', ['summary' => $summary])

        <div class="expenses-list-toolbar">
            <form method="GET" action="{{ route('expenses.index', $selectedProperty ? ['property' => $selectedProperty->uuid] : []) }}"
                id="expensesSearchForm" class="expenses-list-search mb-0">
                <i class="bi bi-search"></i>
                <input
                    id="expensesSearchInput"
                    type="search"
                    class="form-control"
                    placeholder="Buscar concepto, propiedad, estado, fecha..."
                    autocomplete="off">
            </form>

            <div id="expensesResultCount" class="expenses-list-results">{{ $expenses->count() }} resultados</div>
        </div>

        {{-- TABLA --}}
        <div class="expenses-list-table-card">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle mb-0" id="expensesTable">
                    <thead>
                        <tr class="fw-bold text-muted text-uppercase fs-8">
                            <th class="ps-7 min-w-240px">Concepto</th>
                            <th class="min-w-220px">Propiedad</th>
                            <th class="min-w-140px">Monto</th>
                            <th class="min-w-140px">Vencimiento</th>
                            <th class="min-w-120px">Estado</th>
                            <th class="min-w-120px">Adjuntos</th>
                            <th class="min-w-280px text-end pe-7">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($expenses as $expense)
                            <tr class="expenses-list-row" data-expense-row>
                                <td class="ps-7">
                                    <div class="expenses-list-title">
                                        {{ $expense->concept }}
                                    </div>
                                    @if ($expense->excluded_from_totals)
                                        <span class="badge badge-light-secondary text-muted mt-1">Paga inquilino · No contabiliza</span>
                                    @endif

                                    @if ($expense->description)
                                        <div class="expenses-list-meta">
                                            {{ $expense->description }}
                                        </div>
                                    @endif

                                    @include('expenses.partials.attachments', [
                                        'files' => $expense->files->take(4),
                                    ])
                                </td>

                                <td>
                                    <div class="expenses-list-value">
                                        {{ $expense->property?->internal_name ?? '-' }}
                                    </div>

                                    <div class="expenses-list-meta">
                                        {{ $expense->property?->internal_reference ?: '-' }}
                                    </div>
                                </td>

                                <td>
                                    <div class="expenses-list-value">${{ number_format((float) $expense->amount, 2) }}</div>
                                </td>

                                <td>
                                    <div class="expenses-list-value">{{ $expense->due_date?->format('d/m/Y') ?? '-' }}</div>
                                </td>

                                <td>
                                    @include('expenses.partials.status-badge', [
                                        'expense' => $expense,
                                    ])
                                </td>

                                <td>
                                    @if ($expense->files_count > 0)
                                        <span class="badge badge-light-info text-info">
                                            <i class="ki-outline ki-paper-clip fs-6 me-1"></i>
                                            {{ $expense->files_count }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td class="text-end pe-7">
                                    <div class="expenses-list-actions">

                                        @if (!$expense->is_paid)
                                            <form method="POST"
                                                action="{{ route('expenses.mark-paid', $expense) }}">
                                                @csrf

                                                <button type="submit"
                                                    class="btn btn-sm btn-light-success">
                                                    Marcar pagado
                                                </button>
                                            </form>
                                        @endif

                                        <button type="button"
                                            class="btn btn-sm btn-light-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editExpenseModal-{{ $expense->uuid }}">
                                            Editar
                                        </button>

                                        <form method="POST"
                                            action="{{ route('expenses.destroy', $expense) }}"
                                            class="js-expense-delete-form"
                                            data-confirm-message="¿Deseas eliminar el gasto {{ $expense->concept }}?">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                class="btn btn-sm btn-light-danger">
                                                Eliminar
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7"
                                    class="text-center py-16 text-muted"
                                    data-empty-row="true">
                                    No hay gastos registrados.
                                </td>
                            </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- MODAL CONFIGURACIÓN GLOBAL --}}
    <div class="modal fade"
        id="globalNotificationsModal"
        tabindex="-1"
        aria-hidden="true">

        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <form method="POST"
                    action="{{ route('expenses.setup.global') }}">

                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h3 class="modal-title">
                            Configuración global de notificaciones
                        </h3>

                        <button type="button"
                            class="btn btn-icon btn-sm btn-active-light-primary"
                            data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>

                    <div class="modal-body">

                        <div class="row g-5">

                            <div class="col-md-4">
                                <label class="form-label required">
                                    Días previos de aviso
                                </label>

                                <input type="number"
                                    name="days_before"
                                    min="0"
                                    max="365"
                                    class="form-control @error('days_before', 'expenseGlobalSetup') is-invalid @enderror"
                                    value="{{ old('days_before', (int) ($globalSetup->days_before ?? 0)) }}"
                                    required>

                                @error('days_before', 'expenseGlobalSetup')
                                    <div class="text-danger fs-7 mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    Correos
                                </label>

                                <textarea name="emails"
                                    rows="4"
                                    class="form-control @error('emails', 'expenseGlobalSetup') is-invalid @enderror"
                                    placeholder="correo1@dominio.com, correo2@dominio.com">{{ old('emails', implode(', ', (array) ($globalSetup->emails ?? []))) }}</textarea>

                                <div class="text-muted fs-8 mt-1">
                                    Separar con coma o salto de línea.
                                </div>

                                @error('emails', 'expenseGlobalSetup')
                                    <div class="text-danger fs-7 mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    Teléfonos
                                </label>

                                <textarea name="phones"
                                    rows="4"
                                    class="form-control @error('phones', 'expenseGlobalSetup') is-invalid @enderror"
                                    placeholder="9990000000, 9991111111">{{ old('phones', implode(', ', (array) ($globalSetup->phones ?? []))) }}</textarea>

                                <div class="text-muted fs-8 mt-1">
                                    Separar con coma o salto de línea.
                                </div>

                                @error('phones', 'expenseGlobalSetup')
                                    <div class="text-danger fs-7 mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button type="submit"
                            class="btn btn-warning">
                            Guardar configuración
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    {{-- MODAL CREAR --}}
    <div class="modal fade"
        id="createExpenseModal"
        tabindex="-1"
        aria-hidden="true">

        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">

                <form method="POST"
                    action="{{ route('expenses.store') }}"
                    enctype="multipart/form-data">

                    @csrf

                    <div class="modal-header">
                        <h3 class="modal-title">
                            Registrar gasto
                        </h3>

                        <button type="button"
                            class="btn btn-icon btn-sm btn-active-light-primary"
                            data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>

                    <div class="modal-body">

                        @if ($errors->createExpense->any())
                            <div class="alert alert-danger mb-6">
                                Revisa los datos del formulario.
                            </div>
                        @endif

                        <div class="row g-5">

                            <div class="col-md-6">
                                <label class="form-label required">
                                    Propiedad
                                </label>

                                <select name="property_id"
                                    class="form-select @error('property_id', 'createExpense') is-invalid @enderror"
                                    required>

                                    <option value="">
                                        Seleccionar...
                                    </option>

                                    @foreach ($properties as $property)
                                        <option value="{{ $property->id }}"
                                            {{ (string) old('property_id', $selectedProperty?->id) === (string) $property->id ? 'selected' : '' }}>

                                            {{ $property->internal_name }}
                                            {{ $property->internal_reference ? ' - ' . $property->internal_reference : '' }}
                                        </option>
                                    @endforeach

                                </select>

                                @error('property_id', 'createExpense')
                                    <div class="text-danger fs-7 mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">
                                    Concepto
                                </label>

                                <input type="text"
                                    name="concept"
                                    maxlength="190"
                                    class="form-control @error('concept', 'createExpense') is-invalid @enderror"
                                    value="{{ old('concept') }}"
                                    required>

                                @error('concept', 'createExpense')
                                    <div class="text-danger fs-7 mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">
                                    Monto
                                </label>

                                <input type="number"
                                    name="amount"
                                    min="0.01"
                                    step="0.01"
                                    class="form-control @error('amount', 'createExpense') is-invalid @enderror"
                                    value="{{ old('amount') }}"
                                    required>

                                @error('amount', 'createExpense')
                                    <div class="text-danger fs-7 mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">
                                    Fecha vencimiento
                                </label>

                                <input type="date"
                                    name="due_date"
                                    class="form-control @error('due_date', 'createExpense') is-invalid @enderror"
                                    value="{{ old('due_date') }}"
                                    required>

                                @error('due_date', 'createExpense')
                                    <div class="text-danger fs-7 mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    Adjuntos
                                </label>

                                <input type="file"
                                    name="files[]"
                                    multiple
                                    accept=".jpg,.jpeg,.png,.webp,.pdf"
                                    class="form-control @error('files.*', 'createExpense') is-invalid @enderror">

                                <div class="text-muted fs-8 mt-1">
                                    JPG, PNG, WEBP, PDF.
                                </div>

                                @error('files.*', 'createExpense')
                                    <div class="text-danger fs-7 mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">
                                    Descripción
                                </label>

                                <textarea name="description"
                                    rows="3"
                                    class="form-control @error('description', 'createExpense') is-invalid @enderror">{{ old('description') }}</textarea>

                                @error('description', 'createExpense')
                                    <div class="text-danger fs-7 mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button type="submit"
                            class="btn btn-primary">
                            Guardar gasto
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    {{-- MODALES EDITAR --}}
    @foreach ($expenses as $expense)
        <div class="modal fade"
            id="editExpenseModal-{{ $expense->uuid }}"
            tabindex="-1"
            aria-hidden="true">

            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">

                    <form method="POST"
                        action="{{ route('expenses.update', $expense) }}"
                        enctype="multipart/form-data">

                        @csrf
                        @method('PUT')

                        <div class="modal-header">
                            <h3 class="modal-title">
                                Editar gasto
                            </h3>

                            <button type="button"
                                class="btn btn-icon btn-sm btn-active-light-primary"
                                data-bs-dismiss="modal">
                                <i class="ki-outline ki-cross fs-1"></i>
                            </button>
                        </div>

                        <div class="modal-body">

                            <div class="row g-5">

                                <div class="col-md-6">
                                    <label class="form-label required">
                                        Concepto
                                    </label>

                                    <input type="text"
                                        name="concept"
                                        maxlength="190"
                                        class="form-control"
                                        value="{{ $expense->concept }}"
                                        required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label required">
                                        Monto
                                    </label>

                                    <input type="number"
                                        name="amount"
                                        min="0.01"
                                        step="0.01"
                                        class="form-control"
                                        value="{{ number_format((float) $expense->amount, 2, '.', '') }}"
                                        required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label required">
                                        Vencimiento
                                    </label>

                                    <input type="date"
                                        name="due_date"
                                        class="form-control"
                                        value="{{ $expense->due_date?->format('Y-m-d') }}"
                                        required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">
                                        Descripción
                                    </label>

                                    <textarea name="description"
                                        rows="3"
                                        class="form-control">{{ $expense->description }}</textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">
                                        Adjuntar nuevos archivos
                                    </label>

                                    <input type="file"
                                        name="files[]"
                                        multiple
                                        accept=".jpg,.jpeg,.png,.webp,.pdf"
                                        class="form-control">
                                </div>

                                @if ($expense->files->isNotEmpty())
                                    <div class="col-12">

                                        <label class="form-label">
                                            Adjuntos actuales
                                        </label>

                                        <div class="d-flex flex-column gap-3">

                                            @foreach ($expense->files as $file)
                                                <div class="d-flex align-items-center justify-content-between border rounded px-3 py-2">

                                                    <div class="d-flex align-items-center gap-3">

                                                        @if ($file->is_image)
                                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($file->path) }}"
                                                                alt="Adjunto"
                                                                style="width: 36px; height: 36px; object-fit: cover; border-radius: 6px;">
                                                        @else
                                                            <span class="badge badge-light-primary text-primary">
                                                                PDF
                                                            </span>
                                                        @endif

                                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($file->path) }}"
                                                            target="_blank">

                                                            {{ $file->original_name ?: 'Archivo' }}
                                                        </a>

                                                    </div>

                                                    <label class="form-check form-check-custom form-check-solid">
                                                        <input class="form-check-input"
                                                            type="checkbox"
                                                            name="remove_file_ids[]"
                                                            value="{{ $file->id }}">

                                                        <span class="form-check-label">
                                                            Eliminar
                                                        </span>
                                                    </label>

                                                </div>
                                            @endforeach

                                        </div>
                                    </div>
                                @endif

                            </div>

                        </div>

                        <div class="modal-footer">
                            <button type="button"
                                class="btn btn-light"
                                data-bs-dismiss="modal">
                                Cancelar
                            </button>

                            <button type="submit"
                                class="btn btn-primary">
                                Guardar cambios
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    @endforeach
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('expensesSearchForm');
            const input = document.getElementById('expensesSearchInput');
            const table = document.getElementById('expensesTable');
            const resultCount = document.getElementById('expensesResultCount');

            document.querySelectorAll('.js-expense-delete-form').forEach((deleteForm) => {
                deleteForm.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const message = deleteForm.dataset.confirmMessage || '¿Deseas eliminar este gasto?';
                    let confirmed = false;

                    if (window.Swal?.fire) {
                        const result = await window.Swal.fire({
                            title: 'Eliminar gasto',
                            text: message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#d9214e',
                            reverseButtons: true,
                        });
                        confirmed = !!result.isConfirmed;
                    } else {
                        confirmed = window.confirm(message);
                    }

                    if (confirmed) {
                        deleteForm.submit();
                    }
                });
            });

            form?.addEventListener('submit', (event) => {
                event.preventDefault();
            });

            if (!table || typeof $ === 'undefined' || !$.fn.DataTable) {
                return;
            }

            table.querySelectorAll('td[data-empty-row="true"]').forEach((cell) => {
                cell.closest('tr')?.remove();
            });

            const dataTable = $(table).DataTable({
                dom: "rt<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-md-end'p>>",
                pageLength: 10,
                lengthChange: false,
                order: [],
                info: true,
                searching: true,
                autoWidth: false,
                language: {
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ gastos',
                    infoEmpty: 'Mostrando 0 a 0 de 0 gastos',
                    paginate: {
                        first: 'Primera',
                        last: 'Ultima',
                        next: 'Siguiente',
                        previous: 'Anterior',
                    },
                    emptyTable: 'No hay gastos registrados.',
                    zeroRecords: 'No se encontraron coincidencias con este filtro.',
                },
                columnDefs: [
                    {
                        targets: [6],
                        orderable: false,
                        searchable: false,
                    },
                ],
            });

            const syncResultCount = () => {
                if (!resultCount) {
                    return;
                }

                const count = dataTable.rows({ filter: 'applied' }).count();
                resultCount.textContent = `${count} ${count === 1 ? 'resultado' : 'resultados'}`;
            };

            input?.addEventListener('input', (event) => {
                dataTable.search(event.target.value || '').draw();
                syncResultCount();
            });

            dataTable.on('draw', syncResultCount);
            syncResultCount();
        })();
    </script>

    @if ($errors->createExpense->any())
        <script>
            (() => {
                const modalEl = document.getElementById('createExpenseModal');

                if (!modalEl) return;

                new bootstrap.Modal(modalEl).show();
            })();
        </script>
    @endif

    @if ($errors->expenseGlobalSetup->any())
        <script>
            (() => {
                const modalEl = document.getElementById('globalNotificationsModal');

                if (!modalEl) return;

                new bootstrap.Modal(modalEl).show();
            })();
        </script>
    @endif
@endpush
