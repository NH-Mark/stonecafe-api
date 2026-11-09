<?php

namespace Tests\Unit;

use App\Models\MenuItem;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MenuItemCostPrecisionTest extends TestCase
{
    #[Test]
    public function it_keeps_four_decimal_places_for_cost_price(): void
    {
        $menuItem = MenuItem::make([
            'cost_price' => 4.196,
        ]);

        $this->assertSame(4.196, (float) $menuItem->cost_price);
    }
}
