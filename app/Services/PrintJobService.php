<?php

namespace App\Services;
use App\Models\Order;
use App\Models\PrintJob;
use Illuminate\Database\Eloquent\Collection;

class PrintJobService
{
    public function createKotJobs(
        Order $order,
        ?Collection $items = null
    ): void {
        if ($items === null) {
            $order->loadMissing([
                'items.menuItem.printer',
            ]);

            $items = $order->items;
        } else {
            $items->loadMissing([
                'menuItem.printer',
            ]);
        }

        if ($items->isEmpty()) {
            return;
        }

        $itemsByPrinter = $items->groupBy(
            fn ($item) =>
                $item->menuItem?->printer_id ?? 'default'
        );

        foreach ($itemsByPrinter as $printerId => $printerItems) {
            if ($printerId === 'default') {
                $printerName = 'EPSON TM-T20III Receipt';
            } else {
                $printer = $printerItems
                    ->first()
                    ->menuItem
                    ->printer;

                if (!$printer || !$printer->is_active) {
                    continue;
                }

                $printerName = $printer->system_name
                    ?: $printer->name;
            }

            $printJob = PrintJob::create([
                'order_id' => $order->id,
                'dining_session_id' => $order->dining_session_id,
                'payment_batch_id' => null,
                'printer' => $printerName,
                'type' => 'KOT',
                'status' => 'pending',
            ]);

            foreach ($printerItems as $item) {
                $printJob->items()->create([
                    'order_item_id' => $item->id,
                    'quantity' => $item->quantity,
                ]);
            }
        }
    }
}