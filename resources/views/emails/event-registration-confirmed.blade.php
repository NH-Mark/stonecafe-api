<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registration Confirmed</title>
</head>

<body style="margin: 0; padding: 0; background-color: #40332A; font-family: Arial, Helvetica, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #40332A; padding: 40px 20px;">
        <tr>
            <td align="center">

                <table
                    width="600"
                    cellpadding="0"
                    cellspacing="0"
                    border="0"
                    style="max-width: 600px; width: 100%; background-color: #DDCFBE; border-radius: 16px; overflow: hidden;">

                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 25px; text-align: center;">

                            <p style="
                            margin: 0 0 12px;
                            color: #A57653;
                            font-size: 11px;
                            font-weight: bold;
                            letter-spacing: 3px;
                            text-transform: uppercase;
                        ">
                                Stone Cafe
                            </p>

                            <h1 style="
                            margin: 0;
                            color: #40332A;
                            font-size: 28px;
                            line-height: 36px;
                        ">
                                Registration Confirmed
                            </h1>

                            <p style="
                            margin: 16px 0 0;
                            color: #40332A;
                            opacity: 0.65;
                            font-size: 15px;
                            line-height: 24px;
                        ">
                                Thank you for registering, {{ $registration->full_name }}.
                            </p>

                        </td>
                    </tr>

                    <!-- Confirmation -->
                    <tr>
                        <td style="padding: 0 40px 30px;">

                            <table
                                width="100%"
                                cellpadding="0"
                                cellspacing="0"
                                border="0"
                                style="background-color: #ffffff; border-radius: 12px;">
                                <tr>
                                    <td style="padding: 25px;">

                                        <p style="
                                        margin: 0 0 8px;
                                        color: #A57653;
                                        font-size: 11px;
                                        font-weight: bold;
                                        letter-spacing: 2px;
                                        text-transform: uppercase;
                                    ">
                                            You're confirmed for
                                        </p>

                                        <h2 style="
                                        margin: 0;
                                        color: #40332A;
                                        font-size: 22px;
                                        line-height: 30px;
                                    ">
                                            {{ $registration->event->name }}
                                        </h2>

                                        @if($registration->event->description)
                                        <p style="
                                            margin: 12px 0 0;
                                            color: #40332A;
                                            opacity: 0.65;
                                            font-size: 14px;
                                            line-height: 22px;
                                        ">
                                            {{ $registration->event->description }}
                                        </p>
                                        @endif

                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Event Details -->
                    <tr>
                        <td style="padding: 0 40px 32px;">

                            <!-- Event Details Card -->
                            <table
                                width="100%"
                                cellpadding="0"
                                cellspacing="0"
                                border="0"
                                style="
                background-color: #ffffff;
                border: 1px solid #E4DDD5;
                border-radius: 14px;
                overflow: hidden;
            ">

                                <!-- Header -->
                                <tr>
                                    <td style="
                    padding: 22px 24px 18px;
                    border-bottom: 1px solid #E4DDD5;
                ">

                                        <p style="
                        margin: 0 0 5px;
                        color: #A57653;
                        font-size: 10px;
                        font-weight: bold;
                        letter-spacing: 2px;
                        text-transform: uppercase;
                    ">
                                            Your Event
                                        </p>

                                        <h3 style="
                        margin: 0;
                        color: #40332A;
                        font-size: 18px;
                        line-height: 26px;
                        font-weight: 700;
                    ">
                                            Event Details
                                        </h3>

                                    </td>
                                </tr>

                                <!-- Date -->
                                <tr>
                                    <td style="padding: 20px 24px 14px;">

                                        <p style="
                        margin: 0 0 5px;
                        color: #8A817A;
                        font-size: 10px;
                        font-weight: bold;
                        letter-spacing: 1.5px;
                        text-transform: uppercase;
                    ">
                                            Date
                                        </p>

                                        <p style="
                        margin: 0;
                        color: #40332A;
                        font-size: 14px;
                        line-height: 21px;
                        font-weight: 600;
                    ">
                                            {{ $registration->event->date->format('l, M d, Y') }}
                                        </p>

                                    </td>
                                </tr>

                                <!-- Time -->
                                <tr>
                                    <td style="padding: 8px 24px 14px;">

                                        <p style="
                        margin: 0 0 5px;
                        color: #8A817A;
                        font-size: 10px;
                        font-weight: bold;
                        letter-spacing: 1.5px;
                        text-transform: uppercase;
                    ">
                                            Time
                                        </p>

                                        <p style="
                        margin: 0;
                        color: #40332A;
                        font-size: 14px;
                        line-height: 21px;
                        font-weight: 600;
                    ">
                                            @if($registration->event->start_time)
                                            {{ \Carbon\Carbon::parse($registration->event->start_time)->format('g:i A') }}

                                            @if($registration->event->end_time)
                                            &nbsp;&ndash;&nbsp;
                                            {{ \Carbon\Carbon::parse($registration->event->end_time)->format('g:i A') }}
                                            @endif
                                            @else
                                            Time TBA
                                            @endif
                                        </p>

                                    </td>
                                </tr>

                                <!-- Location -->
                                <tr>
                                    <td style="padding: 8px 24px 14px;">

                                        <p style="
                        margin: 0 0 5px;
                        color: #8A817A;
                        font-size: 10px;
                        font-weight: bold;
                        letter-spacing: 1.5px;
                        text-transform: uppercase;
                    ">
                                            Location
                                        </p>

                                        <p style="
                        margin: 0;
                        color: #40332A;
                        font-size: 14px;
                        line-height: 21px;
                        font-weight: 600;
                    ">
                                            {{ $registration->event->location ?? 'Doha' }}
                                        </p>

                                    </td>
                                </tr>

                                <!-- Entry -->
                                <tr>
                                    <td style="padding: 8px 24px 22px;">

                                        <p style="
                        margin: 0 0 5px;
                        color: #8A817A;
                        font-size: 10px;
                        font-weight: bold;
                        letter-spacing: 1.5px;
                        text-transform: uppercase;
                    ">
                                            Entry
                                        </p>

                                        <p style="
                        margin: 0;
                        color: #40332A;
                        font-size: 14px;
                        line-height: 21px;
                        font-weight: 600;
                    ">
                                            Free
                                        </p>

                                    </td>
                                </tr>

                            </table>

                        </td>
                    </tr>

                    <!-- Important Message -->
                    <tr>
                        <td style="padding: 0 40px 35px;">

                            <table
                                width="100%"
                                cellpadding="0"
                                cellspacing="0"
                                border="0"
                                style="background-color: #ffffff; border-radius: 12px;">
                                <tr>
                                    <td style="padding: 20px;">

                                        <p style="
                                        margin: 0;
                                        color: #40332A;
                                        font-size: 13px;
                                        line-height: 22px;
                                    ">
                                            Please keep this email for your records.
                                            We look forward to seeing you at the event.
                                        </p>

                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="
                        padding: 25px 40px;
                        background-color: #40332A;
                        text-align: center;
                    ">

                            <p style="
                            margin: 0;
                            color: #DDCFBE;
                            font-size: 12px;
                            line-height: 20px;
                        ">
                                Thank you for choosing Stone Cafe.
                            </p>

                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>