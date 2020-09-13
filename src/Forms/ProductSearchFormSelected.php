<?php

namespace Sunnysideup\Ecommerce\Forms;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;

class ProductSearchFormSelected extends ProductSearchForm
{
    protected $nameOfProductsBeingSearched = '';

    protected $productsToSearch = null;
    /**
     * ProductsToSearch can be left blank to search all products.
     *
     * @param Controller              $controller                  - associated controller
     * @param string                  $name                        - name of form
     * @param string                  $nameOfProductsBeingSearched - name of the products being search (also see productsToSearch below)
     * @param DataList|array|null     $productsToSearch            (see comments above)
     */
    public function __construct($controller, $name, $nameOfProductsBeingSearched, $productsToSearch)
    {
        parent::__construct($controller, $name);
        $this->nameOfProductsBeingSearched = $nameOfProductsBeingSearched;
        $this->productsToSearch = $productsToSearch;
        //set basics
        $productsToSearchCount = 0;
        if ($this->productsToSearch) {
            if (is_array($this->productsToSearch)) {
                $productsToSearchCount = count($this->productsToSearch);
            } elseif ($productsToSearch instanceof DataList) {
                $productsToSearchCount = $this->productsToSearch->count();
            } else {
                user_error('Bad list provided');
            }
        }
        if ($productsToSearchCount) {
            $this->getFields()->push(
                CheckboxField::create('SearchOnlyFieldsInThisSection', _t('ProductSearchForm.ONLY_SHOW', 'Only search in') . ' <i>' . $nameOfProductsBeingSearched . '</i> ', true)
            );
        }
    }

    protected function getResultsPage()
    {
        return $this->controller->dataRecord;
    }

    protected function createBaseList()
    {
        parent::createBaseList();
        if (! empty($data['SearchOnlyFieldsInThisSection'])) {
            if (! $this->productsToSearch) {
                $controller = Controller::curr();
                if ($controller) {
                    $this->productsToSearch = $controller->Products();
                }
            }
            if ($this->productsToSearch instanceof DataList) {
                $this->productsToSearch = $this->productsToSearch->map('ID', 'ID')->toArray();
            }
            //last resort
            if ($this->productsToSearch) {
                $this->baseList = $this->baseList->filter(['ID' => $this->productsToSearch]);
                if ($this->debug) {
                    $this->debugOutput('<hr /><h3>PRODUCTS TO SEARCH</h3><pre>' . print_r($this->productsToSearch->count(), 1) . '</pre>');
                }
            }
        }

    }

}
