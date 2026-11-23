# Comparativa funcional SuHomes → Naboo

Revisión del código local: SuHomes `a3c84ac` y Naboo `0a292a2`. Este documento es un inventario inicial de migración, no una certificación de equivalencia. Se compararon archivos, migraciones, historial y nombres de pruebas; una prueba con nombre distinto no demuestra por sí sola que la funcionalidad falte en Naboo. Cada punto debe contrastarse con su implementación antes de marcarse como migrado.

## Funcionalidades y reglas a revisar

### Propiedades y expedientes

- Mapa interactivo con Leaflet/OpenStreetMap, sincronización de coordenadas pendientes y actualización cuando cambia la URL del mapa.
- Bitácora por propiedad con notas e imágenes; exige texto o adjunto, acepta imágenes, restringe la descarga a la propiedad correspondiente y elimina los adjuntos al borrar la nota.
- Visibilidad por asesor y acceso limitado de proveedores a las propiedades asignadas; restricciones para inventario y detalle.
- Asignación de varios proveedores y mantenimientos programados, incluidas frecuencias de dos y tres semanas.
- Reasignación del técnico de una propiedad con confirmación y preservación de tickets de proveedores externos.
- Movimiento de tickets entre propiedades por administradores, conservando registros relacionados.
- Marcas manuales en el control de propiedades y exportación del control y documentos PDF vigentes, con faltantes y vencimientos.
- Estados de expediente calculados a partir de la presencia del archivo y su vencimiento; revisar consecuencias para documentos previamente rechazados y las validaciones de asignación de inquilino antes de migrar datos.
- Moneda de facturación por propiedad y resúmenes financieros por moneda.

### Cobranza y finanzas

- Filtro de cobranza por propiedad, orden por vencimiento, pestañas por estado, identificación del usuario que registró el pago y descarga de comprobantes individual o conjunta.
- Hasta diez comprobantes por pago, imágenes o PDF, de hasta 10 MB cada uno, manteniendo compatibilidad con el comprobante anterior.
- Mostrar la pestaña de validación solo cuando hay comprobantes pendientes y Stripe en el enlace público según las credenciales configuradas.
- Pagos manuales en la moneda de facturación de la propiedad; filtros de gastos por periodo y propiedad.
- Conservar las correcciones propias de Naboo para saldos, ciclo de vida y aplicación de cambios de renta a meses posteriores.

### Mantenimiento y cortes

- Tablero agrupado por urgencia y programación, búsqueda y filtros dinámicos, paginación independiente y navegación completa del navegador.
- Permisos y visibilidad diferenciados para administradores, asesores, técnicos, proveedores e inquilinos; el técnico solo ve tickets correspondientes y los proveedores tienen acceso limitado.
- Gestión de costos y adjuntos por responsable autorizado, permisos específicos de edición y eliminación, bloqueo de cambios después del pago y reglas para tickets terminados.
- Finalización de tickets restringida según rol; validación de costos antes de completar y exclusión de costos ajenos a la administración de los totales aplicables.
- Administración de proveedores, acceso de usuario, asignaciones y restricciones para archivar proveedores vinculados a propiedades.
- Agrupación de tickets activos o terminados en un ticket maestro; separación de tickets sin costo y validación de los responsables al generar cortes.
- Filtros y exportación de tickets y cortes a PDF y Excel; historial, fechas de terminación y detalles de pago.
- Rol de revisor de tickets: ventana relativa de fechas, validación y acceso limitado al módulo de mantenimiento sin otras modificaciones.

### Accesos, almacén y panel

- Restringir almacén por permiso para técnicos y denegar acceso a proveedores externos, incluso si tienen el permiso.
- Panel filtrable por propiedad y visibilidad restringida para asesores; tareas del técnico limitadas a sus asignaciones.
- Fotografías de perfil en las tablas de usuarios y manejo de avatar predeterminado.
- SuHomes incorpora instalación PWA y una pantalla propia de Copilot; comparar su conveniencia sin sustituir el Copilot y el diseño existentes de Naboo.

### Integración Airbnb SU

- API autenticada con token Sanctum y capacidad específica; listar, vincular y desvincular propiedades disponibles.
- Rechazar vinculación si la propiedad no está habilitada, está ocupada o rentada, o tiene cargos abiertos.
- Registrar eventos financieros idempotentes como cargos pagados y rechazar eventos para propiedades no vinculadas.
- La integración está acoplada a Airbnb SU; adaptar configuración, nombres, credenciales y contratos para Naboo antes de habilitarla. No copiar tokens ni datos de producción.

## Dependencias y precauciones de migración

- SuHomes contiene migraciones adicionales para coordenadas, bitácora, comprobantes, proveedores, control, permisos, tickets agrupados, revisores, Airbnb y moneda. La migración `2026_10_27_003000_restore_casa_320_b_pool_supplier_assignments.php` contiene una corrección específica de datos de SuHomes y no debe trasladarse como migración general de Naboo.
- Revisar dependencias de Composer para exportaciones Excel y sus archivos de bloqueo antes de portar reportes.
- Integrar rutas, middleware, permisos y cambios de modelos por módulo: copiar controladores o vistas completos podría eliminar SSO central, sincronización de identidad, suscripción, Copilot o correcciones posteriores de Naboo.
- Conservar layouts, logotipos, hoja de estilos y colores de Naboo; adaptar los controles nuevos a sus componentes visuales.
- Añadir o adaptar las pruebas funcionales de SuHomes junto con las de regresión de Naboo; probar permisos, migraciones sobre datos existentes y saldos antes de habilitar cada módulo.

## Orden sugerido

1. Inventariar y probar permisos y visibilidad, y adaptar sus migraciones y middleware.
2. Propiedades, proveedores y bitácora; después mantenimiento y cortes, por sus dependencias compartidas.
3. Expedientes, control y reportes; cobranza y moneda con pruebas de datos y cálculos.
4. Mapa, panel y mejoras de usabilidad; evaluar PWA.
5. Integración Airbnb, después de adaptar su contrato y configuración al entorno de Naboo.

## Avance en Naboo local

Se integraron y probaron la bitácora de propiedades con imágenes, el registro de varios comprobantes por pago y su descarga, las marcas manuales del control de propiedades, el permiso de acceso al almacén y las fotos de perfil en tablas de usuarios. Se aplicaron únicamente las migraciones de bitácora, comprobantes, control y permiso de almacén. La migración específica de Casa 320 B no fue trasladada ni ejecutada.

En una segunda etapa se agregó el filtro por propiedad al panel y se restringieron a las propiedades visibles por asesor los listados de propiedades, cobranza, gastos, documentos y tickets, además del acceso directo a propiedades y varios registros vinculados. También se ajustaron los indicadores y comisiones del panel al alcance visible. Los accesos y operaciones no cubiertos por estas pruebas, en particular los flujos generales de propietarios e inquilinos, todavía requieren revisión.

La base local se respaldó antes de los cambios. Los conteos de las tablas originales de negocio se conservaron; los cambios en `migrations`, permisos y caché corresponden a las migraciones aplicadas. La comparación de conteos no es una comprobación de cada valor individual.

**Pendiente:** los demás puntos del inventario requieren implementación y verificación individual. Las pruebas generales conservan cinco fallos de perfil que ya existían antes de esta integración.
