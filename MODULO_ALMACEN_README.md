# Módulo de Almacén (Storage Items) - Guía de Instalación

## ✅ Cambios Realizados

### 1. **Base de Datos**
- ✅ Migración para tabla `storage_items` (campos: product_type, name, description, brand, condition, quantity, photo, timestamps)
- ✅ Migración para tabla `storage_item_logs` (bitácora de cambios con user_id, action, note, changes)
- ✅ Soft delete en `storage_items` para ocultar items sin eliminarlos físicamente

### 2. **Modelos**
- ✅ `StorageItem` con SoftDeletes
- ✅ `StorageItemLog` para registrar cambios

### 3. **Controlador**
- ✅ `StorageItemController` con métodos:
  - `index()` - Lista items activos
  - `create()` - Formulario crear
  - `store()` - Guardar nuevo item
  - `show()` - Ver detalle con bitácora
  - `edit()` - Editar item
  - `update()` - Actualizar item
  - `destroy()` - Soft delete (oculta item y pide nota)
  - `addNote()` - Agregar nota de trazabilidad
  - `trashed()` - Ver items eliminados
  - `restore()` - Restaurar item eliminado
  - `deleteWithNote()` - Eliminar con nota obligatoria

### 4. **Validación**
- ✅ `StorageItemRequest` con reglas para todos los campos

### 5. **Rutas**
- ✅ Resource route completo: `/storage_items` (index, create, store, show, edit, update, destroy)
- ✅ Rutas adicionales: `/storage_items/{id}/note`, `/storage_items/trash/view`, `/storage_items/{id}/restore`

### 6. **Vistas Blade**
- ✅ `index.blade.php` - Cards responsivos con Bootstrap 5, modal para eliminar con nota
- ✅ `create.blade.php` - Formulario crear item con estilos modernos
- ✅ `edit.blade.php` - Editar + formulario para agregar notas
- ✅ `show.blade.php` - Detalle con bitácora visual (timeline)
- ✅ `trashed.blade.php` - Visualizar y restaurar items eliminados

### 7. **Características**
- ✅ **Compresión de fotos**: Usa Intervention/Image o fallback con GD (redimensiona a 1200px, calidad 75%)
- ✅ **Bitácora completa**: Registra creación, actualización, eliminación, notas y movimientos
- ✅ **Soft delete**: Items eliminados se pueden restaurar desde "Ver eliminados"
- ✅ **Cantidad**: Campo para llevar stock de cada item
- ✅ **Menú header**: "Almacén" agregado al menú principal
- ✅ **Diseño**: Bootstrap 5 con cards, colores por estado, iconos Bootstrap Icons

---

## 🚀 Pasos de Instalación

### Paso 1: Ejecutar migraciones
```bash
php artisan migrate
```

### Paso 2: Crear enlace simbólico para storage (si no existe)
```bash
php artisan storage:link
```

### Paso 3: (Opcional) Instalar Intervention/Image para mejor compresión
```bash
composer require intervention/image
```

### Paso 4: Acceder al módulo
Una vez autenticado, ir a: **http://tuapp.local/storage_items**

---

## 📋 Campos del Item

| Campo | Tipo | Requerido | Descripción |
|-------|------|----------|-------------|
| `product_type` | string | ✅ | Ej: Herramienta, Mueble, Electrodoméstico |
| `name` | string | ✅ | Nombre del item |
| `description` | text | ❌ | Descripción detallada |
| `brand` | string | ❌ | Marca del producto |
| `condition` | enum | ✅ | bueno, regular, malo |
| `quantity` | integer | ✅ | Cantidad disponible (mín 1) |
| `photo` | file | ❌ | Foto (se comprime automáticamente) |

---

## 🔍 Estados del Item

| Estado | Color | Significado |
|--------|-------|------------|
| Bueno | Verde | ✓ En óptimas condiciones |
| Regular | Amarillo | ~ Funcional pero necesita atención |
| Malo | Rojo | ✗ No funcional o muy dañado |

---

## 📝 Acciones en Bitácora

| Acción | Icono | Descripción |
|--------|-------|------------|
| `created` | ➕ | Item creado |
| `updated` | ✏️ | Datos modificados |
| `soft_deleted` | 🗑️ | Item marcado como eliminado |
| `restored` | 🔄 | Item restaurado |
| `note` | 📌 | Nota de trazabilidad (movimiento, reparación, etc.) |

---

## 🎯 Flujo de Uso

### Crear un item
1. Click en "Almacén" en el menú
2. Click "Nuevo item"
3. Llenar formulario (foto es opcional)
4. Click "Guardar item"

### Editar un item
1. En listado, click icono ✏️ 
2. Modificar datos
3. Click "Actualizar Item"
4. (Opcional) Agregar nota de cambio

### Agregar nota (trazabilidad)
1. En la edición del item, ir a "Agregar Nota / Movimiento"
2. Escribir nota (ej: "Movido a bodega 2", "Reparado", "Revisado")
3. Click "Agregar Nota"
4. La nota aparece en la bitácora

### Eliminar un item
1. En listado, click icono 🗑️
2. Se abre modal pidiendo razón
3. Escribir razón (ej: "Dañado irreparablemente")
4. Click "Eliminar"
5. Item se oculta pero se puede restaurar

### Ver/Restaurar eliminados
1. Click botón "Ver eliminados" en listado
2. Ver items marcados como eliminados
3. Click "Restaurar" para traer de vuelta

### Ver bitácora completa
1. Click icono 👁️ en item
2. Ver detalles + timeline de cambios
3. Expandir "Ver detalles" para ver JSON de cambios

---

## 🛠️ Desarrollo / Debugging

### Ver logs en la DB
```bash
php artisan tinker
>>> DB::table('storage_item_logs')->latest()->get();
```

### Limpiar caché (si hay problemas)
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Resetear datos de prueba
```bash
php artisan migrate:refresh
php artisan migrate
```

---

## 📱 Características Bootstrap Icons

Las vistas usan iconos de Bootstrap Icons (CDN). Ejemplos:
- `bi bi-box-seam` - Icono almacén
- `bi bi-plus-circle` - Nuevo item
- `bi bi-trash` - Eliminar
- `bi bi-pencil` - Editar
- `bi bi-eye` - Ver
- `bi bi-clock-history` - Bitácora

---

## ⚙️ Configuración Personalizada

Si necesitas cambiar:

### Ruta de almacenamiento de fotos
Editar en [StorageItemController.php](app/Http/Controllers/StorageItemController.php#L152):
```php
$filename = 'storage_items/'.time().'_'.Str::random(8).'.jpg';
```

### Tamaño máximo de foto
Editar en [StorageItemRequest.php](app/Http/Requests/StorageItemRequest.php):
```php
'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5120 = 5MB
```

### Calidad de compresión
Editar en [StorageItemController.php](app/Http/Controllers/StorageItemController.php#L165):
```php
->encode('jpg', 75) // 75 = 75% calidad
```

---

## ✨ Próximas mejoras sugeridas

- [ ] Permisos por rol (admin solo puede editar eliminados)
- [ ] Exportar bitácora a PDF/Excel
- [ ] Búsqueda y filtros avanzados
- [ ] Códigos QR para items
- [ ] Historial de cambios de cantidad
- [ ] Alertas de items en mal estado
- [ ] Integración con inventario de propiedades

---

## 📞 Soporte

Si hay errores:
1. Revisar logs: `storage/logs/laravel.log`
2. Verificar migraciones: `php artisan migrate:status`
3. Limpiar cache: `php artisan optimize:clear`
