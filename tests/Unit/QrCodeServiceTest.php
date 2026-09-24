<?php

namespace Tests\Unit;

use App\Services\QrCodeService;
use Tests\TestCase;

class QrCodeServiceTest extends TestCase
{
    public function test_it_generates_standard_svg_and_png_qr_images(): void
    {
        $payload = 'https://kp.safaubp.com/undangan-sidang/verifikasi/SCAN-TEST-2026';
        $service = new QrCodeService;

        $svg = $service->svg($payload);
        $png = $service->png($payload);

        $this->assertStringStartsWith('<svg', $svg);
        $this->assertStringContainsString('viewBox=', $svg);
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $png);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $service->dataUri($payload));
        $this->assertStringStartsWith('data:image/png;base64,', $service->pngDataUri($payload));
    }
}
