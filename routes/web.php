<?php

use Illuminate\Support\Facades\Route;

// HM Cargo Services is an administration application, so the root has no
// public landing page of its own. Public shipment tracking gets its own
// unguessable-token route in Phase 5; until then everything lives behind
// the admin panel.
Route::redirect('/', '/admin');
