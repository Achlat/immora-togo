<?php

declare(strict_types=1);

namespace App\Support;

use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * QR codes : vérification des quittances (PNG, pour les PDF) et configuration 2FA (SVG).
 */
final class Qr
{
    public static function svgDataUri(string $data): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => true,
            'addQuietzone' => true,
        ]);

        return (new QRCode($options))->render($data);
    }

    public static function pngDataUri(string $data): string
    {
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => true,
            'scale' => 6,
            'addQuietzone' => true,
        ]);

        return (new QRCode($options))->render($data);
    }
}
