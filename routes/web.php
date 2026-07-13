<?php

use App\Http\Controllers\AdvisorTaskController;
use App\Http\Controllers\ChargeController;
use App\Http\Controllers\ChargePaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DossierConfigurationController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryCheckController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\NotificationConfigurationController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyControlController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\StorageItemController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UserAccessController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        $isTenant = $user->hasRole('inquilino') || $user->hasRole('tenant');
        $isTechnician = $user->hasRole('tecnico') || $user->hasRole('technician');
        $isAdmin = $user->hasRole('administrador') || $user->hasRole('admin');
        $isAdvisor = ! $isAdmin && ($user->hasRole('asesores') || $user->hasRole('asesor') || $user->can('propiedades.ver_propias'));

        return redirect()->route(match (true) {
            $isTenant || $isTechnician => 'maintenance.index',
            $isAdvisor => 'advisor.tasks.index',
            default => 'dashboard',
        });
    }

    return redirect()->route('login');
});

Route::get('/lang/{lang}', [LocaleController::class, 'switch'])->name('lang.switch');
Route::get('/cobranza/pagar/{token}', [ChargePaymentController::class, 'show'])->name('charges.public.show');
Route::post('/cobranza/pagar/{token}/checkout', [ChargePaymentController::class, 'createCheckoutSession'])->name('charges.public.checkout');
Route::post('/cobranza/pagar/{token}/transferencia', [ChargePaymentController::class, 'storeTransferProof'])->name('charges.public.transfer-proof');
Route::get('/cobranza/pago-exitoso/{token}', [ChargePaymentController::class, 'success'])->name('charges.public.success');
Route::post('/stripe/webhook', [ChargePaymentController::class, 'webhook'])
    ->name('stripe.webhook')
    ->withoutMiddleware([ValidateCsrfToken::class]);

