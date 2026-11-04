<?php

use App\Models\User;

it('renders the panel with the brand name and font', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk()
        ->assertSee('Beddy Web Agency')
        ->assertSee('Plus Jakarta Sans');
});
