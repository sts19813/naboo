# Naboo Copilot

Esta rama incorpora un MVP conversacional de solo lectura dentro de Naboo. El copiloto permite consultar informacion operativa del sistema en espanol, conserva el historial por usuario y muestra consumo estimado. El widget solo se muestra cuando `OPENAI_API_KEY` tiene un valor no vacio; si OpenAI no responde, puede continuar con respuestas locales.

## Alcance entregado

- Widget flotante disponible desde el layout autenticado, adaptable a movil y modo oscuro.
- Conversaciones persistentes por usuario, con historial, acciones para abrir vistas relacionadas y opcion de iniciar un chat nuevo.
- Integracion con la API Responses de OpenAI y continuidad de contexto mediante `previous_response_id`.
- Nueve herramientas de consulta sobre propiedades, cobranza, gastos, mantenimiento, expedientes, inventario de almacen e informacion transversal.
- Restriccion de los datos ligados a propiedades segun el rol y las propiedades asignadas al usuario.
- Registro de llamadas a herramientas, latencia, resultado, tokens y costo estimado.
- Fallback local basado en reglas cuando falla la peticion a OpenAI.
- Dataset local de demostracion con usuarios y datos representativos de todos los modulos que consulta el copiloto.

El MVP no crea, modifica ni elimina informacion de negocio. La unica eliminacion que expone es el reinicio del historial propio del copiloto.

## Flujo de una consulta

1. El navegador envia el mensaje y el identificador de conversacion a `POST /copilot/chat`.
2. El backend valida que la conversacion pertenezca al usuario autenticado y guarda el mensaje.
3. Si la pregunta es sobre consumo, la respuesta se calcula localmente sin llamar a OpenAI.
4. Si no hay API key, el layout no renderiza el widget y el flujo no se inicia desde la interfaz.
5. Con API key, el servicio selecciona solamente las herramientas relacionadas con la intencion del mensaje y llama a la API Responses.
6. Las llamadas a funciones solicitadas por el modelo se ejecutan contra la base de datos, se auditan y sus resultados vuelven al modelo para redactar la respuesta.
7. La respuesta, su consumo y hasta tres enlaces relacionados se guardan y se devuelven al widget.

Las llamadas son sincronas, no usan streaming y se ejecutan de forma secuencial. Cada respuesta admite hasta cuatro rondas de herramientas y limita la salida del modelo a 1,200 tokens.

## Componentes principales

| Componente | Responsabilidad |
| --- | --- |
| `resources/views/partials/copilot.blade.php` | Interfaz, estado del panel, historial visible, medidor de uso y peticiones HTTP. |
| `app/Http/Controllers/CopilotController.php` | Validacion de entrada y endpoints JSON. |
| `app/Services/Copilot/CopilotService.php` | Orquestacion de conversaciones, OpenAI, herramientas, fallback y medicion de consumo. |
| `app/Services/Copilot/CopilotToolRegistry.php` | Esquemas y consultas de las nueve herramientas de solo lectura. |
| `app/Services/Copilot/OpenAiResponsesClient.php` | Cliente HTTP para `POST https://api.openai.com/v1/responses`. |
| `app/Models/AiConversation.php` | Conversacion y continuidad con OpenAI. |
| `app/Models/AiMessage.php` | Mensajes y metadatos de uso/acciones. |
| `app/Models/AiToolCall.php` | Auditoria de argumentos, resultado, estado y latencia de cada herramienta. |
| `database/seeders/DemoDataSeeder.php` | Escenario local realista para validar respuestas en todos los modulos. |

## Herramientas de consulta

| Herramienta | Datos consultados | Filtros o limites relevantes |
| --- | --- | --- |
| `get_dashboard_summary` | Propiedades, cobranza, gastos, mantenimiento, documentos y almacen. | Mes actual por defecto; tambien mes anterior, proximos 30 dias, todo o rango personalizado. |
| `search_properties` | Propiedades visibles y sus datos generales. | Texto, estado; maximo 20. |
| `get_property_detail` | Propiedad, propietarios, inquilino, cargos, gastos, tickets, documentos e inventario. | Nombre, referencia o UUID; reporta ambiguedad. |
| `list_charges` | Cargos y saldos pendientes. | Estado, periodo, propiedad; maximo 30. |
| `list_expenses` | Gastos y estado calculado. | Estado, periodo, propiedad; maximo 30. |
| `list_maintenance_tickets` | Tickets y proveedor actual. | Estado, prioridad, categoria, propiedad; maximo 30. |
| `list_documents_status` | Documentos de propiedades, propietarios e inquilinos. | Entidad, estado, vencimiento; maximo 30. |
| `search_storage_items` | Articulos, condicion, cantidad, bodega y zona. | Texto, condicion; maximo 30. |
| `search_system_knowledge` | Busqueda SQL transversal sobre descripciones y notas de varios modulos. | Texto; maximo 20. |

