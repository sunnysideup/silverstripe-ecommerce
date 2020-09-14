<?php

namespace Sunnysideup\Ecommerce\Forms;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;

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
