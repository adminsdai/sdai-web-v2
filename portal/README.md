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

1. Opción recomendada para laboratorio nuevo: en `portal/`, copiar `.env.example` a `.env` y definir `PORTAL_DB_ROOT_PASSWORD`, `PORTAL_DB_PASSWORD` y la misma contraseña codificada para URL dentro de `DATABASE_URL`. Ejecutar `docker compose up -d db`. Esto crea una instancia exclusiva MariaDB 11.8.9 con volumen `sdai-portal_portal_db_data`, puerto **127.0.0.1:3308** y base `sdai_portal_dev`. No toca los contenedores CRM ni `mariadb118`. `PORTAL_ORIGIN` es `http://127.0.0.1:3100`.
2. Comprobar con `docker compose ps` y `mariadb --protocol=TCP -h 127.0.0.1 -P 3308 -u portal_user -p sdai_portal_dev`. Si ya existe `portal/.env`, editarlo manualmente: copiar `.env.example` no reemplaza un archivo existente. En una alternativa con instancia ya existente, omitir Compose y apuntar `DATABASE_URL` exclusivamente a una base nueva con un usuario limitado.
3. En `portal/`: `npm ci`, `npm run db:validate`, `npm run db:generate`, revisar `prisma/migrations/20260929000000_init/migration.sql`, luego `npm run db:migrate`. No usar `db push` contra bases existentes.
4. Definir `PORTAL_BOOTSTRAP_EMAIL` y `PORTAL_BOOTSTRAP_PASSWORD` en el entorno local, ejecutar una sola vez `npm run seed:admin` y retirar las variables del proceso. La contraseña debe tener 12 caracteres o más y no debe versionarse. Arrancar con `npm run dev`.
5. El servidor escucha sólo en `127.0.0.1:3100`. Dispone de login, logout, menú por permisos, creación/listado de empresas, creación/movimiento de tareas propias y registro interno de solicitudes de privacidad pendientes de verificación. Una instancia productiva necesitará proxy HTTPS, almacenamiento compartido para límite de intentos, CSRF robusto, recuperación de cuentas y pruebas integradas con MariaDB.
6. Implementar el canal público ARCOP+B con verificación de titular, endpoint de escaneo con verificación del dominio, formularios completos de CRM/Kanban y pruebas 200/401/403 con base real.
7. Verificar en las bases actuales que realmente no existen registros antes de cualquier retiro de endpoints; mantener exportación y rollback.

Este corte entrega un portal local utilizable para el equipo. Las pruebas de `npm test` y Prisma validan código y esquema sin conexión a MariaDB; la migración SQL se generó sin ejecutar cambios sobre ninguna base. No está preparado para publicación en Internet.