`search_system_knowledge` es una busqueda "RAG lite" basada en coincidencias de texto (`LIKE`). No usa embeddings, una base vectorial ni archivos externos.

## Acceso y aislamiento de datos

Las tres rutas del copiloto estan dentro de los middlewares `auth` y `system.access`. Adicionalmente, cada conversacion se busca por UUID y `user_id`, por lo que un usuario no puede continuar ni leer la conversacion de otro usuario mediante el API.

La visibilidad de propiedades aplicada por las herramientas es:

| Usuario | Propiedades visibles para el copiloto |
| --- | --- |
| Rol `administrador` o `admin` | Todas. |
| Rol `asesores`/`asesor`, o permiso `propiedades.ver_propias` | Propiedades donde es asesor principal o esta en la relacion de asesores. |
| Cualquier otro rol | Ninguna propiedad ni sus cargos, gastos, tickets o expedientes relacionados. |

Consideraciones importantes antes de aprobar o desplegar:

- Las consultas y metricas de almacen son globales; actualmente no se filtran por propiedad, asesor ni un permiso especifico de almacen.
- El widget se incluye en el layout general solamente cuando `OPENAI_API_KEY` tiene un valor no vacio. En ese caso, todo usuario que alcance el layout y pase `system.access` puede abrirlo; no existe un permiso exclusivo de copiloto.
- Nombre, roles, pregunta del usuario y resultados de herramientas necesarios para responder pueden enviarse a OpenAI. Los resultados pueden incluir nombres, importes, estados, notas y vencimientos visibles para ese usuario.
- Argumentos y resultados completos de las herramientas quedan almacenados en `ai_tool_calls`; la aplicacion no agrega cifrado de campo para esos registros.
- El prompt indica al modelo que no revele secretos y los errores visibles reemplazan patrones de API keys por `[REDACTED]`, pero esto no sustituye una revision de privacidad y retencion para produccion.

## Rutas HTTP

| Metodo | Ruta | Uso |
| --- | --- | --- |
| `GET` | `/copilot/history` | Devuelve la conversacion mas reciente, hasta 40 mensajes y el consumo del usuario. El widget muestra los ultimos 12. |
| `POST` | `/copilot/chat` | Envia un mensaje de hasta 2,000 caracteres y un `conversation_id` opcional de hasta 80. |
| `DELETE` | `/copilot/history` | Elimina todas las conversaciones, mensajes, llamadas y consumo historico del usuario autenticado. |

Ejemplo de peticion de chat:

```json
{
  "message": "Que cobranza esta vencida o pendiente?",
  "conversation_id": "uuid-opcional"
}
```

La respuesta incluye `conversation_id`, el mensaje del asistente con sus metadatos y `usage_summary` para hoy y el mes actual.

## Persistencia

La migracion `2026_08_05_150000_create_ai_copilot_tables.php` crea:

- `ai_conversations`: propietario de la conversacion, UUID, titulo, ultimo `response_id` de OpenAI, actividad y metadatos.
- `ai_messages`: mensajes `user`/`assistant`, contenido y metadatos. En respuestas del asistente, `meta` guarda modelo, numero de herramientas, errores, tokens, costo estimado y acciones.
- `ai_tool_calls`: nombre, argumentos, resultado serializado, estado y latencia de cada consulta ejecutada.

Las relaciones tienen eliminacion en cascada. Eliminar un usuario elimina sus conversaciones; eliminar una conversacion elimina sus mensajes y llamadas a herramientas.

## Configuracion

Agregar o revisar las siguientes variables en `.env`:

```dotenv
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4.1-mini
OPENAI_TIMEOUT=45
OPENAI_INPUT_COST_PER_1M=
OPENAI_OUTPUT_COST_PER_1M=
```

- `OPENAI_API_KEY`: habilita y muestra el Copilot. Si no existe, queda vacia o contiene solamente espacios, el widget no se renderiza.
- `OPENAI_MODEL`: modelo enviado a la API Responses.
- `OPENAI_TIMEOUT`: timeout HTTP en segundos.
- `OPENAI_INPUT_COST_PER_1M` y `OPENAI_OUTPUT_COST_PER_1M`: tarifas en USD usadas para estimar costo por millon de tokens.

El codigo incluye tarifas por defecto para algunos modelos, pero son solo una referencia estatica. Para reportes de costo confiables se deben configurar ambas tarifas mediante variables de entorno y mantenerlas actualizadas. Si el modelo no tiene tarifa conocida ni configurada, el costo estimado sera cero.

Instalacion o actualizacion:

