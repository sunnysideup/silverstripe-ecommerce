<?php

namespace Sunnysideup\Ecommerce\Forms;

use SilverStripe\Core\Config\Config;

class ProductSearchFormShort extends ProductSearchForm
{
    public function __construct($controller, $name, $nameOfProductsBeingSearched = '', $productsToSearch = null)
    {
        $this->isShortForm = true;

        parent::__construct($controller, $name, $nameOfProductsBeingSearched, $productsToSearch);

        $session = $controller->getRequest()->getSession();
        $oldData = $session->get(Config::inst()->get(ProductSearchForm::class, 'form_data_session_variable'));

        if ($oldData && (is_array($oldData) || is_object($oldData))) {
            $this->loadDataFrom($oldData);
        }
    }
}
