# MODEL-COST-001 — Motor de Cubicación SDAI PYME

**Versión:** 0.1  
**Estado:** WORKING  
**Fecha:** 2026-09-25  
**Ámbito:** SDAI PYME

## Propósito

Convertir una brecha de capacidad validada en una intervención trazable, reutilizable y cubicable sin confundir hallazgos, capacidades, servicios, actividades, esfuerzo técnico ni precio comercial.

> **SDAI no dimensiona sus servicios por la cantidad de problemas encontrados. Dimensiona la intervención necesaria para desarrollar las capacidades que la empresa realmente necesita.**

## Principio rector

**La unidad atómica de cubicación es ACT.**

Un hallazgo no crea automáticamente una actividad ni un servicio. El motor consolida primero las necesidades de capacidad y reutiliza actividades comunes cuando el alcance y la evidencia son equivalentes.

```
ASSESSMENT
  → CONTEXTO
  → CAPACIDAD ACTUAL
  → PISO OBJETIVO
  → GAP
  → CAPACIDADES COMPARTIDAS
  → ACTIVIDADES MAESTRAS + ACTIVIDADES ESPECIALIZADAS
  → CPLX-SRV
  → ACT × PRF × HH
  → VALOR TÉCNICO
  → REVISIÓN SDAI
  → INVERSIÓN COMERCIAL
  → ROADMAP / INFORME EJECUTIVO
```

## Entidades mínimas

| Entidad | Responsabilidad |
|---|---|
| CAP | Capacidad organizacional a desarrollar |
| GAP | Diferencia entre capacidad actual y objetivo |
| ACT | Actividad ejecutable y cubicable |
| PRF | Perfil profesional requerido |
| EVD | Evidencia esperada de la actividad/capacidad |
| CPLX-SRV | Complejidad efectiva del alcance |
| SRV | Empaquetamiento comercial de una intervención |
| RATE | Tarifa versionada del perfil |
| EXEC | Registro Estimated vs Actual |

## Relaciones

- Un GAP puede requerir una o más CAP.
- Una CAP puede cerrar o contribuir a múltiples GAP.
- Una ACT puede contribuir a múltiples CAP, GAP y SRV.
- Una ACT se cubica una sola vez cuando el mismo alcance produce evidencia reutilizable para múltiples necesidades.
- Una ACT puede requerir uno o más PRF.
- CPLX-SRV selecciona la curva de esfuerzo propia de cada ACT; no aplica multiplicadores globales.
- RATE permanece independiente de ACT y SRV.
- El precio comercial permanece separado del valor técnico.

## Actividad maestra

Se define **Actividad Maestra** como una ACT diseñada para obtener contexto, ejecutar trabajo o producir evidencia que puede ser consumida por más de una capacidad o frente de intervención.

Ejemplo:

`ACT-MST-002 Levantamiento de sistemas, datos y dependencias`

Si tres GAP requieren el mismo levantamiento dentro del mismo alcance, el motor no calcula:

`3 GAP × 4 HH = 12 HH`

sino:

`ACT-MST-002 × alcance consolidado = 4 HH`

y conserva las tres relaciones de trazabilidad.

## Regla de brecha neta

```
Hallazgos
→ brecha bruta
→ capacidades existentes
→ tecnología reutilizable
→ actividades compartidas
→ evidencia reutilizable
→ priorización
→ brecha neta de intervención
→ ACT × PRF × HH
```

La eficiencia no se obtiene reduciendo controles o tarifas. Se obtiene diseñando una intervención que reutiliza contexto, actividades y evidencia cuando corresponde.

## Cálculo técnico

Por actividad:

`technical_value_act = estimated_hh × rate_uf(profile, effective_date)`

Por intervención:

`technical_value = Σ technical_value_act`

**Valor técnico ≠ precio comercial.**

El precio comercial puede considerar gestión, QA/gobernanza, costos directos, riesgo de ejecución, margen y decisiones comerciales mediante reglas independientes.

## Datos mínimos de ACT

| Campo | Descripción |
|---|---|
| act_code | Identificador gobernado |
| name | Nombre ejecutivo/técnico |
| act_type | MASTER o SPECIALIZED |
| profile_code | Perfil primario |
| hh_t1 | Esfuerzo T1 |
| hh_t2 | Esfuerzo T2 |
| hh_t3 | Esfuerzo T3 |
| evidence_type | Evidencia esperada |
| acceptance_criteria | Condición observable de término |
| active_version | Versión vigente |

## Salidas del motor

### Vista interna SDAI
- GAP y CAP trazables.
- CPLX-SRV.
- ACT seleccionadas y reutilizadas.
- PRF.
- HH por ACT.
- tarifa vigente.
- valor técnico.
- evidencia y aceptación.
- Estimated vs Actual.

### Vista ejecutiva cliente
- frentes de intervención;
- capacidades objetivo;
- resultados esperados;
- horizonte/roadmap;
- esfuerzo profesional agregado cuando corresponda;
- inversión comercial.

La matriz ACT × PRF × RATE es respaldo interno y no debe dominar la narrativa ejecutiva.

## Prueba de regresión inicial

El caso metodológico CS-002 queda como referencia inicial del motor:

- Esfuerzo integrado esperado: **72 HH**.
- Valor técnico esperado: **118.62 UF**.
- La prueba valida cálculo bottom-up y reutilización de actividades maestras.
- No constituye precio comercial ni baseline estadística.

La implementación automática deberá reproducir estos resultados antes de considerarse apta para pruebas con casos reales.

## Aprendizaje empírico

Toda ejecución real debe alimentar MODEL-EXEC-001:

`estimated_hh → actual_hh → deviation → cause → evidence → review`

La experiencia no modifica automáticamente las curvas de HH. Los cambios requieren evidencia acumulada y Governance Review.

## Reglas de control

1. Hallazgo ≠ servicio.
2. GAP ≠ compra tecnológica.
3. CAP ≠ SRV.
4. ACT es la unidad de cubicación.
5. CPLX-ORG informa contexto; CPLX-SRV determina esfuerzo del alcance.
6. No usar multiplicadores globales T1/T2/T3.
7. No cobrar dos veces una ACT reutilizable dentro del mismo alcance.
8. No deduplicar actividades que, aunque tengan nombre similar, requieran ejecuciones o evidencias distintas.
9. Toda HH debe poder trazarse hacia GAP → CAP → ACT → PRF.
10. Toda recomendación de inversión debe poder trazarse hacia una necesidad concreta de negocio, exposición o impacto.
11. La salida automática es preliminar y requiere revisión profesional SDAI.
12. El motor debe conservar versión de catálogos, tarifas y reglas utilizadas en cada cálculo.

## Estado de producto

MODEL-COST-001 v0.1 consolida el diseño del motor. Permanece WORKING hasta implementar el modelo de datos, cargar los catálogos iniciales y superar la prueba CS-002.

