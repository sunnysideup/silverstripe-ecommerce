<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Core\Injector\Injectable;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;

class GetParentDetails
{
    use Injectable;

    protected object $objectWithParentID;

    // input
    protected string $expectedParentClassName;
    protected array $allowedClassNames;
    protected array $notAllowedClassNames;
    protected string $sortField = 'Sort';

    // output
    protected string $parentsTitle;

    protected array $parentSortArray;

    public function setSortField(string $sortField): self
    {
        $this->sortField = $sortField;
        return $this;
    }

    public static function format_sort_numbers(int $number, int $groupSize = 5, string $delimiter = '-'): string
    {
        // Convert the number to a string
        $numberStr = (string)$number;

        // Calculate the number of leading zeroes needed
        $remainder = strlen($numberStr) % $groupSize;
        if ($remainder !== 0) {
            $padding = $groupSize - $remainder;
            $numberStr = str_pad($numberStr, strlen($numberStr) + $padding, '0', STR_PAD_LEFT);
        }

        // Split the string into chunks of the specified size
        $chunks = str_split($numberStr, $groupSize);

        // Join the chunks with the specified delimiter
        return implode($delimiter, $chunks);
    }

    public function setAllowedClassNames(array|string $allowedClassNames): self
    {
        if (is_string($allowedClassNames)) {
            $allowedClassNames = [$allowedClassNames];
        }
        $this->allowedClassNames = $allowedClassNames;
        return $this;
    }

    public function setNotAllowedClassNames(array|string $notAllowedClassNames): self
    {
        if (is_string($notAllowedClassNames)) {
            $notAllowedClassNames = [$notAllowedClassNames];
        }
        $this->notAllowedClassNames = $notAllowedClassNames;
        return $this;
    }


    public function __construct(object $objectWithParentID, string $expectedParentClassName)
    {
        $this->expectedParentClassName = $expectedParentClassName;
        $this->objectWithParentID = $objectWithParentID;
        if (!isset($this->objectWithParentID->ParentID)) {
            user_error('ParentID not set on object', E_USER_ERROR);
        }
    }

    public function run(): self
    {
        $sortField = $this->sortField;
        $expectedParentClassName = $this->expectedParentClassName;
        $obj = $this->objectWithParentID;
        $this->parentSortArray = [sprintf('%05d', $obj->$sortField)];
        $parentTitleArray = [];
        while ($obj && $obj->ParentID) {

            $obj = $expectedParentClassName::get_by_id((int) $obj->ParentID - 0);
            if ($obj) {
                $this->parentSortArray[] = sprintf('%05d', $obj->$sortField);
                if (! empty($this->notAllowedClassNames) > 0) {
                    foreach ($this->notAllowedClassNames as $notAllowedClassName) {
                        if (is_a($obj, EcommerceConfigClassNames::getName($notAllowedClassName))) {
                            $obj = null;
                            break 2;
                        }
                    }
                }
                if (! empty($this->allowedClassNames) === 0) {
                    foreach ($this->allowedClassNames as $allowedClassName) {
                        if (is_a($obj, EcommerceConfigClassNames::getName($allowedClassName))) {
                            $parentTitleArray[] = $obj->Title;
                        } else {
                            $obj = null;
                            break 2;
                        }
                    }
                } else {
                    $parentTitleArray[] = $obj->Title;
                }
            }
        }

        $this->parentSortArray = array_reverse($this->parentSortArray);
        $this->parentsTitle = '';
        if ([] !== $parentTitleArray) {
            $this->parentsTitle = implode(' / ', $parentTitleArray);
        }
        return $this;
    }

    public function getParentsTitle(): string
    {
        return $this->parentsTitle;
    }

    public function getParentSortArray(): array
    {
        return $this->parentSortArray;
    }

}
