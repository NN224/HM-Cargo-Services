<?php

test('the dashboard loads at the root path', function () {
    $this->get('/')->assertRedirect('/login');
});

test('an anonymous visitor is sent to the login screen', function () {
    $this->get('/login')->assertOk();
});
