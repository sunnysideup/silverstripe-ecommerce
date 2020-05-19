<?php


class ProductGroupSearchPageController extends ProductGroupController
{
    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $allowed_actions = [
        'debug' => 'ADMIN',
        'filterforgroup' => true,
        'ProductSearchForm' => true,
        'searchresults' => true,
        'resetfilter' => true,
    ];

    public function init()
    {
        parent::init();
        $array = $this->searchResultsArrayFromSession();
        if (count($array) > 1) {
            $this->isSearchResults = true;
        }
    }

    /**
     * returns child product groups for use in
     * 'in this section'. For example the vegetable Product Group
     * May have listed here: Carrot, Cabbage, etc...
     *
     * @return ArrayList (ProductGroups)
     */
    public function MenuChildGroups()
    {
        return;
    }

    public function ProductsShowable($extraFilter = null, $alternativeSort = null, $alternativeFilterKey = '')
    {
        $alternativeSort = $this->getSearchResultsDefaultSort($this->searchResultsArrayFromSession(), $alternativeSort);

        $this->allProducts = parent::ProductsShowable($extraFilter, $alternativeSort, $alternativeFilterKey);

        return $this->allProducts;
    }

    /**
     * The link that Google et al. need to index.
     * @return string
     */
    public function CanonicalLink()
    {
        $link = $this->Link();
        $this->extend('UpdateCanonicalLink', $link);

        return $link;
    }
}
