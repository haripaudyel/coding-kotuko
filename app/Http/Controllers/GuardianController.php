<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class GuardianController extends Controller
{
    //Provided API key not woking so I have used test as API key
    private $apiKey = 'test';

    public function getRssFeed($section)
    {
        if (!preg_match('/^[a-z\-]+$/', $section)) {
            return response()->json(['error' => 'Invalid section format'], 400);
        }
        $cacheKey = "rss_feed_{$section}";
        if (Cache::has($cacheKey)) {
            return response(Cache::get($cacheKey), 200, ['Content-Type' => 'application/rss+xml']);
        }

        // Fetch data from The Guardian API
        $response = Http::get("https://content.guardianapis.com/search?sectionName={$section}", [
            'api-key' => $this->apiKey,
            'show-fields' => 'all',
        ]);
        if ($response->failed()) {
            Log::error('Failed to fetch data from The Guardian API.', ['section' => $section]);
            return response()->json(['error' => 'Unable to fetch data'], 500);
        }

        $data = $response->json()['response']['results'] ?? [];

        $rssFeed = $this->generateRss($data, $section);

        Cache::put($cacheKey, $rssFeed, now()->addMinutes(10));

        return response($rssFeed, 200, ['Content-Type' => 'application/rss+xml']);
    }

    private function generateRss($articles, $section)
    {
        $rss = '<?xml version="1.0" encoding="UTF-8"?>';
        $rss .= '<rss version="2.0"><channel>';
        $rss .= "<title>The Guardian - {$section}</title>";
        $rss .= "<link>https://www.theguardian.com/{$section}</link>";
        $rss .= "<description>Latest articles from The Guardian's {$section} section</description>";

        foreach ($articles as $article) {
            $rss .= '<item>';
            $rss .= "<title>{$article['webTitle']}</title>";
            $rss .= "<link>{$article['webUrl']}</link>";
            $rss .= "<description><![CDATA[" . (isset($article['fields']['bodyText']) ? $article['fields']['bodyText'] : 'No description') . "]]></description>";
            $rss .= "<pubDate>" . date('r', strtotime($article['webPublicationDate'])) . "</pubDate>";
            $rss .= '</item>';
        }

        $rss .= '</channel></rss>';

        return $rss;
    }
}
