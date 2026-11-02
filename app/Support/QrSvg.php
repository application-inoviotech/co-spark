<?php

namespace App\Support;

use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/** Scannable QR code as inline SVG (member QR codes for partner redemption). */
class QrSvg
{
    public static function make(string $data): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
            'drawLightModules' => false,
            'svgDefs' => '',
            'markupDark' => '#0A111C',
        ]);

        return (new QRCode($options))->render($data);
    }
}
