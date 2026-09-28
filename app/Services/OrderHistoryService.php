<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderHistory;

class OrderHistoryService
{
    public function log(
        Order $order,
        string $action,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null
    ): OrderHistory {
        return $order->histories()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => $reason,
        ]);
    }
}