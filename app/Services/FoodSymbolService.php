<?php

namespace App\Services;

use App\Models\FoodSymbol;


class FoodSymbolService
{

    public function create(array $data):FoodSymbol
    {

        return FoodSymbol::create(
            $data
        );

    }



    public function update(
        FoodSymbol $symbol,
        array $data
    ):FoodSymbol {


        $symbol->update(
            $data
        );


        return $symbol->refresh();

    }



    public function delete(
        FoodSymbol $symbol
    ):bool {

        return $symbol->delete();

    }



    public function active()
    {

        return FoodSymbol::where(
            'active',
            true
        )
        ->orderBy('name')
        ->get();

    }

}