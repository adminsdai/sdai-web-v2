# SDAI PYME — Decision Log

Este registro conserva decisiones y hallazgos para evitar que futuras reformulaciones borren el razonamiento que originó el modelo.

## 2026-09-23 — KB-DEC-001
**Decisión:** crear una Knowledge Base gobernada dentro del repositorio de implementación.  
**Motivo:** preservar análisis, supuestos y evolución del modelo SDAI PYME.  
**Regla:** la KB no sustituye los activos canónicos institucionales.

## 2026-09-23 — KB-DEC-002
**Decisión:** congelar Baseline Comercial SDAI PYME v1.0 para lanzamiento.  
**Posterior al lanzamiento:** motor detallado de HH e integración automática CRM/cotizador.

## 2026-09-23 — KB-DEC-003
**Decisión:** mantener Continuidad como subfamilia de CYBER y no crear un quinto dominio estratégico.

## 2026-09-23 — KB-DEC-004
**Decisión:** separar nivel global del assessment, madurez de capacidad y complejidad.  
**Motivo:** evitar que L1–L5, M0–M5 y T1–T3 se utilicen como conceptos equivalentes.

## 2026-09-23 — KB-DEC-005
**Decisión:** separar CPLX-ORG de CPLX-SRV.  
**Motivo:** la complejidad general de la organización no determina por sí sola el esfuerzo de un alcance específico.

## 2026-09-23 — KB-DEC-006
**Decisión:** SRV-CON-001 será el servicio patrón para validar ACT → PRF → HH → RATE antes de replicarlo.

## 2026-09-23 — KB-DEC-007
**Decisión:** el valor técnico calculado por HH × tarifa no equivale automáticamente a precio comercial.

## 2026-09-23 — KB-DEC-008
**Decisión:** una prueba de recuperación fallida se conserva como evidencia de brecha y no habilita artificialmente la madurez objetivo.

## 2026-09-23 — KB-DEC-009
**Decisión:** MODEL-CPLX-001 v1.1 establece CPLX-ORG como contexto y CPLX-SRV como determinante del esfuerzo del alcance.  
**Regla:** CPLX-ORG no multiplica HH.

## 2026-09-23 — KB-DEC-010
**Decisión:** no usar multiplicadores globales T1/T2/T3 para HH. Cada ACT mantiene su propia curva de esfuerzo por complejidad.

## 2026-09-23 — KB-DEC-011
**Decisión:** desde la primera ejecución se registra Estimated vs Actual por actividad.  
**Mínimo:** HH estimadas, HH reales, desviación y causa.  
**Objetivo:** convertir experiencia de ejecución en evidencia para evolución gobernada.

## 2026-09-23 — KB-DEC-012
**Decisión:** MODEL-CPLX-001 v1.1 + MODEL-EXEC-001 v1.0 constituyen el piso operacional del futuro motor de cubicación.  
**Regla:** ningún ajuste automático de estimaciones o complejidad se realiza sin evidencia acumulada y Governance Review.

## Próxima revisión

Aplicar MODEL-CPLX-001 v1.1 y MODEL-EXEC-001 v1.0 a SRV-CON-001 en casos reales. Revisar desviaciones antes de modificar umbrales, actividades o HH baseline.