```bash
composer install
npm install
php artisan migrate
php artisan config:clear
npm run build
```

Para comprobar la interfaz en cualquier entorno se debe configurar una API key. La clave permanece exclusivamente en el servidor y no se incluye en el HTML.

## Datos de demostracion

Para poblar un entorno local desechable:

```bash
php artisan db:seed --class=DemoDataSeeder
php artisan storage:link
```

El seeder crea usuarios, configuracion, propiedades, expedientes, inventarios, cobranza, gastos, mantenimiento, almacen y archivos PDF/SVG de muestra. Es idempotente en gran parte mediante `updateOrCreate`, aunque algunos nombres de archivo se generan aleatoriamente.

Usuarios incluidos:

| Rol | Correo |
| --- | --- |
| Administrador | `admin@naboo.local` |
| Asesor principal | `andrea.campos@naboo.local` |
| Asesor secundario | `marco.balam@naboo.local` |
| Tecnico | `rafael.pacheco@naboo.local` |
| Inquilino | `laura.pech@naboo.local` |

La contrasena local comun esta definida en el seeder. No se debe ejecutar `DemoDataSeeder` en produccion: crea cuentas con credenciales conocidas y puede actualizar la contrasena de cuentas que coincidan con esos correos.

## Consumo, fallback y operacion

- Cada respuesta suma tokens de entrada, entrada cacheada, salida y total reportados por OpenAI.
- El medidor muestra consumo del usuario para hoy y para el mes actual. No es un total global de la organizacion.
- Preguntas que contienen terminos como `tokens`, `costo`, `consumo` o `precio` se contestan localmente y no generan una llamada adicional a OpenAI.
- Si OpenAI falla, el error se reporta al log de Laravel y se intenta la misma clase de fallback local.
- El costo es estimado y no reemplaza la facturacion del proveedor.

## Checklist de validacion manual

### Configuracion y UI

- [ ] Ejecutar la migracion y confirmar la existencia de las tres tablas `ai_*`.
- [ ] Abrir una pagina autenticada y comprobar launcher, apertura/cierre, `Escape`, modo oscuro y vista movil.
- [ ] Enviar con `Enter`, agregar una linea con `Shift+Enter` y validar el limite de 2,000 caracteres.
- [ ] Recargar la pagina y confirmar que el widget recupera la conversacion mas reciente.
- [ ] Iniciar un chat nuevo y confirmar que se elimina todo el historial del usuario.

### Respuestas y datos

- [ ] Sin `OPENAI_API_KEY`, confirmar que el launcher y el panel no aparecen en ninguna pagina autenticada.
- [ ] Con `OPENAI_API_KEY` vacia o compuesta solo por espacios, confirmar que el launcher y el panel tampoco aparecen.
- [ ] Con `OPENAI_API_KEY`, confirmar respuesta en espanol, fuente funcional, enlaces relacionados y registro de tokens.
- [ ] Preguntar por una propiedad con nombre parcial y probar los casos sin coincidencia, una coincidencia y varias coincidencias.
- [ ] Preguntar por consumo y confirmar que el contador de solicitudes a OpenAI no aumenta por esa consulta.
- [ ] Simular un error o timeout de OpenAI y confirmar que se muestra el fallback y que no aparece la API key en el mensaje.

### Permisos y auditoria

- [ ] Como administrador, confirmar acceso a todas las propiedades.
- [ ] Como cada asesor demo, confirmar que solo aparecen propiedades asignadas directa o indirectamente.
- [ ] Como tecnico o inquilino, confirmar que las herramientas ligadas a propiedades no devuelven registros.
- [ ] Revisar conscientemente que almacen sigue siendo una consulta global para todos los usuarios con acceso al widget.
- [ ] Confirmar en `ai_tool_calls` el estado, latencia, argumentos y resultado de cada herramienta ejecutada.

## Limitaciones conocidas del MVP

- Solo lectura: no hay confirmaciones ni herramientas para cambiar datos de negocio.
- Sin streaming, colas ni ejecucion en segundo plano; una consulta lenta mantiene abierta la peticion web.
- Sin rate limiting, cuota diaria, presupuesto por usuario ni circuit breaker especificos del copiloto.
- Sin selector de conversaciones: solo se recupera la conversacion mas reciente.
- Reiniciar elimina todo el historial del usuario, no solo la conversacion activa.
- Sin panel administrativo para auditoria, costos o retencion; la informacion se revisa directamente en base de datos/logs.
- La visibilidad condicional del widget tiene pruebas automatizadas; el flujo conversacional completo conserva validacion manual.
- La busqueda transversal es lexical y puede omitir sinonimos o coincidencias semanticas.
- La visibilidad de almacen y la disponibilidad general del widget requieren una decision explicita antes de produccion.
