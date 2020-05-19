<?php

namespace Sunnysideup\Ecommerce\Forms\Fields;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\TreeDropdownField;

/**
 * TreeDropdown-like field that gives you a tree of items including an empty field, using ajax.
 * Author: Marijn Kampf www.exadium.com
 * Date:                        24 Nov 2009
 * Version:                2.2
 * Revision date:    9 May 2011
 * Changes:                Fixed ajax sub tree request
 * Revision date:    8 October 2010
 * Changes:                Changed $this->postTree to OptionalTreeDropdownField::$postTree to avoid Undefined error
 * Revision date:    17 June 2010
 * Changes:                Updated to work with SilverStripe 2.4, tree function added.
 */
class OptionalTreeDropdownField extends TreeDropdownField
{
    private static $allowed_actions = [
        'tree',
    ];

    /**
     * Define once rather than defining same line twice.
     */
    private static $postTree = '</ul>';

    /**
     * Helper function to return the header (rather than defining same line twice).
     */
    public function preTree()
    {
        return '<ul class="tree"><li id="" class="l"><a>' . _t('OptionalTreeDropdownField.NONE', '(None)') . '</a>';
    }

    public function getField($field)
    {
        return $this->{$field};
    }

    /**
     * Get the whole tree of a part of the tree via an AJAX request with empty / none item prepended.
     *
     * @param SS_HTTPRequest $request
     *
     * @return string
     *                for version 2.4 and later
     */
    public function tree(HTTPRequest $request)
    {
        if ($request->latestparam('ID')) {
            return parent::tree($request);
        }
        return $this->preTree() . parent::tree($request) . self::$postTree;
    }
}
