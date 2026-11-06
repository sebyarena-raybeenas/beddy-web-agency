<?php

it('shows the login page', function () {
    $this->get('/login')->assertOk()->assertSee('Beddy Web Agency');
});

it('redirects guests from the root to the login page', function () {
    $this->get('/')->assertRedirect('/login');
});
