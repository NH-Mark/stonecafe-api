<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SalesDashboardService;
use App\Http\Resources\SalesDashboardResource;
use Illuminate\Http\Request;

class SalesDashboardController extends Controller
{


    public function __construct(
        protected SalesDashboardService $service
    )
    {

    }



    public function index(Request $request)
    {


        $data =
            $this->service->dashboard($request);



        return new SalesDashboardResource(
            $data
        );


    }


}