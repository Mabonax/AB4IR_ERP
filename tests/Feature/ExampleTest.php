<?php

it('redirects the root path to the dashboard entrypoint', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('dashboard'));
});
