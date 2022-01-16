<?php

namespace Sunnysideup\Ecommerce\Control;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;

use SilverStripe\Core\ClassInfo;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\FieldType\DBField;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;

class QuickUpdates extends Controller
{
    private static $url_segment = 'admin/ecommerce/quick-updates';

    private static $allowed_actions = [
        'index' => 'SHOPASSISTANTS',
        'do' => 'SHOPASSISTANTS',
        'MyForm' => 'SHOPASSISTANTS',
    ];

    public function MyForm()
    {
        $fields = new FieldList(
        );

        $actions = new FieldList(
            FormAction::create('do')->setTitle('Submit')
        );

        $required = new RequiredFields();

        return new Form($this, 'MyForm', $fields, $actions, $required);
    }

    public function Menu() : ArrayList
    {
        $classes = ClassInfo::subclassesFor(static::class, false);
        $al = ArrayList::create();
        foreach($classes as $class) {
            $obj = Injector::inst()->get($class);
            $al->push(
                ArrayData::create(
                    [
                        'Title' => $obj->getTitle(),
                        'Link' => $obj->Link(),
                    ]
                )
            );
        }
        return $al;
    }


    public function index($request)
    {
        return $this->renderWith(static::class);
    }

    public function do($request)
    {
        return $this->renderWith(static::class);
    }


    protected function init()
    {
        parent::init();
        Requirements::javascript('silverstripe/admin: thirdparty/jquery/jquery.js');
    }
}
