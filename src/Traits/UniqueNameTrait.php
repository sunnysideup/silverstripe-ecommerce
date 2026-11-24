<?php

namespace Sunnysideup\Ecommerce\Traits;

trait UniqueNameTrait
{
    protected function generateTitle(): string
    {
        $title = (string) $this->Title;

        if ('' === $title) {
            $list  = $this->Products()->limit(5);
            $title = $list->exists()
                ? implode('; ', $list->column('Title'))
                : $this->defaultTitle();
        }

        // Fallback to generic name if nothing usable was produced
        if ('' === $title || '-' === $title || '-1' === $title) {
            $title = $this->defaultTitle();
        }

        // Ensure uniqueness — rebuild from $base, and check the CANDIDATE
        $base = $title;
        $x    = 1;
        while ($this->titleExists($title) && $x < 100) {
            $x++;
            $title = $base . ' (#' . $x . ')';
        }

        return $title;
    }

    protected function titleExists(?string $title = null): bool
    {
        $title ??= (string) $this->Title;

        return static::get()
            ->filter('Title', $title)
            ->exclude('ID', (int) $this->ID) // don't match self
            ->exists();
    }

}
