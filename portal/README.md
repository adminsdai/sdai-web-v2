# Portal SDAI — reconstrucción v1

Base aislada para el portal dinámico. El sitio Astro público continúa estático. Este esquema se aplica únicamente a una base **nueva**, nunca a `sdai_db` ni a las bases actuales de CRM y Kanban.

## Modelo

- `User → Role → Permission → Session`: identidad interna y autorización. Los tokens de sesión sólo se almacenan como hash; resolver la sesión consulta el estado y los permisos vigentes, y la desactivación impide su uso de inmediato.
- `Account → Contact / Deal`: CRM. Las operaciones consultan permiso y propietario del registro.
- `Task`: Kanban. Las operaciones consultan permiso y asignación; un cambio de asignación exige permiso global.
- `PrivacyRequest → PrivacyRequestEvent`: canal de titulares y expediente interno. Los solicitantes externos no reciben un `User` corporativo.
- `Scan`: verificación del solicitante, consentimiento versionado y evidencia del escaneo de dominio. La autorización de dominio debe comprobarse antes de escanear, en servidor.
- `AuditEvent`: acciones internas. Las bitácoras de solicitudes y consentimientos se conservan como eventos independientes.

## Antes de activar

1. Crear una base local vacía `sdai_portal_dev` y una cuenta limitada a ella. Copiar `.env.example` a `.env` y configurar `DATABASE_URL` apuntando **únicamente** a esa base. `PORTAL_ORIGIN` debe ser `http://127.0.0.1:3100` en el laboratorio. El proceso Node necesita esas variables en su entorno; Node 20 puede arrancarse con `node --env-file=.env src/server.mjs` si no se han exportado.
2. En `portal/`: `npm ci`, `npm run db:validate`, `npm run db:generate`, revisar `prisma/migrations/20260929000000_init/migration.sql`, luego `npm run db:migrate`. No usar `db push` contra bases existentes.
3. Definir `PORTAL_BOOTSTRAP_EMAIL` y `PORTAL_BOOTSTRAP_PASSWORD` en el entorno local, ejecutar una sola vez `npm run seed:admin` y retirar las variables del proceso. La contraseña debe tener 12 caracteres o más y no debe versionarse. Arrancar con `npm run dev`.
4. El servidor escucha sólo en `127.0.0.1:3100`. Dispone de login, logout, menú por permisos, creación/listado de empresas, creación/movimiento de tareas propias y registro interno de solicitudes de privacidad pendientes de verificación. Una instancia productiva necesitará proxy HTTPS, almacenamiento compartido para límite de intentos, CSRF robusto, recuperación de cuentas y pruebas integradas con MariaDB.
5. Implementar el canal público ARCOP+B con verificación de titular, endpoint de escaneo con verificación del dominio, formularios completos de CRM/Kanban y pruebas 200/401/403 con base real.
5. Verificar en las bases actuales que realmente no existen registros antes de cualquier retiro de endpoints; mantener exportación y rollback.

Este corte entrega un portal local utilizable para el equipo. Las pruebas de `npm test` y Prisma validan código y esquema sin conexión a MariaDB; la migración SQL se generó sin ejecutar cambios sobre ninguna base. No está preparado para publicación en Internet.
