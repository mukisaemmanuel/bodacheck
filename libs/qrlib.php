<?php
/**
 * QR Code Generator Library
 * --------------------------
 * Generates QR code PNG images using the QR Server API.
 * Falls back to a GD-based placeholder if the API is unreachable.
 * No external PHP dependencies required.
 */

/**
 * Generate a QR code PNG image from a text string.
 * @param string $text     — data to encode (usually a URL)
 * @param string $filepath — where to save the PNG file
 * @param int    $size     — image size in pixels (default 300)
 * @return bool — true on success
 */
function generateQRCode(string $text, string $filepath, int $size = 300): bool {
    // Use the free QR Server API to generate the QR code image
    $qr_url = 'https://api.qrserver.com/v1/create-qr-code/'
            . '?size=' . $size . 'x' . $size
            . '&data=' . urlencode($text)
            . '&format=png'
            . '&color=0a192f'
            . '&bgcolor=ffffff';

    // Try to fetch the QR code image
    $image_data = @file_get_contents($qr_url);

    if ($image_data !== false) {
        return file_put_contents($filepath, $image_data) !== false;
    }

    // Fallback: generate a simple placeholder with GD
    if (!extension_loaded('gd')) return false;

    $img_size = 200;
    $image = imagecreatetruecolor($img_size, $img_size);
    $white = imagecolorallocate($image, 255, 255, 255);
    $navy = imagecolorallocate($image, 10, 25, 47);
    imagefill($image, 0, 0, $white);

    // Draw a QR-like pattern
    $border = 20;
    imagerectangle($image, $border, $border, $img_size - $border, $img_size - $border, $navy);
    // Corner squares (QR code style)
    $sq = 30;
    imagefilledrectangle($image, $border+5, $border+5, $border+5+$sq, $border+5+$sq, $navy);
    imagefilledrectangle($image, $img_size-$border-5-$sq, $border+5, $img_size-$border-5, $border+5+$sq, $navy);
    imagefilledrectangle($image, $border+5, $img_size-$border-5-$sq, $border+5+$sq, $img_size-$border-5, $navy);

    $result = imagepng($image, $filepath);
    imagedestroy($image);
    return $result;
}

/**
 * Build the full scan URL for a rider's QR code.
 * This URL is encoded into the QR image.
 * When scanned, the phone opens this URL in the browser.
 */
function getQrScanUrl(string $qr_token): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host . baseUrl() . '/scan.php?token=' . urlencode($qr_token);
}
