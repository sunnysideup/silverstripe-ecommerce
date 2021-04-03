<?php

namespace Sunnysideup\Ecommerce\Model\Extensions;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Convert;

use SilverStripe\Core\Extension;

use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Controller for ErrorPages.
 * @package cms
 */
class ErrorPageExtension extends Extension
{
    /**
     * @throws HTTPResponse_Exception
     * @param HTTPRequest $request
     */
    public function onBeforeHTTPError404($request)
    {
        $product = $this->urlToProduct($request);
        if ($product) {
            $response = new HTTPResponse();
            $dest = $product->Link();
            $response->redirect(Director::absoluteURL($dest), '302');

            throw new HTTPResponse_Exception($response);
        }
    }

    /**
     * @param  integer $request
     * @return Product
     */
    protected function urlToProduct($request)
    {
        $path = '';
        $url = parse_url($request->getURL());
        if (isset($url['path'])) {
            $path = str_replace('/', '', $url['path']);
        }
        if ($path) {
            return Product::get()->filter(['InternalItemID' => Convert::raw2sql($path)])->first();
        }
    }
}
