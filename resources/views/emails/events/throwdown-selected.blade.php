
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Confirmed</title>
</head>
<body style="margin:0; padding:24px; font-family:Arial,Helvetica,sans-serif; color:#222222; line-height:1.6; background:#ffffff;">
    <p style="margin:0 0 16px 0;">Hi {{ $firstName }},</p>

    <p style="margin:0 0 16px 0;">
        Congratulations! Your place is confirmed as one of the
        16 competitors in the Brewers Throwdown by Stone &amp; Fuel.
    </p>

    <p style="margin:0 0 16px 0;"><strong>Confirm your place</strong></p>

    <p style="margin:0 0 16px 0;">
        To secure your spot, please complete your payment of
        QAR {{ $amount }} by {{ $paymentDeadline }}:
    </p>

    @if (!empty($paymentUrl))
        <p style="margin:0 0 16px 0;">
            <a href="{{ $paymentUrl }}">Complete Payment</a>
        </p>
        <p style="margin:0 0 16px 0;">
            If the link does not open directly, use this URL:
            <br>
            <a href="{{ $paymentUrl }}">{{ $paymentUrl }}</a>
        </p>
    @else
        <p style="margin:0 0 16px 0; background:transparent; border:0; padding:0; box-shadow:none;">
            <strong>Payment link pending:</strong>
            Your personal payment link will be provided separately.
        </p>
    @endif

    <p style="margin:0 0 16px 0;">
        This link is personal to you, so please do not share it.
        If we do not receive payment by the deadline, your place
        may be offered to the next person on the waitlist.
    </p>

    <p style="margin:0 0 16px 0;"><strong>Event details</strong></p>

    <p style="margin:0 0 16px 0;">
        Date: Monday, 16 November 2026<br>
        Venue: Stone Specialty Coffee, Old Airport Road, Doha<br>
        Time: Introduction at 6:20 PM, competition starts at 6:30 PM.
        Please arrive by {{ $arrivalTime }}.<br>
        Format: 7 minutes to brew and 3 minutes to clean up,
        across Round 1, Round 2 and the Finals.
    </p>

    <p style="margin:0 0 16px 0;"><strong>Practice coffee</strong></p>

    <p style="margin:0 0 16px 0;">
        You can collect your practice coffee, packaged by Stone
        and Fuel, from Stone from Friday, 13 November. There is no
        practice time at Stone on the day, so please practice at home.
    </p>

    <p style="margin:0 0 16px 0;"><strong>What to bring</strong></p>

    <p style="margin:0 0 16px 0;">
        Any conical brewer of your choice. You may bring your own
        grinder, water and paper filters. An EK43 grinder will be
        available at both stations if you prefer to use it.
    </p>

    <p style="margin:0 0 16px 0;">
        We will send your set time before the event. If you can
        no longer take part, please let us know as soon as possible.
    </p>

    <p style="margin:0 0 16px 0;">We cannot wait to see you brew.</p>

    <p style="margin:0;">
        Warm regards,<br>
        <strong>Stone Specialty Coffee and Fuel Roastery</strong>
    </p>
</body>
</html>