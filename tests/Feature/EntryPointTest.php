<?php

test('the root path sends visitors to the admin panel', function () {
    // There is no public landing page: this is an administration application.
    // Public shipment tracking arrives in Phase 5 on its own token route.
    $this->get('/')->assertRedirect('/admin');
});

test('an anonymous visitor reaching the panel is sent to the login screen', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('the login screen is reachable', function () {
    $this->get('/admin/login')->assertOk();
});
