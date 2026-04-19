<?php

namespace Sunnysideup\Ecommerce\Pages;

use Override;

/**
 * Class \Sunnysideup\Ecommerce\Pages\ProductGroupSearchPageController
 *
 * @property ProductGroupSearchPage $dataRecord
 * @method ProductGroupSearchPage data()
 * @mixin ProductGroupSearchPage
 */
class ProductGroupSearchPageController extends ProductGroupController
{
    #[Override]
    public function getSearchFilterHeader(): string
    {
        return _t('Ecommerce.SEARCH_ALL_PRODUCTS', 'Search all Products');
    }

    #[Override]
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
