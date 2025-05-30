<?php

namespace Sunnysideup\Ecommerce\Pages;

use PageController;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Api\SetThemed;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\Fields\EcomQuantityField;

/**
 * Class \Sunnysideup\Ecommerce\Pages\ProductController
 *
 * @property \Sunnysideup\Ecommerce\Pages\Product $dataRecord
 * @method \Sunnysideup\Ecommerce\Pages\Product data()
 * @mixin \Sunnysideup\Ecommerce\Pages\Product
 */
class ProductController extends PageController
{

    private static float $price_range_lower_multiplier = 0.85;
    private static float $price_range_upper_multiplier = 1.15;


    /**
     * is this the current version?
     *
     * @var bool
     */
    protected $isCurrentVersion = true;

    private static $allowed_actions = [
        'viewversion',
        'ajaxview',
        'addproductfromform',
        'debug' => 'ADMIN',
    ];

    /**
     * view earlier version of a product
     * returns error or changes datarecord to earlier version
     * if the ID does not match the Page then we look for the variation.
     */
    public function viewversion(HTTPRequest $request)
    {
        $version = (int) $request->param('ID') - 0;
        $currentVersion = $this->Version;
        if ($currentVersion !== $version) {
            $record = $this->getVersionOfBuyable($this->ID, $version);
            if ($record) {
                //we check again, because we may actually get the same version back...
                if ($record->Version !== $this->Version) {
                    $this->dataRecord = $record;
                    $this->dataRecord->AllowPurchase = false;
                    $this->AllowPurchase = false;
                    $this->isCurrentVersion = false;
                    $this->Title .= _t('Product.OLDERVERSION', ' - Older Version');
                    $this->MetaTitle .= _t('Product.OLDERVERSION', ' - Older Version');
                }
            } else {
                return $this->httpError(404);
            }
        }

        return [];
    }

    /**
     * Standard SS method
     * Returns a snippet when requested by ajax.
     */
    public function ajaxview(HTTPRequest $request)
    {
        SetThemed::start();
        $html = $this->RenderWith('Sunnysideup\Ecommerce\Includes\ProductGroupItemMoreDetail');
        SetThemed::end();

        return $html;
    }

    /**
     * returns a form for adding products to cart.
     *
     * @return Form|string
     */
    public function AddProductForm()
    {
        if ($this->canPurchase()) {
            $farray = [];
            $fields = new FieldList($farray);
            $fields->push(new NumericField('Quantity', 'Quantity', 1)); //TODO: perhaps use a dropdown instead (elimiates need to use keyboard)
            $actions = new FieldList(
                new FormAction('addproductfromform', _t('Product.ADDLINK', 'Add this item to cart'))
            );
            $requiredFields = ['Quantity'];
            $validator = new RequiredFields($requiredFields);

            return new Form($this, 'AddProductForm', $fields, $actions, $validator);
        }

        return _t('Product.PRODUCTNOTFORSALE', 'Product not for sale');
    }

    /**
     * executes the AddProductForm.
     */
    public function addproductfromform(array $data, Form $form)
    {
        if (! $this->IsInCart()) {
            $quantity = round($data['Quantity'], $this->QuantityDecimals());
            if (! $quantity) {
                $quantity = 1;
            }
            $product = Product::get_by_id($this->ID);
            if ($product) {
                ShoppingCart::singleton()->addBuyable($product, $quantity);
            }
            if ($this->IsInCart()) {
                $msg = _t('Order.SUCCESSFULLYADDED', 'Added to cart.');
                $status = 'good';
            } else {
                $msg = _t('Order.NOTADDEDTOCART', 'Not added to cart.');
                $status = 'bad';
            }
            if (Director::is_ajax()) {
                return ShoppingCart::singleton()->setMessageAndReturn($msg, $status);
            }
            $form->sessionMessage($msg, $status);
            $this->redirectBack();
        } else {
            return EcomQuantityField::create($this);
        }
    }

    /**
     * Is this an older version?
     */
    public function IsOlderVersion(): bool
    {
        return ! $this->isCurrentVersion;
    }

    /**
     * This method can be extended to show products in the side bar.
     */
    public function SidebarProducts(): ?DataList
    {
        return null;
    }

    /**
     * This method can be extended to show products in the side bar.
     *
     * @return null|Product
     */
    public function NextProduct()
    {
        $array = $this->getListOfIDs();
        foreach ($array as $key => $id) {
            $id = (int) $id;
            if ($id === $this->ID) {
                if (isset($array[$key + 1])) {
                    return Product::get_by_id((int) $array[$key + 1]);
                }
            }
        }

        return null;
    }

    /**
     * This method can be extended to show products in the side bar.
     *
     * @return null|Product
     */
    public function PreviousProduct()
    {
        $array = $this->getListOfIDs();
        $previousID = 0;
        foreach ($array as $id) {
            $id = (int) $id;
            if ($id === $this->ID) {
                return Product::get_by_id($previousID);
            }
            $previousID = $id;
        }

        return null;
    }

    /**
     * This method can be extended to show products in the side bar.
     */
    public function HasPreviousOrNextProduct(): bool
    {
        return $this->PreviousProduct() || $this->NextProduct();
    }

    public function debug()
    {
        $member = Security::getCurrentUser();
        if (! $member || ! $member->IsShopAdmin()) {
            $messages = [
                'default' => 'You must login as an admin to access debug functions.',
            ];
            Security::permissionFailure($this, $messages);
        }

        return $this->dataRecord->debug();
    }

    /**
     * Standard SS method.
     */
    protected function init()
    {
        parent::init();
        Requirements::themedCSS('client/css/Product');
        Requirements::javascript('sunnysideup/ecommerce: client/javascript/EcomProducts.js');
    }

    /**
     * returns an array of product IDs, as saved in the last
     * ProductGroup view (saved using session).
     *
     * @return array
     */
    protected function getListOfIDs()
    {
        $listOfIDs = $this->getRequest()->getSession()->get(EcommerceConfig::get(ProductGroup::class, 'session_name_for_product_array'));
        if ($listOfIDs) {
            $arrayOfIDs = explode(',', (string) $listOfIDs);
            if (is_array($arrayOfIDs)) {
                return $arrayOfIDs;
            }
        }

        return [];
    }

    public function CompareToSimilarProductsLink(): string
    {
        return $this->Parent()->Link() . '?searchfilter=MinimumPrice~' . $this->getLowerRange() . '...MaximumPrice~' . $this->getUpperRange() . '...OnlyThisSection~1';
    }



    protected function getLowerRange(): int
    {
        $lower = $this->Price * $this->Config()->get('price_range_lower_multiplier');
        return (int) floor($lower / 10) * 10;
    }

    /**
     * Get the rounded upper bound (+10%) for a given price.
     */
    protected function getUpperRange(): int
    {
        $upper = $this->Price * $this->Config()->get('price_range_upper_multiplier');
        return (int) ceil($upper / 10) * 10;
    }
}
