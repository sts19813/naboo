<?php

namespace App\Services;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Support\Facades\Http;
use Throwable;

class PropertyMapLocationResolver
{
    public function resolve(string $mapUrl): ?array
    {
        $mapUrl = trim($mapUrl);

        if ($mapUrl === '') {
            return null;
        }

        $coordinates = $this->extractCoordinates($mapUrl);

        if ($coordinates !== null) {
            return [
                ...$coordinates,
                'resolved_url' => $mapUrl,
            ];
        }

        $resolved = $this->resolveRedirect($mapUrl);

        if ($resolved === null || $resolved['url'] === $mapUrl) {
            return null;
        }

        $resolvedUrl = $resolved['url'];
        $coordinates = $this->extractCoordinates($resolvedUrl);

        if ($coordinates === null) {
            $coordinates = $this->extractCoordinates($resolved['body']);
        }

        if ($coordinates === null) {
            return null;
        }

        return [
            ...$coordinates,
            'resolved_url' => $resolvedUrl,
        ];
    }

    public function extractCoordinates(string $url): ?array
    {
        $decodedUrl = urldecode($url);
        $patterns = [
            '/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/',
            '/@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/',
            '/\/place\/(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/',
            '/\/maps\/search\/(-?\d+(?:\.\d+)?),\s*\+?(-?\d+(?:\.\d+)?)/',
            '/[?&](?:q|ll)=(-?\d+(?:\.\d+)?),\s*\+?(-?\d+(?:\.\d+)?)/',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $decodedUrl, $matches)) {
                continue;
            }

            $latitude = (float) $matches[1];
            $longitude = (float) $matches[2];

            if ($this->isValidCoordinate($latitude, $longitude)) {
                return [
                    'latitude' => round($latitude, 7),
                    'longitude' => round($longitude, 7),
                ];
            }
        }

        return null;
    }

    private function resolveRedirect(string $mapUrl): ?array
    {
        $current = $mapUrl;
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $parts = parse_url($current);
            $host = strtolower((string) ($parts['host'] ?? ''));
            $allowedHost = in_array($host, ['maps.app.goo.gl', 'goo.gl', 'google.com', 'www.google.com', 'maps.google.com', 'google.com.mx', 'www.google.com.mx', 'maps.google.com.mx'], true);
            if (! $allowedHost || strtolower((string) ($parts['scheme'] ?? '')) !== 'https' || isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && $parts['port'] !== 443)) {
                return null;
            }

            try {
                $response = Http::withoutRedirecting()->timeout(8)->connectTimeout(5)
                    ->withHeaders(['User-Agent' => 'Naboo map resolver'])
                    ->get($current);
            } catch (Throwable) {
                return null;
            }

            if ($response->redirect() && filled($response->header('Location'))) {
                try {
                    $current = (string) UriResolver::resolve(new Uri($current), new Uri($response->header('Location')));
                } catch (Throwable) {
                    return null;
                }
                continue;
            }

            return ['url' => $current, 'body' => $response->body()];
        }

        return null;
    }

    private function isValidCoordinate(float $latitude, float $longitude): bool
    {
        return $latitude >= -90
            && $latitude <= 90
            && $longitude >= -180
            && $longitude <= 180;
    }
}
