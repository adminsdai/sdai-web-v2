# AUTH-001 — Inventario de Identidades y Plan de Correlación

Estado: WORKING  
Fecha: 2026-09-25

## Resultado del inventario

### Web / Privacidad
Fuente de identidad actual: `authorized_users.user_id`.
Credencial adicional: `passkey_credentials`.
No se debe asumir que `user_id` es correo hasta validar datos productivos.

### CRM
Tabla `users`: id, name, email UNIQUE, password_hash, role, active, last_login_at, deleted_at.
Roles locales: ADMIN, SALES_MANAGER, SALES_MEMBER, AUDITOR.
Existe alcance por propietario para organizaciones, contactos, oportunidades y actividades. Este alcance debe conservarse después de AUTH-001.

### Kanban
Tabla `users`: id, name, email UNIQUE, role; migración agrega password_hash y active.
Roles locales: ADMIN, MEMBER.
Actualmente bootstrap expone todas las tareas y usuarios a cualquier usuario autenticado y las operaciones create/update/delete/move no aplican alcance por asignatario. AUTH-001 debe agregar autorización de servidor antes de retirar el login local.

## Regla de correlación

La correlación automática sólo puede proponerse cuando existe correo normalizado idéntico y único en los sistemas involucrados.

`lower(trim(email))`

No se copian contraseñas ni hashes. No se fusionan IDs locales. Cada aplicación conserva su ID local y se agrega una referencia a la identidad corporativa.

Casos sin correo, correo placeholder, duplicado o conflicto de nombre quedan en REVIEW y requieren resolución humana.

## Mapeo inicial de roles locales

CRM ADMIN -> ADMIN (revisión requerida)
CRM SALES_MANAGER -> COMERCIAL + permisos de supervisión CRM por definir
CRM SALES_MEMBER -> COMERCIAL
CRM AUDITOR -> AUDITOR

Kanban ADMIN -> ADMIN (revisión requerida)
Kanban MEMBER -> no determina por sí solo un rol corporativo. Se conserva como membresía de aplicación hasta conocer función de la persona.

Los mapeos son propuesta de migración, no asignaciones productivas.

## Enlace local recomendado

Agregar a cada tabla local de usuarios una columna nullable `identity_user_id` durante compatibilidad. El login local continúa funcionando hasta completar validación. Una vez correlacionado, la autorización consulta la identidad corporativa y mantiene el alcance de datos local.

## Gates antes de SSO

1. Exportar sólo metadatos de usuarios desde cada base productiva: id, nombre, correo, rol, activo. Nunca password_hash.
2. Normalizar y comparar correos.
3. Resolver REVIEW manualmente.
4. Confirmar al menos un ADMIN corporativo recuperable.
5. Ejecutar pruebas de permisos y alcance por aplicación.
6. Recién entonces habilitar sesión corporativa y posteriormente retirar login local.

## Hallazgo de seguridad Kanban

El modelo actual autentica, pero no limita operaciones de tareas por propietario/asignatario. Este hallazgo se tratará antes de SSO para que la autenticación unificada no amplifique privilegios existentes.
