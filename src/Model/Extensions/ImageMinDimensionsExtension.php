<?php

declare(strict_types=1);

namespace Sunnysideup\Ecommerce\Model\Extensions;

use SilverStripe\Assets\Image;
use SilverStripe\Assets\Storage\AssetContainer;
use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\Core\Extension;

/**
 * Scales an image proportionally so that BOTH sides are at least the given
 * minimum. The image is scaled up or down so the "tight" side exactly matches
 * its minimum and the other side is larger. Nothing is cropped.
 *
 * Examples with ScaleMinDimensions(500, 500):
 *   200 x 300   -> 500 x 750    (upscaled)
 *   1200 x 800  -> 750 x 500    (downscaled)
 *   1000 x 3000 -> 500 x 1500   (downscaled)
 *
 * Template: $Image.ScaleMinDimensions(500, 500)
 * PHP:      $image->ScaleMinDimensions(500, 500)
 *
 * @extends Extension<Image|DBFile>
 */
class ImageMinDimensionsExtension extends Extension
{
    public function ScaleMinDimensions(int $minWidth = 500, int $minHeight = 500): ?AssetContainer
    {
        /** @var Image|DBFile $owner */
        $owner = $this->getOwner();

        if (!$owner->exists() || !$owner->getIsImage()) {
            return null;
        }

        $width = (int) $owner->getWidth();
        $height = (int) $owner->getHeight();

        if ($width <= 0 || $height <= 0 || $minWidth <= 0 || $minHeight <= 0) {
            return $owner;
        }

        // Ratio needed for each side to reach its minimum.
        // The larger ratio wins, so both sides end up >= their minimum.
        $widthRatio = $minWidth / $width;
        $heightRatio = $minHeight / $height;

        // ScaleWidth / ScaleHeight both upscale and downscale
        // (unlike ScaleMaxWidth / ScaleMaxHeight, which never upscale).
        return $widthRatio >= $heightRatio
            ? $owner->ScaleWidth($minWidth)
            : $owner->ScaleHeight($minHeight);
    }

    /**
     * Shortcut: $Image.ScaleMinSide(500)
     */
    public function ScaleMinSide(int $size = 500): ?AssetContainer
    {
        return $this->ScaleMinDimensions($size, $size);
    }
}
