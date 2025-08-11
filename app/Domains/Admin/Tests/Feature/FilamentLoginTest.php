<?php

declare(strict_types=1);

it('serves the Filament login page', function () {
    $response = $this->get('/admin/login');

    $response->assertOk();
});
