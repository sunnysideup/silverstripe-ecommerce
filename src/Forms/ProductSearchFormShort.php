<?php

namespace Sunnysideup\Ecommerce\Forms;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;

class ProductSearchFormShort extends ProductSearchForm
{
    public function __construct($controller, $name)
    {
        parent::__construct($controller, $name);
        $fields = FieldList::create(
            $this->Fields()->dataFieldByName('Keyword')
                ->setTitle('')
                ->setAttribute(
                    'placeholder',
                    _t('ProductSearchForm.SHORT_KEYWORD_PLACEHOLDER', 'search products ...')
                )
        );
        $this->setFields($fields);
    }
}
