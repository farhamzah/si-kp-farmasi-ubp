<?php

namespace App\Support;

class SimplePdfReport
{
    public static function table(string $title, array $meta, array $headings, array $rows): string
    {
        $lines = [$title];
        foreach ($meta as $label => $value) {
            $lines[] = $label.': '.$value;
        }

        $lines[] = '';
        $lines[] = implode(' | ', $headings);
        $lines[] = str_repeat('-', 120);

        foreach ($rows as $row) {
            $lines[] = implode(' | ', array_map(fn ($value) => (string) $value, $row));
        }

        $pages = array_chunk($lines, 42);
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pageObjectNumbers = [];
        $contentObjectNumbers = [];

        foreach ($pages as $pageIndex => $pageLines) {
            $pageObjectNumber = count($objects) + 1;
            $contentObjectNumber = $pageObjectNumber + 1;
            $pageObjectNumbers[] = $pageObjectNumber;
            $contentObjectNumbers[] = $contentObjectNumber;

            $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R >> >> /Contents {$contentObjectNumber} 0 R >>";
            $objects[] = self::contentObject($pageLines, $pageIndex + 1, count($pages));
        }

        $kids = implode(' ', array_map(fn ($number) => "{$number} 0 R", $pageObjectNumbers));
        $objects[1] = "<< /Type /Pages /Kids [{$kids}] /Count ".count($pageObjectNumbers).' >>';

        return self::render($objects);
    }

    private static function contentObject(array $lines, int $page, int $totalPages): string
    {
        $commands = ['BT /F1 9 Tf'];
        $y = 560;

        foreach ($lines as $index => $line) {
            $fontSize = $index === 0 && $page === 1 ? 14 : 8;
            $safeLine = self::escape(self::truncate($line, 155));
            $commands[] = "/F1 {$fontSize} Tf 1 0 0 1 32 {$y} Tm ({$safeLine}) Tj";
            $y -= $index === 0 && $page === 1 ? 22 : 12;
        }

        $commands[] = '/F1 8 Tf 1 0 0 1 32 24 Tm (Halaman '.$page.' dari '.$totalPages.') Tj';
        $commands[] = 'ET';

        $stream = implode("\n", $commands);

        return "<< /Length ".strlen($stream)." >>\nstream\n{$stream}\nendstream";
    }

    private static function render(array $objects): string
    {
        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $number = $index + 1;
            $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private static function escape(string $text): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text) ?: $text;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);
    }

    private static function truncate(string $text, int $length): string
    {
        return strlen($text) > $length ? substr($text, 0, $length - 3).'...' : $text;
    }
}
