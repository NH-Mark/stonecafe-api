<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class DiscountResource extends JsonResource
{

    public function toArray(Request $request): array
    {

        return [

            'id' => $this->id,
            'name' => $this->name,
            'type'=>$this->type,
            'value'=>$this->value,
            'status'=>$this->status,
            'created_at' => $this->created_at,
          

        ];

    }

}