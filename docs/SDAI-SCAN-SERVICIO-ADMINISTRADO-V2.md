# SDAI Scan — Servicio administrado v2 (diseño para revisión)

Fecha: 2026-10-30. Estado: **BORRADOR / SIN DESPLIEGUE**.

## Decisión funcional
- /scan/ queda público únicamente para describir el servicio y recibir solicitudes.
- Un solicitante **no ejecuta** análisis. El backend de ejecución debe exigir identidad de operador SDAI y autorización vigente vinculada a un alcance concreto. No basta ocultar botones.
- El cliente confirma por canal de correo controlado su autorización para **dominio, alcance, finalidad y vigencia**. OTP o correo del dominio, por sí solos, no prueban representación legal. Debe conservarse declaración expresa y evidencia verificable.
- El operador ejecuta un escaneo limitado al alcance autorizado; registra eventos y entrega reporte al solicitante, con copia a contacto@sdaichile.com solo si corresponde a la finalidad informada.
- Revisar limitaciones técnicas de crawler (SSRF, DNS rebinding, redirects, rangos internos, TLS, número de páginas, timeout, cuerpo máximo) antes de habilitar ejecución.

## Estado observado producción (solo metadata informada por hPanel)
Base: `u315290167_sdai_scan`. Tres tablas:
- `authorized_users`: 1 fila estimada; columnas user_id, role, created_at.
- `consent_logs`: 2 filas (acción AGREED_TO_POLICIES, política v1.0, julio 2026); sin columna email.
- `passkey_credentials`: 0 filas estimadas; FK user_id → authorized_users.user_id.
No existen: `scan_tokens`, registros propios de solicitudes, autorizaciones, ejecuciones o reportes. **No borrar ni modificar registros anteriores**.
El SQL en el repositorio es diseño esperado, no equivalencia demostrada del esquema productivo.
Un backup de Hostinger está descargándose; distinguir respaldo de archivos versus respaldo SQL y verificar restauración antes de migrar.

## Modelo propuesto (nuevas entidades, no aplicado)
1. `scan_requests`: request_id, requester_email, organization, requested_domain, purpose, status, created_at, updated_at.
2. `scan_authorizations`: authorization_id, request_id FK, authorized_domain, scope_json, confirmation_method, confirmation_evidence_hash, policy_version, confirmed_at, expires_at, revoked_at, status.
3. `scan_executions`: execution_id, authorization_id FK, operator_user_id FK a authorized_users, target_domain, started_at, ended_at, status, scanner_version, error_code.
4. `scan_reports`: report_id, execution_id FK, storage_reference, sha256, generated_at, delivered_at, recipient_email, delivery_status.
5. `scan_audit_events`: event_id, request_id opcional, execution_id opcional, actor_id opcional, event_type, occurred_at, metadata_json **minimizada**.
No guardar OTP en claro, ni resultados completos en el log. No asumir inmutabilidad sin controles de escritura, retención y monitoreo.
Separar confirmación contractual de tratamiento de datos personales: documentar base de licitud por finalidad en RAT.

## Máquina de estados y reglas
Solicitud: REQUESTED → PENDING_CONFIRMATION → AUTHORIZED → SCHEDULED → COMPLETED; alternativas REJECTED, EXPIRED, REVOKED, FAILED.
- Solo AUTHORIZED con confirmación vigente permite PROGRAMAR/EJECUTAR.
- Backend verifica rol operador, autorización y alcance **en cada ejecución**, en transacción.
- La revocación invalida ejecuciones futuras.
- La notificación de entrega se emite una vez por ejecución y queda con estado de entrega; reintento idempotente.

## Gates de validación
G0: backup SQL consistente + ensayo de restore aislado + esquema real confirmado.
G1: migración aditiva en clon; FK, índices, política de mínimos privilegios y rollback probados.
G2: autenticación por operador y bloqueo del antiguo API público; casos negativos y SSRF.
G3: solicitud → confirmación email → autorización → ejecución → PDF → entrega; auditoría y protección de datos.
G4: despliegue reversible y monitoreo.
No fusionar los PR históricos de OTP público (#5 y #6) sin revisión, pues el requisito funcional cambió.
