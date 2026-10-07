<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your seat is confirmed</title>
</head>
<body style="font-family: Arial, sans-serif; color: #40332A; line-height: 1.6;">

    <p>Hi {{ $firstName }},</p>

    <p>
        Thank you for booking. Your seat at the
        <strong>Omakase Coffee Experience with UAE Barista Champion Mariam Erin</strong>
        is confirmed.
    </p>

    <p>
        <strong>Booking reference:</strong> {{ $reference }}<br>
        <strong>Date:</strong> Thursday, 12 November 2026<br>
        <strong>Session:</strong> {{ $sessionTime }}<br>
        <strong>Seats:</strong> {{ $seatCount }}<br>
        <strong>Amount paid:</strong> QAR {{ number_format((float) $amount, 2) }}<br>
        <strong>Venue:</strong> Stone Specialty Coffee, Old Airport Road, Doha
    </p>

    <p>
        You will enjoy an exclusive 3 to 4 course coffee beverage experience,
        prepared and served by Mariam in an intimate setting. Seating is limited
        to 5 guests per session.
    </p>

    <p>
        Please arrive about 10 minutes before your session starts.
        If you have any allergies or dietary needs we have not yet noted,
        simply reply to this email and let us know.
    </p>

    <p>
        If you need to change or cancel your booking, please contact us at
        <a href="mailto:info@stone.qa">info@stone.qa</a>
        or
        <a href="tel:+97466022878">+974 66022878</a>
        as early as possible.
    </p>

    <p>
        We look forward to welcoming you.
    </p>

    <p>
        Warm regards,<br>
        <strong>Stone Specialty Coffee</strong>
    </p>

</body>
</html>