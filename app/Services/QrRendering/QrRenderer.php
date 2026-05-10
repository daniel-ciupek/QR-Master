<?php

declare(strict_types=1);

namespace App\Services\QrRendering;

use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QREps;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QRCodeException;
use chillerlan\QRCode\QROptions;
use InvalidArgumentException;

final class QrRenderer
{
    private const ALLOWED_ECC = ['L', 'M', 'Q', 'H'];

    private const ECC_MAP = [
        'L' => EccLevel::L,
        'M' => EccLevel::M,
        'Q' => EccLevel::Q,
        'H' => EccLevel::H,
    ];

    /**
     * Render QR code as inline SVG string.
     *
     * @throws InvalidArgumentException
     * @throws QRCodeException
     */
    public function svg(string $data, string $ecc = 'M', bool $addXmlHeader = false): string
    {
        $this->guardData($data);

        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'eccLevel' => $this->eccLevel($ecc),
            'addQuietzone' => true,
            'quietzoneSize' => 4,
            'svgAddXmlHeader' => $addXmlHeader,
            'outputBase64' => false,
        ]);

        return (new QRCode($options))->render($data);
    }

    /**
     * Render QR code as PNG binary string.
     *
     * @throws InvalidArgumentException
     * @throws QRCodeException
     */
    public function png(string $data, string $ecc = 'M', int $scale = 10): string
    {
        $this->guardData($data);

        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'eccLevel' => $this->eccLevel($ecc),
            'scale' => max(1, min(50, $scale)),
            'addQuietzone' => true,
            'quietzoneSize' => 4,
            'outputBase64' => false,
            'imageTransparent' => false,
        ]);

        return (new QRCode($options))->render($data);
    }

    /**
     * Render QR code as EPS string.
     *
     * @throws InvalidArgumentException
     * @throws QRCodeException
     */
    public function eps(string $data, string $ecc = 'M'): string
    {
        $this->guardData($data);

        $options = new QROptions([
            'outputInterface' => QREps::class,
            'eccLevel' => $this->eccLevel($ecc),
            'addQuietzone' => true,
            'quietzoneSize' => 4,
            'outputBase64' => false,
            'moduleValues' => [
                // dark modules: black RGB
                1520 => [0, 0, 0],
                // light modules: white RGB
                6 => [255, 255, 255],
            ],
        ]);

        return (new QRCode($options))->render($data);
    }

    /**
     * Render QR code as PDF binary string (SVG embedded in PDF via dompdf).
     *
     * @throws InvalidArgumentException
     * @throws QRCodeException
     */
    public function pdf(string $data, string $ecc = 'M', int $sizeMm = 60): string
    {
        $svg = $this->svg($data, $ecc);

        $html = sprintf(
            '<!DOCTYPE html><html><head><meta charset="utf-8">
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { display: flex; align-items: center; justify-content: center;
                       width: %1$dmm; height: %1$dmm; }
                .qr { width: %1$dmm; height: %1$dmm; }
            </style></head><body>
            <div class="qr">%2$s</div>
            </body></html>',
            $sizeMm,
            $svg
        );

        return Pdf::loadHTML($html)
            ->setPaper([$sizeMm, $sizeMm], 'portrait')
            ->output();
    }

    /**
     * Render QR code as base64-encoded PNG (ready for <img src="...">).
     *
     * @throws InvalidArgumentException
     * @throws QRCodeException
     */
    public function pngBase64(string $data, string $ecc = 'M', int $scale = 10): string
    {
        return 'data:image/png;base64,'.base64_encode($this->png($data, $ecc, $scale));
    }

    /**
     * Render QR code as base64-encoded SVG data URI.
     *
     * @throws InvalidArgumentException
     * @throws QRCodeException
     */
    public function svgDataUri(string $data, string $ecc = 'M'): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->svg($data, $ecc));
    }

    private function eccLevel(string $ecc): int
    {
        $ecc = strtoupper($ecc);

        if (! in_array($ecc, self::ALLOWED_ECC, true)) {
            throw new InvalidArgumentException(
                "Invalid ECC level '{$ecc}'. Allowed: ".implode(', ', self::ALLOWED_ECC)
            );
        }

        return self::ECC_MAP[$ecc];
    }

    private function guardData(string $data): void
    {
        if (trim($data) === '') {
            throw new InvalidArgumentException('QR code data must not be empty.');
        }
    }
}
