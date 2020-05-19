<?php


class ProductController extends Page_Controller
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
     * Standard SS method.
     */
    public function init()
    {
        parent::init();
        Requirements::themedCSS('Product', 'ecommerce');
        Requirements::javascript('ecommerce/javascript/EcomProducts.js');
    }

    /**
     * view earlier version of a product
     * returns error or changes datarecord to earlier version
     * if the ID does not match the Page then we look for the variation.
     *
     * @param SS_HTTPRequest $request
     */
    public function viewversion(SS_HTTPRequest $request)
    {
        $version = intval($request->param('ID')) - 0;
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
    public function ajaxview(SS_HTTPRequest $request)
    {
        Config::nest();
        Config::inst()->update('SSViewer', 'theme_enabled', true);
        $html = $this->renderWith('ProductGroupItemMoreDetail');
        Config::unnest();

        return $html;
    }

    /**
     * returns a form for adding products to cart.
     *
     * @return Form
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
     *
     * @param array $data
     * @param Form  $form
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
    public function IsOlderVersion()
    {
        return $this->isCurrentVersion ? false : true;
    }

    /**
     * This method can be extended to show products in the side bar.
     *
     * @return DataList (Products)
     */
    public function SidebarProducts()
    {
        return;
    }

    /**
     * This method can be extended to show products in the side bar.
     *
     * @return Product | Null
     */
    public function NextProduct()
    {
        $array = $this->getListOfIDs();
        foreach ($array as $key => $id) {
            $id = intval($id);
            if ($id === $this->ID) {
                if (isset($array[$key + 1])) {
                    return Product::get()->byID(intval($array[$key + 1]));
                }
            }
        }
    }

    /**
     * This method can be extended to show products in the side bar.
     *
     * @return Product | Null
     */
    public function PreviousProduct()
    {
        $array = $this->getListOfIDs();
        $previousID = 0;
        foreach ($array as $id) {
            $id = intval($id);
            if ($id === $this->ID) {
                return Product::get()->byID($previousID);
            }
            $previousID = $id;
        }

        return;
    }

    /**
     * This method can be extended to show products in the side bar.
     *
     * @return bool
     */
    public function HasPreviousOrNextProduct()
    {
        return $this->PreviousProduct() || $this->NextProduct() ? true : false;
    }

    public function debug()
    {
        $member = Member::currentUser();
        if (! $member || ! $member->IsShopAdmin()) {
            $messages = [
                'default' => 'You must login as an admin to access debug functions.',
            ];
            Security::permissionFailure($this, $messages);
        }

        return $this->dataRecord->debug();
    }

    /**
     * returns an array of product IDs, as saved in the last
     * ProductGroup view (saved using session).
     *
     * @return array
     */
    protected function getListOfIDs()
    {
        $listOfIDs = Session::get(EcommerceConfig::get('ProductGroup', 'session_name_for_product_array'));
        if ($listOfIDs) {
            $arrayOfIDs = explode(',', $listOfIDs);
            if (is_array($arrayOfIDs)) {
                return $arrayOfIDs;
            }
        }

        return [];
    }
}

