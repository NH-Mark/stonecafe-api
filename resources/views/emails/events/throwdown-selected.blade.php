
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Congratulations, you are in!</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f5f5; font-family:Arial,Helvetica,sans-serif; color:#222222; line-height:1.6;">
    <div style="max-width:640px; margin:0 auto; padding:32px 20px;">
        <div style="background:#ffffff; padding:32px; border-radius:8px;">
            <p>Hi {{ $firstName }},</p>

            <p>
                Congratulations! You have been selected as one of the
                16 competitors in the Brewers Throwdown by Stone &amp; Fuel.
            </p>

            <h3 style="margin-bottom:8px;">Confirm your place</h3>

            <p>
                To secure your spot, please complete your payment of
                QAR {{ $amount }} here by {{ $paymentDeadline }}:
            </p>

            @if (!empty($paymentUrl))
                <p style="margin:24px 0;">
                    <a
                        href="{{ $paymentUrl }}"
                        style="display:inline-block; background:#222222; color:#ffffff; text-decoration:none; padding:12px 22px; border-radius:5px; font-weight:bold;"
                    >
                        Complete Payment
                    </a>
                </p>
                <p style="font-size:13px; color:#555555;">
                    If the button does not work, use this personal link:
                    <br>
                    <a href="{{ $paymentUrl }}">{{ $paymentUrl }}</a>
                </p>
            @else
                <p style="padding:12px; background:#fff8e1; border:1px solid #f0d98c;">
                    <strong>Payment link pending:</strong>
                    Your personal payment link will be provided separately.
                </p>
            @endif

            <p>
                This link is personal to you, so please do not share it.
                If we do not receive payment by the deadline, your place
                may be offered to the next person on the waitlist.
            </p>

            <h3 style="margin-bottom:8px;">Event details</h3>

            <p>
                <strong>Date:</strong> Monday, 16 November 2026<br>
                <strong>Venue:</strong> Stone Specialty Coffee, Old Airport Road, Doha<br>
                <strong>Time:</strong> Introduction at 6:20 PM, competition
                starts at 6:30 PM. Please arrive by {{ $arrivalTime }}.<br>
                <strong>Format:</strong> 7 minutes to brew and 3 minutes
                to clean up, across Round 1, Round 2 and the Finals.
            </p>

            <h3 style="margin-bottom:8px;">Practice coffee</h3>

            <p>
                You can collect your practice coffee, packaged by Stone
                and Fuel, from Stone from Friday, 13 November. There is no
                practice time at Stone on the day, so please practice at home.
            </p>

            <h3 style="margin-bottom:8px;">What to bring</h3>

            <p>
                Any conical brewer of your choice. You may bring your own
                grinder, water and paper filters. An EK43 grinder will be
                available at both stations if you prefer to use it.
            </p>

            <p>
                We will send your set time before the event. If you can
                no longer take part, please let us know as soon as possible.
            </p>

            <p>We cannot wait to see you brew.</p>

            <p>
                Warm regards,<br>
                <strong>Stone Specialty Coffee and Fuel Roastery</strong>
            </p>
        </div>
    </div>
</body>
</html>