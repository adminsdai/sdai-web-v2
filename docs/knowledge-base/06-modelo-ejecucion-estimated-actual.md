# MODEL-EXEC-001 — Estimated vs Actual

**Versión:** 1.0  
**Estado:** BASELINE  
**Fecha:** 2026-09-23

## Propósito

Crear desde el primer servicio una base empírica para calibrar actividades, complejidad y cubicación económica SDAI PYME.

## Unidad mínima de observación

La unidad mínima es una **actividad ACT ejecutada dentro de un servicio SRV**.

Por cada actividad se registrará como mínimo:

| Campo | Propósito |
|---|---|
| service_instance_id | Identificar la ejecución concreta del servicio |
| srv_code | Servicio ejecutado |
| act_code | Actividad ejecutada |
| cplx_org | Contexto organizacional T1/T2/T3 |
| cplx_srv | Complejidad del servicio T1/T2/T3 |
| profile_code | Perfil responsable/participante |
| estimated_hh | HH presupuestadas |
| actual_hh | HH efectivamente utilizadas |
| deviation_hh | actual_hh - estimated_hh |
| deviation_pct | desviación porcentual cuando estimated_hh > 0 |
| deviation_cause | Explicación de la desviación |
| evidence_ref | Referencia a evidencia verificable |
| observed_at | Fecha de observación |
| reviewer | Responsable de validar el registro |

## Taxonomía inicial de causas

La causa no debe quedar sólo como texto libre. Baseline:
- SCOPE — cambio/ampliación de alcance.
- CLIENT — disponibilidad, información o retraso del cliente.
- THIRD_PARTY — dependencia o retraso de tercero/proveedor.
- TECH — complejidad técnica no prevista.
- EVIDENCE — evidencia inexistente, dispersa o insuficiente.
- REWORK — retrabajo/corrección.
- ESTIMATION — estimación base inadecuada.
- OTHER — otra causa documentada.

Puede existir detalle narrativo adicional.

## Métricas iniciales

- Desviación HH por ACT.
- Desviación HH por SRV.
- Desviación por CPLX-SRV.
- Desviación por perfil.
- Frecuencia de causas.
- Actividades con desviación recurrente.

## Regla de aprendizaje

Una ejecución individual puede generar un hallazgo, pero no modifica automáticamente la baseline.

Los cambios a ACT, HH, umbrales o reglas de complejidad requieren:
1. evidencia acumulada;
2. análisis de patrón;
3. evaluación de impacto;
4. Governance Review;
5. nueva versión del modelo;
6. conservación de la versión anterior.

## Ejemplo

`ACT-CON-007 | CPLX-SRV T2 | estimado 3 HH | real 5.5 HH | +83.3% | THIRD_PARTY | proveedor demoró restauración`

El objetivo no es penalizar desviaciones; es convertir ejecución en conocimiento organizacional.

## Piso operacional

Desde la primera ejecución comercial de SDAI PYME deberán conservarse **HH estimadas, HH reales, desviación y causa** por actividad. Este requisito precede al desarrollo del motor automático de cotización.
