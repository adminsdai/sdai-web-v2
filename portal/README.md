# Portal SDAI — reconstrucción v1

Base aislada para el portal dinámico. El sitio Astro público continúa estático. Este esquema se aplica únicamente a una base **nueva**, nunca a `sdai_db` ni a las bases actuales de CRM y Kanban.

## Modelo

- `User → Role → Permission → Session`: identidad interna y autorización. Los tokens de sesión sólo se almacenarán como hash; las sesiones se revocan al desactivar usuarios.
- `Account → Contact / Deal`: CRM. Las operaciones consultan permiso y propietario del registro.
- `Task`: Kanban. Las operaciones consultan permiso y asignación; un cambio de asignación exige permiso global.
- `PrivacyRequest → PrivacyRequestEvent`: canal de titulares y expediente interno. Los solicitantes externos no reciben un `User` corporativo.
- `Scan`: verificación del solicitante, consentimiento versionado y evidencia del escaneo de dominio. La autorización de dominio debe comprobarse antes de escanear, en servidor.
- `AuditEvent`: acciones internas. Las bitácoras de solicitudes y consentimientos se conservan como eventos independientes.

## Antes de activar

1. Crear una base local vacía `sdai_portal_dev` y una cuenta limitada a ella. Configurar su URL en un `.env` local no versionado.
2. Validar el esquema con Prisma 6.19.3 y generar una migración revisable. No usar `db push` contra bases existentes.
3. Implementar login, hash de contraseña, cookie segura, CSRF, límite de intentos, cierre y revocación en servidor. La matriz de permisos debe sembrarse explícitamente.
4. Implementar endpoints y consultas con filtro de alcance, y pruebas de 200/401/403 para CRM, Kanban, Privacidad y Motor.
5. Verificar en las bases actuales que realmente no existen registros antes de cualquier retiro de endpoints; mantener exportación y rollback.

Este primer corte entrega el modelo y reglas de alcance comprobables. Aún no ofrece login ni endpoints de producción.
