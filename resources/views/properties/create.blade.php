@extends('layouts.app')

@section('title', (($isEdit ?? false) ? 'Editar Propiedad' : 'Nueva Propiedad') . ' | SuWork')

@section('content')
    @php
        $isEdit = $isEdit ?? false;
        $property = $property ?? null;

        $steps = [
            1 => 'Datos de la propiedad',
            2 => 'Datos adicionales',
            3 => 'Propietarios',
            4 => 'Documentos',
            5 => 'Estado inicial',
        ];

        $statusDescriptions = [
            \App\Models\Property::STATUS_AVAILABLE => 'La propiedad está lista para ser rentada.',
            \App\Models\Property::STATUS_IN_PROCESS => 'La propiedad está en preparación o trámite.',
            \App\Models\Property::STATUS_BLOCKED => 'La propiedad no está disponible temporalmente.',
            \App\Models\Property::STATUS_OCCUPIED => 'La propiedad tiene inquilino activo.',
        ];

        $ownerDefaults = [
            'name' => '',
            'phone' => '',
            'email' => '',
            'rfc' => '',
            'curp' => '',
            'owner_type' => \App\Models\Owner::OWNER_INDIVIDUAL,
            'bank_name' => '',
            'clabe' => '',
            'account_holder' => '',
            'payment_method' => \App\Models\Owner::PAYMENT_METHOD_TRANSFER,
            'address' => '',
            'notes' => '',
        ];

        $selectedOwnerIds = collect(old('owner_ids', $isEdit && $property ? $property->owners->pluck('id')->all() : []))
            ->map(fn($ownerId) => (int) $ownerId)
            ->all();

        $oldNewOwners = old('new_owners', []);

        $fieldValue = function (string $key, mixed $default = '') use ($isEdit, $property) {
            return old($key, $isEdit && $property ? data_get($property, $key, $default) : $default);
        };

        $selectedStatus = old(
            'status',
            $isEdit && $property ? $property->status : \App\Models\Property::STATUS_AVAILABLE,
        );

        $existingDocuments = $isEdit && $property ? $property->documents->keyBy('document_type') : collect();
        $customPropertyDocuments = $customPropertyDocuments ?? collect();
        $existingCustomDocuments = old(
            'existing_custom_documents',
            $isEdit
                ? $customPropertyDocuments
                    ->mapWithKeys(fn($document) => [
                        $document->document_type => [
                            'label' => $document->label,
                            'expires_at' => $document->expires_at?->format('Y-m-d'),
                        ],
                    ])
                    ->all()
                : [],
        );
        $newCustomDocuments = old('new_custom_documents', []);
        $existingFacadePhoto = $isEdit && $property ? $property->facade_photo_path : null;
        $selectedType = (string) $fieldValue('property_type_id');
        $selectedZone = (string) $fieldValue('zone_id');
        $selectedAdvisorId = (string) old('advisor_user_id', $isEdit && $property ? ($property->advisor_user_id ?: '') : (auth()->id() ?: ''));
        $selectedTenantId = (string) old('tenant_id', $isEdit && $property ? ($property->tenant_id ?: '') : '');
        $initialRentChargePlan = old('rent_charge_plan', $isEdit && $property ? ($property->rent_charge_plan ?? []) : []);
        $initialRentChargePlan = collect($initialRentChargePlan)
            ->filter(fn($row) => is_array($row))
            ->values()
            ->all();

        $initialStep = (int) old('wizard_step', 1);
        if ($errors->isNotEmpty()) {
            $initialStep = 1;
            foreach ($errors->keys() as $errorKey) {
                if (str_starts_with($errorKey, 'details') || str_starts_with($errorKey, 'description') || str_starts_with($errorKey, 'rental_requirements') || str_starts_with($errorKey, 'amenities')) {
                    $initialStep = 2;
                    break;
                }
                if (str_starts_with($errorKey, 'owner_ids') || str_starts_with($errorKey, 'new_owners.')) {
                    $initialStep = 3;
                    break;
                }
                if (str_starts_with($errorKey, 'documents.')) {
                    $initialStep = 4;
                    break;
                }
                if (str_starts_with($errorKey, 'existing_custom_documents.') || str_starts_with($errorKey, 'new_custom_documents.')) {
                    $initialStep = 4;
                    break;
                }
                if ($errorKey === 'status') {
                    $initialStep = 5;
                    break;
                }
                if ($errorKey === 'tenant_id') {
                    $initialStep = 5;
                    break;
                }
                if ($errorKey === 'advisor_user_id') {
                    $initialStep = 1;
                    break;
                }
                if ($errorKey === 'contract_starts_at' || $errorKey === 'contract_expires_at') {
                    $initialStep = 5;
                    break;
                }
                if ($errorKey === 'rent_charge_plan' || str_starts_with($errorKey, 'rent_charge_plan.')) {
                    $initialStep = 5;
                    break;
                }
            }
        }
    @endphp

    <div class="py-10 property-module">
        <div class="mb-8">
            <a href="{{ route('properties.index') }}" class="text-gray-600 text-hover-primary fw-semibold">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Volver al listado
            </a>
        </div>

        <div class="mb-9">
            <h1 class="mb-1 fw-bold">{{ $isEdit ? 'Editar Propiedad' : 'Nueva Propiedad' }}</h1>
            <p class="text-muted mb-0">
                {{ $isEdit ? 'Actualiza la información de la propiedad en los siguientes pasos.' : 'Completa los siguientes pasos para registrar una nueva propiedad.' }}
            </p>
        </div>

        <form id="property-wizard-form" method="POST"
            action="{{ $isEdit ? route('properties.update', $property) : route('properties.store') }}"
            enctype="multipart/form-data">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif
            <input type="hidden" name="wizard_step" id="wizard-step-input" value="{{ $initialStep }}">

            @if ($errors->any())
                <div class="alert alert-danger mb-8">
                    <div class="fw-bold mb-2">Hay errores en el formulario:</div>
                    <ul class="mb-0 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="property-stepper mb-10" id="property-stepper" style="--property-step-count: {{ count($steps) }}">
                @foreach ($steps as $stepNumber => $stepLabel)
                    <div class="step-item" data-step="{{ $stepNumber }}">
                        <div class="step-circle">{{ $stepNumber }}</div>
                        <div class="step-label">{{ $stepLabel }}</div>
                    </div>
                @endforeach
            </div>

            {{-- STEP 1 --}}
            <div class="card mb-8 wizard-step" data-step-panel="1">
                <div class="card-body p-lg-10">
                    <h3 class="mb-6 fw-bold">Datos de la propiedad</h3>

                    <div class="notice d-flex bg-light-warning rounded border border-warning border-dashed p-4 mb-8">
                        <div class="d-flex flex-column text-warning">
                            <span class="fw-bold">Estado: {{ \App\Models\Property::STATUS_LABELS[$selectedStatus] ?? 'Borrador' }}</span>
                        </div>
                    </div>

                    <div class="row g-6">
                        <div class="col-lg-6">
                            <label class="form-label required">Nombre interno de la propiedad</label>
                            <input type="text" name="internal_name" class="form-control @error('internal_name') is-invalid @enderror"
                                value="{{ $fieldValue('internal_name') }}" placeholder="Ej: Casa Montebello 101">
                            @error('internal_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Referencia interna o alias</label>
                            <input type="text" name="internal_reference"
                                class="form-control @error('internal_reference') is-invalid @enderror"
                                value="{{ $fieldValue('internal_reference') }}" placeholder="Ej: MB-101">
                            @error('internal_reference')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label required">Tipo de propiedad</label>
                            <select name="property_type_id" required
                                class="form-select @error('property_type_id') is-invalid @enderror">
                                <option value="" disabled {{ $selectedType ? '' : 'selected' }}>Seleccionar tipo</option>
                                @foreach ($propertyTypes as $type)
                                    <option value="{{ $type->id }}"
                                        {{ $selectedType === (string) $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('property_type_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label required">Zona</label>
                            <input type="text" name="zone_text" class="form-control @error('zone_text') is-invalid @enderror"
                                value="{{ $fieldValue('zone_text') }}" placeholder="Ej: Montebello, Temozon, etc.">
                            @error('zone_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Puedes seleccionar una zona existente o escribir una personalizada</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label required">Dirección completa</label>
                            <input type="text" name="full_address" class="form-control @error('full_address') is-invalid @enderror"
                                value="{{ $fieldValue('full_address') }}" placeholder="Calle, número, colonia, CP">
                            @error('full_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">URL del mapa (opcional)</label>
                            <input type="url" name="map_url" class="form-control @error('map_url') is-invalid @enderror"
                                value="{{ $fieldValue('map_url') }}" placeholder="https://maps.google.com/...">
                            @error('map_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label">Complejo o privada</label>
                            <input type="text" name="complex_name" class="form-control @error('complex_name') is-invalid @enderror"
                                value="{{ $fieldValue('complex_name') }}" placeholder="Nombre del complejo">
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Número Interior</label>
                            <input type="text" name="official_number"
                                class="form-control @error('official_number') is-invalid @enderror"
                                value="{{ $fieldValue('official_number') }}" placeholder="Número">
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Número Exterior</label>
                            <input type="text" name="unit_number" class="form-control @error('unit_number') is-invalid @enderror"
                                value="{{ $fieldValue('unit_number') }}" placeholder="Ej: A-302">
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label">Precio de renta mensual</label>
                            <input type="number" name="monthly_rent_price" class="form-control @error('monthly_rent_price') is-invalid @enderror"
                                value="{{ $fieldValue('monthly_rent_price') }}" placeholder="0.00" step="0.01">
                            @error('monthly_rent_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label required">Asesor responsable</label>
                            <select name="advisor_user_id" class="form-select @error('advisor_user_id') is-invalid @enderror">
                                <option value="">Selecciona un usuario</option>
                                @foreach ($availableAdvisors as $advisor)
                                    <option value="{{ $advisor->id }}" {{ $selectedAdvisorId === (string) $advisor->id ? 'selected' : '' }}>
                                        {{ $advisor->name }}{{ $advisor->email ? ' · ' . $advisor->email : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('advisor_user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label required">Foto de fachada de la propiedad</label>
                            <label class="upload-box upload-box-sm">
                                <input type="file" name="facade_photo" id="facade-photo-input"
                                    accept=".jpg,.jpeg,.png,.webp" class="js-drop-input">
                                <i class="ki-outline ki-cloud-add fs-2x text-muted mb-2"></i>
                                <span class="fw-semibold text-gray-700">Arrastra y suelta la imagen aqui</span>
                                <span class="text-muted fs-8">o haz clic para seleccionar</span>
                                <span class="text-muted fs-8 d-block">PNG, JPG, WEBP hasta 10MB</span>
                                <span class="file-selected-label text-success fs-8 d-none"></span>
                            </label>
                            @if ($existingFacadePhoto)
                                <div class="mt-3">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($existingFacadePhoto) }}" alt="Fachada actual"
                                        class="property-cover">
                                    <p class="text-muted fs-8 mt-2">Imagen actual. Sube una nueva para reemplazarla.</p>
                                </div>
                            @endif
                            @error('facade_photo')
                                <div class="text-danger fs-8 mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 2 --}}
            <div class="card mb-8 wizard-step d-none" data-step-panel="2">
                <div class="card-body p-lg-10">
                    <h3 class="mb-6 fw-bold">Datos adicionales</h3>

                    <div class="row g-6">
                        <div class="col-12">
                            <label class="form-label">Detalles</label>

                            <textarea name="details" id="details-editor" class="form-control" rows="4">
                                {!! $fieldValue('details', '
                                <table>
                                    <tbody>
                                        <tr><td><strong>ID:</strong></td><td>000</td></tr>
                                        <tr><td><strong>Clave interna:</strong></td><td>A-00</td></tr>
                                        <tr><td><strong>Terreno:</strong></td><td>0m²</td></tr>
                                        <tr><td><strong>Fondo del terreno:</strong></td><td>0 m</td></tr>
                                        <tr><td><strong>Frente del terreno:</strong></td><td>0 m</td></tr>
                                    </tbody>
                                </table>
                                ') !!}
                            </textarea>

                            @error('details')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror

                            <div class="form-text">Campo con formato enriquecido</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="description" id="description-editor" class="form-control" rows="4"
                                placeholder="Describe la propiedad para los anuncios...">{{ $fieldValue('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Campo con formato enriquecido para la descripción pública</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Requisitos de renta</label>

                            <textarea name="rental_requirements" id="rental-requirements-editor" class="form-control" rows="6">
                                {!! $fieldValue('rental_requirements', '
                                <ul>
                                    <li>1 mes por adelantado para apartado</li>
                                    <li>Aval más un depósito o doble depósito</li>
                                    <li>Costo del convenio notariado mínimo 1 año</li>
                                </ul>
                                ') !!}
                            </textarea>

                            @error('rental_requirements')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror

                            <div class="form-text">Campo con formato enriquecido</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Amenidades</label>
                            <textarea name="amenities" id="amenities-editor" class="form-control" rows="4"
                                placeholder="Lista las amenidades de la propiedad...">{{ $fieldValue('amenities') }}</textarea>
                            @error('amenities')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Campo con formato enriquecido para las amenidades</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 3 --}}
            <div class="card mb-8 wizard-step d-none" data-step-panel="3">
                <div class="card-body p-lg-10">
                    <h3 class="mb-3 fw-bold">Propietarios</h3>
                    <p class="text-muted mb-8">Selecciona propietarios registrados o crea uno nuevo desde este paso.</p>

                    @error('owner_ids')
                        <div class="alert alert-danger mb-6">{{ $message }}</div>
                    @enderror

                    <div class="mb-6">
                        <label class="form-label">Buscar propietario</label>
                        <input type="text" id="owners-search-input" class="form-control"
                            placeholder="Buscar por nombre, telefono, email, RFC...">
                    </div>

                    <div id="owners-select-list" class="row g-5 mb-8">
                        @forelse ($availableOwners as $owner)
                            @php
                                $isChecked = in_array($owner->id, $selectedOwnerIds, true);
                                $searchText = strtolower(trim(($owner->name ?? '') . ' ' . ($owner->phone ?? '') . ' ' . ($owner->email ?? '') . ' ' . ($owner->rfc ?? '')));
                            @endphp
                            <div class="col-lg-6 owner-option-item" data-owner-search="{{ $searchText }}">
                                <label class="owner-option-card {{ $isChecked ? 'is-selected' : '' }}">
                                    <input type="checkbox" name="owner_ids[]" value="{{ $owner->id }}"
                                        class="form-check-input owner-option-checkbox" {{ $isChecked ? 'checked' : '' }}>
                                    <div class="owner-option-content">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold text-gray-900">{{ $owner->name }}</span>
                                            <span class="badge badge-light-info text-info">{{ $owner->owner_type_label }}</span>
                                        </div>
                                        <div class="text-muted fs-7 mb-1">{{ $owner->phone }} {{ $owner->email ? '| ' . $owner->email : '' }}</div>
                                        <div class="text-muted fs-8">Banco: {{ $owner->bank_name ?: '-' }} | CLABE: {{ $owner->clabe ?: '-' }}</div>
                                    </div>
                                </label>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-light-info mb-0">No hay propietarios registrados todavía.</div>
                            </div>
                        @endforelse
                    </div>

                    <div class="border rounded p-6">
                        <div class="d-flex justify-content-between align-items-center mb-5">
                            <h4 class="mb-0">Crear nuevo propietario desde aqui</h4>
                            <button type="button" id="add-inline-owner" class="btn btn-sm btn-light-primary">
                                <i class="ki-outline ki-plus fs-5 me-1"></i> Nuevo propietario
                            </button>
                        </div>

                        <div id="inline-new-owners" class="d-flex flex-column gap-5">
                            @foreach ($oldNewOwners as $newOwnerIndex => $newOwner)
                                <div class="new-owner-block border rounded p-5" data-new-owner-index="{{ $newOwnerIndex }}">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="mb-0">Nuevo propietario {{ $loop->iteration }}</h5>
                                        <button type="button" class="btn btn-sm btn-light-danger btn-remove-new-owner">
                                            Eliminar
                                        </button>
                                    </div>
                                    <div class="row g-4">
                                        <div class="col-lg-6">
                                            <label class="form-label required">Nombre completo</label>
                                            <input type="text" name="new_owners[{{ $newOwnerIndex }}][name]"
                                                class="form-control @error("new_owners.$newOwnerIndex.name") is-invalid @enderror"
                                                value="{{ $newOwner['name'] ?? '' }}">
                                            @error("new_owners.$newOwnerIndex.name")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label required">Telefono</label>
                                            <input type="text" name="new_owners[{{ $newOwnerIndex }}][phone]"
                                                class="form-control @error("new_owners.$newOwnerIndex.phone") is-invalid @enderror"
                                                value="{{ $newOwner['phone'] ?? '' }}">
                                            @error("new_owners.$newOwnerIndex.phone")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="new_owners[{{ $newOwnerIndex }}][email]"
                                                class="form-control @error("new_owners.$newOwnerIndex.email") is-invalid @enderror"
                                                value="{{ $newOwner['email'] ?? '' }}">
                                            @error("new_owners.$newOwnerIndex.email")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label">RFC</label>
                                            <input type="text" name="new_owners[{{ $newOwnerIndex }}][rfc]" class="form-control"
                                                value="{{ $newOwner['rfc'] ?? '' }}">
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label">CURP</label>
                                            <input type="text" name="new_owners[{{ $newOwnerIndex }}][curp]" class="form-control"
                                                value="{{ $newOwner['curp'] ?? '' }}">
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label">Banco</label>
                                            <input type="text" name="new_owners[{{ $newOwnerIndex }}][bank_name]" class="form-control"
                                                value="{{ $newOwner['bank_name'] ?? '' }}">
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label">CLABE (18 digitos)</label>
                                            <input type="text" name="new_owners[{{ $newOwnerIndex }}][clabe]"
                                                class="form-control @error("new_owners.$newOwnerIndex.clabe") is-invalid @enderror"
                                                value="{{ $newOwner['clabe'] ?? '' }}">
                                            @error("new_owners.$newOwnerIndex.clabe")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-lg-6">
                                            <label class="form-label">Titular de la cuenta</label>
                                            <input type="text" name="new_owners[{{ $newOwnerIndex }}][account_holder]" class="form-control"
                                                value="{{ $newOwner['account_holder'] ?? '' }}">
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label">Tipo de titular</label>
                                            <select name="new_owners[{{ $newOwnerIndex }}][owner_type]" class="form-select">
                                                @foreach ($ownerTypes as $typeValue => $typeLabel)
                                                    <option value="{{ $typeValue }}"
                                                        {{ ($newOwner['owner_type'] ?? \App\Models\Owner::OWNER_INDIVIDUAL) === $typeValue ? 'selected' : '' }}>
                                                        {{ $typeLabel }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label">Metodo de pago</label>
                                            <select name="new_owners[{{ $newOwnerIndex }}][payment_method]" class="form-select">
                                                @foreach ($paymentMethods as $methodValue => $methodLabel)
                                                    <option value="{{ $methodValue }}"
                                                        {{ ($newOwner['payment_method'] ?? \App\Models\Owner::PAYMENT_METHOD_TRANSFER) === $methodValue ? 'selected' : '' }}>
                                                        {{ $methodLabel }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Domicilio</label>
                                            <textarea name="new_owners[{{ $newOwnerIndex }}][address]" rows="2" class="form-control">{{ $newOwner['address'] ?? '' }}</textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Notas</label>
                                            <textarea name="new_owners[{{ $newOwnerIndex }}][notes]" rows="2" class="form-control">{{ $newOwner['notes'] ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 4 --}}
            <div class="card mb-8 wizard-step d-none" data-step-panel="4">
                <div class="card-body p-lg-10">
                    <h3 class="mb-3 fw-bold">Documentos de la propiedad</h3>
                    <p class="text-muted mb-8">Sube los documentos obligatorios para completar el expediente de la propiedad.</p>

                    <div class="alert alert-light-danger border border-danger border-dashed d-flex justify-content-between align-items-center mb-8">
                        <div>
                            <div class="fw-bold text-danger">Faltan documentos</div>
                            <div class="text-danger fs-7">Puedes completar este paso más adelante desde el expediente.</div>
                        </div>
                        <i class="ki-outline ki-information-5 fs-2 text-danger"></i>
                    </div>

                    <div class="d-flex flex-column gap-6">
                        @foreach ($requiredDocuments as $documentKey => $documentLabel)
                            @php
                                $existingDocument = $existingDocuments->get($documentKey);
                            @endphp
                            <div class="border rounded p-6">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h4 class="mb-0">{{ $documentLabel }}</h4>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge {{ $existingDocument?->status_badge_class ?? 'badge-light-secondary text-secondary' }}">
                                            {{ $existingDocument?->status_label ?? 'Pendiente' }}
                                        </span>
                                        @if ($existingDocument)
                                            <span class="badge badge-light-info text-info">v{{ $existingDocument->versions->count() }}</span>
                                        @endif
                                    </div>
                                </div>
                                <label class="upload-box upload-box-sm">
                                    <input type="file" name="documents[{{ $documentKey }}]" accept=".pdf,.jpg,.jpeg,.png" class="js-drop-input">
                                    <i class="ki-outline ki-cloud-add fs-2x text-muted mb-2"></i>
                                    <span class="fw-semibold text-gray-700">Haz clic para subir documento</span>
                                    <span class="text-muted fs-8">PDF, JPG, PNG hasta 10MB</span>
                                    <span class="file-selected-label text-success fs-8 d-none"></span>
                                </label>
                                @if ($existingDocument?->file_path)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($existingDocument->file_path) }}"
                                        target="_blank" class="btn btn-sm btn-light-primary mt-3">
                                        Ver archivo actual
                                    </a>
                                @endif
                                @error("documents.$documentKey")
                                    <div class="text-danger fs-8 mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        @endforeach
                    </div>

                    @if ($isEdit && $customPropertyDocuments->isNotEmpty())
                        <div class="separator my-10"></div>
                        <h4 class="mb-5 fw-bold">Otros documentos del expediente</h4>
                        <div class="d-flex flex-column gap-6 mb-6">
                            @foreach ($customPropertyDocuments as $customDocument)
                                @php
                                    $customOld = $existingCustomDocuments[$customDocument->document_type] ?? [];
                                    $customLabel = $customOld['label'] ?? $customDocument->label;
                                    $customExpiresAt = $customOld['expires_at'] ?? $customDocument->expires_at?->format('Y-m-d');
                                @endphp
                                <div class="border rounded p-6">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="mb-0">{{ $customDocument->label }}</h5>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge {{ $customDocument->status_badge_class }}">{{ $customDocument->status_label }}</span>
                                            <span class="badge badge-light-info text-info">v{{ $customDocument->versions->count() }}</span>
                                        </div>
                                    </div>
                                    <div class="row g-4 align-items-end">
                                        <div class="col-lg-5">
                                            <label class="form-label">Nombre del documento</label>
                                            <input type="text" name="existing_custom_documents[{{ $customDocument->document_type }}][label]"
                                                value="{{ $customLabel }}"
                                                class="form-control @error("existing_custom_documents.{$customDocument->document_type}.label") is-invalid @enderror">
                                            @error("existing_custom_documents.{$customDocument->document_type}.label")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-lg-4">
                                            <label class="form-label">Vence el (opcional)</label>
                                            <input type="date" name="existing_custom_documents[{{ $customDocument->document_type }}][expires_at]"
                                                value="{{ $customExpiresAt }}"
                                                class="form-control @error("existing_custom_documents.{$customDocument->document_type}.expires_at") is-invalid @enderror">
                                            @error("existing_custom_documents.{$customDocument->document_type}.expires_at")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-lg-3">
                                            @if ($customDocument->file_path)
                                                <a href="{{ \Illuminate\Support\Facades\Storage::url($customDocument->file_path) }}" target="_blank"
                                                    class="btn btn-light-primary w-100">
                                                    Ver vigente
                                                </a>
                                            @endif
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Subir nueva version</label>
                                            <label class="upload-box upload-box-sm">
                                                <input type="file" name="existing_custom_documents[{{ $customDocument->document_type }}][file]"
                                                    accept=".pdf,.jpg,.jpeg,.png"
                                                    class="js-drop-input @error("existing_custom_documents.{$customDocument->document_type}.file") is-invalid @enderror">
                                                <i class="ki-outline ki-cloud-add fs-2x text-muted mb-2"></i>
                                                <span class="fw-semibold text-gray-700">Arrastra o selecciona un archivo</span>
                                                <span class="text-muted fs-8">PDF, JPG, PNG hasta 10MB</span>
                                                <span class="file-selected-label text-success fs-8 d-none"></span>
                                            </label>
                                            @error("existing_custom_documents.{$customDocument->document_type}.file")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="separator my-10"></div>
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <h4 class="mb-0 fw-bold">Agregar otros documentos</h4>
                        <button type="button" id="add-custom-document-btn" class="btn btn-sm btn-light-primary">
                            <i class="ki-outline ki-plus fs-5 me-1"></i> Agregar documento
                        </button>
                    </div>
                    <p class="text-muted mb-6">Estos documentos se agregan al expediente y tambien quedaran disponibles al editar.</p>
                    <div id="new-custom-documents-container" class="d-flex flex-column gap-5">
                        @foreach ($newCustomDocuments as $customIndex => $customDocumentData)
                            <div class="border rounded p-5 new-custom-document-row">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0">Documento adicional {{ $loop->iteration }}</h5>
                                    <button type="button" class="btn btn-sm btn-light-danger btn-remove-custom-document">
                                        Eliminar
                                    </button>
                                </div>
                                <div class="row g-4">
                                    <div class="col-lg-5">
                                        <label class="form-label required">Nombre del documento</label>
                                        <input type="text" name="new_custom_documents[{{ $customIndex }}][label]"
                                            value="{{ $customDocumentData['label'] ?? '' }}"
                                            class="form-control @error("new_custom_documents.$customIndex.label") is-invalid @enderror"
                                            placeholder="Ej: Convenio adicional">
                                        @error("new_custom_documents.$customIndex.label")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4">
                                        <label class="form-label">Vence el (opcional)</label>
                                        <input type="date" name="new_custom_documents[{{ $customIndex }}][expires_at]"
                                            value="{{ $customDocumentData['expires_at'] ?? '' }}"
                                            class="form-control @error("new_custom_documents.$customIndex.expires_at") is-invalid @enderror">
                                        @error("new_custom_documents.$customIndex.expires_at")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-lg-3">
                                        <label class="form-label required">Archivo</label>
                                        <label class="upload-box upload-box-sm">
                                            <input type="file" name="new_custom_documents[{{ $customIndex }}][file]"
                                                accept=".pdf,.jpg,.jpeg,.png"
                                                class="js-drop-input @error("new_custom_documents.$customIndex.file") is-invalid @enderror">
                                            <i class="ki-outline ki-cloud-add fs-2x text-muted mb-2"></i>
                                            <span class="fw-semibold text-gray-700">Arrastra o selecciona</span>
                                            <span class="text-muted fs-8">PDF, JPG, PNG hasta 10MB</span>
                                            <span class="file-selected-label text-success fs-8 d-none"></span>
                                        </label>
                                        @error("new_custom_documents.$customIndex.file")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- STEP 5 --}}
            <div class="card mb-8 wizard-step d-none" data-step-panel="5">
                <div class="card-body p-lg-10">
                    <h3 class="mb-3 fw-bold">Estado inicial de la propiedad</h3>
                    <p class="text-muted mb-8">Selecciona el estado inicial con el que se registrará esta propiedad en el sistema.</p>

                    <div class="d-flex flex-column gap-4 mb-8">
                        @foreach ($statusOptions as $statusValue => $statusLabel)
                            @php
                                $isSelected = $selectedStatus === $statusValue;
                            @endphp
                            <label class="status-option {{ $isSelected ? 'is-selected' : '' }}">
                                <input type="radio" name="status" value="{{ $statusValue }}"
                                    {{ $isSelected ? 'checked' : '' }}>
                                <div>
                                    <div class="fw-bold fs-4 mb-1">{{ $statusLabel }}</div>
                                    <div class="text-gray-700">{{ $statusDescriptions[$statusValue] ?? '' }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('status')
                        <div class="text-danger fs-7 mb-4">{{ $message }}</div>
                    @enderror

                    <div class="notice d-flex bg-light-primary border border-primary border-dashed rounded p-4 mb-6">
                        <span class="text-primary">Nota: Podrás cambiar el estado de la propiedad en cualquier momento desde
                            su expediente.</span>
                    </div>
                    <p class="text-muted mb-0">
                        La asignacion de inquilino, configuracion de contrato y generacion de pagos se administra desde el modulo de cobranza de la propiedad.
                    </p>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <button type="button" id="wizard-prev" class="btn btn-light">
                    <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Anterior
                </button>
                <div class="d-flex gap-3">
                    <button type="button" id="wizard-next" class="btn btn-primary">
                        Guardar y continuar <i class="ki-outline ki-arrow-right fs-4 ms-1"></i>
                    </button>
                    <button type="submit" id="wizard-submit" class="btn btn-success d-none">
                        <i class="ki-outline ki-check fs-4 me-1"></i> {{ $isEdit ? 'Actualizar propiedad' : 'Guardar propiedad' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
<template id="new-owner-template">
    <div class="new-owner-block border rounded p-5" data-new-owner-index="__INDEX__">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">Nuevo propietario __NUMBER__</h5>
            <button type="button" class="btn btn-sm btn-light-danger btn-remove-new-owner">Eliminar</button>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <label class="form-label required">Nombre completo</label>
                <input type="text" name="new_owners[__INDEX__][name]" class="form-control">
            </div>
            <div class="col-lg-3">
                <label class="form-label required">Telefono</label>
                <input type="text" name="new_owners[__INDEX__][phone]" class="form-control">
            </div>
            <div class="col-lg-3">
                <label class="form-label">Email</label>
                <input type="email" name="new_owners[__INDEX__][email]" class="form-control">
            </div>
            <div class="col-lg-3">
                <label class="form-label">RFC</label>
                <input type="text" name="new_owners[__INDEX__][rfc]" class="form-control">
            </div>
            <div class="col-lg-3">
                <label class="form-label">CURP</label>
                <input type="text" name="new_owners[__INDEX__][curp]" class="form-control">
            </div>
            <div class="col-lg-3">
                <label class="form-label">Banco</label>
                <input type="text" name="new_owners[__INDEX__][bank_name]" class="form-control">
            </div>
            <div class="col-lg-3">
                <label class="form-label">CLABE (18 digitos)</label>
                <input type="text" name="new_owners[__INDEX__][clabe]" class="form-control">
            </div>
            <div class="col-lg-6">
                <label class="form-label">Titular de la cuenta</label>
                <input type="text" name="new_owners[__INDEX__][account_holder]" class="form-control">
            </div>
            <div class="col-lg-3">
                <label class="form-label">Tipo de titular</label>
                <select name="new_owners[__INDEX__][owner_type]" class="form-select">
                    @foreach ($ownerTypes as $typeValue => $typeLabel)
                        <option value="{{ $typeValue }}"
                            {{ $typeValue === \App\Models\Owner::OWNER_INDIVIDUAL ? 'selected' : '' }}>
                            {{ $typeLabel }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Metodo de pago</label>
                <select name="new_owners[__INDEX__][payment_method]" class="form-select">
                    @foreach ($paymentMethods as $methodValue => $methodLabel)
                        <option value="{{ $methodValue }}"
                            {{ $methodValue === \App\Models\Owner::PAYMENT_METHOD_TRANSFER ? 'selected' : '' }}>
                            {{ $methodLabel }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Domicilio</label>
                <textarea name="new_owners[__INDEX__][address]" rows="2" class="form-control"></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Notas</label>
                <textarea name="new_owners[__INDEX__][notes]" rows="2" class="form-control"></textarea>
            </div>
        </div>
    </div>
</template>

<template id="new-custom-document-template">
    <div class="border rounded p-5 new-custom-document-row">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">Documento adicional __NUMBER__</h5>
            <button type="button" class="btn btn-sm btn-light-danger btn-remove-custom-document">Eliminar</button>
        </div>
        <div class="row g-4">
            <div class="col-lg-5">
                <label class="form-label required">Nombre del documento</label>
                <input type="text" name="new_custom_documents[__INDEX__][label]" class="form-control"
                    placeholder="Ej: Convenio adicional">
            </div>
            <div class="col-lg-4">
                <label class="form-label">Vence el (opcional)</label>
                <input type="date" name="new_custom_documents[__INDEX__][expires_at]" class="form-control">
            </div>
            <div class="col-lg-3">
                <label class="form-label required">Archivo</label>
                <label class="upload-box upload-box-sm">
                    <input type="file" name="new_custom_documents[__INDEX__][file]" accept=".pdf,.jpg,.jpeg,.png"
                        class="js-drop-input">
                    <i class="ki-outline ki-cloud-add fs-2x text-muted mb-2"></i>
                    <span class="fw-semibold text-gray-700">Arrastra o selecciona</span>
                    <span class="text-muted fs-8">PDF, JPG, PNG hasta 10MB</span>
                    <span class="file-selected-label text-success fs-8 d-none"></span>
                </label>
            </div>
        </div>
    </div>
</template>

@endsection

@push('scripts')
    <script>
        (() => {
            const totalSteps = 5;
            const stepper = document.getElementById('property-stepper');
            const panels = [...document.querySelectorAll('.wizard-step')];
            const prevBtn = document.getElementById('wizard-prev');
            const nextBtn = document.getElementById('wizard-next');
            const submitBtn = document.getElementById('wizard-submit');
            const stepInput = document.getElementById('wizard-step-input');
            const form = document.getElementById('property-wizard-form');
            const urlParams = new URLSearchParams(window.location.search);
            const stepFromUrl = parseInt(urlParams.get('step'));
            const editorInstances = [];
            const initialRentChargePlan = @json($initialRentChargePlan);
            const rentChargePlanInputsHost = document.getElementById('rent-charge-plan-inputs');
            const rentChargePlanTableBody = document.getElementById('rentChargePlanTableBody');
            const rentChargePlanSummary = document.getElementById('rentChargePlanSummary');
            const rentChargePlanRowsCount = document.getElementById('rentChargePlanRowsCount');
            const rentChargePlanEmptyState = document.getElementById('rentChargePlanEmptyState');
            const monthlyRentInput = form.querySelector('input[name="monthly_rent_price"]');
            const contractStartsInput = form.querySelector('input[name="contract_starts_at"]');
            const contractExpiresInput = form.querySelector('input[name="contract_expires_at"]');

            let currentStep = stepFromUrl || parseInt(stepInput.value || '1', 10);
            let rentChargePlanRows = [];

            if (Number.isNaN(currentStep) || currentStep < 1 || currentStep > totalSteps) {
                currentStep = 1;
            }

            const showToast = (type, message) => {
                if (window.toastr) {
                    toastr[type](message);
                    return;
                }

                if (type === 'error') {
                    alert(message);
                }
            };

            const confirmWithSwal = async ({
                title = 'Confirmar',
                text = 'Esta accion no se puede deshacer.',
                confirmButtonText = 'Si, eliminar',
                cancelButtonText = 'Cancelar',
                icon = 'warning',
            } = {}) => {
                if (window.Swal?.fire) {
                    const result = await window.Swal.fire({
                        title,
                        text,
                        icon,
                        showCancelButton: true,
                        confirmButtonText,
                        cancelButtonText,
                        reverseButtons: true,
                    });

                    return !!result.isConfirmed;
                }

                return window.confirm(text);
            };

            const determineStepFromErrorKey = (errorKey = '') => {
                if (
                    errorKey.startsWith('details') ||
                    errorKey.startsWith('description') ||
                    errorKey.startsWith('rental_requirements') ||
                    errorKey.startsWith('amenities')
                ) {
                    return 2;
                }

                if (errorKey.startsWith('owner_ids') || errorKey.startsWith('new_owners.')) {
                    return 3;
                }

                if (
                    errorKey.startsWith('documents.') ||
                    errorKey.startsWith('existing_custom_documents.') ||
                    errorKey.startsWith('new_custom_documents.')
                ) {
                    return 4;
                }

                if (
                    errorKey === 'status' ||
                    errorKey === 'tenant_id' ||
                    errorKey === 'contract_starts_at' ||
                    errorKey === 'contract_expires_at' ||
                    errorKey === 'rent_charge_plan' ||
                    errorKey.startsWith('rent_charge_plan.')
                ) {
                    return 5;
                }

                return 1;
            };

            const goToStep = (step) => {
                currentStep = Math.max(1, Math.min(totalSteps, step));
                renderWizard();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };

            const renderWizard = () => {
                const stepNodes = [...stepper.querySelectorAll('.step-item')];
                stepNodes.forEach((node) => {
                    const step = parseInt(node.dataset.step, 10);
                    node.classList.toggle('is-active', step === currentStep);
                    node.classList.toggle('is-completed', step < currentStep);
                });

                panels.forEach((panel) => {
                    panel.classList.toggle('d-none', parseInt(panel.dataset.stepPanel, 10) !== currentStep);
                });

                prevBtn.disabled = currentStep === 1;
                nextBtn.classList.toggle('d-none', currentStep === totalSteps);
                submitBtn.classList.toggle('d-none', currentStep !== totalSteps);
                stepInput.value = currentStep.toString();
            };

            // Add click event listeners to step circles
            stepper.querySelectorAll('.step-circle').forEach((circle) => {
                circle.addEventListener('click', () => {
                    const stepItem = circle.closest('.step-item');
                    const targetStep = parseInt(stepItem.dataset.step, 10);
                    if (targetStep >= 1 && targetStep <= totalSteps) {
                        goToStep(targetStep);
                    }
                });
            });

            prevBtn.addEventListener('click', () => {
                if (currentStep > 1) {
                    goToStep(currentStep - 1);
                }
            });

            nextBtn.addEventListener('click', () => {
                if (currentStep < totalSteps) {
                    goToStep(currentStep + 1);
                }
            });

            const clearAjaxErrors = () => {
                form.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
                form.querySelectorAll('[data-ajax-error="1"]').forEach((node) => node.remove());
            };

            const errorKeyToBracketName = (errorKey) => {
                const segments = errorKey.split('.');
                if (segments.length === 1) {
                    return errorKey;
                }

                return segments.reduce((name, segment, index) => {
                    if (index === 0) {
                        return segment;
                    }

                    return `${name}[${segment}]`;
                }, '');
            };

            const findFieldByErrorKey = (errorKey) => {
                const names = [errorKey, errorKeyToBracketName(errorKey)];
                const elements = [...form.elements];

                for (const element of elements) {
                    if (element.name && names.includes(element.name)) {
                        return element;
                    }
                }

                return null;
            };

            const appendInlineError = (field, message) => {
                const host = field.closest('.upload-box') || field;
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback d-block';
                feedback.dataset.ajaxError = '1';
                feedback.textContent = message;
                host.insertAdjacentElement('afterend', feedback);
            };

            const handleValidationErrors = (errors = {}) => {
                clearAjaxErrors();

                const keys = Object.keys(errors);
                if (!keys.length) {
                    showToast('error', 'No fue posible validar el formulario.');
                    return;
                }

                const firstKey = keys[0];
                goToStep(determineStepFromErrorKey(firstKey));

                let firstField = null;
                keys.forEach((key) => {
                    const field = findFieldByErrorKey(key);
                    const messages = Array.isArray(errors[key]) ? errors[key] : [];
                    if (!field) {
                        return;
                    }

                    if (!firstField) {
                        firstField = field;
                    }

                    field.classList.add('is-invalid');
                    messages.forEach((message) => appendInlineError(field, message));
                });

                const firstMessage = (errors[firstKey] || [])[0] || 'Hay errores en el formulario.';
                showToast('error', firstMessage);

                if (firstField) {
                    const target = firstField.closest('.upload-box') || firstField;
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            };

            const syncEditors = () => {
                editorInstances.forEach((editor) => {
                    if (typeof editor.updateSourceElement === 'function') {
                        editor.updateSourceElement();
                    }
                });
            };

            const setSubmittingState = (isSubmitting) => {
                submitBtn.disabled = isSubmitting;
                prevBtn.disabled = isSubmitting || currentStep === 1;
                nextBtn.disabled = isSubmitting;
                submitBtn.innerHTML = isSubmitting
                    ? '<i class="ki-outline ki-loading fs-4 me-1"></i> Guardando...'
                    : '<i class="ki-outline ki-check fs-4 me-1"></i> {{ $isEdit ? 'Actualizar propiedad' : 'Guardar propiedad' }}';
            };

            const initDropInputs = (root = document) => {
                const inputs = root.querySelectorAll('input[type="file"].js-drop-input:not([data-drop-ready])');
                inputs.forEach((input) => {
                    input.dataset.dropReady = '1';
                    const box = input.closest('.upload-box');
                    const isInventoryPhotoInput = input.name?.includes('inventory_areas[') && input.name?.includes('[photos][]');

                    let label = null;
                    if (box) {
                        label = box.querySelector('.file-selected-label');
                        if (!label) {
                            label = document.createElement('span');
                            label.className = 'file-selected-label text-success fs-8 d-none';
                            box.appendChild(label);
                        }
                    }

                    const ensurePreviewContainer = () => {
                        if (!isInventoryPhotoInput) {
                            return null;
                        }

                        let preview = input.parentElement?.querySelector('.inventory-selected-preview[data-preview-for="' + input.name + '"]');
                        if (!preview) {
                            preview = document.createElement('div');
                            preview.className = 'inventory-selected-preview d-flex flex-wrap gap-3 mt-3';
                            preview.dataset.previewFor = input.name || '';
                            input.insertAdjacentElement('afterend', preview);
                        }

                        return preview;
                    };

                    const renderSelected = () => {
                        if (label) {
                            if (!input.files || !input.files.length) {
                                label.textContent = '';
                                label.classList.add('d-none');
                            } else {
                                label.textContent = input.files.length === 1
                                    ? `Archivo seleccionado: ${input.files[0].name}`
                                    : `${input.files.length} archivos seleccionados`;
                                label.classList.remove('d-none');
                            }
                        }

                        const preview = ensurePreviewContainer();
                        if (!preview) {
                            return;
                        }

                        preview.innerHTML = '';
                        if (!input.files?.length) {
                            return;
                        }

                        [...input.files].forEach((file, index) => {
                            const item = document.createElement('div');
                            item.className = 'inventory-thumb-item position-relative';

                            const image = document.createElement('img');
                            image.className = 'inventory-thumb';
                            image.alt = file.name;

                            const removeBtn = document.createElement('button');
                            removeBtn.type = 'button';
                            removeBtn.className = 'btn btn-icon btn-danger btn-sm position-absolute top-0 end-0 m-1 js-remove-selected-photo';
                            removeBtn.dataset.fileIndex = index.toString();
                            removeBtn.dataset.targetInput = input.name || '';
                            removeBtn.title = 'Eliminar foto';
                            removeBtn.innerHTML = '<i class="ki-outline ki-trash fs-7"></i>';

                            const reader = new FileReader();
                            reader.onload = (e) => {
                                image.src = e.target?.result || '';
                            };
                            reader.readAsDataURL(file);

                            item.appendChild(image);
                            item.appendChild(removeBtn);
                            preview.appendChild(item);
                        });
                    };

                    input.addEventListener('change', renderSelected);

                    if (box) {
                        ['dragenter', 'dragover'].forEach((eventName) => {
                            box.addEventListener(eventName, (event) => {
                                event.preventDefault();
                                event.stopPropagation();
                                box.classList.add('is-dragover');
                            });
                        });

                        ['dragleave', 'drop'].forEach((eventName) => {
                            box.addEventListener(eventName, (event) => {
                                event.preventDefault();
                                event.stopPropagation();
                                box.classList.remove('is-dragover');
                            });
                        });

                        box.addEventListener('drop', (event) => {
                            if (!event.dataTransfer?.files?.length) {
                                return;
                            }

                            input.files = event.dataTransfer.files;
                            input.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                    }

                    renderSelected();
                });
            };

            const monthNames = [
                'Enero',
                'Febrero',
                'Marzo',
                'Abril',
                'Mayo',
                'Junio',
                'Julio',
                'Agosto',
                'Septiembre',
                'Octubre',
                'Noviembre',
                'Diciembre',
            ];

            const toMoney = (value, fallback = 0) => {
                const parsed = Number.parseFloat(String(value ?? '').replace(/,/g, ''));
                if (!Number.isFinite(parsed)) {
                    return fallback;
                }

                return Math.round(parsed * 100) / 100;
            };

            const parseIsoDate = (value) => {
                const stringValue = String(value || '').trim();
                const parts = stringValue.split('-');
                if (parts.length !== 3) {
                    return null;
                }

                const year = Number.parseInt(parts[0], 10);
                const month = Number.parseInt(parts[1], 10);
                const day = Number.parseInt(parts[2], 10);
                if (!year || month < 1 || month > 12 || day < 1 || day > 31) {
                    return null;
                }

                return { year, month, day };
            };

            const pad2 = (value) => String(value).padStart(2, '0');
            const periodKey = (year, month) => `${year}-${pad2(month)}`;

            const formatIsoDate = (year, month, day) => `${year}-${pad2(month)}-${pad2(day)}`;

            const resolveDueDateForPeriod = (candidate, year, month, fallbackDay) => {
                const parsedCandidate = parseIsoDate(candidate);
                if (parsedCandidate && parsedCandidate.year === year && parsedCandidate.month === month) {
                    return formatIsoDate(year, month, parsedCandidate.day);
                }

                const daysInMonth = new Date(year, month, 0).getDate();
                return formatIsoDate(year, month, Math.min(Math.max(1, fallbackDay), daysInMonth));
            };

            const buildConceptLabel = (periodMonth, periodYear) => {
                const monthLabel = monthNames[periodMonth - 1] || String(periodMonth);
                return `Renta ${monthLabel} ${periodYear}`;
            };

            const normalizeExistingPlanRows = (rows) => {
                if (!Array.isArray(rows)) {
                    return [];
                }

                return rows
                    .map((row) => {
                        const month = Number.parseInt(row?.period_month, 10);
                        const year = Number.parseInt(row?.period_year, 10);
                        if (!month || !year || month < 1 || month > 12) {
                            return null;
                        }

                        return {
                            period_month: month,
                            period_year: year,
                            due_date: String(row?.due_date || ''),
                            amount: toMoney(row?.amount, 0),
                            concept: String(row?.concept || '').trim(),
                            notes: row?.notes ? String(row.notes) : null,
                            is_custom_amount: Boolean(row?.is_custom_amount),
                        };
                    })
                    .filter(Boolean);
            };

            const buildAutoRentChargePlan = () => {
                const starts = parseIsoDate(contractStartsInput?.value);
                const expires = parseIsoDate(contractExpiresInput?.value);
                if (!starts || !expires) {
                    return [];
                }

                const startsDate = new Date(starts.year, starts.month - 1, 1);
                const expiresDate = new Date(expires.year, expires.month - 1, 1);
                if (startsDate > expiresDate) {
                    return [];
                }

                const defaultAmount = toMoney(monthlyRentInput?.value, 0);
                const baseContractDay = starts.day;
                const existingByPeriod = new Map(
                    rentChargePlanRows.map((row) => [periodKey(row.period_year, row.period_month), row]),
                );
                const builtRows = [];
                const cursor = new Date(startsDate.getFullYear(), startsDate.getMonth(), 1);

                while (cursor <= expiresDate) {
                    const year = cursor.getFullYear();
                    const month = cursor.getMonth() + 1;
                    const key = periodKey(year, month);
                    const current = existingByPeriod.get(key);
                    const customAmount = Boolean(current?.is_custom_amount);
                    const amount = customAmount
                        ? toMoney(current?.amount, defaultAmount)
                        : defaultAmount;
                    const dueDate = resolveDueDateForPeriod(current?.due_date, year, month, baseContractDay);
                    const concept = (current?.concept || '').trim() || buildConceptLabel(month, year);

                    if (amount > 0) {
                        builtRows.push({
                            period_month: month,
                            period_year: year,
                            due_date: dueDate,
                            amount,
                            concept,
                            notes: current?.notes || null,
                            is_custom_amount: customAmount,
                        });
                    }

                    cursor.setMonth(cursor.getMonth() + 1);
                }

                return builtRows;
            };

            const syncRentChargePlanInputs = () => {
                if (!rentChargePlanInputsHost) {
                    return;
                }

                rentChargePlanInputsHost.innerHTML = '';
                rentChargePlanRows.forEach((row, index) => {
                    const appendInput = (name, value) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `rent_charge_plan[${index}][${name}]`;
                        input.value = value;
                        rentChargePlanInputsHost.appendChild(input);
                    };

                    appendInput('period_month', row.period_month);
                    appendInput('period_year', row.period_year);
                    appendInput('due_date', row.due_date);
                    appendInput('amount', toMoney(row.amount, 0).toFixed(2));
                    appendInput('concept', row.concept || '');
                    appendInput('is_custom_amount', row.is_custom_amount ? '1' : '0');

                    if (row.notes) {
                        appendInput('notes', row.notes);
                    }
                });
            };

            const renderRentChargePlan = () => {
                if (!rentChargePlanTableBody) {
                    return;
                }

                rentChargePlanTableBody.innerHTML = '';
                if (!rentChargePlanRows.length) {
                    if (rentChargePlanEmptyState) {
                        rentChargePlanTableBody.appendChild(rentChargePlanEmptyState);
                    } else {
                        rentChargePlanTableBody.innerHTML = `
                            <tr>
                                <td colspan="4" class="text-center text-muted py-8">No hay pagos configurados.</td>
                            </tr>
                        `;
                    }
                } else {
                    rentChargePlanRows.forEach((row, index) => {
                        const tr = document.createElement('tr');

                        const periodCell = document.createElement('td');
                        periodCell.textContent = `${pad2(row.period_month)}/${row.period_year}`;
                        tr.appendChild(periodCell);

                        const dueDateCell = document.createElement('td');
                        const dueDateInput = document.createElement('input');
                        dueDateInput.type = 'date';
                        dueDateInput.className = 'form-control form-control-sm';
                        dueDateInput.value = row.due_date || '';
                        dueDateInput.dataset.planField = 'due_date';
                        dueDateInput.dataset.planIndex = String(index);
                        dueDateCell.appendChild(dueDateInput);
                        tr.appendChild(dueDateCell);

                        const amountCell = document.createElement('td');
                        const amountInput = document.createElement('input');
                        amountInput.type = 'number';
                        amountInput.min = '0.01';
                        amountInput.step = '0.01';
                        amountInput.className = 'form-control form-control-sm';
                        amountInput.value = toMoney(row.amount, 0).toFixed(2);
                        amountInput.dataset.planField = 'amount';
                        amountInput.dataset.planIndex = String(index);
                        amountCell.appendChild(amountInput);
                        tr.appendChild(amountCell);

                        const conceptCell = document.createElement('td');
                        const conceptInput = document.createElement('input');
                        conceptInput.type = 'text';
                        conceptInput.className = 'form-control form-control-sm';
                        conceptInput.maxLength = 190;
                        conceptInput.value = row.concept || '';
                        conceptInput.dataset.planField = 'concept';
                        conceptInput.dataset.planIndex = String(index);
                        conceptCell.appendChild(conceptInput);
                        tr.appendChild(conceptCell);

                        rentChargePlanTableBody.appendChild(tr);
                    });
                }

                const total = rentChargePlanRows.reduce((sum, row) => sum + toMoney(row.amount, 0), 0);
                if (rentChargePlanSummary) {
                    if (rentChargePlanRows.length) {
                        rentChargePlanSummary.textContent = `Total proyectado: $${total.toFixed(2)} en ${rentChargePlanRows.length} cargos.`;
                    } else {
                        rentChargePlanSummary.textContent = 'Configura contrato y renta mensual para generar la lista automatica.';
                    }
                }
                if (rentChargePlanRowsCount) {
                    rentChargePlanRowsCount.textContent = String(rentChargePlanRows.length);
                }
            };

            const rebuildRentChargePlan = () => {
                rentChargePlanRows = buildAutoRentChargePlan();
                renderRentChargePlan();
                syncRentChargePlanInputs();
            };

            rentChargePlanTableBody?.addEventListener('change', (event) => {
                const target = event.target.closest('[data-plan-field]');
                if (!target) {
                    return;
                }

                const index = Number.parseInt(target.dataset.planIndex || '-1', 10);
                if (!Number.isInteger(index) || !rentChargePlanRows[index]) {
                    return;
                }

                const row = rentChargePlanRows[index];
                const field = target.dataset.planField;
                if (field === 'amount') {
                    row.amount = toMoney(target.value, row.amount);
                    row.is_custom_amount = true;
                } else if (field === 'due_date') {
                    row.due_date = String(target.value || '').trim();
                } else if (field === 'concept') {
                    row.concept = String(target.value || '').trim();
                }

                syncRentChargePlanInputs();
                renderRentChargePlan();
            });

            monthlyRentInput?.addEventListener('input', rebuildRentChargePlan);
            contractStartsInput?.addEventListener('change', rebuildRentChargePlan);
            contractExpiresInput?.addEventListener('change', rebuildRentChargePlan);

            rentChargePlanRows = normalizeExistingPlanRows(initialRentChargePlan);
            rebuildRentChargePlan();

            const ownersSearchInput = document.getElementById('owners-search-input');
            const ownerOptionItems = [...document.querySelectorAll('.owner-option-item')];

            ownersSearchInput?.addEventListener('input', () => {
                const searchTerm = ownersSearchInput.value.trim().toLowerCase();
                ownerOptionItems.forEach((item) => {
                    const haystack = item.dataset.ownerSearch || '';
                    item.classList.toggle('d-none', searchTerm && !haystack.includes(searchTerm));
                });
            });

            document.querySelectorAll('.owner-option-checkbox').forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    const card = checkbox.closest('.owner-option-card');
                    card?.classList.toggle('is-selected', checkbox.checked);
                });
            });

            const inlineNewOwnersContainer = document.getElementById('inline-new-owners');
            const addInlineOwnerBtn = document.getElementById('add-inline-owner');
            const newOwnerTemplate = document.getElementById('new-owner-template').innerHTML;
            let inlineOwnerIndex = inlineNewOwnersContainer?.querySelectorAll('.new-owner-block').length || 0;

            const refreshInlineOwners = () => {
                const blocks = inlineNewOwnersContainer?.querySelectorAll('.new-owner-block') || [];
                blocks.forEach((block, index) => {
                    const title = block.querySelector('h5');
                    if (title) {
                        title.textContent = `Nuevo propietario ${index + 1}`;
                    }
                });
            };

            addInlineOwnerBtn?.addEventListener('click', () => {
                const html = newOwnerTemplate
                    .replaceAll('__INDEX__', inlineOwnerIndex.toString())
                    .replaceAll('__NUMBER__', (inlineOwnerIndex + 1).toString());
                inlineNewOwnersContainer?.insertAdjacentHTML('beforeend', html);
                inlineOwnerIndex++;
                refreshInlineOwners();
            });

            inlineNewOwnersContainer?.addEventListener('click', (event) => {
                const removeButton = event.target.closest('.btn-remove-new-owner');
                if (!removeButton) {
                    return;
                }
                removeButton.closest('.new-owner-block')?.remove();
                refreshInlineOwners();
            });

            const customDocumentsContainer = document.getElementById('new-custom-documents-container');
            const addCustomDocumentBtn = document.getElementById('add-custom-document-btn');
            const customDocumentTemplate = document.getElementById('new-custom-document-template')?.innerHTML || '';
            let customDocumentIndex = customDocumentsContainer?.querySelectorAll('.new-custom-document-row').length || 0;

            const refreshCustomDocumentTitles = () => {
                const rows = customDocumentsContainer?.querySelectorAll('.new-custom-document-row') || [];
                rows.forEach((row, index) => {
                    const title = row.querySelector('h5');
                    if (title) {
                        title.textContent = `Documento adicional ${index + 1}`;
                    }
                });
            };

            addCustomDocumentBtn?.addEventListener('click', () => {
                const html = customDocumentTemplate
                    .replaceAll('__INDEX__', customDocumentIndex.toString())
                    .replaceAll('__NUMBER__', (customDocumentIndex + 1).toString());

                customDocumentsContainer?.insertAdjacentHTML('beforeend', html);
                customDocumentIndex++;
                refreshCustomDocumentTitles();
                initDropInputs(customDocumentsContainer);
            });

            customDocumentsContainer?.addEventListener('click', (event) => {
                const removeBtn = event.target.closest('.btn-remove-custom-document');
                if (!removeBtn) {
                    return;
                }

                removeBtn.closest('.new-custom-document-row')?.remove();
                refreshCustomDocumentTitles();
            });

            document.querySelectorAll('input[name="status"]').forEach((radio) => {
                radio.addEventListener('change', () => {
                    document.querySelectorAll('.status-option').forEach((node) => node.classList.remove('is-selected'));
                    radio.closest('.status-option')?.classList.add('is-selected');
                });
            });

            refreshInlineOwners();
            refreshCustomDocumentTitles();
            renderWizard();
            initDropInputs();

            // Initialize rich text editors
            if (typeof ClassicEditor !== 'undefined') {
                const editors = ['#details-editor', '#description-editor', '#rental-requirements-editor', '#amenities-editor'];
                editors.forEach(selector => {
                    const element = document.querySelector(selector);
                    if (element) {
                        ClassicEditor
                            .create(element, {
                                toolbar: ['bold', 'italic', 'underline', '|', 'bulletedList', 'numberedList', '|', 'link', '|', 'undo', 'redo']
                            })
                            .then((editor) => {
                                editorInstances.push(editor);
                            })
                            .catch(error => {
                                console.error(error);
                            });
                    }
                });
            }

            if (false) {
                const facadeDropzone = new Dropzone('#facade-photo-dropzone', {
                    url: '{{ route("properties.store") }}', // This will be overridden by form submission
                    autoProcessQueue: false,
                    maxFiles: 1,
                    acceptedFiles: '.jpg,.jpeg,.png,.webp',
                    maxFilesize: 10, // MB
                    addRemoveLinks: true,
                    dictDefaultMessage: 'Arrastra y suelta la imagen aquí o haz clic para seleccionar',
                    dictRemoveFile: 'Remover archivo',
                    dictFileTooBig: 'El archivo es demasiado grande (@{{filesize}}MB). Tamano maximo: @{{maxFilesize}}MB.',
                    dictInvalidFileType: 'Tipo de archivo no valido. Solo se permiten imagenes.',
                    init: function() {
                        this.on('addedfile', function(file) {
                            // Create a hidden input to store the file
                            const input = document.createElement('input');
                            input.type = 'file';
                            input.name = 'facade_photo';
                            input.style.display = 'none';
                            const dt = new DataTransfer();
                            dt.items.add(file);
                            input.files = dt.files;
                            document.getElementById('property-wizard-form').appendChild(input);
                        });

                        this.on('removedfile', function(file) {
                            // Remove the hidden input
                            const inputs = document.querySelectorAll('input[name="facade_photo"]');
                            inputs.forEach(input => input.remove());
                        });
                    }
                });
            }

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                syncRentChargePlanInputs();
                syncEditors();
                clearAjaxErrors();
                setSubmittingState(true);

                try {
                    const formData = new FormData(form);
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    const payload = await response.json().catch(() => ({}));
                    if (response.status === 422) {
                        setSubmittingState(false);
                        handleValidationErrors(payload.errors || {});
                        return;
                    }

                    if (!response.ok) {
                        throw new Error(payload.message || 'No fue posible guardar la propiedad.');
                    }

                    showToast('success', payload.message || 'Propiedad guardada correctamente.');
                    if (payload.redirect) {
                        window.location.href = payload.redirect;
                        return;
                    }

                    window.location.reload();
                } catch (error) {
                    setSubmittingState(false);
                    showToast('error', error.message || 'No fue posible guardar la propiedad.');
                }
            });
        })();
    </script>
@endpush
