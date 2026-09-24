<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaManifestTest extends TestCase
{
    public function test_manifest_is_publicly_accessible(): void
    {
        $response = $this->get(route('pwa.manifest'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/manifest+json');
        $response->assertJsonPath('short_name', 'KotBean');
        $response->assertJsonPath('display', 'standalone');
    }
}
