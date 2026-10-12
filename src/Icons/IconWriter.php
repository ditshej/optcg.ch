<?php

declare(strict_types=1);

namespace Optcg\Icons;

/**
 * Scales a SelectedIcon to the stored size (docs/plan.md section 5: 180x180
 * px) and writes it to public/icons/. Candidates are already square (see
 * IconSelector), so this is a plain resample, no cropping needed.
 */
final class IconWriter
{
    public const STORED_SIZE_PX = 180;

    public function write(SelectedIcon $icon, string $targetPath): void
    {
        $resized = imagecreatetruecolor(self::STORED_SIZE_PX, self::STORED_SIZE_PX);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle($resized, 0, 0, self::STORED_SIZE_PX, self::STORED_SIZE_PX, $transparent);

        imagecopyresampled(
            $resized,
            $icon->image,
            0,
            0,
            0,
            0,
            self::STORED_SIZE_PX,
            self::STORED_SIZE_PX,
            $icon->width,
            $icon->height,
        );

        imagepng($resized, $targetPath);
    }
}
