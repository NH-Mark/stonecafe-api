<?php

namespace App\Services;

use App\Models\Modifier;

class ModifierService
{
    public function create(array $data): Modifier
    {
        return Modifier::create($data);
    }

    public function update(
        Modifier $modifier,
        array $data
    ): Modifier {

        $modifier->update($data);

        return $modifier->refresh();
    }
}