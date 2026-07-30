<?php

namespace Tests\Feature;

use Tests\TestCase;

class CsrfTokenRefreshTest extends TestCase
{
    public function test_browser_can_refresh_its_own_csrf_token_without_caching_it(): void
    {
        $response = $this->getJson(route('csrf-token.refresh'));

        $response
            ->assertOk()
            ->assertJsonStructure(['token']);

        $this->assertSame(session()->token(), $response->json('token'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }
}
