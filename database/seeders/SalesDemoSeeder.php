<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderSource;
use App\Models\OrderType;
use App\Models\Payment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


class SalesDemoSeeder extends Seeder
{


    public function run()
    {

     OrderType::insert([
        [
        'name'=>'Dine In',
        'code'=>'dine_in'
        ],

        [
        'name'=>'QR Order',
        'code'=>'qr'
        ],

        [
        'name'=>'Takeaway',
        'code'=>'takeaway'
        ],

        [
        'name'=>'Delivery',
        'code'=>'delivery'
        ]

        ]);


     $sources = [

            [
                'name' => 'POS',
                'code' => 'pos',
                'status' => true,
            ],

            [
                'name' => 'QR Order',
                'code' => 'qr',
                'status' => true,
            ],

            [
                'name' => 'Website',
                'code' => 'website',
                'status' => true,
            ],

            [
                'name' => 'Mobile App',
                'code' => 'mobile_app',
                'status' => true,
            ],

            [
                'name' => 'Talabat',
                'code' => 'talabat',
                'status' => true,
            ],

            [
                'name' => 'Snoonu',
                'code' => 'snoonu',
                'status' => true,
            ],

            [
                'name' => 'Deliveroo',
                'code' => 'deliveroo',
                'status' => true,
            ],

            [
                'name' => 'Phone Order',
                'code' => 'phone',
                'status' => true,
            ],

        ];


        foreach ($sources as $source) {

            OrderSource::updateOrCreate(
                [
                    'code' => $source['code'],
                ],
                $source
            );

        }
        
    }
}
