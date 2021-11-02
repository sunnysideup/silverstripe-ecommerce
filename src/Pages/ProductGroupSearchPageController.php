<?php

namespace Sunnysideup\Ecommerce\Pages;

class ProductGroupSearchPageController extends ProductGroupController
{
    public function getSearchFilterHeader(): string
    {
        return _t('Ecommerce.SEARCH_ALL_PRODUCTS', 'Search all products');
    }

    protected function setSearchString()
    {
        $params = $this->getUserPreferencesClass()->getCurrentUserPreferencesParams('SEARCHFILTER');
        if (! empty($params)) {
            ProductGroup::set_search_string_for_base_list(
                $this->ID,
                $this->getUserPreferencesClass()->getCurrentUserPreferencesParams('SEARCHFILTER')
            );
        }
    }
}
