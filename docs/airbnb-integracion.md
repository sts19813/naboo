# Integración Airbnb SU en Naboo

La pestaña de Airbnb no se muestra en la interfaz de propiedades. La integración está preparada pero desactivada por defecto mediante `AIRBNB_INTEGRATION_ENABLED=false`. No se han creado tokens ni se ha configurado una conexión externa.

Para una activación futura, configurar el indicador en el entorno correspondiente, aplicar las migraciones y emitir un token con `php artisan integrations:issue-airbnb-token`. El token tiene la capacidad `airbnb`; debe guardarse como secreto y no incluirse en el repositorio. El cliente debe estar activo. La opción `--rotate` invalida los tokens anteriores de ese cliente.

Los endpoints `/api/internal/airbnb/properties`, `/properties/{uuid}/claim`, `/properties/{uuid}/release` y `/financial-events` requieren autenticación Sanctum, un cliente de integración activo y la capacidad `airbnb`. Antes de vincular una propiedad, un administrador puede habilitarla mediante `PATCH /propiedades/{uuid}/airbnb` con `enabled=true`, usando una sesión autenticada y CSRF. Esta operación requiere la integración activada y una propiedad disponible, sin inquilino ni cobranza pendiente. Rechaza propiedades ocupadas y no elimina inquilinos ni contratos. Para deshabilitarla debe liberarse primero el enlace externo.

Los eventos financieros se deduplican por `external_event_id` y generan un cargo y un pago vinculados a un registro de auditoría. El identificador debe permanecer estable en los reintentos. El campo `customer_name` identifica al huésped y no crea un inquilino.

La migración para `airbnb_enabled_at` agrega la columna sin ejecutar el ajuste de registros históricos existente en SuHomes. Ninguna migración particular de corrección de registros de SuHomes se incluye en Naboo.
