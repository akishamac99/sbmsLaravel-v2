<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The home page is now the public landing page, not a redirect to login.
     */
    public function test_the_home_page_shows_the_landing_page(): void
    {
        $this->get('/')->assertOk();
    }
}
