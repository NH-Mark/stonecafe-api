<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Daily Order Summary</title>

</head>

<body
    style="
        margin:0;
        padding:0;
        background:#f5f5f5;
        font-family:Arial, Helvetica, sans-serif;
        color:#40332a;
    ">

    <table
        width="100%"
        cellpadding="0"
        cellspacing="0"
        style="background:#f5f5f5;padding:30px 15px;">

        <tr>
            <td align="center">

                <table
                    width="680"
                    cellpadding="0"
                    cellspacing="0"
                    style="
        max-width:680px;
        width:100%;
        background:#ffffff;
        border-radius:12px;
        overflow:hidden;
    ">

                    {{-- HEADER --}}

                    <tr>

                        <td
                            style="
                background:#40332a;
                padding:28px 30px;
                color:#ffffff;
            ">

                            <div
                                style="
                    font-size:22px;
                    font-weight:bold;
                ">
                                Daily Order Summary
                            </div>

                            <div
                                style="
                    margin-top:6px;
                    font-size:13px;
                    color:#ddd6d0;
                ">
                                {{ now()->subDay()->format('l, d F Y') }}
                            </div>

                        </td>

                    </tr>


                    {{-- INTRO --}}

                    <tr>

                        <td style="padding:28px 30px 10px;">

                            <div
                                style="
                    font-size:16px;
                    font-weight:bold;
                ">
                                Daily Sales Report
                            </div>

                            <p
                                style="
                    margin:8px 0 0;
                    font-size:14px;
                    line-height:22px;
                    color:#81786f;
                ">
                                Here is the sales and order performance summary
                                for the previous business day.
                            </p>

                        </td>

                    </tr>


                    {{-- SUMMARY CARDS --}}

                    <tr>

                        <td style="padding:20px 30px;">

                            <table
                                width="100%"
                                cellpadding="0"
                                cellspacing="0">

                                <tr>

                                    @foreach($dashboard['stats'] as $stat)

                                    @if(in_array($stat['title'], [
                                    'Total Sales',
                                    'Orders',
                                    'Net Sales'
                                    ]))

                                    <td
                                        width="33%"
                                        style="
                                    padding:5px;
                                    vertical-align:top;
                                ">

                                        <table
                                            width="100%"
                                            cellpadding="0"
                                            cellspacing="0"
                                            style="
                                        border:1px solid #e5e0dc;
                                        border-radius:8px;
                                    ">

                                            <tr>

                                                <td
                                                    style="
                                                padding:16px;
                                            ">

                                                    <div
                                                        style="
                                                    font-size:11px;
                                                    color:#8a8179;
                                                ">
                                                        {{ $stat['title'] }}
                                                    </div>

                                                    <div
                                                        style="
                                                    margin-top:7px;
                                                    font-size:20px;
                                                    font-weight:bold;
                                                    color:#40332a;
                                                ">
                                                        {{ $stat['value'] }}
                                                    </div>

                                                    <div
                                                        style="
                                                    margin-top:5px;
                                                    font-size:11px;
                                                    color:#6b8e5a;
                                                ">
                                                        {{ $stat['change'] }}
                                                        vs previous period
                                                    </div>

                                                </td>

                                            </tr>

                                        </table>

                                    </td>

                                    @endif

                                    @endforeach

                                </tr>

                            </table>

                        </td>

                    </tr>


                    {{-- FINANCIAL SUMMARY --}}

                    <tr>

                        <td style="padding:10px 30px;">

                            <h3
                                style="
                    margin:0 0 15px;
                    font-size:16px;
                ">
                                Financial Summary
                            </h3>

                            <table
                                width="100%"
                                cellpadding="0"
                                cellspacing="0"
                                style="
                    border:1px solid #e5e0dc;
                    border-radius:8px;
                ">

                                @foreach($dashboard['stats'] as $stat)

                                @if(in_array($stat['title'], [
                                'Gross Sales',
                                'Net Sales',
                                'Average Order',
                                'Discounts',
                                'Refunds'
                                ]))

                                <tr>

                                    <td
                                        style="
                                    padding:11px 15px;
                                    border-bottom:1px solid #eeeae6;
                                    font-size:13px;
                                    color:#81786f;
                                ">
                                        {{ $stat['title'] }}
                                    </td>

                                    <td
                                        align="right"
                                        style="
                                    padding:11px 15px;
                                    border-bottom:1px solid #eeeae6;
                                    font-size:13px;
                                    font-weight:bold;
                                    color:#40332a;
                                ">
                                        {{ $stat['value'] }}
                                    </td>

                                </tr>

                                @endif

                                @endforeach

                            </table>

                        </td>

                    </tr>


                    {{-- SALES BY ORDER TYPE --}}

                    @if(count($dashboard['sales_by_order_type']) > 0)

                    <tr>

                        <td style="padding:25px 30px 10px;">

                            <h3
                                style="
                    margin:0 0 15px;
                    font-size:16px;
                ">
                                Sales by Order Type
                            </h3>

                            <table
                                width="100%"
                                cellpadding="0"
                                cellspacing="0">

                                <tr
                                    style="
                        background:#faf9f7;
                    ">

                                    <td
                                        style="
                            padding:10px;
                            font-size:11px;
                            color:#81786f;
                        ">
                                        Order Type
                                    </td>

                                    <td
                                        align="right"
                                        style="
                            padding:10px;
                            font-size:11px;
                            color:#81786f;
                        ">
                                        Sales
                                    </td>

                                </tr>

                                @foreach($dashboard['sales_by_order_type'] as $item)

                                <tr>

                                    <td
                                        style="
                                padding:10px;
                                border-bottom:1px solid #eeeae6;
                                font-size:13px;
                            ">
                                        {{ $item['name'] }}
                                    </td>

                                    <td
                                        align="right"
                                        style="
                                padding:10px;
                                border-bottom:1px solid #eeeae6;
                                font-size:13px;
                                font-weight:bold;
                            ">
                                        QAR {{ number_format($item['value'], 2) }}
                                    </td>

                                </tr>

                                @endforeach

                            </table>

                        </td>

                    </tr>

                    @endif


                    {{-- TOP SELLING ITEMS --}}

                    @php
                    $topItems = collect($dashboard['top_selling_items'])
                    ->filter(function ($item) {
                    return (float) ($item['sales'] ?? 0) > 0;
                    })
                    ->sortByDesc(function ($item) {
                    return (float) ($item['sales'] ?? 0);
                    })
                    ->take(5);
                    @endphp

                    @if($topItems->isNotEmpty())

                    <tr>

                        <td style="padding:25px 30px 10px;">

                            <h3
                                style="
                margin:0 0 15px;
                font-size:16px;
                color:#40332a;
            ">
                                Top Selling Items
                            </h3>

                            <table
                                width="100%"
                                cellpadding="0"
                                cellspacing="0"
                                style="border-collapse:collapse;">

                                {{-- HEADER --}}

                                <tr style="background:#faf9f7;">

                                    <td
                                        style="
                        padding:10px;
                        font-size:11px;
                        color:#81786f;
                    ">
                                        Item
                                    </td>

                                    <td
                                        align="center"
                                        style="
                        padding:10px;
                        font-size:11px;
                        color:#81786f;
                    ">
                                        Qty
                                    </td>

                                    <td
                                        align="right"
                                        style="
                        padding:10px;
                        font-size:11px;
                        color:#81786f;
                    ">
                                        Revenue
                                    </td>

                                </tr>

                                {{-- TOP 5 ITEMS --}}

                                @foreach($topItems as $item)

                                <tr>

                                    {{-- ITEM --}}

                                    <td
                                        style="
                        padding:10px;
                        border-bottom:1px solid #eeeae6;
                        font-size:13px;
                        color:#40332a;
                    ">
                                        <strong>
                                            {{ $item['name'] }}
                                        </strong>
                                    </td>

                                    {{-- QUANTITY --}}

                                    <td
                                        align="center"
                                        style="
                        padding:10px;
                        border-bottom:1px solid #eeeae6;
                        font-size:13px;
                        color:#40332a;
                    ">
                                        {{ $item['qty'] }}
                                    </td>

                                    {{-- REVENUE --}}

                                    <td
                                        align="right"
                                        style="
                        padding:10px;
                        border-bottom:1px solid #eeeae6;
                        font-size:13px;
                        font-weight:bold;
                        color:#40332a;
                    ">
                                        QAR {{ number_format($item['sales'], 2) }}
                                    </td>

                                </tr>

                                @endforeach

                            </table>

                        </td>

                    </tr>

                    @endif

                    {{-- TOP MODIFIERS --}}

                    @php
                    $topModifiers = collect($dashboard['top_selling_modifiers'])
                    ->filter(function ($modifier) {
                    return (float) ($modifier['sales'] ?? 0) > 0;
                    })
                    ->sortByDesc(function ($modifier) {
                    return (float) ($modifier['sales'] ?? 0);
                    })
                    ->take(5);
                    @endphp

                    @if($topModifiers->isNotEmpty())

                    <tr>

                        <td style="padding:25px 30px 10px;">

                            <h3
                                style="
                margin:0 0 15px;
                font-size:16px;
                color:#40332a;
            ">
                                Top Selling Modifiers
                            </h3>

                            <table
                                width="100%"
                                cellpadding="0"
                                cellspacing="0"
                                style="border-collapse:collapse;">

                                {{-- HEADER --}}

                                <tr style="background:#faf9f7;">

                                    <td
                                        style="
                        padding:10px;
                        font-size:11px;
                        color:#81786f;
                    ">
                                        Modifier
                                    </td>

                                    <td
                                        align="center"
                                        style="
                        padding:10px;
                        font-size:11px;
                        color:#81786f;
                    ">
                                        Qty
                                    </td>

                                    <td
                                        align="right"
                                        style="
                        padding:10px;
                        font-size:11px;
                        color:#81786f;
                    ">
                                        Revenue
                                    </td>

                                </tr>

                                {{-- TOP 5 --}}

                                @foreach($topModifiers as $modifier)

                                <tr>

                                    {{-- MODIFIER --}}

                                    <td
                                        style="
                            padding:10px;
                            border-bottom:1px solid #eeeae6;
                            font-size:13px;
                            color:#40332a;
                        ">

                                        <strong>
                                            {{ $modifier['name'] }}
                                        </strong>

                                        @if(!empty($modifier['menu_item']))
                                        <span style="color:#81786f;">
                                            — {{ $modifier['menu_item'] }}
                                        </span>
                                        @endif

                                    </td>

                                    {{-- QUANTITY --}}

                                    <td
                                        align="center"
                                        style="
                            padding:10px;
                            border-bottom:1px solid #eeeae6;
                            font-size:13px;
                            color:#40332a;
                        ">
                                        {{ $modifier['qty'] }}
                                    </td>

                                    {{-- REVENUE --}}

                                    <td
                                        align="right"
                                        style="
                            padding:10px;
                            border-bottom:1px solid #eeeae6;
                            font-size:13px;
                            font-weight:bold;
                            color:#40332a;
                        ">
                                        QAR {{ number_format($modifier['sales'], 2) }}
                                    </td>

                                </tr>

                                @endforeach

                            </table>

                        </td>

                    </tr>

                    @endif


                    {{-- FOOTER --}}

                    <tr>

                        <td
                            style="
                margin-top:20px;
                padding:25px 30px;
                background:#faf9f7;
                border-top:1px solid #eeeae6;
                text-align:center;
            ">

                            <p
                                style="
                    margin:0;
                    font-size:12px;
                    color:#81786f;
                ">
                                This is an automated daily sales report.
                            </p>

                            <p
                                style="
                    margin:6px 0 0;
                    font-size:11px;
                    color:#aaa29b;
                ">
                                Generated {{ now()->format('d M Y, h:i A') }}
                            </p>

                        </td>

                    </tr>

                </table>

            </td>
        </tr>

    </table>

</body>

</html>