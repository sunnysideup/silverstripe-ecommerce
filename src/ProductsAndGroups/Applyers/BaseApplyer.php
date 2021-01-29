<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\FinalProductList;

/**
 * provides data on the user
 */
abstract class BaseApplyer
{
    use Injectable;
    use Configurable;

    private const SQL_PARAM_PLACEHOLDER = '[[PARAMS_GO_HERE]]';

    /**
     * final product list object, always present.
     * @var FinalProductList
     */
    protected $finalProductList = null;

    /**
     * @var SS_List
     */
    protected $products = null;

    protected $selectedOption = '';

    /**
     * @var string|array
     */
    protected $selectedOptionParams = '';

    private static $options = [];

    public function __construct($finalProductList = null)
    {
        if ($finalProductList) {
            ClassHelpers::check_for_instance_of($finalProductList, FinalProductList::class, true);
            $this->finalProductList = $finalProductList;
            $this->products = $this->finalProductList->getProducts();
        }
    }

    /**
     * manipulates the product lists.
     * @param string         $key     optional key
     * @param string|array   $params  optional params to go with key
     *
     * @return self
     */
    abstract public function apply(?string $key = null, $params = null): self;

    public function getOptions(): array
    {
        return Config::inst()->get(static::class, 'options');
    }

    public function getProducts()
    {
        return $this->products;
    }

    public function getSelectedOption(): string
    {
        return $this->selectedOption;
    }

    public function getSelectedOptionParams()
    {
        return $this->selectedOptionParams;
    }

    /**
     * dropdown list of options.
     * @return array
     */
    public function getOptionsMap(): array
    {
        $options = $this->getOptions();
        $map = [];
        foreach ($options as $key => $values) {
            $map[$key] = $values['Title'];
        }

        return $map;
    }

    /**
     * get the title for an option
     * @param  string        $key - e.g. default
     * @param  mixed         $params - optional
     * @return string
     */
    public function getTitle(?string $key = '', $params = null): string
    {
        return $this->checkOption($key, 'Title');
    }

    /**
     * get the sql for an option
     * @param  string        $key string, e.g. default.
     * @param  string|array  $params additional param for sql.
     *
     * @return string|array
     */
    public function getSql(?string $key = null, $params = null)
    {
        $sql = $this->checkOption($key, 'SQL');
        return str_replace($sql, self::SQL_PARAM_PLACEHOLDER, $params);
    }

    /**
     * get the RequiresData for an option
     * @param  string        $key string, e.g. default.
     * @return bool
     */
    public function getRequiresData(?string $key = null): bool
    {
        return $this->checkOption($key, 'RequiresData');
    }

    /**
     * get the sql for an option
     * @param  string        $key string, e.g. default.
     * @return bool
     */
    public function IsShowFullList(?string $key = null): bool
    {
        return $this->checkOption($key, 'IsShowFullList');
    }

    /**
     * check for one option. If no return value is specified then all of the options are returned.
     * @param  string $key        e.g. default
     * @param  string $returnValue   mixed
     * @param  string $defaultKey
     *
     * @return mixed
     */
    public function checkOption(?string $key = '', ?string $returnValue = 'SQL', ?string $defaultKey = 'default')
    {
        // an array we leave alone...
        if (! $key) {
            $key = $defaultKey;
        }
        if (is_string($key)) {
            $options = $this->getOptions();
            if (isset($options[$key])) {
                return $options[$key][$returnValue];
            }
            //backup!
            if ($key !== $defaultKey) {
                return $this->checkOption($defaultKey, $returnValue);
            }
        }

        return $key;
    }

    protected function applyStart(?string $key = null, $params = null)
    {
        $this->selectedOption = $key;
        $this->selectedOptionParams = $params;
    }

    protected function applyEnd(?string $key = null, $params = null)
    {
    }
}
