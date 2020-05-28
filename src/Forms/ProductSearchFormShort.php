<?php

namespace Sunnysideup\Ecommerce\Forms;

use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Controller;

class ProductSearchFormShort extends ProductSearchForm
{
    public function __construct($controller, $name, $nameOfProductsBeingSearched = '', $productsToSearch = null)
    {
        $this->isShortForm = true;
        parent::__construct($controller, $name, $nameOfProductsBeingSearched, $productsToSearch);

        $oldData = Controller::curr()->getRequest()->getSession()->get(Config::inst()->get(ProductSearchForm::class, 'form_data_session_variable'));
        if ($oldData && (is_array($oldData) || is_object($oldData))) {
            //$this->loadDataFrom($oldData);
        }
    }
}
