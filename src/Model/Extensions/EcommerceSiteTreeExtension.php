<?php

namespace Sunnysideup\Ecommerce\Model\Extensions;

use SilverStripe\CMS\Model\SiteTreeExtension;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigAjax;
use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * Class \Sunnysideup\Ecommerce\Model\Extensions\EcommerceSiteTreeExtension
 *
 * @property \SilverStripe\CMS\Model\SiteTree|\Sunnysideup\Ecommerce\Model\Extensions\EcommerceSiteTreeExtension $owner
 */
class EcommerceSiteTreeExtension extends SiteTreeExtension
{
    /**
     * returns the instance of EcommerceConfigAjax for use in templates.
     * In templates, it is used like this:
     * $AJAXDefinitions.TableID.
     *
     * @return EcommerceConfigAjax
     */
    public function AJAXDefinitions()
    {
        return EcommerceConfigAjax::get_one($this->owner);
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
        if ($this->getOwner()->IsEcommercePage()) {
            $link = $this->getOwner()->Link();
        } else {
            $link = EcommerceConfig::inst()->AccountPageLink();
        }

        return Controller::join_links(Director::absoluteBaseURL(), 'Security/login')
        . '?BackURL=' . urlencode($link);
    }

    public function augmentValidURLSegment()
    {
        if ($this->owner instanceof ProductGroup) {
            $checkForDuplicatesURLSegments = ProductGroup::get()
                ->filter(['URLSegment' => $this->getOwner()->URLSegment])
                ->exclude(['ID' => $this->getOwner()->ID])
            ;
            if ($checkForDuplicatesURLSegments->exists()) {
                return false;
            }
        }
        return null;
    }

    /**
     * returns a URL without the -2 or -3, at the end,
     * so that the URLSegment can be used as a code.
     *
     * @return string [description]
     */
    public function CleanURLSegment(): string
    {
        $urlSegment = $this->getOwner()->URLSegment;
        $x = 2;
        while ($x < 10) {
            if (substr((string) $urlSegment, -2) === '-' . $x) {
                return substr((string) $urlSegment, 0, -2);
            }
            ++$x;
        }

        return $urlSegment;
    }
}
