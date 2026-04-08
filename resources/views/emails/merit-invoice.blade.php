<div style="font-family: Arial, sans-serif; color: #222; line-height: 1.5;">
    <h2 style="margin-bottom: 12px;">Aitäh tellimuse eest!</h2>

    <p>Teie Merit arve on loodud.</p>

    <p>
        <strong>Tellimus:</strong> #{{ $order->increment_id }}<br>
        <strong>Arve number:</strong> {{ $invoiceNo }}
    </p>

    <p style="margin: 20px 0;">
        <a
            href="{{ $invoiceUrl }}"
            style="display: inline-block; padding: 10px 14px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px;"
        >
            Ava / laadi arve (PDF)
        </a>
    </p>

    <p>Kui link ei avane, kopeeri see aadress brauserisse:</p>
    <p><a href="{{ $invoiceUrl }}">{{ $invoiceUrl }}</a></p>
</div>
