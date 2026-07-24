<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Generates the PWA icon set into public/icons. The mark is a WHOOP-style
 * progress ring on near-black — reads at small sizes and matches the app.
 */
#[Signature('app:generate-icons')]
#[Description('Generate the PWA icon set (192/512/maskable/apple-touch)')]
class GenerateIcons extends Command
{
    public function handle(): int
    {
        $dir = public_path('icons');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // any-purpose: mark fills most of the canvas.
        $this->write("$dir/192.png", $this->draw(192, 0.78));
        $this->write("$dir/512.png", $this->draw(512, 0.78));
        // maskable: keep the mark inside the centered ~80% safe circle.
        $this->write("$dir/maskable-512.png", $this->draw(512, 0.56));
        // apple-touch: 180×180, opaque, no alpha, square corners.
        $this->write("$dir/apple-touch-icon.png", $this->draw(180, 0.78, opaque: true));

        $this->info('Icons written to public/icons.');

        return self::SUCCESS;
    }

    /** @return \GdImage */
    private function draw(int $size, float $markFraction, bool $opaque = false)
    {
        $img = imagecreatetruecolor($size, $size);
        if (! $opaque) {
            imagesavealpha($img, true);
        }

        $bg = imagecolorallocate($img, 0x0A, 0x0A, 0x0B);
        imagefilledrectangle($img, 0, 0, $size, $size, $bg);

        $amber = imagecolorallocate($img, 0xF5, 0xA5, 0x24);
        $text = imagecolorallocate($img, 0xF2, 0xF2, 0xF3);

        $c = $size / 2;
        $r = $size * $markFraction / 2;
        $thickness = max(2, (int) round($size * 0.055));

        // Amber ring (outer amber disc, inner bg disc).
        imagefilledellipse($img, (int) $c, (int) $c, (int) ($r * 2), (int) ($r * 2), $amber);
        imagefilledellipse($img, (int) $c, (int) $c, (int) ($r * 2 - $thickness * 2), (int) ($r * 2 - $thickness * 2), $bg);

        // Ring endpoint dot (top), echoing the app's progress arc.
        $dotR = $thickness * 0.9;
        imagefilledellipse($img, (int) $c, (int) ($c - $r + $thickness / 2), (int) ($dotR * 2), (int) ($dotR * 2), $text);

        // Center dot.
        imagefilledellipse($img, (int) $c, (int) $c, (int) ($size * 0.14), (int) ($size * 0.14), $text);

        return $img;
    }

    private function write(string $path, \GdImage $img): void
    {
        imagepng($img, $path);
        imagedestroy($img);
        $this->line("  → {$path}");
    }
}
