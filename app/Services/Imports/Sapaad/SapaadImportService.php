<?php

namespace App\Services\Imports\Sapaad;

use App\Models\Customer;
use App\Models\Discount;
use App\Models\HistoricalImport;
use App\Models\HistoricalImportRow;
use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\Payment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SapaadImportService
{
    public function __construct(
        protected SapaadCsvReader $csvReader,
        protected SapaadOrderParser $orderParser,
        protected SapaadItemParser $itemParser,
        protected SapaadImportValidator $validator,
        protected SapaadMapper $mapper
    ) {}

    public function createImport(
        UploadedFile $ordersFile,
        UploadedFile $itemsFile,
        ?int $locationId = 1
    ): HistoricalImport {
        return DB::transaction(function () use (
            $ordersFile,
            $itemsFile,
            $locationId
        ) {
            $import = HistoricalImport::create([
                'source' => 'sapaad',
                'status' => 'validating',
                'location_id' => 1,
            ]);

            $directory = 'imports/sapaad/' . $import->id;

            $ordersPath = $ordersFile->store(
                $directory
            );

            $itemsPath = $itemsFile->store(
                $directory
            );

            $import->update([
                'orders_file_path' => $ordersPath,
                'items_file_path' => $itemsPath,
            ]);

            $this->validateImport($import);

            return $import->fresh();
        });
    }

    public function validateImport(
        HistoricalImport $import
    ): void {
        $rawOrders = $this->csvReader->read(
            $import->orders_file_path
        );

        $rawItems = $this->csvReader->read(
            $import->items_file_path
        );

        $orders = array_map(
            fn($row) => $this->orderParser->parse($row),
            $rawOrders
        );

        $items = array_map(
            fn($row) => $this->itemParser->parse($row),
            $rawItems
        );

        $results = $this->validator->validate(
            $orders,
            $items
        );

        $valid = 0;
        $invalid = 0;

        foreach ($results as $result) {
            HistoricalImportRow::create([
                'historical_import_id' => $import->id,
                'external_order_no' => $result['external_order_no'],
                'status' => $result['status'],
                'source_total' => $result['source_total'],
                'calculated_total' => $result['calculated_total'],
                'data' => $result['data'],
                'errors' => $result['errors'],
                'warnings' => $result['warnings'],
            ]);

            if ($result['status'] === 'valid') {
                $valid++;
            } else {
                $invalid++;
            }
        }

        $import->update([
            'status' => $invalid > 0
                ? 'validation_failed'
                : 'ready',

            'total_orders' => count($results),
            'valid_orders' => $valid,
            'invalid_orders' => $invalid,
        ]);
    }




    public function executeImport(
        HistoricalImport $import
    ): array {
        if ($import->status !== 'ready') {
            throw new RuntimeException(
                'This import is not ready for execution.'
            );
        }

        $rows = $import->rows()
            ->where('status', 'valid')
            ->whereNull('order_id')
            ->get();

        $import->update([
            'status' => 'importing',
            'started_at' => now(),
        ]);

        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            DB::transaction(function () use (
                $row,
                $import,
                &$imported,
                &$skipped
            ) {
                $orderData = $row->data['order'] ?? [];

                $externalId = $orderData['external_id']
                    ?? $row->external_order_no;

                /*
             * =============================================
             * CHECK IF ORDER WAS ALREADY IMPORTED
             * =============================================
             */

                $existingOrder = Order::query()
                    ->where('external_source', 'sapaad')
                    ->where('external_id', $externalId)
                    ->first();

                if ($existingOrder) {
                    /*
                 * Link this import row to the existing order.
                 */

                    $row->update([
                        'order_id' => $existingOrder->id,
                        'status' => 'imported',
                    ]);

                    $skipped++;

                    return;
                }

                /*
             * =============================================
             * CREATE ORDER
             * =============================================
             */

                $order = $this->createOrder(
                    $import,
                    $row
                );

                /*
             * =============================================
             * CREATE ORDER ITEMS
             * =============================================
             */

                $this->createOrderItems(
                    $order,
                    $row
                );

                /*
             * =============================================
             * CREATE ORDER DISCOUNT
             * =============================================
             */

                $this->createOrderDiscount(
                    $order,
                    $row
                );

                /*
             * =============================================
             * CREATE PAYMENT
             * =============================================
             */

                $this->createPayment(
                    $order,
                    $row
                );

                /*
             * =============================================
             * MARK ROW AS IMPORTED
             * =============================================
             */

                $row->update([
                    'order_id' => $order->id,
                    'status' => 'imported',
                ]);

                $imported++;
            });
        }

        $import->update([
            'status' => 'completed',
            'imported_orders' => $imported,
            'completed_at' => now(),
        ]);

        return [
            'import_id' => $import->id,
            'imported_orders' => $imported,
            'skipped_orders' => $skipped,
            'status' => 'completed',
        ];
    }


    protected function createOrder(
        HistoricalImport $import,
        HistoricalImportRow $row
    ): Order {
        $data = $row->data;



        $orderData = $data['order'];

        $orderSource = $this->mapper->orderSource('POS');

        if (!$orderSource) {
            throw new RuntimeException(
                "Order source with code 'POS' was not found."
            );
        }

        $customer = $this->resolveCustomer($orderData);

        return Order::create([
            'order_no' =>
            $orderData['order_no'],

            'order_sequence' =>
            $orderData['order_sequence'],

            'location_id' =>
            $import->location_id,

            'customer_id' => $customer?->id,

            'order_type_id' => $orderData['order_type_id'],

            'order_source_id' => $orderSource->id,

            'table_id' =>
            null,

            'cashier_id' =>
            $orderData['cashier_id'],

            'status' =>
            $this->mapOrderStatus(
                $orderData['status'] ?? null
            ),

            // 'payment_status' =>
            // $this->mapPaymentStatus(
            //     $orderData
            // ),
            'payment_status' => 'paid',
            'subtotal' =>
            $orderData['subtotal'],

            'discount_amount' =>
            $orderData['discount_amount'],

            'tax_amount' =>
            0,

            'service_charge' =>
            0,

            'total_amount' =>
            $orderData['total_amount'],

            'notes' =>
            $orderData['notes'],

            'ordered_at' =>
            $orderData['ordered_at'],

            'payment_reference' =>
            $orderData['payment_reference'] ?? null,

            'payment_gateway' =>
            null,

            'paid_at' =>
            $orderData['ordered_at'],

            'kitchen_status' =>
            Order::KITCHEN_STATUS_READY,

            'completed_at' =>
            $orderData['ready_at'] ?? null,

            'dining_session_id' =>
            null,

            'number_plate' =>
            null,

            'external_source' =>
            'sapaad',

            'external_id' =>
            $orderData['external_id'],
        ]);
    }

    protected function createOrderItems(
        Order $order,
        HistoricalImportRow $row
    ): void {
        foreach (
            $row->data['items'] as $itemData
        ) {
            $orderItem = $order->items()->create([
                'menu_item_id' =>
                $itemData['menu_item_id'],

                'quantity' =>
                $itemData['quantity'],

                'unit_price' =>
                $itemData['unit_price'],

                'total_price' =>
                $itemData['total_price'],

                'notes' =>
                null,
            ]);

            /*
         * Item discount
         */
            if (
                !empty($itemData['discount_id']) &&
                $itemData['discount_amount'] > 0
            ) {
                $orderItem->discounts()->create([
                    'discount_id' =>
                    $itemData['discount_id'],

                    'amount' =>
                    $itemData['discount_amount'],
                ]);
            }

            /*
         * Modifiers
         */
            foreach (
                $itemData['modifiers'] as $modifierData
            ) {
                if (
                    empty($modifierData['modifier_id'])
                ) {
                    continue;
                }

                $orderItem->modifiers()->create([
                    'modifier_id' =>
                    $modifierData['modifier_id'],

                    'quantity' =>
                    $modifierData['quantity'],

                    'price' =>
                    $modifierData['price'],
                ]);
            }
        }
    }

    protected function createOrderDiscount(
        Order $order,
        HistoricalImportRow $row
    ): void {
        $orderData = $row->data['order'];

        if (
            empty($orderData['discount_id']) ||
            (float) $orderData['discount_amount'] <= 0
        ) {
            return;
        }

        OrderDiscount::create([
            'order_id' =>
            $order->id,

            'discount_id' =>
            $orderData['discount_id'],

            'amount' =>
            $orderData['discount_amount'],
        ]);
    }

    protected function createPayment(
        Order $order,
        HistoricalImportRow $row
    ): void {
        $paymentData =
            $row->data['payment'] ?? null;

        if (!$paymentData) {
            return;
        }

        $amount = (float) (
            $paymentData['amount'] ?? 0
        );

        if ($amount <= 0) {
            return;
        }

        if (
            empty($paymentData['payment_method_id'])
        ) {
            return;
        }

        Payment::create([
            'order_id' =>
            $order->id,

            'payment_method_id' =>
            $paymentData['payment_method_id'],

            'amount' =>
            $amount,

            'reference' =>
            $paymentData['reference'] ?? null,

            'received_by' =>
            $paymentData['received_by_id'] ?? null,

            'paid_at' =>
            $order->paid_at ?? $order->ordered_at,
        ]);
    }

    protected function mapOrderStatus(?string $status): string
    {
        $status = mb_strtolower(trim((string) $status));

        return match ($status) {
            'completed',
            'complete',
            'delivered',
            'picked',
            'picked up',
            'ready' => Order::STATUS_COMPLETED,

            'cancelled',
            'canceled' => Order::STATUS_CANCELLED,

            'pending',
            'new' => Order::STATUS_PENDING,

            default => Order::STATUS_COMPLETED,
        };
    }

    protected function mapPaymentStatus(array $orderData): string
    {
        $amountReceived = (float) ($orderData['amount_received'] ?? 0);
        $totalAmount = (float) ($orderData['total_amount'] ?? 0);

        if ($amountReceived <= 0) {
            return 'unpaid';
        }

        if ($amountReceived + 0.01 >= $totalAmount) {
            return 'paid';
        }

        return 'partial';
    }

    protected function resolveCustomer(array $orderData): ?Customer
    {
        $phone = $this->normalizePhone(
            $orderData['customer_number'] ?? null
        );

        if (!$phone) {
            return null;
        }

        $customer = Customer::query()
            ->where('phone', $phone)
            ->first();

        if ($customer) {
            return $customer;
        }

        return Customer::create([
            'name' => trim(
                $orderData['customer_name'] ?? 'Sapaad Customer'
            ),
            'phone' => $phone,
            'email' => null,
            'address' => null,
            'loyalty_points' => 0,
            'total_spent' => 0,
        ]);
    }

    protected function normalizePhone(?string $phone): ?string
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
}
