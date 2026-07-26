<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class UploadController extends Controller
{

    public function store(Request $request)
    {

        $request->validate([
            'image'=>'required|image|max:2048'
        ]);


        $path = $request
            ->file('image')
            ->store(
                'uploads',
                'public'
            );


        return response()->json([

            'path'=>$path,

            'url'=>Storage::url($path)

        ]);

    }

}