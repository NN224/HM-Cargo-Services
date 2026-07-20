# HM Cargo Services

HM Cargo Services is a planned Arabic-first cargo operations system for shipments originating in Dubai and travelling to Lebanon, Syria, and future destinations. It focuses on multi-package shipments, route-specific customer pricing, shipment batches, warehouse scanning, public tracking, payments, credit accounts, and batch profitability without last-mile delivery complexity.

## Project status

The repository currently contains the approved product and domain design only. Product code must not be scaffolded until the owner reviews the specification and approves an implementation plan.

## Planned stack

- Backend and administration: Laravel + Filament
- Local development and tests: SQLite
- Production database: self-hosted PostgreSQL
- UI: Arabic-first, RTL, mobile-friendly
- Public access: secure shipment tracking without login
- Barcode workflow: A4 printing, phone display, and phone-camera scanning

No paid database or paid core-service dependency is permitted.

## Documentation

Start with [`AGENTS.md`](AGENTS.md), then read [`docs/README.md`](docs/README.md).

Key specifications:

- [`docs/product-spec.md`](docs/product-spec.md)
- [`docs/domain/shipment-lifecycle.md`](docs/domain/shipment-lifecycle.md)
- [`docs/domain/pricing-payments.md`](docs/domain/pricing-payments.md)
- [`docs/architecture/data-model.md`](docs/architecture/data-model.md)
- [`docs/decisions.md`](docs/decisions.md)
- [`docs/domain/shipment-mind-map.md`](docs/domain/shipment-mind-map.md)

## Core principle

Use ready-made administration infrastructure, but implement HM Cargo Services' domain rules explicitly. Do not buy or imitate a large courier SaaS system and do not add features merely because a template includes them.

