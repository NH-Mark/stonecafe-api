<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CustomerService;



class CustomerController extends Controller
{


    public function __construct(
        private CustomerService $service
    )
    {

    }





   public function find(
    string $phone
    )
    {

        $customer =
            $this->service->findByPhone(
                $phone
            );


        return response()->json([

            'success'=>true,

            'exists'=>(bool)$customer,

            'data'=>$customer

        ]);

    }






    public function store(
        Request $request
    )
    {


        $validated =
            $request->validate([

                'name'=>'required',

                'phone'=>'required|unique:customers,phone',

                'email'=>'nullable|email',

                'address'=>'nullable'

            ]);



        $customer =
            $this->service->create(
                $validated
            );



        return response()->json([

            'success'=>true,

            'data'=>$customer

        ],201);


    }



}