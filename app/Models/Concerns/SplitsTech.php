<?php

namespace App\Models\Concerns;

trait SplitsTech
{
    /** @return list<string> */
    public function techList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->tech))));
    }
}
