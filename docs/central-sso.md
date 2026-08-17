# Acceso central SSO

Cada instalación de Naboo recibe un código de un solo uso en:

```text
GET /sso/callback?code=...&workspace=...
```

El servidor de la instalación canjea el código con `naboo.cloud`, valida la
identidad y abre una sesión local. El usuario debe existir previamente en la
base local, estar activo y tener al menos un rol o permiso.

## Configuración por instalación

Las credenciales son distintas para cada workspace y no deben agregarse al
repositorio.

Tayde:

```dotenv
CENTRAL_SSO_URL=https://naboo.cloud
CENTRAL_SSO_WORKSPACE=tayde
CENTRAL_SSO_CLIENT_ID=valor_privado_de_tayde
CENTRAL_SSO_CLIENT_SECRET=valor_privado_de_tayde
CENTRAL_SSO_SYNC_USERS=true
```

Tipi:

```dotenv
CENTRAL_SSO_URL=https://naboo.cloud
CENTRAL_SSO_WORKSPACE=tipi
CENTRAL_SSO_CLIENT_ID=valor_privado_de_tipi
CENTRAL_SSO_CLIENT_SECRET=valor_privado_de_tipi
CENTRAL_SSO_SYNC_USERS=true
```

Después de actualizar el `.env`:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
```

Este proyecto no debe ejecutar `route:cache` hasta resolver los nombres de ruta
duplicados heredados del módulo de perfil.

Los códigos duran aproximadamente un minuto y solo pueden canjearse una vez.
Si aparece un error de código expirado, se debe volver a iniciar el flujo desde
`https://naboo.cloud`.

## Sincronización de usuarios

Al crear o modificar un usuario local, Naboo aprovisiona su identidad en el
portal central y le concede acceso únicamente al workspace configurado. El hash
de la contraseña viaja por HTTPS entre servidores y nunca se envía en texto
plano. Si el portal no responde, el usuario queda pendiente y el programador
vuelve a intentar la sincronización cada cinco minutos.

Para conciliar manualmente usuarios existentes:

```bash
php artisan sso:sync-users --all
```
