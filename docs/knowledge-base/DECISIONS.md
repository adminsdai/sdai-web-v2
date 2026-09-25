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

## 2026-09-25 — KB-DEC-013
**Decisión:** cerrar el laboratorio de escenarios sintéticos como fuente de nuevas capas metodológicas y avanzar a productización.  
**Regla:** nuevas dimensiones sólo se incorporan cuando ejecución real o Governance Review demuestren su necesidad.

## 2026-09-25 — KB-DEC-014
**Decisión:** MODEL-COST-001 adopta ACT como unidad atómica de cubicación.  
**Regla:** hallazgos y GAP no generan automáticamente servicios ni HH.

## 2026-09-25 — KB-DEC-015
**Decisión:** incorporar Actividades Maestras y reutilización de evidencia para calcular la brecha neta de intervención.  
**Regla:** una actividad común se cubica una vez cuando el alcance y la evidencia son efectivamente compartidos; no se deduplican ejecuciones materialmente distintas.

## 2026-09-25 — KB-DEC-016
**Decisión:** separar explícitamente la salida interna del motor de la salida ejecutiva al cliente.  
**Interna:** GAP → CAP → ACT → PRF → HH → RATE → valor técnico → evidencia.  
**Cliente:** capacidades objetivo → intervención → resultados → roadmap → inversión comercial.

## 2026-09-25 — KB-DEC-017
**Decisión:** CS-002 queda como primera prueba de regresión del motor automático.  
**Resultado esperado:** 72 HH y 118.62 UF de valor técnico bottom-up.  
**Alcance:** referencia metodológica; no constituye precio comercial ni baseline estadística.

## Próxima revisión

Aplicar MODEL-CPLX-001 v1.1 y MODEL-EXEC-001 v1.0 a SRV-CON-001 en casos reales. Revisar desviaciones antes de modificar umbrales, actividades o HH baseline.
