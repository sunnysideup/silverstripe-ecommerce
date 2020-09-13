<?php

namespace Sunnysideup\Ecommerce\Forms;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;

class ProductSearchFormShort extends ProductSearchForm
{
    public function __construct($controller, $name, $nameOfProductsBeingSearched = '', $productsToSearch = null)
    {
        parent::__construct($controller, $name);
        $fields = FieldList::create(
            $shortKeywordField = TextField::create('Keyword', '')
        );
        $shortKeywordField->setAttribute('placeholder', _t('ProductSearchForm.SHORT_KEYWORD_PLACEHOLDER', 'search products ...'));
        $this->setFields($fields);

    }


}
