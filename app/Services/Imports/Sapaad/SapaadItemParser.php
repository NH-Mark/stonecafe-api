<?php

namespace App\Services\Imports\Sapaad;

class SapaadItemParser
{
    public function parse(array $row): array
    {
        return [
            'order_no' => trim(
                (string) ($row['Order No'] ?? '')
            ),

            'order_time' =>
                $row['Order Time'] ?? null,

            'order_type' =>
                $this->clean(
                    $row['Order Type'] ?? null
                ),

            'cashier' =>
                $this->clean(
                    $row['Order Taken By'] ?? null
                ),

            'customer_name' =>
                $this->clean(
                    $row['Customer Name'] ?? null
                ),

            'customer_number' =>
                $this->clean(
                    $row['Customer Number'] ?? null
                ),

            'name' =>
                trim(
                    (string) ($row['Item name'] ?? '')
                ),

            'quantity' =>
                $this->integer(
                    $row['Quantity'] ?? null
                ),

            'type' =>
                $this->clean(
                    $row['Item Type'] ?? null
                ),

            'price' =>
                $this->money(
                    $row['Price'] ?? null
                ),

            'tax' =>
                $this->money(
                    $row['Item Tax'] ?? null
                ),

            /*
             * Sapaad exports the discount NAME here.
             *
             * Example:
             * Loopy Loyality
             */
            'discount' =>
                $this->discount(
                    $row['Item discount'] ?? null
                ),
        ];
    }

    protected function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === ''
            ? null
            : $value;
    }

    protected function integer(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) $value;
    }

    protected function money(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (float) $value;
    }

    protected function discount(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === ''
            ? null
            : $value;
    }
}