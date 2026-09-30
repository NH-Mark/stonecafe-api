<?php

namespace App\Services\Imports\Sapaad;

use App\Models\Discount;

class SapaadImportValidator
{
    public function __construct(
        protected SapaadMapper $mapper
    ) {}

    public function validate(
        array $orders,
        array $items
    ): array {
        /*
     * =================================================
     * GROUP ITEMS BY ORDER
     * =================================================
     */

        $itemsByOrder = $this->groupItems($items);

        /*
     * =================================================
     * BUILD CUSTOMER MAP
     * =================================================
     *
     * Customer information comes from the items CSV.
     *
     * This only looks up existing customers.
     * It does NOT create customers.
     */

        $customerByOrder = $this->buildCustomerByOrder($items);

        $results = [];

        /*
     * =================================================
     * PROCESS ORDERS
     * =================================================
     */

        foreach ($orders as $order) {
            $orderNo = trim(
                (string) (
                    $order['external_id'] ?? ''
                )
            );

            $orderData =
                $itemsByOrder[$orderNo]
                ?? [
                    'items' => [],
                    'orphan_modifiers' => [],
                ];

            $errors = [];
            $warnings = [];

            /*
         * =================================================
         * ORDER TYPE
         * =================================================
         */

            $orderType = null;

            if (!empty($order['order_type'])) {
                $orderType = $this->mapper->orderType(
                    $order['order_type']
                );

                if (!$orderType) {
                    $errors[] = [
                        'type' => 'unmapped_order_type',
                        'value' => $order['order_type'],
                        'message' =>
                        "Order type '{$order['order_type']}' was not found.",
                    ];
                }
            }

            /*
         * =================================================
         * CASHIER
         * =================================================
         */

            $cashier = null;

            if (!empty($order['cashier'])) {
                $cashier = $this->mapper->cashier(
                    $order['cashier']
                );

                if (!$cashier) {
                    $errors[] = [
                        'type' => 'unmapped_cashier',
                        'value' => $order['cashier'],
                        'message' =>
                        "Cashier '{$order['cashier']}' was not found.",
                    ];
                }
            }

            /*
         * =================================================
         * PAYMENT METHOD
         * =================================================
         */

            $paymentMethod = null;

            if (!empty($order['payment_method'])) {
                $paymentMethod = $this->mapper->paymentMethod(
                    $order['payment_method']
                );

                if (!$paymentMethod) {
                    $errors[] = [
                        'type' => 'unmapped_payment_method',
                        'value' => $order['payment_method'],
                        'message' =>
                        "Payment method '{$order['payment_method']}' was not found.",
                    ];
                }
            }

            /*
         * =================================================
         * RECEIVED BY
         * =================================================
         */

            $receivedBy = null;

            if (!empty($order['received_by'])) {
                $receivedBy = $this->mapper->cashier(
                    $order['received_by']
                );

                if (!$receivedBy) {
                    $errors[] = [
                        'type' => 'unmapped_received_by',
                        'value' => $order['received_by'],
                        'message' =>
                        "Received by user '{$order['received_by']}' was not found.",
                    ];
                }
            }

            /*
         * =================================================
         * CUSTOMER
         * =================================================
         *
         * Customer information comes from the items CSV.
         *
         * IMPORTANT:
         * Validation only searches for the customer.
         * It NEVER creates a customer.
         *
         * If the customer does not exist, we add a warning.
         * The customer will be created during execution.
         */

            $customer = null;

            $customerNumber = null;
            $customerName = null;

            if (isset($customerByOrder[$orderNo])) {
                $customerNumber =
                    $customerByOrder[$orderNo]['number']
                    ?? null;

                $customerName =
                    $customerByOrder[$orderNo]['name']
                    ?? null;

                $customer =
                    $customerByOrder[$orderNo]['customer']
                    ?? null;

                if ($customerNumber && !$customer) {
                    $warnings[] = [
                        'type' => 'new_customer',
                        'value' => $customerNumber,
                        'message' =>
                        "Customer '{$customerName}' ({$customerNumber}) was not found. " .
                            "A new customer will be created when this order is imported.",
                    ];
                }
            }

            /*
         * =================================================
         * ITEMS
         * =================================================
         */

            $mappedItems = [];

            foreach ($orderData['items'] as $item) {
                /*
             * =============================================
             * MENU ITEM
             * =============================================
             */

                $menuItem = $this->mapper->menuItem(
                    $item['name']
                );

                if (!$menuItem) {
                    $errors[] = [
                        'type' => 'unmapped_menu_item',
                        'value' => $item['name'],
                        'message' =>
                        "Menu item '{$item['name']}' was not found.",
                    ];
                }

                /*
             * =============================================
             * MODIFIERS
             * =============================================
             */

                $mappedModifiers = [];

                foreach ($item['modifiers'] as $modifier) {
                    $localModifier = $this->mapper->modifier(
                        $modifier['name']
                    );

                    if (!$localModifier) {
                        $errors[] = [
                            'type' => 'unmapped_modifier',
                            'value' => $modifier['name'],
                            'message' =>
                            "Modifier '{$modifier['name']}' was not found.",
                        ];
                    }

                    $mappedModifiers[] = [
                        'modifier_id' =>
                        $localModifier?->id,

                        'name' =>
                        $modifier['name'],

                        'quantity' =>
                        $modifier['quantity'],

                        'price' =>
                        $modifier['price'],

                        'tax' =>
                        $modifier['tax'],

                        'discount' =>
                        $modifier['discount'],
                    ];
                }

                /*
 * =============================================
 * ITEM AMOUNT
 * =============================================
 *
 * Item amount includes:
 *
 * menu item price
 * + modifier prices
 */

                $itemAmount = round(
                    $item['price'] * $item['quantity'],
                    2
                );

                foreach ($mappedModifiers as $modifier) {
                    $itemAmount +=
                        $modifier['price']
                        * $modifier['quantity'];
                }

                $itemAmount = round(
                    $itemAmount,
                    2
                );

                /*
 * =============================================
 * ITEM DISCOUNT
 * =============================================
 *
 * Sapaad discount applies to the entire item
 * amount, including modifiers.
 */

                $discount = null;
                $discountAmount = 0;

                if (!empty($item['discount'])) {
                    $discount = $this->mapper->discount(
                        $item['discount']
                    );

                    if (!$discount) {
                        $warnings[] = [
                            'type' => 'unmapped_discount',
                            'value' => $item['discount'],
                            'message' =>
                            "Discount '{$item['discount']}' was not found.",
                        ];
                    } else {
                        $discountAmount =
                            $this->calculateDiscountAmount(
                                $discount,
                                $itemAmount
                            );
                    }
                }

                /*
 * =============================================
 * ITEM TOTAL
 * =============================================
 *
 * Full item amount
 * - item discount
 */

                $itemTotal = round(
                    $itemAmount - $discountAmount,
                    2
                );


                /*
             * =============================================
             * STORE MAPPED ITEM
             * =============================================
             */

                $mappedItems[] = [
                    'menu_item_id' =>
                    $menuItem?->id,

                    'name' =>
                    $item['name'],

                    'quantity' =>
                    $item['quantity'],

                    'unit_price' =>
                    $item['price'],

                    'total_price' =>
                    $itemTotal,

                    'tax' =>
                    $item['tax'],

                    'discount_id' =>
                    $discount?->id,

                    'discount_name' =>
                    $item['discount'],

                    'discount_amount' =>
                    $discountAmount,

                    'modifiers' =>
                    $mappedModifiers,
                ];
            }


            $subtotalBeforeOrderDiscount = 0;

                        /*
            * =================================================
            * SUBTOTAL AFTER ITEM DISCOUNTS
            * =================================================
            *
            * Each item total already contains:
            *
            * menu item
            * + modifiers
            * - item discount
            */

            $subtotalBeforeOrderDiscount = 0;

            foreach ($mappedItems as $item) {
                $subtotalBeforeOrderDiscount +=
                    $item['total_price'];
            }

            $subtotalBeforeOrderDiscount = round(
                $subtotalBeforeOrderDiscount,
                2
            );

            /*
         * =================================================
         * SOURCE TOTAL
         * =================================================
         *
         * This is the total directly from Sapaad.
         */

            $sourceTotal = (float) (
                $order['total_amount'] ?? 0
            );

            /*
            * =================================================
            * ORDER DISCOUNT
            * =================================================
            *
            * At this point:
            *
            * subtotalBeforeOrderDiscount
            * =
            * item totals
            * - item discounts
            * + modifiers
            *
            * If this already matches the Sapaad total,
            * there is NO order-level discount.
            */

            $orderDiscountAmount = 0;
            $orderDiscount = null;
            $orderDiscountPercentage = null;

            if (
                $sourceTotal < $subtotalBeforeOrderDiscount
                && !$this->amountsEqual(
                    $sourceTotal,
                    $subtotalBeforeOrderDiscount
                )
            ) {
                /*
     * There is a real difference between the
     * calculated subtotal and the Sapaad total.
     *
     * Therefore, Sapaad likely has an order-level
     * discount.
     */

                $orderDiscountAmount = round(
                    $subtotalBeforeOrderDiscount - $sourceTotal,
                    2
                );

                /*
     * Cannot calculate percentage from zero subtotal.
     */

                if ($subtotalBeforeOrderDiscount <= 0) {
                    $errors[] = [
                        'type' => 'invalid_order_discount',
                        'message' =>
                        'Unable to calculate order discount because the order subtotal is zero.',
                    ];
                } else {
                    /*
         * Calculate order discount percentage.
         */

                    $orderDiscountPercentage = round(
                        (
                            $orderDiscountAmount
                            / $subtotalBeforeOrderDiscount
                        ) * 100,
                        2
                    );

                    /*
         * Find matching local percentage discount.
         */

                    $orderDiscount =
                        $this->mapper->percentageDiscount(
                            $orderDiscountPercentage
                        );

                    /*
         * Discount does not exist.
         */

                    if (!$orderDiscount) {
                        $errors[] = [
                            'type' => 'unmapped_order_discount',
                            'value' => $orderDiscountPercentage,
                            'amount' => $orderDiscountAmount,
                            'message' => sprintf(
                                'Order discount of %.2f%% (%.2f) was calculated, but no matching percentage discount was found.',
                                $orderDiscountPercentage,
                                $orderDiscountAmount
                            ),
                        ];
                    }
                }
            }

            /*
         * =================================================
         * FINAL CALCULATED TOTAL
         * =================================================
         *
         * Subtotal before order discount
         * - order discount
         */

            $calculatedTotal = round(
                $subtotalBeforeOrderDiscount
                    - $orderDiscountAmount,
                2
            );

            /*
         * =================================================
         * TOTAL VALIDATION
         * =================================================
         */

            if (
                !$this->amountsEqual(
                    $sourceTotal,
                    $calculatedTotal
                )
            ) {
                $errors[] = [
                    'type' => 'total_mismatch',

                    'source_total' =>
                    $sourceTotal,

                    'calculated_total' =>
                    $calculatedTotal,

                    'message' =>
                    sprintf(
                        'Sapaad total is %.2f but calculated total is %.2f.',
                        $sourceTotal,
                        $calculatedTotal
                    ),
                ];
            }

            /*
         * =================================================
         * ORPHAN MODIFIERS
         * =================================================
         */

            foreach (
                $orderData['orphan_modifiers']
                as $modifier
            ) {
                $errors[] = [
                    'type' => 'orphan_modifier',

                    'value' =>
                    $modifier['name'],

                    'message' =>
                    "Modifier '{$modifier['name']}' does not have a preceding regular item.",
                ];
            }

            /*
         * =================================================
         * FINAL RESULT
         * =================================================
         */

            $results[] = [
                'external_order_no' =>
                $orderNo,

                'status' =>
                empty($errors)
                    ? 'valid'
                    : 'invalid',

                'source_total' =>
                $sourceTotal,

                'calculated_total' =>
                $calculatedTotal,

                'data' => [
                    /*
                 * =========================================
                 * ORDER
                 * =========================================
                 */

                    'order' => [
                        ...$order,

                        /*
                     * IDs resolved during validation.
                     */

                        'order_type_id' =>
                        $orderType?->id,

                        'cashier_id' =>
                        $cashier?->id,

                        'payment_method_id' =>
                        $paymentMethod?->id,

                        'customer_id' =>
                        $customer?->id,

                        /*
                     * Customer information.
                     *
                     * If customer_id is null, execution
                     * will create the customer.
                     */

                        'customer_name' =>
                        $customerName,

                        'customer_number' =>
                        $customerNumber,

                        /*
                     * -------------------------------------
                     * Calculated subtotal
                     * -------------------------------------
                     */

                        'subtotal' =>
                        $subtotalBeforeOrderDiscount,

                        /*
                     * -------------------------------------
                     * Order discount
                     * -------------------------------------
                     */

                        'discount_id' =>
                        $orderDiscount?->id,

                        'discount_name' =>
                        $orderDiscount?->name,

                        'discount_percentage' =>
                        $orderDiscountPercentage,

                        'discount_amount' =>
                        $orderDiscountAmount,

                        /*
                     * -------------------------------------
                     * Final total
                     * -------------------------------------
                     */

                        'total_amount' =>
                        $sourceTotal,
                    ],

                    /*
                 * =========================================
                 * PAYMENT
                 * =========================================
                 */

                    'payment' => [
                        'payment_method_id' =>
                        $paymentMethod?->id,

                        'payment_method' =>
                        $order['payment_method'] ?? null,

                        /*
                     * Original Sapaad payment value.
                     *
                     * Example:
                     *
                     * RCVRY CARD PAYMENT - 30.0
                     */

                        'reference' =>
                        $order['payment_reference'] ?? null,

                        'amount' =>
                        $order['amount_received'] ?? 0,

                        'received_by' =>
                        $order['received_by'] ?? null,

                        'received_by_id' =>
                        $receivedBy?->id,
                    ],

                    /*
                 * =========================================
                 * ITEMS
                 * =========================================
                 */

                    'items' =>
                    $mappedItems,
                ],

                /*
             * =============================================
             * VALIDATION
             * =============================================
             */

                'errors' =>
                $errors,

                'warnings' =>
                $warnings,
            ];
        }

        return $results;
    }

