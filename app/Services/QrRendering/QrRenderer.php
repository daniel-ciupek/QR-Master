<?php

declare(strict_types=1);

namespace App\Services\QrRendering;

use chillerlan\QRCode\Common\EccLevel;
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
