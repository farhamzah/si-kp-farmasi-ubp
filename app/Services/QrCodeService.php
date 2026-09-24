<?php

namespace App\Services;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

class QrCodeService
{
    public function svg(string $payload): string
    {
        return (new SvgWriter)->write($this->qrCode($payload), options: [
            SvgWriter::WRITER_OPTION_COMPACT => true,
            SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true,
        ])->getString();
    }

    public function png(string $payload): string
    {
        return (new PngWriter)->write($this->qrCode($payload))->getString();
    }

    public function dataUri(string $payload): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->svg($payload));
    }

    public function pngDataUri(string $payload): string
    {
        return 'data:image/png;base64,'.base64_encode($this->png($payload));
    }

    private function qrCode(string $payload): QrCode
    {
        return new QrCode(
            data: $payload,
            encoding: new Encoding('ISO-8859-1'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 300,
            margin: 20,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );
    }
}
