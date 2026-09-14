<!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="utf-8">
    <title>Tellimusest taganemine</title>
</head>
<body style="font-family: -apple-system, system-ui, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">

    <h1 style="color: #b91c1c;">Klient taganes tellimusest</h1>

    <p>
        Tellimuse number: <strong>{{ $order->increment_id ?? '#'.$order->id }}</strong><br>
        Eelmine staatus: <code>{{ $previousStatus }}</code><br>
        Uus staatus: <code>withdrawn</code> (Taganetud)
    </p>

    <h3>Kliendi info</h3>
    <ul>
        <li><strong>Nimi:</strong> {{ $order->customer_first_name ?? '-' }} {{ $order->customer_last_name ?? '' }}</li>
        <li><strong>Email:</strong> {{ $order->customer_email ?? '-' }}</li>
        @if($order->customer_id)
            <li><strong>Kliendi ID:</strong> {{ $order->customer_id }}</li>
        @endif
    </ul>

    <h3>Tellimuse summa</h3>
    <p style="font-size: 18px;">
        {{ $order->formatted_grand_total ?? $order->grand_total }}
    </p>

    <h3>Toiminguid</h3>
    <p>EL-i 14-päevase taganemisõiguse alusel peate kliendile tagastama tellitud summa (sealhulgas
        kohaletoimetamiskulud, kui need olid kohaldatavad) 14 päeva jooksul taganemisteatest.</p>

    <p>
        <a href="{{ config('app.url') }}/admin/sales/orders/view/{{ $order->id }}"
           style="display:inline-block;padding:12px 20px;background:#0f172a;color:#fff;text-decoration:none;border-radius:4px;">
            Ava tellimus admin paneelis
        </a>
    </p>

    <hr>
    <p style="font-size: 12px; color: #6b7280;">
        Aiamaailm — automaatne teade.
    </p>

</body>
</html>
