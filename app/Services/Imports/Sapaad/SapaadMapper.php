<?php

namespace App\Services\Imports\Sapaad;

use App\Models\Customer;
use App\Models\Discount;
use App\Models\MenuItem;
use App\Models\Modifier;
use App\Models\OrderSource;
use App\Models\OrderType;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SapaadMapper
{
    /*
     * =====================================================
     * MENU ITEM
     * =====================================================
     */

    public function menuItem(string $name): ?MenuItem
    {
        return $this->findByName(
            MenuItem::query(),
            $name
        );
    }

    /*
     * =====================================================
     * MODIFIER
     * =====================================================
     */

    public function modifier(string $name): ?Modifier
    {
        return $this->findByName(
            Modifier::query(),
            $name
        );
    }

    /*
     * =====================================================
     * ORDER TYPE
     * =====================================================
     */

    public function orderType(string $name): ?OrderType
    {
        return $this->findByName(
            OrderType::query(),
            $name
        );
    }

    /*
     * =====================================================
     * CASHIER
     * =====================================================
     */

    public function cashier(string $name): ?User
    {
        return $this->findByName(
            User::query(),
            $name
        );
    }

    /*
     * =====================================================
     * CUSTOMER
     * =====================================================
     */

    public function customer(?string $number): ?Customer
    {
        $phone = $this->normalizePhone($number);

        if (!$phone) {
            return null;
        }

        return Customer::query()
            ->where('phone', $phone)
            ->first();
    }

    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $phone = trim($phone);

        if ($phone === '') {
            return null;
        }

        return preg_replace('/\s+/', '', $phone);
    }

    /*
     * =====================================================
     * PAYMENT METHOD
     * =====================================================
     */

    public function paymentMethod(string $name): ?PaymentMethod
    {
        return $this->findByName(
            PaymentMethod::query(),
            $name
        );
    }

    /*
     * =====================================================
     * DISCOUNT BY NAME
     * =====================================================
     */

    public function discount(string $name): ?Discount
    {
        return $this->findByName(
            Discount::query(),
            $name
        );
    }

    /*
     * =====================================================
     * ORDER DISCOUNT BY PERCENTAGE
     * =====================================================
     *
     * Example:
     *
     * Calculated discount = 10%
     *
     * Find:
     *
     * discounts.type  = percentage
     * discounts.value = 10
     *
     * Small tolerance is used for decimal values.
     */

    public function percentageDiscount(
        float $percentage
    ): ?Discount {
        $percentage = round($percentage, 2);

        return Discount::query()
            ->where('type', 'percentage')
            ->whereBetween('value', [
                $percentage - 0.01,
                $percentage + 0.01,
            ])
            ->first();
    }

    /*
     * =====================================================
     * FIND BY NAME
     * =====================================================
     *
     * Case-insensitive and whitespace-insensitive.
     *
     * Examples:
     *
     * Flat White
     * flat white
     * FLAT WHITE
     * Flat  White
     *  Flat White
     *
     * All match:
     *
     * Flat White
     */

    protected function findByName(
        Builder $query,
        string $name
    ) {
        $normalizedName = $this->normalizeName(
            $name
        );

        if ($normalizedName === '') {
            return null;
        }

        return $query
            ->whereRaw(
                "LOWER(TRIM(REGEXP_REPLACE(name, '[[:space:]]+', ' '))) = ?",
                [$normalizedName]
            )
            ->first();
    }

    /*
     * =====================================================
     * NORMALIZE NAME
     * =====================================================
     */

    protected function normalizeName(
        string $value
    ): string {
        $value = trim($value);

        /*
         * Convert multiple spaces, tabs and newlines
         * into one normal space.
         */
        $value = preg_replace(
            '/\s+/u',
            ' ',
            $value
        );

        /*
         * Case-insensitive comparison.
         */
        return mb_strtolower($value);
    }

    public function orderSource(string $code): ?OrderSource
    {
        return OrderSource::query()
            ->where('code', $code)
            ->first();
    }
}