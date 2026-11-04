<?php

it('shows the login page', function () {
    $this->get('/login')->assertOk()->assertSee('Beddy Web Agency');
});
