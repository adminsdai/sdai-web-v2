# SRV-CON-001 — Continuidad y Recuperación

**Versión:** 1.0  
**Estado:** PILOT BASELINE  
**Fecha:** 2026-09-23  
**Dominio:** CYBER / Continuidad

## Propósito

Desarrollar una capacidad organizacional mínima para identificar información necesaria para la operación, disponer de mecanismos de respaldo y demostrar mediante evidencia que dicha información puede recuperarse.

El servicio no vende simplemente “backup”.

## Capacidades objetivo

- CAP-CON-002 Respaldo de información crítica.
- CAP-CON-003 Recuperación verificable.

Transición objetivo inicial: `M0/M1 → M2`. Si el cliente ya se encuentra en M2 o superior, debe evaluarse una evolución distinta; el motor no debe recomendar automáticamente un servicio ya superado.

## Secuencia

`Descubrir → Priorizar → Diseñar → Probar → Evidenciar`

## Actividades y cubicación inicial

| Código | Actividad | Perfil principal | T1 HH | T2 HH | T3 HH |
|---|---|---|---:|---:|---:|
| ACT-CON-001 | Kickoff, alcance y contexto | Procesos | 1 | 1.5 | 2 |
| ACT-CON-002 | Levantamiento procesos/información crítica | Analista | 2 | 4 | 6 |
| ACT-CON-003 | Levantamiento técnico de respaldos | Cyber | 2 | 3 | 5 |
| ACT-CON-004 | Análisis de brechas y dependencias | Cyber | 1.5 | 2.5 | 4 |
| ACT-CON-005 | Definición esquema mínimo de respaldo | Cyber | 2 | 3 | 5 |
| ACT-CON-006 | Preparación prueba de recuperación | Cyber | 1 | 2 | 3 |
| ACT-CON-007 | Ejecución/acompañamiento restauración | Cyber | 2 | 3 | 5 |
| ACT-CON-008 | Documentación de evidencia | Analista | 1.5 | 2.5 | 4 |
| ACT-CON-009 | Procedimiento y responsabilidades | Procesos | 1.5 | 2 | 3 |
| ACT-CON-010 | QA y validación técnica | Cyber | 1 | 1.5 | 2 |
| ACT-CON-011 | Sesión ejecutiva y roadmap | Cyber | 1 | 1.5 | 2 |

Totales iniciales: **T1 16.5 HH**, **T2 24.5 HH**, **T3 41 HH**.

Con las tarifas baseline, valores técnicos iniciales:
- T1: **27.25 UF**
- T2: **43.00 UF**
- T3: **68.14 UF**

Estos valores son cubicación técnica de diseño, no precio público definitivo.

## Entregables

1. Matriz de Información Crítica.
2. Mapa de Respaldo Actual.
3. Análisis de Brechas de Recuperación.
4. Esquema Mínimo de Respaldo.
5. Prueba de Recuperación.
6. Registro de Evidencia.
7. Procedimiento Básico de Recuperación.
8. Roadmap de Evolución.

## Criterio de aceptación

El objetivo se considera alcanzado cuando existe evidencia suficiente de que al menos una muestra acordada de información priorizada ha sido recuperada exitosamente mediante el mecanismo evaluado y el procedimiento queda documentado.

Si la recuperación falla, no se declara artificialmente M2. Se registra la prueba, la brecha demostrada y las acciones correctivas.

## Exclusiones baseline

No incluye por defecto adquisición de almacenamiento, licencias, cloud, hardware, migraciones masivas, alta disponibilidad, reconstrucción completa de servidores, BCP corporativo, DRP avanzado, pentesting, SOC ni soporte permanente.

## Hipótesis a validar

La cubicación T1/T2/T3 debe probarse en ejecuciones reales. La complejidad del servicio debe calcularse por alcance efectivo (CPLX-SRV), usando CPLX-ORG sólo como contexto.