    /*
     * =====================================================
     * GROUP ITEMS
     * =====================================================
     *
     * Sapaad structure:
     *
     * Regular Item
     * Modifier
     * Modifier
     * Regular Item
     * Modifier
     *
     * A modifier belongs to the immediately preceding
     * regular item.
     */

    protected function groupItems(
        array $items
    ): array {
        $orders = [];

        foreach ($items as $item) {
            $orderNo = trim(
                (string) (
                    $item['order_no'] ?? ''
                )
            );

            if ($orderNo === '') {
                continue;
            }

            if (!isset($orders[$orderNo])) {
                $orders[$orderNo] = [
                    'items' => [],
                    'orphan_modifiers' => [],
                ];
            }

            /*
             * =============================================
             * REGULAR ITEM
             * =============================================
             */

            if (
                $item['type'] === 'Regular Item'
            ) {
                $orders[$orderNo]['items'][] = [
                    'name' =>
                    $item['name'],

                    'quantity' =>
                    $item['quantity'],

                    'price' =>
                    $item['price'],

                    'tax' =>
                    $item['tax'],

                    'discount' =>
                    $item['discount'],

                    'modifiers' => [],
                ];

                continue;
            }

            /*
             * =============================================
             * MODIFIER
             * =============================================
             */

            if (
                $item['type'] === 'Modifier'
            ) {
                $lastIndex =
                    count(
                        $orders[$orderNo]['items']
                    ) - 1;

                /*
                 * Modifier appeared before any regular item.
                 */

                if ($lastIndex < 0) {
                    $orders[$orderNo]['orphan_modifiers'][]
                        = $item;

                    continue;
                }

                /*
                 * Attach modifier to immediately preceding
                 * regular item.
                 */

                $orders[$orderNo]['items'][$lastIndex]['modifiers'][] = [
                    'name' =>
                    $item['name'],

                    'quantity' =>
                    $item['quantity'],

                    'price' =>
                    $item['price'],

                    'tax' =>
                    $item['tax'],

                    'discount' =>
                    $item['discount'],
                ];

                continue;
            }
        }

        return $orders;
    }

