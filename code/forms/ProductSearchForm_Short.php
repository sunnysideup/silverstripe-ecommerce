<?php

class ProductSearchForm_Short extends ProductSearchForm
{

    public function __construct($controller, $name, $nameOfProductsBeingSearched = "", $productsToSearch = null)
    {
        $this->isShortForm = true;
        parent::__construct($controller, $name, $nameOfProductsBeingSearched, $productsToSearch);
        $oldData = Session::get(Config::inst()->get("ProductSearchForm", "form_data_session_variable"));
        if ($oldData && (is_array($oldData) || is_object($oldData))) {
            $this->loadDataFrom($oldData);
        }
    }
}
