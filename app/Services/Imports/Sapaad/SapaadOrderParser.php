<?php

namespace App\Services\Imports\Sapaad;

use Carbon\Carbon;

class SapaadOrderParser
{
    public function parse(array $row): array
    {
        return [
            'external_id' => trim(
                (string) ($row['Order No'] ?? '')
            ),

            'order_no' => 'ORD-' . trim((string) ($row['Order No'] ?? '')),
            
            'order_sequence' => trim(
                (string) ($row['Order No'] ?? '')
            ),

            'ordered_at' => $this->parseDate(
                $row['Order Time'] ?? null
            ),

            'order_type' => $this->clean(
                $row['Order Type'] ?? null
            ),

            'cashier' => $this->clean(
                $row['Order Taken By'] ?? null
            ),

            'ready_at' => $this->parseDate(
                $row['Ready At'] ?? null
            ),

            'picked_at' => $this->parseDate(
                $row['Picked At'] ?? null
            ),

            'picked_by' => $this->clean(
                $row['Picked By'] ?? null
            ),

            'delivered_at' => $this->parseDate(
                $row['Delivered At'] ?? null
            ),

            'total_amount' => $this->money(
                $row['Order Amount'] ?? null
            ),

            /*
             * Original Sapaad payment value.
             *
             * Example:
             *
             * RCVRY CARD PAYMENT - 30.0
             *
             * This entire value can be preserved as reference.
             */
            'payment_reference' => $this->clean(
                $row['Payments'] ?? null
            ),

            /*
             * Normalized payment method.
             *
             * RCVRY CARD PAYMENT
             * becomes:
             *
             * Card Payment
             */
            'payment_method' => $this->paymentMethod(
                $row['Payments'] ?? null
            ),

            'amount_received' => $this->money(
                $row['Amount Received'] ?? null
            ),

            'received_by' => $this->clean(
                $row['Received By'] ?? null
            ),

            'amount_returned' => $this->money(
                $row['Amount Returned'] ?? null
            ),

            'status' => $this->clean(
                $row['Status'] ?? null
            ),

            'notes' => $this->clean(
                $row['Notes'] ?? null
            ),
        ];
    }

    protected function paymentMethod(
        ?string $value
    ): ?string {
        if (!$value) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        /*
         * Remove the payment amount.
         *
         * Example:
         *
         * RCVRY CARD PAYMENT - 30.0
         *
         * becomes:
         *
         * RCVRY CARD PAYMENT
         */

        $parts = explode(
            ' - ',
            $value,
            2
        );

        $method = trim(
            $parts[0]
        );

        /*
         * Sapaad payment name mappings.
         */

        return match (
            mb_strtolower(
                preg_replace(
                    '/\s+/u',
                    ' ',
                    $method
                )
            )
        ) {
            'rcvry card payment' =>
                'Card Payment',

            default =>
                $method,
        };
    }

    protected function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function money(mixed $value): float
    {
        if ($value === null || trim((string) $value) === '') {
            return 0;
        }

        $value = str_replace(
            [',', '$'],
            '',
            (string) $value
        );

        return (float) $value;
    }

    protected function parseDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)
                ->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function paymentName(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $parts = explode(' - ', $value, 2);

        return trim($parts[0]);
    }
}