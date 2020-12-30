<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

use Sunnysideup\Ecommerce\Config\EcommerceConfig;

use Sunnysideup\Ecommerce\Pages\ProductGroup;

/**
 * provides data on the user
 */
abstract class BaseClass
{
    use Injectable;
    use Configurable;

    private static $options = [];

    /**
     *
     * @var SS_List
     */
    protected $finalProductList = null;

    /**
     *
     * @var SS_List
     */
    protected $products = null;

    public function __construct($finalProductList)
    {
        $this->finalProductList = $finalProductList;
        $this->products = $this->finalProductList->getProducts();
    }

    abstract public function apply($param = null) :SS_List;

    public function getOptions() : array
    {
        return Config::inst()->get(get_called_class(), 'options');
    }

    public function getOptionsMap() : array
    {
        $options = $this->getOptions();
        $map = [];
        foreach($options as $key => $values) {
            $map[$key] = $values['Title'];
        }

        return $map;
    }

    public function getOptionsList(string $currentKey, ?bool $ajaxify = true) : array
    {
        $list = new ArrayList();
        $options = $this->getOptionsMap();
        if ($options) {
            foreach ($options as $key=> $obj) {
                $title = $obj['Title'];
                $isCurrent = $currentKey === $key;
                $obj = new ArrayData(
                    [
                        'Current' => $isCurrent ? true : false,
                        'LinkingMode' => $isCurrent ? 'current' : 'link',
                        'Ajaxify' => $ajaxify,
                    ]
                );
                $list->push($obj);
            }
        }

        return $list;
    }

    protected function checkOption($option, ?string $returnValue = 'SQL', ?string $defaultOption = 'default')
    {
        // an array we leave alone...
        if(is_array($option)) {
            return $option;
        }
        if(! $option) {
            $option = $defaultOption;
        }
        if(is_string($option)) {
            $options = $this->getOptions();
            if (isset($options[$option][$returnvalue])) {
                return $options[$option][$returnvalue];
            } else {
                if($option !== $defaultOption) {
                    return $this->checkOption($defaultOption, $returnValue, $defaultOption);
                }
            }
        }

        return $option;
    }


    public function getTitle($param = null) : string
    {
        return $this->checkOption($param, 'Title');
    }


}
