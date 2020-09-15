<?php

namespace Sunnysideup\Ecommerce\Model\Extensions;

use SilverStripe\CMS\Model\SiteTreeExtension;
use Sunnysideup\Ecommerce\Config\EcommerceConfigAjax;
use Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * @description: adds a few functions to SiteTree to give each page
 * some e-commerce related functionality.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: extensions

 **/
class EcommerceSiteTreeExtension extends SiteTreeExtension
{
    /**
     * returns the instance of EcommerceConfigAjax for use in templates.
     * In templates, it is used like this:
     * $AJAXDefinitions.TableID.
     *
     * @return EcommerceConfigAjax
     **/
    public function AJAXDefinitions()
    {
        return EcommerceConfigAjax::get_one($this->owner);
    }

    /**
     * @return EcommerceDBConfig
     **/
    public function EcomConfig()
    {
        return EcommerceDBConfig::current_ecommerce_db_config();
    }

    /**
     * tells us if the current page is part of e-commerce.
     *
     * @return bool
     */
    public function IsEcommercePage()
    {
        return false;
    }

    /**
     * Log in link.
     *
     * @return string
     */
    public function EcommerceLogInLink()
    {
        if ($this->owner->IsEcommercePage()) {
            $link = $this->owner->Link();
        } else {
            $link = EcommerceDBConfig::current_ecommerce_db_config()->AccountPageLink();
        }

        return '/Security/login?BackURL=' . urlencode($link);
    }

    public function augmentValidURLSegment()
    {
        if ($this->owner instanceof ProductGroup) {
            $checkForDuplicatesURLSegments = ProductGroup::get()
                ->filter(['URLSegment' => $this->owner->URLSegment])
                ->exclude(['ID' => $this->owner->ID]);
            if ($checkForDuplicatesURLSegments->count() > 0) {
                return false;
            }
        }
    }
}
