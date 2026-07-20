# HM Cargo Services - Node.js Implementation Plan

**Date:** 2026-07-18
**Status:** Proposed

## Conflict Report & Tech Stack Adaptation
**Conflict:** `AGENTS.md` mandates Laravel with Filament and PostgreSQL. However, the AI Studio environment strictly runs Node.js containers and does not support Laravel/PHP. 
**Resolution:** To build and run the application in this workspace, we must adapt the technology stack to **React (Frontend)** and **Express + Node.js + SQLite (Backend)**. 

## Task-Level Implementation Plan

### Phase 1: Foundation & Scaffold
1. Initialize a Vite + React SPA for the frontend administration panel.
2. Initialize an Express backend to serve API routes and the public tracking surface.
3. Configure Drizzle ORM with SQLite for local development persistence.
4. Set up Tailwind CSS with RTL (Right-to-Left) and Arabic-first typography.

### Phase 2: Domain Data Models & Database Schema
1. Implement `users`, `warehouses`, and `customers` tables.
2. Implement `routes`, `customer_rates`, and `shipments` tables.
3. Implement `packages`, `batches`, and `payments` tables.
4. Implement strictly typed financial (integer cents) and weight (exact decimal) columns.

### Phase 3: Core API Services
1. **Shipment Service:** Create shipment, generate barcodes, and handle package assignments.
2. **Batch Service:** Group shipments, enforce route validation, and snapshot rates.
3. **Warehouse Service:** Handle arrival scans and aggregate shipment status transitions.
4. **Financial Service:** Record payments, allocate FIFO credit, and calculate rounding/profit.

### Phase 4: Administration UI (Filament Alternative)
1. Build Arabic-first RTL responsive layouts.
2. Implement a data table and form system to replicate Filament's ease of use.
3. Build the Dashboard (Shipments awaiting, active batches, collection status).
4. Build the Shipment and Batch management screens with A4 barcode label generation and mobile scanning support.

### Phase 5: Public Tracking & WhatsApp Integration
1. Implement an unauthenticated public tracking view using a high-entropy token.
2. Display safe shipment progress, package count, and payment summary.
3. Generate the one-click WhatsApp message link with prefilled text.

## Action Required
Please approve this Node.js-adapted implementation plan so we can begin scaffolding the product code.
