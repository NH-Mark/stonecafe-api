<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\FoodSymbol;
use  App\Http\Resources\FoodSymbolResource;



class FoodSymbolController extends Controller
{

    public function __construct(
        
    )
    {

    }



    public function index()
    {

        $food_symbols =
            FoodSymbol::get();
        return FoodSymbolResource::collection(
            $food_symbols
        );

    }



}