    /*
     * =====================================================
     * CUSTOMER MAP
     * =====================================================
     */

    protected function buildCustomerByOrder(array $items): array
    {
        $customers = [];

        foreach ($items as $item) {
            $orderNo = trim(
                (string) ($item['order_no'] ?? '')
            );

            if ($orderNo === '') {
                continue;
            }

            /*
         * Keep the first customer information
         * found for the order.
         */
            if (!isset($customers[$orderNo])) {
                $customers[$orderNo] = [
                    'name' => $item['customer_name'] ?? null,
                    'number' => $item['customer_number'] ?? null,
                    'customer' => null,
                ];
            }

            /*
         * If the first row didn't have a name/number,
         * use a later row that does.
         */
            if (
                empty($customers[$orderNo]['name']) &&
                !empty($item['customer_name'])
            ) {
                $customers[$orderNo]['name'] =
                    $item['customer_name'];
            }

            if (
                empty($customers[$orderNo]['number']) &&
                !empty($item['customer_number'])
            ) {
                $customers[$orderNo]['number'] =
                    $item['customer_number'];
            }
        }

        /*
     * Look up existing customers.
     *
     * IMPORTANT:
     * This only reads from the database.
     */
        foreach ($customers as $orderNo => &$customerData) {
            if (!empty($customerData['number'])) {
                $customerData['customer'] =
                    $this->mapper->customer(
                        $customerData['number']
                    );
            }
        }

        unset($customerData);

        return $customers;
    }

    /*
     * =====================================================
     * ITEM DISCOUNT CALCULATION
     * =====================================================
     */

    protected function calculateDiscountAmount(
        Discount $discount,
        float $amount
    ): float {
        return match ($discount->type) {
            'percentage' => round(
                $amount
                    * (
                        (float) $discount->value
                        / 100
                    ),
                2
            ),

            'fixed' => min(
                (float) $discount->value,
                $amount
            ),

            default => 0,
        };
    }

    /*
     * =====================================================
     * MONEY COMPARISON
     * =====================================================
     */

    protected function amountsEqual(
        float $source,
        float $calculated
    ): bool {
        return abs(
            $source - $calculated
        ) < 0.01;
    }
}
