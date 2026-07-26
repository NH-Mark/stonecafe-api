<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "stats" =>
                $this->resource['stats'],
            "sales_trend" =>
                $this->resource['sales_trend'],
            "sales_by_order_type" =>
                $this->resource['sales_by_order_type'],
            "top_selling_items" =>
                $this->resource['top_selling_items'],
            "top_selling_modifiers" =>
                $this->resource['top_selling_modifiers'],
        ];
    }
}
