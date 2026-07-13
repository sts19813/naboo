@php
    /** @var \App\Models\Owner|null $owner */
    $owner = $owner ?? null;
@endphp

<div class="row g-5">
    <div class="col-12">
        <label class="form-label required">Nombre completo</label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $owner?->name) }}">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-lg-4">
        <label class="form-label required">Telefono</label>
        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
            value="{{ old('phone', $owner?->phone) }}">
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-lg-4">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
            value="{{ old('email', $owner?->email) }}">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-lg-4">
        <label class="form-label">Tipo de titular</label>
        <select name="owner_type" class="form-select @error('owner_type') is-invalid @enderror">
            @foreach ($ownerTypes as $typeValue => $typeLabel)
                <option value="{{ $typeValue }}"
                    {{ old('owner_type', $owner?->owner_type ?? \App\Models\Owner::OWNER_INDIVIDUAL) === $typeValue ? 'selected' : '' }}>
                    {{ $typeLabel }}
                </option>
            @endforeach
        </select>
        @error('owner_type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-lg-6">
        <label class="form-label">RFC</label>
        <input type="text" name="rfc" class="form-control @error('rfc') is-invalid @enderror"
            value="{{ old('rfc', $owner?->rfc) }}">
        @error('rfc')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-lg-6">
        <label class="form-label">CURP</label>
        <input type="text" name="curp" class="form-control @error('curp') is-invalid @enderror"
            value="{{ old('curp', $owner?->curp) }}">
        @error('curp')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 mt-3">
        <h5 class="mb-2 fw-bold text-uppercase fs-7 text-muted">Datos bancarios</h5>
    </div>

    <div class="col-lg-6">
        <label class="form-label">Banco</label>
        <input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror"
            value="{{ old('bank_name', $owner?->bank_name) }}">
        @error('bank_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-lg-6">
        <label class="form-label">CLABE (18 digitos)</label>
        <input type="text" name="clabe" class="form-control @error('clabe') is-invalid @enderror"
            value="{{ old('clabe', $owner?->clabe) }}">
        @error('clabe')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-lg-6">
        <label class="form-label">Titular de la cuenta</label>
        <input type="text" name="account_holder" class="form-control @error('account_holder') is-invalid @enderror"
            value="{{ old('account_holder', $owner?->account_holder) }}">
        @error('account_holder')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-lg-6">
        <label class="form-label">Metodo de pago</label>
        <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
            <option value="">Seleccionar metodo</option>
            @foreach ($paymentMethods as $methodValue => $methodLabel)
                <option value="{{ $methodValue }}"
                    {{ old('payment_method', $owner?->payment_method ?? \App\Models\Owner::PAYMENT_METHOD_TRANSFER) === $methodValue ? 'selected' : '' }}>
                    {{ $methodLabel }}
                </option>
            @endforeach
        </select>
        @error('payment_method')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Domicilio</label>
        <textarea name="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $owner?->address) }}</textarea>
        @error('address')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-12">
        <label class="form-label">Notas</label>
        <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $owner?->notes) }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

