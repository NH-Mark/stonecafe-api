<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Receipt - {{ $order->order_no ?? $order->id }}</title>

    <style>
        @page {
            margin: 20px 25px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #40332a;
            margin: 0;
            line-height: 1.4;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* HEADER */
        .header {
            text-align: center;
            padding-bottom: 9px;
            border-bottom: 2px solid #8c6b52;
        }

        .brand {
            font-size: 42px;
            font-weight: bold;
            margin: 0 0 2px;
            color: #40332a;
            line-height: 1;
        }

        .receipt-title {
            font-size: 8px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #8c6b52;
            margin: 0 0 6px;
        }

        .receipt-number {
            font-size: 12px;
            font-weight: bold;
            color: #40332a;
        }

        .receipt-date {
            font-size: 8px;
            color: #82766d;
            margin-top: 2px;
        }

        /* SECTIONS */
        .section {
            margin-top: 11px;
        }

        .section-title {
            font-size: 9px;
            font-weight: bold;
            color: #40332a;
            text-transform: uppercase;
            letter-spacing: .6px;
            border-bottom: 1px solid #e5ddd5;
            padding-bottom: 4px;
            margin-bottom: 5px;
        }

        /* ITEMS AND PAYMENTS */
        .items-table th,
        .payment-table th {
            background: #40332a;
            color: #fff;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 7px;
            text-align: left;
        }

        .items-table td,
        .payment-table td {
            padding: 6px 7px;
            border-bottom: 1px solid #eee9e4;
            vertical-align: top;
        }

        .items-table tbody tr:nth-child(even),
        .payment-table tbody tr:nth-child(even) {
            background: #faf8f6;
        }

        .item-name {
            font-weight: bold;
            color: #40332a;
        }

        .item-name-en {
            display: inline;
        }

        .item-name-separator {
            display: inline;
            margin: 0 4px;
            color: #7d6f64;
            font-weight: normal;
        }

        .item-name-ar {
            font-size: 9px;
            color: #6f6259;
            font-family: DejaVu Sans, sans-serif;
            display: inline;
            direction: ltr;
            unicode-bidi: normal;
            text-align: left;
            line-height: 1.35;
            margin-top: 0;
        }

        .modifier {
            font-size: 8px;
            color: #8a8179;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .empty {
            text-align: center;
            color: #8a8179;
            padding: 8px;
        }

        /* TOTALS */
        .totals-wrapper {
            width: 45%;
            margin-left: auto;
            margin-top: 7px;
        }

        .totals-table td {
            padding: 3px 6px;
        }

        .totals-label {
            text-align: right;
            color: #82766d;
        }

        .totals-value {
            text-align: right;
            font-weight: bold;
            color: #40332a;
            white-space: nowrap;
        }

        .grand-total td {
            border-top: 1.5px solid #8c6b52;
            padding-top: 6px;
            font-size: 11px;
            font-weight: bold;
            color: #40332a;
        }

        .currency {
            font-size: 8px;
            font-weight: normal;
            color: #82766d;
        }

        /* PAYMENT STATUS */
        .payment-status-table {
            margin-top: 5px;
        }

        .payment-status-table td {
            padding: 3px 6px;
            border-top: 1px solid #eee9e4;
        }

        .payment-status {
            text-align: right;
            font-weight: bold;
            color: #8c6b52;
            text-transform: capitalize;
        }

        /* NOTES */
        .notes {
            padding: 6px 8px;
            background: #faf8f6;
            border-left: 2px solid #8c6b52;
            color: #66574d;
        }

        /* FOOTER */
        .footer {
            margin-top: 16px;
            padding-top: 8px;
            border-top: 1px dashed #cbb9aa;
            text-align: center;
        }

        .thank-you {
            font-size: 11px;
            font-weight: bold;
            color: #40332a;
        }

        .footer-text {
            font-size: 8px;
            color: #8a8179;
            margin-top: 2px;
        }

        .location-address {
            font-size: 9px;
            color: #82766d;
            font-weight: normal;
            letter-spacing: 0.4px;
            margin: 1px 0 3px;
            line-height: 1.5;
        }

        .brand-subtitle {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 2px;
            color: #8c6b52;
            margin: 0 0 2px;
            text-transform: uppercase;
        }

        .logo {
            width: 160px;
            height: auto;
            display: block;
            margin: 0 auto 0;
        }
    </style>
</head>

<body>
    {{-- HEADER --}}
    <div class="header">

        <img
            src="{{ public_path('images/logo.png') }}"
            class="logo"
            alt="Restaurant Logo">

        <div class="brand-subtitle">
            STONE SPECIALITY COFFEE
        </div>

        <p class="location-address">
            {{ $order->location?->address }}
        </p>

        <div class="receipt-title">Official Receipt</div>

        <div class="receipt-number">
            #{{ $order->order_no ?? $order->id }}
        </div>

        <div class="receipt-date">
            {{ optional($order->ordered_at)->format('d M Y, h:i A') ?? $generatedAt->format('d M Y, h:i A') }}
            @if ($order->customer?->name)
            &nbsp; | &nbsp; {{ $order->customer->name }}
            @endif
        </div>

    </div>


    {{-- ORDER ITEMS --}}
    <div class="section">
        <div class="section-title">Order Items</div>

        @php
        $arabicShaper = class_exists(\ArPHP\I18N\Arabic::class)
        ? new \ArPHP\I18N\Arabic()
        : null;
        @endphp

        <table class="items-table">
            <thead>
                <tr>
                    <th width="46%">Item Description</th>
                    <th width="12%" class="text-center">Qty</th>
                    <th width="20%" class="text-right">Unit Price</th>
                    <th width="22%" class="text-right">Amount</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($order->items as $item)
                <tr>
                    <td>
                        @php
                        $arabicName = trim((string) ($item->menuItem?->name_ar ?? ''));
                        $arabicNameForPdf = $arabicName;

                        if ($arabicShaper && $arabicName !== '') {
                        $arabicNameForPdf = $arabicShaper->utf8Glyphs(
                        $arabicName,
                        100,
                        false,
                        true
                        );
                        }
                        @endphp

                        <div class="item-name">
                            <span class="item-name-en">{{ $item->menuItem?->name ?? 'Unknown Item' }}</span>
                            @if ($arabicNameForPdf !== '')
                            <span class="item-name-separator">-</span>
                            <span class="item-name-ar" lang="ar">{{ $arabicNameForPdf }}</span>
                            @endif
                        </div>

                        @if ($item->modifiers->isNotEmpty())
                        @foreach ($item->modifiers as $modifier)
                        <div class="modifier">
                            + {{ $modifier->modifier?->name ?? 'Modifier' }}
                            × {{ number_format((float) $modifier->quantity, 0) }}
                            ({{ number_format((float) $modifier->price, 2) }} QAR)
                        </div>
                        @endforeach
                        @endif
                    </td>

                    <td class="text-center">
                        {{ number_format((float) $item->quantity, 0) }}
                    </td>

                    <td class="text-right">
                        {{ number_format((float) $item->unit_price, 2) }}
                    </td>

                    <td class="text-right">
                        <strong>
                            {{ number_format((float) $item->total_price, 2) }}
                        </strong>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="empty">No items found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>


    {{-- TOTALS --}}
    <div class="totals-wrapper">
        <table class="totals-table">
            <tr>
                <td class="totals-label">Subtotal</td>
                <td class="totals-value">
                    {{ number_format((float) $order->subtotal, 2) }}
                    <span class="currency">QAR</span>
                </td>
            </tr>

            @if ((float) $order->discount_amount > 0)
            <tr>
                <td class="totals-label">Discount</td>
                <td class="totals-value">
                    -{{ number_format((float) $order->discount_amount, 2) }}
                    <span class="currency">QAR</span>
                </td>
            </tr>
            @endif

            <tr class="grand-total">
                <td class="totals-label">TOTAL</td>
                <td class="totals-value">
                    {{ number_format((float) $order->total_amount, 2) }}
                    <span class="currency">QAR</span>
                </td>
            </tr>
        </table>
    </div>


    {{-- PAYMENT DETAILS --}}
    <div class="section">
        <div class="section-title">Payment Details</div>

        <table class="payment-table">
            <thead>
                <tr>
                    <th width="35%">Payment Method</th>
                    <th width="40%">Reference</th>
                    <th width="25%" class="text-right">Amount (QAR)</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($order->payments as $payment)
                <tr>
                    <td>
                        {{ $payment->paymentMethod?->name ?? '-' }}
                    </td>

                    <td>
                        {{ $payment->reference ?? '-' }}
                    </td>

                    <td class="text-right">
                        <strong>
                            {{ number_format((float) $payment->amount, 2) }}
                        </strong>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="empty">
                        No payment records available.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <table class="payment-status-table">
            <tr>
                <td class="muted">Payment Status</td>
                <td class="payment-status">
                    {{ ucfirst((string) $order->payment_status) }}
                </td>
            </tr>
        </table>
    </div>


    {{-- NOTES --}}
    @if (!empty($order->notes))
    <div class="section">
        <div class="section-title">Notes</div>
        <div class="notes">{{ $order->notes }}</div>
    </div>
    @endif


    {{-- FOOTER --}}
    <div class="footer">
        <div class="thank-you">Thank You For Your Order!</div>
        <div class="footer-text">We appreciate your business.</div>
        <div class="footer-text">This is a computer-generated receipt.</div>
    </div>

</body>

</html>