    <div class="modal fade" id="editChargeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" id="editChargeForm" class="h-100 d-flex flex-column">
                    @csrf
                    @method('PUT')
                    @if ($editChargeReturnTo) <input type="hidden" name="return_to" value="{{ $editChargeReturnTo }}"> @endif
                    @if ($editChargeProperty)
                        <input type="hidden" name="property_context" value="{{ $editChargeProperty->uuid }}">
                    @endif
                    <div class="modal-header">
                        <h3 class="modal-title">Editar cargo</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->updateCharge->any())
                            <div class="alert alert-danger mb-5">
                                <ul class="mb-0">@foreach ($errors->updateCharge->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                            </div>
                        @endif

                        <input type="hidden" name="charge_uuid" id="editChargeUuid" value="{{ old('charge_uuid') }}">

                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label required">Tipo</label>
                                <select name="type" id="editChargeType" class="form-select" required>
                                    @foreach ($typeOptions as $typeValue => $typeLabel)
                                        <option value="{{ $typeValue }}" {{ old('type') === $typeValue ? 'selected' : '' }}>
                                            {{ $typeLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Fecha vencimiento</label>
                                <input type="date" name="due_date" id="editChargeDueDate" class="form-control"
                                    value="{{ old('due_date') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Monto ({{ strtoupper($editChargeCurrency) }})</label>
                                <input type="number" min="0.01" step="0.01" name="amount" id="editChargeAmount"
                                    class="form-control" value="{{ old('amount') }}" required>
                            </div>
                            <div class="col-12" id="applyFollowingRentsContainer">
                                <label class="form-check form-check-custom form-check-solid">
                                    <input type="hidden" name="apply_following_rents" value="0">
                                    <input type="checkbox" name="apply_following_rents" value="1" class="form-check-input" id="applyFollowingRents" @checked(old('apply_following_rents', $editChargeApplyFollowingDefault ? '1' : '0') == '1')>
                                    <span class="form-check-label">Aplicar el nuevo monto a las rentas posteriores del mismo año</span>
                                </label>
                                <div class="form-text">Se aplica al cambiar el monto, solo para esta propiedad y el mismo inquilino. Se omiten cargos con pagos o comprobantes y los del siguiente año.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label required">Mes (periodo)</label>
                                <input type="number" min="1" max="12" name="period_month" id="editChargePeriodMonth"
                                    class="form-control" value="{{ old('period_month') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label required">Anio (periodo)</label>
                                <input type="number" min="2000" max="2200" name="period_year" id="editChargePeriodYear"
                                    class="form-control" value="{{ old('period_year') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label required">Concepto</label>
                                <input type="text" name="concept" id="editChargeConcept" class="form-control"
                                    value="{{ old('concept') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notas</label>
                                <textarea name="notes" id="editChargeNotes" class="form-control"
                                    rows="3">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

