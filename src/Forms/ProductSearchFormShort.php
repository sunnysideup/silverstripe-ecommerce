<?php

namespace Sunnysideup\Ecommerce\Forms;

use Config;


class ProductSearchFormShort extends ProductSearchForm
{
    public function __construct($controller, $name, $nameOfProductsBeingSearched = '', $productsToSearch = null)
    {
        $this->isShortForm = true;
        parent::__construct($controller, $name, $nameOfProductsBeingSearched, $productsToSearch);

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: Session:: (case sensitive)
  * NEW: SilverStripe\Control\Controller::curr()->getRequest()->getSession()-> (COMPLEX)
  * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly. 
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        $oldData = SilverStripe\Control\Controller::curr()->getRequest()->getSession()->get(Config::inst()->get('ProductSearchForm', 'form_data_session_variable'));
        if ($oldData && (is_array($oldData) || is_object($oldData))) {
            //$this->loadDataFrom($oldData);
        }
    }
}

