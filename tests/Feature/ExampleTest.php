<?php

namespace Tests\Feature;

use App\Models\WebsitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Public website home should render after migrations.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        WebsitePage::create([
            'slug' => 'home',
            'title' => 'Home',
            'template' => 'landing',
            'status' => 'published',
            'show_in_nav' => true,
            'nav_order' => 0,
        ]);

        $response = $this->get('/');

        $response->assertOk();
    }
}
