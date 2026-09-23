# MODEL-CPLX-001 — Complejidad SDAI PYME

**Versión:** 1.0  
**Estado:** WORKING BASELINE  
**Fecha:** 2026-09-23

## Segmento

Aplica al segmento SDAI PYME de **1–80 colaboradores**. Sobre 80 colaboradores corresponde **cotización especial** y no se extrapola automáticamente.

## Drivers

1. Colaboradores.
2. Sistemas y aplicaciones relevantes.
3. Sedes o ubicaciones.
4. Proveedores tecnológicos/SaaS críticos.
5. Complejidad de datos.
6. Dependencia digital e impacto operacional.
7. Complejidad regulatoria/sectorial.

Un sistema se considera relevante cuando soporta un proceso importante, almacena información relevante o personal, gestiona accesos, intercambia datos con terceros o su indisponibilidad impacta la operación.

## Escala conceptual

- **T1:** intervención acotada.
- **T2:** intervención ampliada.
- **T3:** intervención compleja.

T1/T2/T3 representa complejidad, nunca madurez.

## Hallazgo de diseño: dos complejidades

### CPLX-ORG
Complejidad organizacional detectada a partir del contexto general del assessment.

### CPLX-SRV
Complejidad efectiva del alcance de un servicio específico.

`CPLX-ORG` orienta `CPLX-SRV`, pero no lo sustituye. Una organización compleja puede contratar un alcance acotado y una organización pequeña puede presentar un servicio de alta complejidad por criticidad, datos, regulación o dependencias.

## Regla de elevación

El promedio no debe ocultar factores críticos. Como baseline:
- tratamiento intensivo de datos sensibles puede elevar como mínimo a T2;
- dependencia digital capaz de detener la operación puede elevar como mínimo a T2;
- contexto sectorial/regulatorio complejo puede elevar como mínimo a T2;
- dos o más drivers en nivel 3 pueden elevar la intervención a T3.

Estas reglas deben validarse con casos reales antes de automatizarse.

## Principio de cubicación

`Contexto organizacional → Alcance del servicio → CPLX-SRV → ACT → PRF × HH → RATE → valor técnico`

La clasificación final de servicio requiere revisión profesional SDAI.