Route::middleware(['auth', 'system.access'])
    ->group(function () {
        Route::get('/perfil', [ProfileController::class, 'index'])->name('profile.index');
        Route::post('/perfil/actualizar', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/perfil/foto', [ProfileController::class, 'updatePhoto'])->name('profile.update.photo');
        Route::post('/perfil/password', [ProfileController::class, 'updatePassword'])->name('profile.update.password');

        Route::get('/asesor/pendientes', [AdvisorTaskController::class, 'index'])->name('advisor.tasks.index');
        Route::get('/administracion/pendientes', [AdvisorTaskController::class, 'adminIndex'])->name('admin.tasks.index');

        Route::get('/propiedades', [PropertyController::class, 'index'])->name('properties.index');
        Route::get('/propiedades/control', [PropertyControlController::class, 'index'])->name('properties.control');
        Route::get('/propiedades/nueva', [PropertyController::class, 'create'])->name('properties.create');
        Route::post('/propiedades', [PropertyController::class, 'store'])->name('properties.store');
        Route::get('/propiedades/{property}/editar', [PropertyController::class, 'edit'])->name('properties.edit');
        Route::put('/propiedades/{property}', [PropertyController::class, 'update'])->name('properties.update');
        Route::put('/propiedades/{property}/inquilino', [PropertyController::class, 'updateTenant'])->name('properties.update.tenant');
        Route::put('/propiedades/{property}/asesores', [PropertyController::class, 'updateAdvisors'])->name('properties.update.advisors');
        Route::get('/propiedades/{property}', [PropertyController::class, 'show'])->name('properties.show');
        Route::get('/propiedades/{property}/expediente', [DocumentController::class, 'propertyDossier'])->name('dossiers.properties.show');
        Route::post('/propiedades/{property}/expediente/documentos/{documentType}', [DocumentController::class, 'uploadPropertyDocument'])->name('dossiers.properties.documents.upload');
        Route::patch('/propiedades/{property}/expediente/documentos/{documentType}', [DocumentController::class, 'updatePropertyDocumentMetadata'])->name('dossiers.properties.documents.update');
        Route::post('/propiedades/{property}/expediente/documentos', [DocumentController::class, 'storeCustomPropertyDocument'])->name('dossiers.properties.documents.store');
        Route::delete('/propiedades/{property}/expediente/documentos/{documentType}', [DocumentController::class, 'destroyPropertyDocument'])->name('dossiers.properties.documents.destroy');
        Route::delete('/propiedades/{property}/expediente/documentos/{documentType}/versiones/{version}', [DocumentController::class, 'destroyPropertyDocumentVersion'])->name('dossiers.properties.documents.versions.destroy');

        Route::get('/propiedades/{property}/inventario', [InventoryCheckController::class, 'index'])->name('inventory-checks.index');
        Route::get('/propiedades/{property}/inventario/historial', [InventoryCheckController::class, 'history'])->name('inventory-checks.history');
        Route::get('/propiedades/{property}/inventario/nuevo/{type}', [InventoryCheckController::class, 'create'])->name('inventory-checks.create');
        Route::post('/propiedades/{property}/inventario', [InventoryCheckController::class, 'store'])->name('inventory-checks.store');
        Route::get('/propiedades/{property}/inventario/exportar/pdf', [InventoryCheckController::class, 'exportPdf'])->name('inventory-checks.export-pdf');
        Route::get('/propiedades/{property}/inventario/editar', [PropertyController::class, 'editInventory'])->name('properties.inventory.edit');
        Route::get('/propiedades/{property}/inventario/{check}', [InventoryCheckController::class, 'show'])->name('inventory-checks.show');
        Route::patch('/propiedades/{property}/inventario/{check}/items', [InventoryCheckController::class, 'bulkUpdateItems'])->name('inventory-checks.update-items');
        Route::patch('/propiedades/{property}/inventario/{check}/items/{item}', [InventoryCheckController::class, 'updateItem'])->name('inventory-checks.update-item');
        Route::post('/propiedades/{property}/inventario/{check}/items', [InventoryCheckController::class, 'addItem'])->name('inventory-checks.add-item');
        Route::delete('/propiedades/{property}/inventario/{check}/items/{item}', [InventoryCheckController::class, 'removeItem'])->name('inventory-checks.remove-item');
        Route::patch('/propiedades/{property}/inventario/{check}/completar', [InventoryCheckController::class, 'complete'])->name('inventory-checks.complete');
        Route::get('/propiedades/{property}/inventario/items/{itemId}/historial', [InventoryCheckController::class, 'getItemHistory'])->name('inventory-checks.item-history');
        Route::post('/propiedades/{property}/inventario/{check}/nuevo-elemento', [InventoryCheckController::class, 'addNewItem'])->name('inventory-checks.add-new-item');

        // Inventory management routes
        Route::post('/propiedades/{property}/inventario/areas', [InventoryCheckController::class, 'storeArea'])->name('inventory.areas.store');
        Route::patch('/propiedades/{property}/inventario/areas/{area}', [InventoryCheckController::class, 'updateArea'])->name('inventory.areas.update');
        Route::delete('/propiedades/{property}/inventario/areas/{area}', [InventoryCheckController::class, 'destroyArea'])->name('inventory.areas.destroy');
        Route::delete('/propiedades/{property}/inventario/areas/{area}/fotos/{photo}', [InventoryCheckController::class, 'destroyAreaPhoto'])->name('inventory.areas.photos.destroy');
        Route::post('/propiedades/{property}/inventario/areas/{area}/items', [InventoryCheckController::class, 'storeItem'])->name('inventory.items.store');
        Route::patch('/propiedades/{property}/inventario/areas/{area}/items/{item}', [InventoryCheckController::class, 'updateInventoryItem'])->name('inventory.items.update');
        Route::delete('/propiedades/{property}/inventario/areas/{area}/items/{item}', [InventoryCheckController::class, 'destroyInventoryItem'])->name('inventory.items.destroy');
        Route::delete('/propiedades/{property}/inventario/areas/{area}/items/{item}/fotos/{photo}', [InventoryCheckController::class, 'destroyItemPhoto'])->name('inventory.items.photos.destroy');

        Route::get('/propietarios', [OwnerController::class, 'index'])->name('owners.index');
        Route::post('/propietarios', [OwnerController::class, 'store'])->name('owners.store');
        Route::get('/propietarios/{owner}', [OwnerController::class, 'show'])->name('owners.show');
        Route::get('/propietarios/{owner}/editar', [OwnerController::class, 'edit'])->name('owners.edit');
        Route::put('/propietarios/{owner}', [OwnerController::class, 'update'])->name('owners.update');
        Route::delete('/propietarios/{owner}', [OwnerController::class, 'destroy'])->name('owners.destroy');
        Route::get('/propietarios/{owner}/expediente', [DocumentController::class, 'ownerDossier'])->name('dossiers.owners.show');
        Route::post('/propietarios/{owner}/expediente/documentos/{documentType}', [DocumentController::class, 'uploadOwnerDocument'])->name('dossiers.owners.documents.upload');
        Route::patch('/propietarios/{owner}/expediente/documentos/{documentType}', [DocumentController::class, 'updateOwnerDocumentMetadata'])->name('dossiers.owners.documents.update');
        Route::post('/propietarios/{owner}/expediente/documentos', [DocumentController::class, 'storeCustomOwnerDocument'])->name('dossiers.owners.documents.store');
        Route::delete('/propietarios/{owner}/expediente/documentos/{documentType}', [DocumentController::class, 'destroyOwnerDocument'])->name('dossiers.owners.documents.destroy');
        Route::delete('/propietarios/{owner}/expediente/documentos/{documentType}/versiones/{version}', [DocumentController::class, 'destroyOwnerDocumentVersion'])->name('dossiers.owners.documents.versions.destroy');

        Route::get('/inquilinos', [TenantController::class, 'index'])->name('tenants.index');
        Route::post('/inquilinos', [TenantController::class, 'store'])->name('tenants.store');
        Route::get('/inquilinos/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
        Route::get('/inquilinos/{tenant}/editar', [TenantController::class, 'edit'])->name('tenants.edit');
        Route::put('/inquilinos/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
        Route::get('/inquilinos/{tenant}/expediente', [DocumentController::class, 'tenantDossier'])->name('dossiers.tenants.show');
        Route::post('/inquilinos/{tenant}/expediente/documentos/{documentType}', [DocumentController::class, 'uploadTenantDocument'])->name('dossiers.tenants.documents.upload');
        Route::patch('/inquilinos/{tenant}/expediente/documentos/{documentType}', [DocumentController::class, 'updateTenantDocumentMetadata'])->name('dossiers.tenants.documents.update');
        Route::post('/inquilinos/{tenant}/expediente/documentos', [DocumentController::class, 'storeCustomTenantDocument'])->name('dossiers.tenants.documents.store');
        Route::delete('/inquilinos/{tenant}/expediente/documentos/{documentType}', [DocumentController::class, 'destroyTenantDocument'])->name('dossiers.tenants.documents.destroy');
        Route::delete('/inquilinos/{tenant}/expediente/documentos/{documentType}/versiones/{version}', [DocumentController::class, 'destroyTenantDocumentVersion'])->name('dossiers.tenants.documents.versions.destroy');

        Route::get('/documentos', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/documentos/vencidos', [DocumentController::class, 'expired'])->name('documents.expired');
        Route::get('/documentos/bitacora-eliminados', [DocumentController::class, 'deletedFilesLog'])->name('documents.deleted-files-log');

        Route::get('/configuracion/expedientes', [DossierConfigurationController::class, 'index'])->name('settings.dossiers.index');
        Route::get('/configuracion/almacenamiento-expedientes', [DossierConfigurationController::class, 'storage'])->name('settings.dossiers.storage');
        Route::get('/configuracion/notificaciones', [NotificationConfigurationController::class, 'index'])->name('settings.notifications.index');
        Route::patch('/configuracion/notificaciones', [NotificationConfigurationController::class, 'update'])->name('settings.notifications.update');
        Route::post('/configuracion/expedientes/documentos', [DossierConfigurationController::class, 'store'])->name('settings.dossiers.requirements.store');
        Route::put('/configuracion/expedientes/documentos/{requirement}', [DossierConfigurationController::class, 'update'])->name('settings.dossiers.requirements.update');
        Route::delete('/configuracion/expedientes/documentos/{requirement}', [DossierConfigurationController::class, 'destroy'])->name('settings.dossiers.requirements.destroy');
        Route::post('/configuracion/expedientes/orden', [DossierConfigurationController::class, 'reorder'])->name('settings.dossiers.requirements.reorder');
        Route::patch('/configuracion/expedientes/almacenamiento', [DossierConfigurationController::class, 'updateStorage'])->name('settings.dossiers.storage.update');

        Route::get('/seguridad/usuarios', [UserAccessController::class, 'index'])->name('access.index');
        Route::post('/seguridad/usuarios', [UserAccessController::class, 'storeUser'])->name('access.users.store');
        Route::put('/seguridad/usuarios/{user}', [UserAccessController::class, 'updateUser'])->name('access.users.update');
        Route::post('/seguridad/roles', [UserAccessController::class, 'storeRole'])->name('access.roles.store');
        Route::put('/seguridad/roles/{role}', [UserAccessController::class, 'updateRole'])->name('access.roles.update');
        Route::delete('/seguridad/roles/{role}', [UserAccessController::class, 'destroyRole'])->name('access.roles.destroy');
        Route::post('/seguridad/permisos', [UserAccessController::class, 'storePermission'])->name('access.permissions.store');
        Route::put('/seguridad/permisos/{permission}', [UserAccessController::class, 'updatePermission'])->name('access.permissions.update');
        Route::delete('/seguridad/permisos/{permission}', [UserAccessController::class, 'destroyPermission'])->name('access.permissions.destroy');
        Route::get('/cobranza', [ChargeController::class, 'index'])->name('charges.index');
        Route::put('/cobranza/propiedades/{property}/configuracion', [ChargeController::class, 'updatePropertySetup'])->name('charges.properties.setup');
        Route::post('/cobranza', [ChargeController::class, 'store'])->name('charges.store');
        Route::put('/cobranza/{charge}', [ChargeController::class, 'update'])->name('charges.update');
        Route::delete('/cobranza/{charge}', [ChargeController::class, 'destroy'])->name('charges.destroy');
        Route::get('/cobranza/{charge}', [ChargeController::class, 'show'])->name('charges.show');
        Route::post('/cobranza/{charge}/pagos', [ChargeController::class, 'storePayment'])->name('charges.payments.store');
        Route::post('/cobranza/{charge}/pagos/{payment}/validar', [ChargeController::class, 'validatePayment'])->name('charges.payments.validate');
        Route::post('/cobranza/{charge}/notificar', [ChargeController::class, 'sendReminder'])->name('charges.notify');
        Route::post('/cobranza/generar/preview', [ChargeController::class, 'previewBulk'])->name('charges.bulk.preview');
        Route::post('/cobranza/generar', [ChargeController::class, 'storeBulk'])->name('charges.bulk.store');

        Route::get('/gastos', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/gastos', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::put('/gastos/configuracion', [ExpenseController::class, 'updateGlobalSetup'])->name('expenses.setup.global');
        Route::put('/gastos/propiedades/{property}/configuracion', [ExpenseController::class, 'updatePropertySetup'])->name('expenses.properties.setup');
        Route::post('/gastos/propiedades/{property}/items-recurrentes', [ExpenseController::class, 'storeRecurringItem'])->name('expenses.recurring-items.store');
        Route::put('/gastos/items-recurrentes/{recurringExpenseItem}', [ExpenseController::class, 'updateRecurringItem'])->name('expenses.recurring-items.update');
        Route::delete('/gastos/items-recurrentes/{recurringExpenseItem}', [ExpenseController::class, 'destroyRecurringItem'])->name('expenses.recurring-items.destroy');
        Route::put('/gastos/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::post('/gastos/{expense}/marcar-pagado', [ExpenseController::class, 'markAsPaid'])->name('expenses.mark-paid');
        Route::delete('/gastos/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

        Route::get('/mantenimiento', [MaintenanceController::class, 'index'])->name('maintenance.index');
        Route::post('/mantenimiento', [MaintenanceController::class, 'store'])->name('maintenance.store');
        Route::get('/mantenimiento/tecnicos', [MaintenanceController::class, 'technicians'])->name('maintenance.technicians.index');
        Route::get('/mantenimiento/{maintenance}', [MaintenanceController::class, 'show'])->name('maintenance.show');
        Route::put('/mantenimiento/{maintenance}', [MaintenanceController::class, 'update'])->name('maintenance.update');
        Route::patch('/mantenimiento/{maintenance}/meta', [MaintenanceController::class, 'updateMeta'])->name('maintenance.meta');
        Route::patch('/mantenimiento/{maintenance}/estado', [MaintenanceController::class, 'changeStatus'])->name('maintenance.status');
        Route::patch('/mantenimiento/{maintenance}/programar-visita', [MaintenanceController::class, 'scheduleVisit'])->name('maintenance.schedule-visit');
        Route::post('/mantenimiento/conflictos-tecnico', [MaintenanceController::class, 'technicianConflicts'])->name('maintenance.technician-conflicts');
        Route::post('/mantenimiento/{maintenance}/asignar', [MaintenanceController::class, 'assign'])->name('maintenance.assign');
        Route::put('/mantenimiento/{maintenance}/costos', [MaintenanceController::class, 'updateCosts'])->name('maintenance.costs');
        Route::post('/mantenimiento/{maintenance}/archivos', [MaintenanceController::class, 'uploadFiles'])->name('maintenance.files');
        Route::delete('/mantenimiento/{maintenance}/archivos/{file}', [MaintenanceController::class, 'destroyFile'])->name('maintenance.files.destroy');
        Route::post('/mantenimiento/{maintenance}/mensajes', [MaintenanceController::class, 'storeMessage'])->name('maintenance.messages');
        Route::post('/mantenimiento/proveedores', [MaintenanceController::class, 'storeProvider'])->name('maintenance.providers.store');
        Route::put('/mantenimiento/proveedores/{provider}', [MaintenanceController::class, 'updateProvider'])->name('maintenance.providers.update');

        // Almacén (storage items)
        Route::resource('storage_items', StorageItemController::class);
        Route::post('storage_items/catalog/warehouse', [StorageItemController::class, 'storeWarehouse'])->name('storage_items.warehouses.store');
        Route::put('storage_items/catalog/warehouse/{warehouse}', [StorageItemController::class, 'updateWarehouse'])->name('storage_items.warehouses.update');
        Route::post('storage_items/catalog/zone', [StorageItemController::class, 'storeZone'])->name('storage_items.zones.store');
        Route::put('storage_items/catalog/zone/{zone}', [StorageItemController::class, 'updateZone'])->name('storage_items.zones.update');
        Route::delete('storage_items/catalog/zone/{zone}', [StorageItemController::class, 'destroyZone'])->name('storage_items.zones.destroy');
        Route::post('storage_items/{storage_item}/note', [StorageItemController::class, 'addNote'])->name('storage_items.addNote');
        Route::get('storage_items/trash/view', [StorageItemController::class, 'trashed'])->name('storage_items.trashed');
        Route::post('storage_items/{storage_item}/restore', [StorageItemController::class, 'restore'])->name('storage_items.restore');
        Route::post('storage_items/{storage_item}/delete-with-note', [StorageItemController::class, 'deleteWithNote'])->name('storage_items.deleteWithNote');
    });

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'system.access'])
    ->name('dashboard');

Route::middleware(['auth', 'system.access'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
