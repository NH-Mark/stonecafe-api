<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\ModifierGroup;
use Illuminate\Http\Request;


class MenuItemModifierGroupController extends Controller
{

    public function update(
        Request $request,
        MenuItem $menuItem,
        ModifierGroup $modifierGroup
    ) {


        $data = $request->validate([

            'selection_type' => [
                'required',
                'in:single,multiple'
            ],

            'required' => [
                'required',
                'boolean'
            ],

            'min_selection' => [
                'nullable',
                'integer',
                'min:0'
            ],

            'max_selection' => [
                'nullable',
                'integer',
                'min:1'
            ],

        ]);



        if(
            $data['selection_type'] === 'single'
        ){

            $data['min_selection'] =
                $data['required']
                    ? 1
                    : 0;


            $data['max_selection'] = 1;

        }



        $menuItem
            ->modifierGroups()
            ->updateExistingPivot(
                $modifierGroup->id,
                $data
            );



        return response()->json([

            'message'=>'Modifier group updated'

        ]);

    }

}