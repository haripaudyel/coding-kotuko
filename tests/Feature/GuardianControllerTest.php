<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GuardianControllerTest extends TestCase
{
    private $validSection = 'movies';
    private $invalidSection = 'Movies@123';

    public function test_invalid_section_format()
    {
        $response = $this->get("/api/{$this->invalidSection}");
        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid section format']);
    }

    public function test_valid_section_with_cached_response()
    {
        $cachedResponse = '<?xml version="1.0" encoding="UTF-8"?><rss><channel></channel></rss>';
        $cacheKey = "rss_feed_{$this->validSection}";
        Cache::put($cacheKey, $cachedResponse, now()->addMinutes(10));

        $response = $this->get("/api/{$this->validSection}");
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/rss+xml');
        $response->assertSee('<?xml version="1.0" encoding="UTF-8"?><rss><channel></channel></rss>', false);

        Cache::forget($cacheKey);
    }
}
