<?php


class ProductImage extends Image
{
    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $singular_name = 'Product Image';

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $plural_name = 'Product Images';

    private static $casting = [
        'CMSThumbnail' => 'HTMLText',
    ];

    public function i18n_singular_name()
    {
        return _t('ProductImage.SINGULARNAME', 'Product Image');
    }

    public function i18n_plural_name()
    {
        return _t('ProductImage.PLURALNAME', 'Product Images');
    }

    /**
     * Fields.
     *
     * @return array
     */
    public function summaryFields()
    {
        return [
            'CMSThumbnail' => 'Preview',
            'Title' => 'Title',
        ];
    }

    /**
     * @return int
     */
    public function ThumbWidth()
    {
        return EcommerceConfig::get('ProductImage', 'thumbnail_width');
    }

    /**
     * @return int
     */
    public function ThumbHeight()
    {
        return EcommerceConfig::get('ProductImage', 'thumbnail_height');
    }

    /**
     * @return int
     */
    public function SmallWidth()
    {
        return EcommerceConfig::get('ProductImage', 'small_image_width');
    }

    /**
     * @return int
     */
    public function SmallHeight()
    {
        return EcommerceConfig::get('ProductImage', 'small_image_height');
    }

    /**
     * @return int
     */
    public function ContentWidth()
    {
        return EcommerceConfig::get('ProductImage', 'content_image_width');
    }

    /**
     * @return int
     */
    public function LargeWidth()
    {
        return EcommerceConfig::get('ProductImage', 'large_image_width');
    }

    /**
     * @usage can be used in a template like this $Image.Thumbnail.Link
     *
     * @param GD $gd
     *
     * @return GD
     **/
    public function generateThumbnail($gd)
    {
        $gd->setQuality(65);

        return $gd->paddedResize($this->ThumbWidth() * 2, $this->ThumbHeight() * 2);
    }

    public function Thumbnail()
    {
        return $this->getFormattedImage('Thumbnail');
    }

    /**
     * @usage can be used in a template like this $Image.SmallImage.Link
     *
     * @return GD
     **/
    public function generateSmallImage($gd)
    {
        $gd->setQuality(65);

        return $gd->paddedResize($this->SmallWidth() * 2, $this->SmallHeight() * 2);
    }

    public function SmallImage()
    {
        return $this->getFormattedImage('SmallImage');
    }

    /**
     * @usage can be used in a template like this $Image.ContentImage.Link
     *
     * @return GD
     **/
    public function generateContentImage($gd)
    {
        $gd->setQuality(65);

        return $gd->resizeByWidth($this->ContentWidth() * 2);
    }

    public function LargeImage()
    {
        return $this->getFormattedImage('LargeImage');
    }

    /**
     * @usage can be used in a template like this $Image.LargeImage.Link
     *
     * @return GD
     **/
    public function generateLargeImage($gd)
    {
        $gd->setQuality(65);

        return $gd->resizeByWidth($this->LargeWidth() * 2);
    }

    public function exists()
    {
        if (isset($this->ID)) {
            if ($this->ID) {
                if (file_exists($this->getFullPath())) {
                    return true;
                }
            }
        }
    }

    /**
     * @return string HTML
     */
    public function CMSThumbnail()
    {
        return $this->getCMSThumbnail();
    }

    /**
     * @return string HTML
     */
    public function getCMSThumbnail()
    {
        $smallImage = $this->SmallImage();
        if ($smallImage) {
            $icon = '<img src="' . $smallImage->FileName . '" style="border: 1px solid #555; height: 100px; " />';
        } else {
            $icon = '[MISSING IMAGE]';
        }

        return DBField::create_field('HTMLText', $icon);
    }
}
