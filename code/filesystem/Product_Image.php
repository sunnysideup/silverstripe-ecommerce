<?php


class Product_Image extends Image
{
    private static $casting = array(
        'CMSThumbnail' => 'HTMLText',
    );

    /**
     * Fields.
     *
     * @return array
     */
    public function summaryFields()
    {
        return array(
            'CMSThumbnail' => 'Preview',
            'Title' => 'Title',
        );
    }

    /**
     * @return int
     */
    public function ThumbWidth()
    {
        return EcommerceConfig::get('Product_Image', 'thumbnail_width');
    }

    /**
     * @return int
     */
    public function ThumbHeight()
    {
        return EcommerceConfig::get('Product_Image', 'thumbnail_height');
    }

    /**
     * @return int
     */
    public function SmallWidth()
    {
        return EcommerceConfig::get('Product_Image', 'small_image_width');
    }

    /**
     * @return int
     */
    public function SmallHeight()
    {
        return EcommerceConfig::get('Product_Image', 'small_image_height');
    }

    /**
     * @return int
     */
    public function ContentWidth()
    {
        return EcommerceConfig::get('Product_Image', 'content_image_width');
    }

    /**
     * @return int
     */
    public function LargeWidth()
    {
        return EcommerceConfig::get('Product_Image', 'large_image_width');
    }

    /**
     * @usage can be used in a template like this $Image.Thumbnail.Link
     *
     * @param GD $gd
     *
     * @return GD
     **/
    public function generateThumbnail(GD $gd)
    {
        $gd->setQuality(90);

        return $gd->paddedResize($this->ThumbWidth(), $this->ThumbHeight());
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
    public function generateSmallImage(GD $gd)
    {
        $gd->setQuality(90);

        return $gd->paddedResize($this->SmallWidth(), $this->SmallHeight());
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
    public function generateContentImage(GD $gd)
    {
        $gd->setQuality(90);

        return $gd->resizeByWidth($this->ContentWidth());
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
    public function generateLargeImage(GD $gd)
    {
        $gd->setQuality(90);

        return $gd->resizeByWidth($this->LargeWidth());
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
            $icon = '<img src="'.$smallImage->FileName.'" style="border: 1px solid black; height: 100px; " />';
        } else {
            $icon = '[MISSING IMAGE]';
        }

        return DBField::create_field('HTMLText', $icon);
    }
}
