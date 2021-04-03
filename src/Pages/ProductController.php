<?php

namespace Sunnysideup\Ecommerce\Pages;

use PageController;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Security\Security;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\Fields\EcomQuantityField;
use SilverStripe\ORM\DataList;

class ProductController extends PageController
{
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
            if ($record = $this->getVersionOfBuyable($this->ID, $version)) {
                //we check again, because we may actually get the same version back...
                if ($record->Version !== $this->Version) {
                    $this->record = $record;
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
        Config::nest();
        Config::modify()->update(SSViewer::class, 'theme_enabled', true);
        $html = $this->RenderWith('Sunnysideup\Ecommerce\Includes\ProductGroupItemMoreDetail');
        Config::unnest();

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
            $product = Product::get()->byID($this->ID);
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
     *
     * @return bool
     */
    public function IsOlderVersion() : bool
    {
        return ! $this->isCurrentVersion;
    }

    /**
     * This method can be extended to show products in the side bar.
     *
     * @return \SilverStripe\ORM\DataList|null
     */
    public function SidebarProducts() : ?DataList
    {
        return null;
    }

    /**
     * This method can be extended to show products in the side bar.
     *
     * @return Product|null
     */
    public function NextProduct()
    {
        $array = $this->getListOfIDs();
        foreach ($array as $key => $id) {
            $id = (int) $id;
            if ($id === $this->ID) {
                if (isset($array[$key + 1])) {
                    return Product::get()->byID((int) $array[$key + 1]);
                }
            }
        }

        return null;
    }

    /**
     * This method can be extended to show products in the side bar.
     *
     * @return Product|null
     */
    public function PreviousProduct()
    {
        $array = $this->getListOfIDs();
        $previousID = 0;
        foreach ($array as $id) {
            $id = (int) $id;
            if ($id === $this->ID) {
                return Product::get()->byID($previousID);
            }
            $previousID = $id;
        }

        return null;
    }

    /**
     * This method can be extended to show products in the side bar.
     *
     * @return bool
     */
    public function HasPreviousOrNextProduct() : bool
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
            $arrayOfIDs = explode(',', $listOfIDs);
            if (is_array($arrayOfIDs)) {
                return $arrayOfIDs;
            }
        }

        return [];
    }
}
