# MODEL-CPLX-001 — Complejidad SDAI PYME

**Versión:** 1.1  
**Estado:** BASELINE  
**Fecha:** 2026-09-23

## Propósito

Determinar complejidad sin confundir tamaño organizacional con esfuerzo real de una intervención.

`CPLX-ORG → contexto y señales → alcance SRV → CPLX-SRV → ACT × PRF × HH`

**Regla central:** CPLX-ORG no multiplica horas. Orienta la evaluación. Las HH se determinan finalmente por el alcance efectivo del servicio.

## Segmento

Aplica a SDAI PYME de **1–80 colaboradores**. Sobre 80 colaboradores corresponde **cotización especial** y no se extrapola automáticamente.

## CPLX-ORG — Complejidad organizacional

Drivers:
1. Colaboradores.
2. Sistemas y aplicaciones relevantes.
3. Sedes o ubicaciones.
4. Proveedores tecnológicos/SaaS críticos.
5. Complejidad de datos.
6. Dependencia digital e impacto operacional.
7. Complejidad regulatoria/sectorial.

Un sistema es relevante cuando soporta un proceso importante, almacena información relevante o personal, gestiona accesos, intercambia datos con terceros o su indisponibilidad impacta la operación.

CPLX-ORG aporta contexto y señales de complejidad, pero no determina automáticamente CPLX-SRV.

## CPLX-SRV — Complejidad efectiva del servicio

Drivers universales de esfuerzo:

| Driver | T1 / 1 | T2 / 2 | T3 / 3 |
|---|---|---|---|
| S01 Alcance | Acotado | Múltiples componentes | Amplio/transversal |
| S02 Actores involucrados | Un responsable/equipo | Varias áreas | Múltiples áreas/terceros |
| S03 Evidencias | Pocas/homogéneas | Diversas | Numerosas/heterogéneas |
| S04 Dependencias | Simples | Varias | Complejas/terceros |
| S05 Ejecución/validación | Una validación simple | Varias pruebas | Pruebas complejas/coordinadas |

Hipótesis de umbrales a calibrar:
- **5–7:** T1 — intervención acotada.
- **8–11:** T2 — intervención ampliada.
- **12–15:** T3 — intervención compleja.

Estos umbrales son baseline operacional, no una verdad permanente. Deben calibrarse con ejecución real.

## Reglas de elevación

El promedio no debe ocultar criticidad. Como baseline:
- tratamiento intensivo de datos sensibles puede elevar como mínimo a T2;
- dependencia digital capaz de detener la operación puede elevar como mínimo a T2;
- contexto sectorial/regulatorio complejo puede elevar como mínimo a T2;
- dos o más drivers en nivel 3 pueden elevar la intervención a T3.

La elevación debe quedar justificada y registrada.

## Especialización inicial para SRV-CON-001

Los drivers universales se interpretan para Continuidad y Recuperación de esta forma:

- **S01 Activos de recuperación:** T1 1–2 sistemas/repositorios; T2 3–5; T3 6+ o ecosistema distribuido.
- **S02 Actores:** T1 responsable principal; T2 TI + áreas/proveedor; T3 varias áreas y múltiples terceros.
- **S03 Evidencias:** desde configuraciones/registros simples hasta políticas, logs, jobs y múltiples plataformas heterogéneas.
- **S04 Dependencias:** desde recuperación autocontenida hasta identidades, SaaS, bases de datos, integraciones, infraestructura y terceros encadenados.
- **S05 Prueba:** desde una restauración controlada hasta múltiples escenarios coordinados con impacto operacional.

## Curva de esfuerzo por actividad

CPLX-SRV clasifica el servicio, pero no se aplicarán multiplicadores globales de horas. Cada actividad ACT tendrá su propia curva T1/T2/T3.

Ejemplo:
- ACT-CON-001 Kickoff: 1 / 1.5 / 2 HH.
- ACT-CON-007 Prueba de recuperación: 2 / 3 / 5 HH.

Esto permite que actividades sensibles a complejidad escalen más que actividades relativamente estables.

## Principio de cubicación

`Contexto organizacional → Alcance SRV → CPLX-SRV → ACT(T1/T2/T3) → PRF × HH → RATE → valor técnico`

La clasificación final y la propuesta económica requieren revisión profesional SDAI.

## Ciclo de aprendizaje

Toda ejecución deberá alimentar el modelo:

`Modelo → estimación → ejecución → evidencia → desviación → aprendizaje → Governance Review → nueva versión`

MODEL-CPLX-001 v1.1 se considera el **piso operacional** para comenzar a capturar evidencia; no se automatizarán ajustes del modelo sin datos de ejecución y revisión gobernada.
