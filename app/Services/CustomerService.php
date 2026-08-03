<?php

namespace App\Services;


use App\Models\Customer;


class CustomerService
{


    public function findByPhone(
        string $phone
    )
    {

        // normalize Qatar number

        $phone = $this->normalizePhone(
            $phone
        );


        return Customer::where(
            'phone',
            $phone
        )->first();

    }





    public function create(
        array $data
    )
    {

        $data['phone'] =
            $this->normalizePhone(
                $data['phone']
            );


        return Customer::create($data);

    }





    private function normalizePhone(
        string $phone
    )
    {

        $phone =
            preg_replace(
                '/[^0-9]/',
                '',
                $phone
            );


        // Qatar example
        // 55555555 -> 97455555555

        if(
            strlen($phone)==8
        ){

            $phone =
                "974".$phone;

        }


        return $phone;

    }


}