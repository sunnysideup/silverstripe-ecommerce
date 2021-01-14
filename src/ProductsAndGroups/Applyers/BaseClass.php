<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use Sunnysideup\Ecommerce\Pages\ProductGroupController;

/**
 * provides data on the user
 */
abstract class BaseClass
{
    use Injectable;
    use Configurable;

    /**
     * @var SS_List
     */
    protected $finalProductList = null;

    /**
     * @var SS_List
     */
    protected $products = null;

    private static $options = [];

    public function __construct($finalProductList)
    {
        $this->finalProductList = $finalProductList;
        $this->products = $this->finalProductList->getProducts();
    }

    abstract public function apply($param = null) : self;

    public function getOptions(): array
    {
        return Config::inst()->get(static::class, 'options');
    }

    public function getProducts()
    {
        return $this->products;
    }

    public function getOptionsMap(): array
    {
        $options = $this->getOptions();
        $map = [];
        foreach ($options as $key => $values) {
            $map[$key] = $values['Title'];
        }

        return $map;
    }

    public function getOptionsList(string $linkTempalte, ?string $currentKey = '', ?bool $ajaxify = true): array
    {
        $list = new ArrayList();
        $options = $this->getOptionsMap();
        if ($options) {
            foreach ($options as $key => $arrayData) {
                $isCurrent = $currentKey === $key;
                $obj = new ArrayData(
                    [
                        'Title' => $arrayData['Title'],
                        'Current' => $isCurrent ? true : false,
                        'Link' => str_replace(ProductGroupController::GET_VAR_VALUE_PLACE_HOLDER, $key, $linkTemplate),
                        'LinkingMode' => $isCurrent ? 'current' : 'link',
                        'Ajaxify' => $ajaxify,
                    ]
                );
                $list->push($obj);
            }
        }

        return $list;
    }

    public function getTitle($param = null): string
    {
        return $this->checkOption($param, 'Title');
    }

    protected function checkOption($option, ?string $returnValue = 'SQL', ?string $defaultOption = 'default')
    {
        // an array we leave alone...
        if (is_array($option)) {
            return $option;
        }
        if (! $option) {
            $option = $defaultOption;
        }
        if (is_string($option)) {
            $options = $this->getOptions();
            if (isset($options[$option][$returnValue])) {
                return $options[$option][$returnValue];
            }
            if ($option !== $defaultOption) {
                return $this->checkOption($defaultOption, $returnValue, $defaultOption);
            }
        }

        return $option;
    }
}
