<div>
    <label class="form-label required">Nombre del permiso</label>
    <input type="text" class="form-control form-control-solid" name="name" value="{{ $permissionItem?->name }}" placeholder="ej. usuarios.gestionar" required>
    <div class="invalid-feedback d-block" data-error-for="name"></div>
</div>
