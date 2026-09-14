@php
    $name = trim(($order->customer_first_name ?? '') . ' ' . ($order->customer_last_name ?? '')) ?: $order->customer_email;
    $items = $order->items->whereNull('parent_id');
    $fmt = fn ($v) => number_format((float) $v, 2, ',', ' ') . ' €';
@endphp
<!DOCTYPE html>
<html lang="et">
<head><meta charset="utf-8"><title>Arve {{ $invoiceNo }}</title></head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #1a1a1a; background: #f6f9f5; margin: 0; padding: 24px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;">
    <tr>
        <td style="background:#16a34a;color:#fff;padding:20px 24px;font-size:22px;font-weight:700;">
            aiamaailm<span style="opacity:0.75;">.ee</span>
        </td>
    </tr>
    <tr>
        <td style="padding:24px;">
            <p style="margin:0 0 12px;">Tere, {{ $name }}!</p>

            <p style="margin:0 0 12px;">
                Täname tellimuse eest. Su makse on kätte saadud ja tellimus on töösse võetud.
            </p>

            <p style="margin:0 0 12px;">
                Manuses on <strong>PDF-arve {{ $invoiceNo }}</strong> tellimuse
                <strong>#{{ $order->increment_id ?? $order->id }}</strong> kohta,
                summa <strong>{{ $fmt($order->grand_total) }}</strong>.
            </p>

            <div style="background:#f6f9f5;border-left:4px solid #16a34a;padding:12px 16px;margin:16px 0;font-size:13px;">
                <strong>Tellimuse read:</strong><br>
                @foreach ($items as $it)
                    · {{ (int) $it->qty_ordered }} × {{ $it->name }} — {{ $fmt($it->total) }}<br>
                @endforeach
            </div>

            <p style="margin:16px 0 0;font-size:13px;color:#555;">
                Küsimuste korral kirjuta <a href="mailto:{{ $company['email'] }}" style="color:#16a34a;">{{ $company['email'] }}</a>
                või helista <strong>{{ $company['phone'] }}</strong>.
            </p>
        </td>
    </tr>
    <tr>
        <td style="background:#f6f9f5;padding:16px 24px;font-size:11px;color:#666;line-height:1.5;">
            {{ $company['name'] }} · {{ $company['address'] }}<br>
            Reg {{ $company['reg'] }} · KMKR {{ $company['vat'] }} · IBAN {{ $company['iban'] }}
        </td>
    </tr>
</table>
</body>
</html>
