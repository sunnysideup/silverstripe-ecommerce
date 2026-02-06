<?php

namespace Sunnysideup\Ecommerce\Model\Extensions;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\ErrorPage\ErrorPage;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * Controller for ErrorPages.
 *
 * @property \SilverStripe\Control\Controller|\SilverStripe\Forms\Form|\Sunnysideup\Ecommerce\Model\Extensions\ErrorPageExtension $owner
 */
class ErrorPageExtension extends Extension
{
    /**
     * @param HTTPRequest $request
     *
     * @throws HTTPResponse_Exception
     */
    public function onBeforeHTTPError404($request)
    {
        $product = $this->urlToProduct($request);
        if ($product) {
            $getVars = $request->getVars();
            $dest = $product->Link() . '?' . http_build_query($getVars);

            $response = new HTTPResponse();
            $response->redirect(Director::absoluteURL($dest), '302');

            throw new HTTPResponse_Exception($response);
        }
    }

    /**
     * @param HTTPRequest $request
     *
     * @throws HTTPResponse_Exception
     */
    public function onBeforeHTTPError403($request)
    {
        $errorPage = ErrorPage::response_for(403);
        if ($errorPage) {
            $response = new HTTPResponse();
            $response->setStatusCode(403);
            $response->setBody($errorPage->getBody());
            $response->addHeader('X-Error-Page', '403');
            throw new HTTPResponse_Exception($response);
        }
    }

    /**
     * @param HTTPRequest $request
     *
     * @return null|Product
     */
    protected function urlToProduct($request)
    {
        $path = '';
        $url = parse_url($request->getURL());
        if (isset($url['path'])) {
            $path = str_replace('/', '', $url['path']);
        }
        if ($path !== '' && $path !== '0') {
            return Product::get()
                ->filter(['InternalItemID' => Convert::raw2sql($path)])
                ->first()
            ;
        }

        return null;
    }
}
