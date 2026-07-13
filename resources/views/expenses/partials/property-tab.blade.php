<div class="tab-pane fade property-tab-pane" id="tab-expenses" role="tabpanel" aria-labelledby="tab-expenses-tab">
    <div class="card property-block-card">
        <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h3 class="card-title fw-bold mb-1">Resumen de gastos</h3>
                <div class="text-muted fs-7">Seguimiento de pendientes, pagados y atrasados.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                 <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createExpenseModalProperty">
                   + Nuevo gasto
                </button>
                <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="modal"
                    data-bs-target="#createRecurringExpenseItemModal">
                    + Mantenimiento
                </button>
                <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#expenseSetupModal d-none" style="display: none;">
                    Configuración de notificación
                </button>
                <a href="{{ route('expenses.index', ['property' => $property->uuid]) }}" class="btn btn-sm btn-light">
                    Abrir módulo
                </a>
            </div>
        </div>

        <div class="card-body pt-0">
            @include('expenses.partials.summary-cards', ['summary' => $propertyExpenseSummary])

            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">
                <div>
                    <div class="fw-bold text-dark">Gastos registrados</div>
                    <div class="text-muted fs-7">Conceptos únicos y registros generados desde gastos recurrentes.</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-row-bordered align-middle mb-0">
                    <thead>
                        <tr class="text-muted text-uppercase fs-8">
                            <th>Concepto</th>
                            <th>Monto</th>
                            <th>Vencimiento</th>
                            <th>Estado</th>
                            <th>Adjuntos</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($propertyExpenses as $expense)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $expense->concept }}</div>
                                    @if ($expense->recurring_expense_item_id)
                                        <span class="badge badge-light-primary text-primary mt-1">Generado automáticamente</span>
                                    @endif
                                    @if ($expense->excluded_from_totals)
                                        <span class="badge badge-light-secondary text-muted mt-1">Paga inquilino</span>
                                    @endif
                                    @if ($expense->description)
                                        <div class="text-muted fs-7">{{ $expense->description }}</div>
                                    @endif
                                    @include('expenses.partials.attachments', [
                                        'files' => $expense->files->take(4),
                                        'previewInModal' => true,
                                        'previewTriggerClass' => 'js-expense-file-preview',
                                    ])
                                </td>
                                <td>${{ number_format((float) $expense->amount, 2) }}</td>
                                <td>{{ $expense->due_date?->format('d/m/Y') ?? '-' }}</td>
                                <td>@include('expenses.partials.status-badge', ['expense' => $expense])</td>
                                <td>
                                    @if ($expense->files_count > 0)
                                        <span class="badge badge-light-info text-info">
                                            <i class="ki-outline ki-paper-clip fs-6 me-1"></i>{{ $expense->files_count }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex flex-wrap justify-content-end gap-2">
                                        @if (!$expense->is_paid)
                                            @php
                                                $expensePayload = [
                                                    'uuid' => $expense->uuid,
                                                    'concept' => $expense->concept,
                                                    'amount' => number_format((float) $expense->amount, 2, '.', ''),
                                                    'due_date' => $expense->due_date?->format('Y-m-d'),
                                                    'due_date_label' => $expense->due_date?->format('d/m/Y') ?? '-',
                                                    'description' => $expense->description,
                                                    'mark_action' => route('expenses.mark-paid', $expense),
                                                    'update_action' => route('expenses.update', $expense),
                                                ];
                                            @endphp
                                            <button type="button" class="btn btn-sm btn-light-success js-mark-paid-btn"
                                                data-expense='@json($expensePayload)'
                                                data-bs-toggle="modal" data-bs-target="#markPaidExpenseModal">
                                                Marcar pagado
                                            </button>
                                        @endif

                                        <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="collapse"
                                            data-bs-target="#expense-edit-{{ $expense->uuid }}" aria-expanded="false"
                                            aria-controls="expense-edit-{{ $expense->uuid }}">
                                            Editar
                                        </button>

                                        <form method="POST" action="{{ route('expenses.destroy', $expense) }}"
                                            class="js-expense-delete-form"
                                            data-confirm-title="Eliminar gasto"
                                            data-confirm-message="¿Deseas eliminar el gasto {{ $expense->concept }}?"
                                            data-confirm-button="Sí, eliminar">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="property_context" value="{{ $property->uuid }}">
                                            <button type="submit" class="btn btn-sm btn-light-danger">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse" id="expense-edit-{{ $expense->uuid }}">
                                <td colspan="6" class="bg-light-primary">
                                    <form method="POST" action="{{ route('expenses.update', $expense) }}" enctype="multipart/form-data"
                                        class="row g-4 p-2">
                                        @csrf
                                        @method('PUT')

                                        <input type="hidden" name="property_context" value="{{ $property->uuid }}">

                                        <div class="col-md-4">
                                            <label class="form-label required">Concepto</label>
                                            <input type="text" name="concept" maxlength="190" class="form-control"
                                                value="{{ $expense->concept }}" required>
                                        </div>

                                        <div class="col-md-2">
                                            <label class="form-label required">Monto</label>
                                            <input type="number" name="amount" min="0.01" step="0.01" class="form-control"
                                                value="{{ number_format((float) $expense->amount, 2, '.', '') }}" required>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label required">Vencimiento</label>
                                            <input type="date" name="due_date" class="form-control"
                                                value="{{ $expense->due_date?->format('Y-m-d') }}" required>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Nuevos adjuntos</label>
                                            <input type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf"
                                                class="form-control">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Descripción</label>
                                            <textarea name="description" rows="2" class="form-control">{{ $expense->description }}</textarea>
                                        </div>

                                        @if ($expense->files->isNotEmpty())
                                            <div class="col-12">
                                                <div class="d-flex flex-column gap-2">
                                                    @foreach ($expense->files as $file)
                                                        <label class="form-check form-check-custom form-check-solid d-flex justify-content-between border rounded px-3 py-2">
                                                            <div>
                                                                @if ($file->is_image)
                                                                    <span class="badge badge-light-warning text-warning me-2">Imagen</span>
                                                                @else
                                                                    <span class="badge badge-light-primary text-primary me-2">PDF</span>
                                                                @endif
                                                                {{ $file->original_name ?: 'Archivo' }}
                                                            </div>
                                                            <div>
                                                                <input class="form-check-input" type="checkbox" name="remove_file_ids[]"
                                                                    value="{{ $file->id }}">
                                                                <span class="form-check-label ms-2">Eliminar</span>
                                                            </div>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        <div class="col-12 d-flex justify-content-end">
                                            <button type="submit" class="btn btn-sm btn-primary">Guardar cambios</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-8">No hay gastos registrados para esta propiedad.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createRecurringExpenseItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Mantenimiento / Gastos recurrentes</h3>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </button>
                </div>

                <div class="modal-body">
                    @if ($errors->recurringExpenseItem->any())
                        <div class="alert alert-danger py-3 px-4">
                            {{ $errors->recurringExpenseItem->first() }}
                        </div>
                    @endif

                    <ul class="nav nav-tabs nav-line-tabs mb-6" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="recurring-expense-create-tab" data-bs-toggle="tab"
                                data-bs-target="#recurring-expense-create-pane" type="button" role="tab"
                                aria-controls="recurring-expense-create-pane" aria-selected="true">
                                Agregar
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="recurring-expense-history-tab" data-bs-toggle="tab"
                                data-bs-target="#recurring-expense-history-pane" type="button" role="tab"
                                aria-controls="recurring-expense-history-pane" aria-selected="false">
                                Histórico
                                <span class="badge badge-light-primary ms-2">{{ $propertyRecurringExpenseItems->count() }}</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="recurring-expense-create-pane" role="tabpanel"
                            aria-labelledby="recurring-expense-create-tab">
                            <div class="fw-bold text-dark mb-4">Agregar gasto recurrente</div>
                            <form method="POST" action="{{ route('expenses.recurring-items.store', $property) }}"
                                class="js-recurring-item-form" enctype="multipart/form-data">
                                @csrf

                                <div class="row g-5">
                                    <div class="col-md-6">
                                        <label class="form-label required">Concepto</label>
                                        <input type="text" name="concept" maxlength="190"
                                            class="form-control @error('concept', 'recurringExpenseItem') is-invalid @enderror"
                                            value="{{ old('concept') }}" placeholder="Ej. Cuota de mantenimiento" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label required">Monto</label>
                                        <input type="number" name="amount" min="0.01" step="0.01"
                                            class="form-control @error('amount', 'recurringExpenseItem') is-invalid @enderror"
                                            value="{{ old('amount') }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label required">Frecuencia</label>
                                        <select name="frequency"
                                            class="form-select js-recurring-frequency @error('frequency', 'recurringExpenseItem') is-invalid @enderror" required>
                                            @foreach ($recurringExpenseFrequencyOptions as $frequency => $label)
                                                <option value="{{ $frequency }}" {{ old('frequency', 'monthly') === $frequency ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label required">Fecha de inicio</label>
                                        <input type="date" name="starts_on"
                                            class="form-control @error('starts_on', 'recurringExpenseItem') is-invalid @enderror"
                                            value="{{ old('starts_on', now()->toDateString()) }}" required>
                                        <div class="text-muted fs-8 mt-1">La repetición conservará este día cada mes o esta fecha cada año.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label required">Cantidad de registros</label>
                                        <input type="number" name="occurrences_count" min="1" max="120"
                                            class="form-control js-recurring-count @error('occurrences_count', 'recurringExpenseItem') is-invalid @enderror"
                                            value="{{ old('occurrences_count', 12) }}" required>
                                        <div class="text-muted fs-8 mt-1">Ejemplo: 12 mensuales crean un registro por mes durante 12 meses.</div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Adjuntos</label>
                                        <input type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf"
                                            class="form-control @error('files.*', 'recurringExpenseItem') is-invalid @enderror">
                                        <div class="text-muted fs-8 mt-1">Se copiarán a cada gasto generado desde este recurrente.</div>
                                        @error('files.*', 'recurringExpenseItem')
                                            <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Descripción</label>
                                        <textarea name="description" rows="3" maxlength="4000" class="form-control">{{ old('description') }}</textarea>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-primary">Guardar y generar</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="recurring-expense-history-pane" role="tabpanel"
                            aria-labelledby="recurring-expense-history-tab">
                            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">
                                <div>
                                    <div class="fw-bold text-dark">Histórico</div>
                                    <div class="text-muted fs-7">Consulta los gastos recurrentes usados para generar registros en la tabla principal.</div>
                                </div>
                                <span class="badge badge-light-primary">{{ $propertyRecurringExpenseItems->count() }}</span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-row-bordered align-middle mb-0">
                                    <thead>
                                        <tr class="text-muted text-uppercase fs-8">
                                            <th>Concepto</th>
                                            <th>Monto</th>
                                            <th>Frecuencia</th>
                                            <th>Inicio</th>
                                            <th>Registros</th>
                                            <th>Adjuntos</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($propertyRecurringExpenseItems as $item)
                                            <tr>
                                                <td>
                                                    <div class="fw-bold">{{ $item->concept }}</div>
                                                    @if ($item->description)
                                                        <div class="text-muted fs-7">{{ $item->description }}</div>
                                                    @endif
                                                </td>
                                                <td>${{ number_format((float) $item->amount, 2) }}</td>
                                                <td>{{ $item->frequency_label }}</td>
                                                <td>{{ $item->starts_on?->format('d/m/Y') }}</td>
                                                <td>{{ $item->occurrences_count }}</td>
                                                <td>
                                                    @if ($item->files_count > 0)
                                                        <span class="badge badge-light-info text-info">
                                                            <i class="ki-outline ki-paper-clip fs-6 me-1"></i>{{ $item->files_count }}
                                                        </span>
                                                        @include('expenses.partials.attachments', [
                                                            'files' => $item->files->take(3),
                                                            'previewInModal' => true,
                                                            'previewTriggerClass' => 'js-expense-file-preview',
                                                        ])
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge {{ $item->is_active ? 'badge-light-success text-success' : 'badge-light-secondary text-muted' }}">
                                                        {{ $item->is_active ? 'Activo' : 'Pausado' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-6">
                                                    No hay gastos recurrentes en el histórico.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="expenseSetupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('expenses.properties.setup', $property) }}">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h3 class="modal-title">Configuración de notificaciones</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-5">
                            <div class="col-md-4">
                                <label class="form-label required">Configuración</label>
                                <select name="use_global_setup" class="form-select @error('use_global_setup', 'expensePropertySetup') is-invalid @enderror">
                                    <option value="1" {{ (string) old('use_global_setup', $resolvedPropertyExpenseNotificationSetup['uses_global'] ? 1 : 0) === '1' ? 'selected' : '' }}>
                                        Usar configuración global
                                    </option>
                                    <option value="0" {{ (string) old('use_global_setup', $resolvedPropertyExpenseNotificationSetup['uses_global'] ? 1 : 0) === '0' ? 'selected' : '' }}>
                                        Configuración personalizada
                                    </option>
                                </select>
                                @error('use_global_setup', 'expensePropertySetup')
                                    <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">Días de aviso</label>
                                <input type="number" min="0" max="365" name="days_before"
                                    class="form-control @error('days_before', 'expensePropertySetup') is-invalid @enderror"
                                    value="{{ old('days_before', (int) ($resolvedPropertyExpenseNotificationSetup['days_before'] ?? $globalExpenseNotificationSetup->days_before ?? 0)) }}">
                                @error('days_before', 'expensePropertySetup')
                                    <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Correos</label>
                                <textarea name="emails" rows="2" class="form-control @error('emails', 'expensePropertySetup') is-invalid @enderror"
                                    placeholder="correo1@dominio.com, correo2@dominio.com">{{ old('emails', implode(', ', (array) ($resolvedPropertyExpenseNotificationSetup['emails'] ?? []))) }}</textarea>
                                @error('emails', 'expensePropertySetup')
                                    <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Teléfonos</label>
                                <textarea name="phones" rows="2" class="form-control @error('phones', 'expensePropertySetup') is-invalid @enderror"
                                    placeholder="9990000000, 9991111111">{{ old('phones', implode(', ', (array) ($resolvedPropertyExpenseNotificationSetup['phones'] ?? []))) }}</textarea>
                                @error('phones', 'expensePropertySetup')
                                    <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar configuración</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createExpenseModalProperty" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data">
                    @csrf

                    <input type="hidden" name="property_id" value="{{ $property->id }}">
                    <input type="hidden" name="property_context" value="{{ $property->uuid }}">

                    <div class="modal-header">
                        <h3 class="modal-title">Registrar gasto</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label required">Concepto</label>
                                <input type="text" name="concept" maxlength="190"
                                    class="form-control @error('concept', 'createExpense') is-invalid @enderror"
                                    value="{{ old('concept') }}" required>
                                @error('concept', 'createExpense')
                                    <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label required">Monto</label>
                                <input type="number" name="amount" min="0.01" step="0.01"
                                    class="form-control @error('amount', 'createExpense') is-invalid @enderror"
                                    value="{{ old('amount') }}" required>
                                @error('amount', 'createExpense')
                                    <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label required">Fecha vencimiento</label>
                                <input type="date" name="due_date"
                                    class="form-control @error('due_date', 'createExpense') is-invalid @enderror"
                                    value="{{ old('due_date') }}" required>
                                @error('due_date', 'createExpense')
                                    <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Adjuntos</label>
                                <input type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf"
                                    class="form-control @error('files.*', 'createExpense') is-invalid @enderror">
                                @error('files.*', 'createExpense')
                                    <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea name="description" rows="3" class="form-control @error('description', 'createExpense') is-invalid @enderror">{{ old('description') }}</textarea>
                                @error('description', 'createExpense')
                                    <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar gasto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="markPaidExpenseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-scrollable">
            <div class="modal-content">
                <form id="markPaidExpenseForm" method="POST" action="">
                    @csrf
                    <input type="hidden" name="property_context" value="{{ $property->uuid }}">

                    <div class="modal-header">
                        <h3 class="modal-title">Marcar gasto como pagado</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-4">
                            <div class="fw-bold" id="markPaidExpenseConcept">-</div>
                            <div class="text-muted fs-7" id="markPaidExpenseAmount">-</div>
                            <div class="text-muted fs-7" id="markPaidExpenseDueDate">-</div>
                        </div>

                        <div class="form-check form-check-custom form-check-solid mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="markPaidAttachToggle">
                            <label class="form-check-label" for="markPaidAttachToggle">Deseo adjuntar comprobante de pago</label>
                        </div>

                        <div id="markPaidReceiptWrap" class="d-none">
                            <label class="form-label">Comprobante (archivo adicional)</label>
                            <input type="file" id="markPaidReceipt" accept=".jpg,.jpeg,.png,.webp,.pdf" class="form-control">
                            <div class="text-muted fs-8 mt-1">Se guarda como adjunto del gasto antes de marcarlo pagado.</div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success" id="markPaidSubmitBtn">Confirmar pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="expenseFilePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable ticket-file-modal-dialog">
            <div class="modal-content ticket-file-modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="expenseFilePreviewTitle">Archivo</h3>
                    <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">×</button>
                </div>
                <div class="modal-body ticket-file-modal-body">
                    <img src="" alt="" class="ticket-file-modal-media d-none" id="expenseFilePreviewImage">
                    <iframe src="" class="ticket-file-modal-frame d-none" id="expenseFilePreviewPdf" title="Vista previa PDF"></iframe>
                    <div class="ticket-file-modal-fallback d-none" id="expenseFilePreviewFallback">
                        <i class="bi bi-file-earmark"></i>
                        <div>Este archivo no tiene vista previa dentro del navegador.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light-primary" id="expenseFilePreviewDownload" download>
                        <i class="bi bi-download"></i> Descargar
                    </a>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (() => {
            const filePreviewModalEl = document.getElementById('expenseFilePreviewModal');
            const filePreviewModal = filePreviewModalEl && window.bootstrap?.Modal
                ? new window.bootstrap.Modal(filePreviewModalEl)
                : null;
            const filePreviewTitle = document.getElementById('expenseFilePreviewTitle');
            const filePreviewImage = document.getElementById('expenseFilePreviewImage');
            const filePreviewPdf = document.getElementById('expenseFilePreviewPdf');
            const filePreviewFallback = document.getElementById('expenseFilePreviewFallback');
            const filePreviewDownload = document.getElementById('expenseFilePreviewDownload');
            const resetFilePreview = () => {
                [filePreviewImage, filePreviewPdf, filePreviewFallback].forEach((element) => element?.classList.add('d-none'));
                if (filePreviewImage) filePreviewImage.removeAttribute('src');
                if (filePreviewPdf) filePreviewPdf.removeAttribute('src');
            };

            document.querySelectorAll('.js-expense-file-preview').forEach((button) => {
                button.addEventListener('click', () => {
                    const url = button.dataset.fileUrl || '';
                    const name = button.dataset.fileName || 'Archivo';
                    const mime = button.dataset.fileMime || '';
                    if (!url || !filePreviewModal) return;

                    resetFilePreview();
                    if (filePreviewTitle) filePreviewTitle.textContent = name;
                    if (filePreviewDownload) {
                        filePreviewDownload.href = button.dataset.fileDownload || url;
                        filePreviewDownload.setAttribute('download', name);
                    }

                    if (mime.startsWith('image/') && filePreviewImage) {
                        filePreviewImage.src = url;
                        filePreviewImage.alt = name;
                        filePreviewImage.classList.remove('d-none');
                    } else if (mime === 'application/pdf' && filePreviewPdf) {
                        filePreviewPdf.src = url;
                        filePreviewPdf.classList.remove('d-none');
                    } else {
                        filePreviewFallback?.classList.remove('d-none');
                    }

                    filePreviewModal.show();
                });
            });
            filePreviewModalEl?.addEventListener('hidden.bs.modal', resetFilePreview);

            const modalEl = document.getElementById('markPaidExpenseModal');
            if (!modalEl) return;

            const form = document.getElementById('markPaidExpenseForm');
            const conceptEl = document.getElementById('markPaidExpenseConcept');
            const amountEl = document.getElementById('markPaidExpenseAmount');
            const dueDateEl = document.getElementById('markPaidExpenseDueDate');
            const attachToggle = document.getElementById('markPaidAttachToggle');
            const receiptWrap = document.getElementById('markPaidReceiptWrap');
            const receiptInput = document.getElementById('markPaidReceipt');
            const submitBtn = document.getElementById('markPaidSubmitBtn');
            const csrfToken = @json(csrf_token());
            const propertyContext = @json($property->uuid);
            let payload = null;

            document.querySelectorAll('.js-recurring-item-form').forEach((recurringForm) => {
                const frequencySelect = recurringForm.querySelector('.js-recurring-frequency');
                const countInput = recurringForm.querySelector('.js-recurring-count');

                if (!frequencySelect || !countInput) {
                    return;
                }

                const syncOccurrencesCount = (resetValue = false) => {
                    const frequency = frequencySelect.value;
                    const isSinglePayment = frequency === 'once';

                    if (isSinglePayment) {
                        countInput.value = '1';
                        countInput.disabled = true;
                        return;
                    }

                    countInput.disabled = false;
                    if (resetValue) {
                        countInput.value = frequency === 'monthly' ? '12' : '1';
                    }
                };

                frequencySelect.addEventListener('change', () => syncOccurrencesCount(true));
                syncOccurrencesCount(false);
            });

            document.querySelectorAll('.js-expense-delete-form').forEach((deleteForm) => {
                deleteForm.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const title = deleteForm.dataset.confirmTitle || 'Eliminar gasto';
                    const message = deleteForm.dataset.confirmMessage || '¿Deseas eliminar este registro?';
                    const confirmButtonText = deleteForm.dataset.confirmButton || 'Sí, eliminar';
                    let confirmed = false;

                    if (window.Swal?.fire) {
                        const result = await window.Swal.fire({
                            title,
                            text: message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText,
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

            document.querySelectorAll('.js-mark-paid-btn').forEach((button) => {
                button.addEventListener('click', () => {
                    try {
                        payload = JSON.parse(button.dataset.expense || '{}');
                    } catch (error) {
                        payload = {};
                    }

                    form.setAttribute('action', payload.mark_action || '');
                    conceptEl.textContent = payload.concept || '-';
                    amountEl.textContent = payload.amount ? `$${Number(payload.amount).toFixed(2)}` : '-';
                    dueDateEl.textContent = payload.due_date_label || '-';
                    attachToggle.checked = false;
                    receiptWrap.classList.add('d-none');
                    receiptInput.value = '';
                });
            });

            attachToggle.addEventListener('change', () => {
                receiptWrap.classList.toggle('d-none', !attachToggle.checked);
            });

            form.addEventListener('submit', async (event) => {
                if (!attachToggle.checked) {
                    return;
                }

                const file = receiptInput.files && receiptInput.files[0] ? receiptInput.files[0] : null;
                if (!file) {
                    event.preventDefault();
                    alert('Selecciona un comprobante para adjuntar o desactiva la opción de adjuntar.');
                    return;
                }

                event.preventDefault();
                submitBtn.disabled = true;
                submitBtn.textContent = 'Guardando...';

                try {
                    const formData = new FormData();
                    formData.append('_token', csrfToken);
                    formData.append('_method', 'PUT');
                    formData.append('property_context', propertyContext);
                    formData.append('concept', payload?.concept || '');
                    formData.append('amount', payload?.amount || '0.01');
                    formData.append('due_date', payload?.due_date || new Date().toISOString().slice(0, 10));
                    formData.append('description', payload?.description || '');
                    formData.append('files[]', file);

                    const response = await fetch(payload?.update_action || '', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                        body: formData,
                    });

                    if (!response.ok) {
                        throw new Error('No fue posible adjuntar el comprobante.');
                    }

                    attachToggle.checked = false;
                    receiptWrap.classList.add('d-none');
                    receiptInput.value = '';
                    form.submit();
                } catch (error) {
                    alert(error.message || 'No fue posible adjuntar el comprobante.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Confirmar pago';
                }
            });

            @if ($errors->expensePropertySetup->any())
                const setupModal = document.getElementById('expenseSetupModal');
                if (setupModal) {
                    new bootstrap.Modal(setupModal).show();
                }
            @endif

            @if ($errors->createExpense->any())
                const createModal = document.getElementById('createExpenseModalProperty');
                if (createModal) {
                    new bootstrap.Modal(createModal).show();
                }
            @endif

            @if ($errors->recurringExpenseItem->any())
                const recurringItemModal = document.getElementById('createRecurringExpenseItemModal');
                if (recurringItemModal) {
                    new bootstrap.Modal(recurringItemModal).show();
                }
            @endif
        })();
    </script>
@endpush
