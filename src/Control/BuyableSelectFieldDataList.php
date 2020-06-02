<?php

namespace Sunnysideup\Ecommerce\Control;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Versioned\Versioned;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig;

class BuyableSelectFieldDataList extends Controller
{
    protected $fieldsToSearch = [
        'InternalItemID',
        'Title',
        'FullName',
        'MetaDescription',
    ];

    private static $allowed_actions = [
        'json',
    ];

    private static $url_segment = 'ecommercebuyabledatalist';

    public function Link($action = null)
    {
        $URLSegment = Config::inst()->get(get_class($this), 'url_segment');
        if (! $URLSegment) {
            $URLSegment = static::class;
        }

        return Controller::join_links(
            Director::BaseURL(),
            $URLSegment,
            $action
        );
    }

    /**
     * returns JSON in this format:
     * Array(
     *  ClassName => $className,
     *  ID => $obj->ID,
     *  Version => $obj->Version,
     *  Title => $name
     * );.
     *
     * @param SS_HTTPRequest $request
     *
     * @return string (JSON)
     */
    public function json(HTTPRequest $request)
    {
        $countOfSuggestions = $request->requestVar('countOfSuggestions');
        $term = Convert::raw2sql($request->requestVar('term'));
        $arrayOfBuyables = EcommerceConfig::get(EcommerceDBConfig::class, 'array_of_buyables');
        $arrayOfAddedItemIDsByClassName = [];
        $lengthOfFieldsToSearch = count($this->fieldsToSearch);
        $lenghtOfBuyables = count($arrayOfBuyables);
        $array = [];
        //search by InternalID ....
        $absoluteCount = 0;
        $buyables = [];
        foreach ($arrayOfBuyables as $key => $buyableClassName) {
            $buyables[$key] = [];
            $singleton = singleton($buyableClassName);
            $buyables[$key]['Singleton'] = $singleton;
            $buyables[$key]['ClassName'] = $buyableClassName;
            $buyables[$key]['TableName'] = $buyableClassName;

            /**
             * ### @@@@ START REPLACEMENT @@@@ ###
             * WHY: automated upgrade
             * OLD:  Object:: (case sensitive)
             * NEW:  SilverStripe\\Core\\Injector\\Injector::inst()-> (COMPLEX)
             * EXP: Check if this is the right implementation, this is highly speculative.
             * ### @@@@ STOP REPLACEMENT @@@@ ###
             */
            if (is_a($singleton, EcommerceConfigClassNames::getName(SiteTree::class))) {
                if (Versioned::get_stage() === 'Live') {
                    $buyables[$key]['TableName'] .= '_Live';
                }
            }
        }
        unset($arrayOfBuyables);
        while ((count($array) <= $countOfSuggestions) && ($absoluteCount < 30)) {
            ++$absoluteCount;
            for ($i = 0; $i < $lengthOfFieldsToSearch; ++$i) {
                $fieldName = $this->fieldsToSearch[$i];
                for ($j = 0; $j < $lenghtOfBuyables; ++$j) {
                    $buyableArray = $buyables[$j];
                    $singleton = $buyableArray['Singleton'];

                    /**
                     * ### @@@@ START REPLACEMENT @@@@ ###
                     * WHY: automated upgrade
                     * OLD: $className (case sensitive)
                     * NEW: $className (COMPLEX)
                     * EXP: Check if the class name can still be used as such
                     * ### @@@@ STOP REPLACEMENT @@@@ ###
                     */
                    $className = $buyableArray['ClassName'];
                    $tableName = $buyableArray['TableName'];

                    /**
                     * ### @@@@ START REPLACEMENT @@@@ ###
                     * WHY: automated upgrade
                     * OLD: $className (case sensitive)
                     * NEW: $className (COMPLEX)
                     * EXP: Check if the class name can still be used as such
                     * ### @@@@ STOP REPLACEMENT @@@@ ###
                     */
                    if (! isset($arrayOfAddedItemIDsByClassName[$className])) {

                        /**
                         * ### @@@@ START REPLACEMENT @@@@ ###
                         * WHY: automated upgrade
                         * OLD: $className (case sensitive)
                         * NEW: $className (COMPLEX)
                         * EXP: Check if the class name can still be used as such
                         * ### @@@@ STOP REPLACEMENT @@@@ ###
                         */
                        $arrayOfAddedItemIDsByClassName[$className] = [-1 => -1];
                    }
                    if ($singleton->hasDatabaseField($fieldName)) {
                        // $where = "\"${fieldName}\" LIKE '%${term}%'
                        //         AND \"" . $tableName . '"."ID" NOT IN
                        //         AND "AllowPurchase" = 1';

                        /**
                         * ### @@@@ START REPLACEMENT @@@@ ###
                         * WHY: automated upgrade
                         * OLD: $className (case sensitive)
                         * NEW: $className (COMPLEX)
                         * EXP: Check if the class name can still be used as such
                         * ### @@@@ STOP REPLACEMENT @@@@ ###
                         */
                        $obj = $className::get()
                            ->filter([
                                $fieldName . ':PartialMatch' => $term,
                                'AllowPurchase' => 1,
                            ])

                            /**
                             * ### @@@@ START REPLACEMENT @@@@ ###
                             * WHY: automated upgrade
                             * OLD: $className (case sensitive)
                             * NEW: $className (COMPLEX)
                             * EXP: Check if the class name can still be used as such
                             * ### @@@@ STOP REPLACEMENT @@@@ ###
                             */
                            ->where("\"${tableName}\".\"ID\" NOT IN (" . implode(',', $arrayOfAddedItemIDsByClassName[$className]) . ')')
                            ->First();
                        if ($obj) {
                            //we found an object, we dont need to find it again.

                            /**
                             * ### @@@@ START REPLACEMENT @@@@ ###
                             * WHY: automated upgrade
                             * OLD: $className (case sensitive)
                             * NEW: $className (COMPLEX)
                             * EXP: Check if the class name can still be used as such
                             * ### @@@@ STOP REPLACEMENT @@@@ ###
                             */
                            $arrayOfAddedItemIDsByClassName[$className][$obj->ID] = $obj->ID;
                            //now we are only going to add it, if it is available!
                            if ($obj->canPurchase()) {
                                $useVariationsInstead = false;
                                if ($obj->hasExtension('ProductWithVariationDecorator')) {
                                    $variations = $obj->Variations();
                                    if ($variations->count()) {
                                        $useVariationsInstead = true;
                                    }
                                }
                                if (! $useVariationsInstead) {
                                    $name = $obj->FullName ?: $obj->getTitle();

                                    /**
                                     * ### @@@@ START REPLACEMENT @@@@ ###
                                     * WHY: automated upgrade
                                     * OLD: $className (case sensitive)
                                     * NEW: $className (COMPLEX)
                                     * EXP: Check if the class name can still be used as such
                                     * ### @@@@ STOP REPLACEMENT @@@@ ###
                                     */
                                    $array[$className . $obj->ID] = [

                                        /**
                                         * ### @@@@ START REPLACEMENT @@@@ ###
                                         * WHY: automated upgrade
                                         * OLD: $className (case sensitive)
                                         * NEW: $className (COMPLEX)
                                         * EXP: Check if the class name can still be used as such
                                         * ### @@@@ STOP REPLACEMENT @@@@ ###
                                         */
                                        'ClassName' => $className,
                                        'ID' => $obj->ID,
                                        'Version' => $obj->Version,
                                        'Title' => $name,
                                    ];
                                }
                            }
                        }
                    }
                    //echo $singleton->ClassName ." does not have $fieldName";
                }
            }
        }
        //remove KEYS
        $finalArray = [];
        $count = 0;
        foreach ($array as $item) {
            if ($count < $countOfSuggestions) {
                $finalArray[] = $item;
            }
            ++$count;
        }

        return $this->array2json($finalArray);
    }

    /**
     * converts an Array into JSON and formats it nicely for easy debugging.
     *
     * @param array $array
     *
     * @return JSON
     */
    protected function array2json(array $array)
    {
        return Convert::array2json($array);
    }
}
