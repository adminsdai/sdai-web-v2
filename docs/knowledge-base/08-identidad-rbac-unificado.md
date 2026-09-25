# AUTH-001 — Identidad y RBAC Unificado SDAI

**Estado:** WORKING  
**Fecha:** 2026-09-25  
**Ámbito:** Web SDAI, Privacidad, CRM, Kanban y Motor SDAI.

## Decisión

SDAI adoptará una identidad corporativa única y una política RBAC común para todas sus aplicaciones internas. La unificación es de **identidad, roles, permisos y auditoría**, no de los datos de negocio.

Las bases de CRM, Kanban, Privacidad/Web y Motor pueden permanecer físicamente separadas en MariaDB. No se fusionarán tablas de clientes, tareas, consentimientos o cubicación sólo para resolver autenticación.

## Arquitectura objetivo

SDAI Identity/Auth centraliza users, roles, permissions, sessions y audit. CRM, Kanban, Privacidad y Motor consumen esa identidad y mantienen sus bases de negocio separadas.

## Hallazgos del estado actual

### sdai-web-v2
- MariaDB/MySQL vía Prisma.
- Existe AuthorizedUser y PasskeyCredential.
- Existía un panel PHP con autenticación administrativa independiente.
- AUTHZ-001 ya define ADMIN, CONSULTOR y ANALISTA para el Motor.

### crm-sdai
- PHP + PDO + MariaDB.
- Sesión PHP, CSRF, password_hash/password_verify.
- Roles actuales: ADMIN, SALES_MANAGER, SALES_MEMBER, AUDITOR.
- Implementa restricción por propietario para información comercial.
- Mantiene su base productiva separada.

### kanban-sdai
- PHP + PDO + MariaDB en el despliegue actual.
- Sesión PHP, CSRF, password_hash/password_verify.
- Roles actuales: ADMIN y MEMBER.
- Mantiene su base productiva separada.

## Modelo corporativo propuesto

No se forzarán los roles de negocio de cada aplicación a convertirse en un único rol global.

### Roles corporativos
- ADMIN — administración de identidad, seguridad y configuración.
- CONSULTOR — trabajo profesional SDAI.
- ANALISTA — operación/consulta técnica y funcional.
- AUDITOR — lectura y revisión.
- COMERCIAL — operación comercial.

### Permisos por aplicación

Los permisos son la unidad efectiva de autorización.

- crm:read, crm:write, crm:users, crm:audit
- kanban:read, kanban:write, kanban:assign, kanban:users
- privacy:read, privacy:manage, privacy:audit
- motor:read, motor:execute, motor:review, motor:catalog, motor:rates, motor:report
- identity:users, identity:roles, identity:audit

Un usuario puede tener uno o más roles y permisos. Los permisos de aplicación permiten conservar diferencias legítimas entre CRM, Kanban y Motor.

## Principios

1. Una persona = una identidad SDAI.
2. No compartir contraseñas entre aplicaciones ni replicar hashes.
3. Autenticación central; autorización explícita por aplicación.
4. Denegar por defecto.
5. RBAC siempre validado en servidor/API; nunca sólo en interfaz.
6. Sesiones con expiración, regeneración de ID, cookies Secure/HttpOnly/SameSite y protección CSRF cuando corresponda.
7. Toda acción administrativa o sensible genera auditoría.
8. Baja de usuario central revoca acceso a todas las aplicaciones.
9. Datos de negocio permanecen segregados por sistema y mínimo privilegio.
10. Passkeys/MFA se vinculan a la identidad corporativa, no a una aplicación individual.

## Migración sin interrupción

**Fase 1 — Inventario:** correlacionar usuarios existentes de CRM, Kanban y Web por correo corporativo; no modificar producción.

**Fase 2 — Identidad:** crear catálogo central de usuarios, roles, permisos y asignaciones.

**Fase 3 — Compatibilidad:** cada aplicación conserva temporalmente su sesión actual, pero resuelve autorización contra el modelo corporativo.

**Fase 4 — SSO:** emitir una sesión corporativa para los subdominios SDAI y retirar logins locales.

**Fase 5 — Gobierno:** auditoría central de accesos, altas/bajas, roles y eventos sensibles.

## Regla de despliegue

No migrar usuarios ni reemplazar autenticación productiva hasta disponer de respaldo de las bases involucradas, inventario y correlación de cuentas, cuenta ADMIN de recuperación, pruebas de login/logout/revocación, pruebas de autorización positiva y negativa por aplicación y rollback documentado.

## Resultado esperado

LOGIN SDAI → IDENTIDAD ÚNICA → ROLES + PERMISOS → CRM / KANBAN / PRIVACY / MOTOR SDAI.

El usuario ve únicamente las aplicaciones y operaciones para las que posee permisos.
