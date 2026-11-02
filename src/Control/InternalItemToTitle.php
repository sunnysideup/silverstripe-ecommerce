<?php

namespace Sunnysideup\Ecommerce\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use Sunnysideup\Ecommerce\Pages\Product;

/**
 * ShoppingCartController.
 *
 * Handles the modification of a shopping cart via http requests.
 * Provides links for making these modifications.
 */
class InternalItemToTitle extends Controller
{
    /**
     * @var string
     */
    private static $url_segment = 'ecommerceinternalitemtotitle';

    private static $allowed_actions = [
        'index' => 'ADMIN',
        'lookup' => 'ADMIN',
    ];

    public function index()
    {
        $form = new Form(
            $this,
            'lookup',
            new FieldList(
                HeaderField::create('Header', 'Lookup Product Title by Internal Item ID'),
                new TextareaField('InternalItemIDs', 'List product codes below, comma or line separated')
            ),
            new FieldList(
                new FormAction('lookup', 'Lookup')
            )
        );

        $form->setFormMethod('POST');
        return $this->renderWith(static::class, ['Form' => $form]);
    }

    public function lookup(HTTPRequest $request)
    {
        $title = $request->requestVar('InternalItemIDs');
        if (! is_string($title)) {
            $title = '';
        }
        // values are only ever used in ORM filters (parameterised) or HTML-escaped on output
        $title = str_replace(["\r", "\n", "\t"], ',', $title);
        $array = explode(',', $title);
        $array = array_map('trim', $array);
        $array = array_filter($array);
        $array = array_unique($array);
        $html = '
        <h1>Full Product Names ' . count($array) . '</h1>
        <p><a href="' . Convert::raw2att($this->Link()) . '">Try again</a></p>
        <ul>';
        foreach ($array as $code) {
            $product = Product::get()->filter(['InternalItemID' => $code])->first();
            if ($product) {
                $html .= '<li><a href="' . Convert::raw2att($product->CMSEditLink()) . '"></a><a href="' . Convert::raw2att($product->Link()) . '">' . Convert::raw2xml($product->FullName) . '</a></li>';
            } else {
                $html .= '<li>Product with code "' . Convert::raw2xml($code) . '" not found</li>';
            }
        }
        $html .= '</ul><br /><br />';

        return $this->renderWith(static::class, ['Form' => DBHTMLText::create_field('HTMLText', $html)]);

    }
}
