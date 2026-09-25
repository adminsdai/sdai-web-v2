# Cubicacion Engine — MODEL-COST-001

Primer prototipo ejecutable del motor SDAI PYME.

## Ejecutar prueba

```bash
node src/lib/cubicacion/engine.test.mjs
```

La prueba CS-002 debe producir exactamente:

- 72 HH
- 118.62 UF de valor técnico

El motor es deliberadamente puro en esta etapa: no escribe base de datos, no genera precio comercial y no altera el assessment. La siguiente iteración moverá los catálogos a persistencia gobernada y conectará GAP/CAP/CPLX-SRV.
