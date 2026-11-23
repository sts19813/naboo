<?php
namespace Tests\Feature;

use Tests\TestCase;

class PwaConfigurationTest extends TestCase
{
    public function test_manifest_uses_naboo_icons_and_login_loads_pwa(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('Naboo', $manifest['name']);
        $this->assertSame('standalone', $manifest['display']);
        foreach ([192, 512] as $size) {
            $path = public_path("pwa-icons/icon-{$size}.png");
            $dimensions = getimagesize($path);
            $this->assertSame([$size, $size], [$dimensions[0], $dimensions[1]]);
        }
        $this->get('/login')->assertOk()->assertSee('manifest.webmanifest')->assertSee("navigator.serviceWorker.register('/sw.js')", false);
    }
}
