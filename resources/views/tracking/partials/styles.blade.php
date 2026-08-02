{{--
    Deliberately inline and self-contained.

    This is the one page that must render for a customer standing in a
    warehouse on a bad connection, and the only page served without the
    Filament shell. Routing it through Vite would give it a build dependency
    and a manifest that can go missing; it has neither today.
--}}
<style>
    :root {
        --ink: #06090f;
        --surface: #0e131c;
        --surface-2: #151d29;
        --line: #212c3c;
        --text: #f4f6f8;
        --dim: #93a0b4;
        --brand: #10b981;
        --brand-bright: #34d399;
        --brand-soft: rgba(16, 185, 129, 0.12);
        --warn: #f59e0b;
        --warn-bright: #fbbf24;
        --warn-soft: rgba(245, 158, 11, 0.12);
        --live: #60a5fa;
        --radius: 1.1rem;
    }

    *, *::before, *::after { box-sizing: border-box; }

    body {
        margin: 0;
        background: var(--ink);
        color: var(--text);
        font-family: 'Cairo', 'Noto Kufi Arabic', system-ui, -apple-system, 'Segoe UI', sans-serif;
        font-size: 16px;
        line-height: 1.6;
        -webkit-font-smoothing: antialiased;
        -webkit-text-size-adjust: 100%;
    }

    main {
        width: min(100% - 1.6rem, 34rem);
        margin-inline: auto;
        padding-block: 1.25rem 3rem;
        display: grid;
        gap: 0.85rem;
    }

    /* ---------------------------------------------------------------- masthead */

    .masthead {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding-block: 0.25rem 0.5rem;
    }

    .masthead img { height: 40px; width: auto; }

    .masthead .ref {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--dim);
        direction: ltr;
        letter-spacing: 0.04em;
    }

    /* ------------------------------------------------------------------- hero */

    .hero {
        position: relative;
        overflow: hidden;
        padding: 1.35rem 1.3rem;
        border-radius: var(--radius);
        background: var(--surface);
        border: 1px solid var(--line);
    }

    .hero::before {
        content: "";
        position: absolute;
        inset-block-start: -70%;
        inset-inline-end: -30%;
        width: 22rem;
        height: 22rem;
        background: radial-gradient(circle, var(--glow, rgba(16, 185, 129, 0.16)) 0%, transparent 68%);
        pointer-events: none;
    }

    .hero > * { position: relative; }

    .hero .eyebrow {
        margin: 0 0 0.3rem;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--dim);
    }

    .hero h1 {
        margin: 0;
        font-size: 1.6rem;
        font-weight: 800;
        line-height: 1.35;
        color: var(--accent, var(--brand-bright));
    }

    .hero .sub {
        margin: 0.35rem 0 0;
        font-size: 0.95rem;
        color: var(--dim);
    }

    /* A slim bar reads faster than a fraction when the answer is "how far". */
    .meter {
        margin-top: 0.9rem;
        height: 6px;
        border-radius: 99px;
        background: var(--surface-2);
        overflow: hidden;
    }

    .meter span {
        display: block;
        height: 100%;
        border-radius: 99px;
        background: linear-gradient(90deg, var(--brand) 0%, var(--brand-bright) 100%);
    }

    /* ---------------------------------------------------------------- notices */

    .notice {
        display: flex;
        gap: 0.6rem;
        padding: 0.85rem 1rem;
        border-radius: var(--radius);
        background: var(--warn-soft);
        border: 1px solid rgba(245, 158, 11, 0.32);
        color: var(--warn-bright);
        font-size: 0.9rem;
        font-weight: 600;
    }

    .notice svg { flex: none; margin-top: 0.2rem; }

    /* ------------------------------------------------------------------ cards */

    .card {
        padding: 1.15rem 1.15rem 1.25rem;
        border-radius: var(--radius);
        background: var(--surface);
        border: 1px solid var(--line);
    }

    .card > h2 {
        margin: 0 0 1rem;
        font-size: 0.95rem;
        font-weight: 800;
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .card > h2 .aside {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--dim);
    }

    /* ---------------------------------------------------------------- stepper */

    .stepper {
        display: flex;
        align-items: flex-start;
    }

    .stepper .step {
        flex: 1 1 0;
        min-width: 0;
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.45rem;
        text-align: center;
    }

    /* The connector sits behind the dot and runs toward the previous step,
       which in RTL means it extends to the right. */
    .stepper .step:not(:first-child)::before {
        content: "";
        position: absolute;
        top: 7px;
        inset-inline-end: 50%;
        width: 100%;
        height: 2px;
        background: var(--line);
    }

    .stepper .step[data-state="done"]::before,
    .stepper .step[data-state="current"]::before { background: var(--brand); }

    .stepper .dot {
        position: relative;
        z-index: 1;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: var(--surface);
        border: 3px solid var(--line);
    }

    .stepper .step[data-state="done"] .dot {
        background: var(--brand);
        border-color: var(--brand);
    }

    .stepper .step[data-state="current"] .dot {
        background: var(--live);
        border-color: rgba(96, 165, 250, 0.28);
        box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.16);
    }

    .stepper .step[data-delayed="true"] .dot {
        background: var(--warn);
        border-color: rgba(245, 158, 11, 0.3);
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.16);
    }

    .stepper .caption {
        font-size: 0.63rem;
        font-weight: 700;
        line-height: 1.35;
        color: var(--dim);
        overflow-wrap: anywhere;
    }

    .stepper .step[data-state="done"] .caption { color: var(--brand-bright); }
    .stepper .step[data-state="current"] .caption { color: var(--live); }

    .stepper .tally {
        font-size: 0.6rem;
        font-weight: 700;
        color: var(--dim);
    }

    .stepper .tally .held {
        display: block;
        margin-top: 0.15rem;
        color: var(--warn-bright);
    }

    @media (min-width: 380px) {
        .stepper .caption { font-size: 0.68rem; }
        .stepper .tally { font-size: 0.64rem; }
    }

    /* --------------------------------------------------------------- packages */

    .packages { display: grid; gap: 0.6rem; }

    .package {
        border-radius: 0.9rem;
        background: var(--surface-2);
        border: 1px solid var(--line);
    }

    .package[data-delayed="true"] { border-color: rgba(245, 158, 11, 0.4); }

    .package > summary {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.85rem 0.95rem;
        cursor: pointer;
        list-style: none;
        border-radius: 0.9rem;
    }

    /* Safari and Chrome each need their own hook to drop the default marker. */
    .package > summary::-webkit-details-marker { display: none; }
    .package > summary::marker { content: ""; }

    .package > summary:focus-visible {
        outline: 2px solid var(--live);
        outline-offset: 2px;
    }

    .package .pip {
        flex: none;
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: var(--brand);
    }

    .package[data-state="upcoming"] .pip { background: var(--line); }
    .package[data-delayed="true"] .pip,
    .package[data-exception="true"] .pip { background: var(--warn); }

    .package .headline {
        flex: 1 1 auto;
        min-width: 0;
    }

    .package .headline .where {
        display: block;
        font-size: 0.9rem;
        font-weight: 700;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .package[data-delayed="true"] .headline .where,
    .package[data-exception="true"] .headline .where { color: var(--warn-bright); }

    .package .headline .meta {
        display: block;
        margin-top: 0.15rem;
        font-size: 0.78rem;
        color: var(--dim);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .package .code {
        flex: none;
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--dim);
        direction: ltr;
        letter-spacing: 0.03em;
    }

    .package .chevron {
        flex: none;
        color: var(--dim);
        transition: transform 0.2s ease;
    }

    .package[open] .chevron { transform: rotate(180deg); }

    @media (prefers-reduced-motion: reduce) {
        .package .chevron { transition: none; }
    }

    .package .body {
        padding: 0 0.95rem 0.95rem;
        border-top: 1px solid var(--line);
        margin-top: 0.1rem;
        padding-top: 0.9rem;
    }

    /* --------------------------------------------------- per-package timeline */

    .trail { display: grid; gap: 0.7rem; }

    .trail .leg {
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        gap: 0.65rem;
        font-size: 0.83rem;
    }

    .trail .mark {
        width: 15px;
        text-align: center;
        font-weight: 800;
        color: var(--line);
    }

    .trail .leg[data-state="done"] .mark { color: var(--brand); }
    .trail .leg[data-state="current"] .mark { color: var(--live); }

    .trail .leg[data-state="upcoming"] .what { color: var(--dim); }
    .trail .leg[data-state="current"] .what { font-weight: 700; color: var(--live); }

    .trail .when {
        font-size: 0.75rem;
        color: var(--dim);
        direction: ltr;
        white-space: nowrap;
    }

    .package .reason {
        margin-top: 0.9rem;
        padding: 0.7rem 0.85rem;
        border-radius: 0.7rem;
        background: var(--warn-soft);
        border: 1px solid rgba(245, 158, 11, 0.3);
        color: var(--warn-bright);
        font-size: 0.83rem;
        font-weight: 600;
    }

    /* ---------------------------------------------------------------- figures */

    .figures {
        display: grid;
        grid-auto-flow: column;
        grid-auto-columns: 1fr;
        gap: 0.75rem;
        padding-bottom: 1.1rem;
        margin-bottom: 1.1rem;
        border-bottom: 1px solid var(--line);
        text-align: center;
    }

    .figures .figure .key {
        font-size: 0.75rem;
        color: var(--dim);
    }

    .figures .figure .val {
        margin-top: 0.15rem;
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--brand-bright);
    }

    /* ------------------------------------------------------------------- rows */

    .rows { display: grid; gap: 0.8rem; }

    .rows .row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 1rem;
        font-size: 0.9rem;
    }

    .rows .row dt { color: var(--dim); flex: none; }

    .rows .row dd {
        margin: 0;
        font-weight: 700;
        text-align: end;
        overflow-wrap: anywhere;
    }

    /* --------------------------------------------------------------- disclose */

    .disclose > summary {
        list-style: none;
        cursor: pointer;
        font-size: 0.88rem;
        font-weight: 700;
        color: var(--dim);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
    }

    .disclose > summary::-webkit-details-marker { display: none; }
    .disclose > summary::marker { content: ""; }
    .disclose[open] > summary { margin-bottom: 1rem; }

    .qr {
        display: grid;
        justify-items: center;
        gap: 0.6rem;
    }

    .qr svg {
        width: 148px;
        height: auto;
        padding: 0.6rem;
        border-radius: 0.7rem;
        background: #fff;
    }

    .qr p {
        margin: 0;
        font-size: 0.78rem;
        color: var(--dim);
        text-align: center;
    }

    /* -------------------------------------------------------------------- cta */

    .cta {
        display: grid;
        gap: 0.85rem;
        padding: 1.25rem;
        border-radius: var(--radius);
        background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
        border: 1px solid rgba(16, 185, 129, 0.3);
        text-align: center;
    }

    .cta p { margin: 0; font-size: 0.88rem; color: #a7f3d0; }
    .cta p strong { display: block; font-size: 1rem; color: #fff; margin-bottom: 0.2rem; }

    .cta a {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.85rem 1.25rem;
        border-radius: 0.8rem;
        background: #fff;
        color: #064e3b;
        font-weight: 800;
        font-size: 0.95rem;
        text-decoration: none;
    }

    .cta a:active { transform: scale(0.99); }

    .colophon {
        margin: 0;
        text-align: center;
        font-size: 0.75rem;
        color: var(--dim);
    }
</style>
