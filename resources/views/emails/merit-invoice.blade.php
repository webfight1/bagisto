<!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tellimuse kinnitus #{{ $order->increment_id }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;color:#222;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

    {{-- Header --}}
    <tr>
        <td style="background:linear-gradient(to bottom, #1c0d25 0%, #56265d 100%);padding:28px 32px;">
            <h1 style="margin:0;color:#d9c3ad;font-size:22px;font-weight:700;letter-spacing:-0.3px;">
                Nailedit Store
            </h1>
        </td>
    </tr>

    {{-- Hero --}}
    <tr>
        <td style="padding:32px 32px 20px;">
            <h2 style="margin:0 0 8px;font-size:24px;color:#111;">
                Aitäh tellimuse eest! 🎉
            </h2>
            <p style="margin:0;color:#555;font-size:15px;line-height:1.6;">
                Teie tellimus <strong>#{{ $order->increment_id }}</strong> on vastu võetud ja makse laekunud.<br>
                Arve on lisatud sellele kirjale.
            </p>
        </td>
    </tr>

    {{-- Invoice PDF Button --}}
    <tr>
        <td style="padding:0 32px 28px;">
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td style="background:linear-gradient(90deg, #e0b873 0%, #f9d892 50%, #e0b873 100%);border-radius:50px;">
                        <a href="{{ $invoiceUrl }}"
                           style="display:inline-block;padding:12px 24px;color:#5c285c;text-decoration:none;font-size:15px;font-weight:600;">
                            ⬇&nbsp; Lae alla arve (PDF) – {{ $invoiceNo }}
                        </a>
                    </td>
                </tr>
            </table>
            <p style="margin:8px 0 0;font-size:12px;color:#888;">
                Kui nupp ei tööta, kopeeri link brauserisse:<br>
                <a href="{{ $invoiceUrl }}" style="color:#5c285c;word-break:break-all;">{{ $invoiceUrl }}</a>
            </p>
        </td>
    </tr>

    {{-- Divider --}}
    <tr><td style="padding:0 32px;"><hr style="border:none;border-top:1px solid #e8e8e8;margin:0;"></td></tr>

    {{-- Order Items --}}
    <tr>
        <td style="padding:24px 32px 8px;">
            <h3 style="margin:0 0 14px;font-size:16px;color:#111;">
                Tellitud tooted
            </h3>
            <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                <tr style="border-bottom:2px solid #eee;">
                    <th align="left"  style="padding:6px 8px 10px 0;color:#888;font-weight:600;">Toode</th>
                    <th align="center" style="padding:6px 0 10px;color:#888;font-weight:600;width:50px;">Tk</th>
                    <th align="right"  style="padding:6px 0 10px 8px;color:#888;font-weight:600;width:80px;">Hind</th>
                </tr>
                @foreach ($order->items as $item)
                <tr style="border-bottom:1px solid #f0f0f0;">
                    <td style="padding:10px 8px 10px 0;line-height:1.4;">
                        {{ $item->name }}
                        @if ($item->sku)
                            <br><span style="color:#aaa;font-size:12px;">SKU: {{ $item->sku }}</span>
                        @endif
                    </td>
                    <td align="center" style="padding:10px 0;color:#444;">{{ (int)$item->qty_ordered }}</td>
                    <td align="right"  style="padding:10px 0 10px 8px;color:#222;white-space:nowrap;">
                        {{ number_format($item->price * $item->qty_ordered, 2, ',', ' ') }} €
                    </td>
                </tr>
                @endforeach

                {{-- Shipping --}}
                @if ($order->shipping_amount > 0)
                <tr style="border-bottom:1px solid #f0f0f0;">
                    <td style="padding:10px 8px 10px 0;color:#555;">
                        Tarne: {{ $order->shipping_title ?? 'Saatmine' }}
                    </td>
                    <td></td>
                    <td align="right" style="padding:10px 0 10px 8px;color:#555;white-space:nowrap;">
                        {{ number_format($order->shipping_amount, 2, ',', ' ') }} €
                    </td>
                </tr>
                @endif

                {{-- Total --}}
                <tr>
                    <td colspan="2" style="padding:14px 8px 4px 0;font-weight:700;font-size:16px;">
                        Kokku
                    </td>
                    <td align="right" style="padding:14px 0 4px 8px;font-weight:700;font-size:16px;white-space:nowrap;">
                        {{ number_format($order->grand_total, 2, ',', ' ') }} €
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Divider --}}
    <tr><td style="padding:16px 32px 0;"><hr style="border:none;border-top:1px solid #e8e8e8;margin:0;"></td></tr>

    {{-- Addresses --}}
    @php
        $billing  = $order->addresses()->where('address_type', 'order_billing')->first();
        $shipping = $order->addresses()->where('address_type', 'order_shipping')->first();
    @endphp

    <tr>
        <td style="padding:20px 32px 24px;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    {{-- Billing --}}
                    <td width="48%" valign="top" style="font-size:14px;">
                        <p style="margin:0 0 6px;font-weight:700;color:#111;">Arveaadress</p>
                        @if ($billing)
                            <p style="margin:0;color:#555;line-height:1.7;">
                                {{ trim(($billing->company_name ?? '') . ' ' . ($billing->first_name ?? '') . ' ' . ($billing->last_name ?? '')) }}<br>
                                @if (!empty($billing->address) || !empty($billing->address1))
                                    {{ $billing->address ?? $billing->address1 }}<br>
                                @endif
                                {{ $billing->city ?? '' }}{{ ($billing->postcode && $billing->postcode !== '0') ? ', ' . $billing->postcode : '' }}<br>
                                {{ $billing->country ?? '' }}
                            </p>
                        @endif
                    </td>
                    <td width="4%"></td>
                    {{-- Shipping --}}
                    <td width="48%" valign="top" style="font-size:14px;">
                        <p style="margin:0 0 6px;font-weight:700;color:#111;">Tarneaadress</p>
                        @if ($shipping)
                            <p style="margin:0;color:#555;line-height:1.7;">
                                {{ trim(($shipping->company_name ?? '') . ' ' . ($shipping->first_name ?? '') . ' ' . ($shipping->last_name ?? '')) }}<br>
                                @if (!empty($shipping->address) || !empty($shipping->address1))
                                    {{ $shipping->address ?? $shipping->address1 }}<br>
                                @endif
                                {{ $shipping->city ?? '' }}{{ ($shipping->postcode && $shipping->postcode !== '0') ? ', ' . $shipping->postcode : '' }}<br>
                                {{ $shipping->country ?? '' }}
                            </p>
                        @else
                            <p style="margin:0;color:#aaa;font-size:13px;">Sama mis arveaadress</p>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Divider --}}
    <tr><td style="padding:0 32px;"><hr style="border:none;border-top:1px solid #e8e8e8;margin:0;"></td></tr>

    {{-- Footer --}}
    <tr>
        <td style="padding:24px 32px;background:#fafafa;border-radius:0 0 8px 8px;">
            <p style="margin:0 0 6px;font-size:13px;color:#888;line-height:1.6;">
                Küsimuste korral kirjuta meile:
                <a href="mailto:info@nailedit.ee" style="color:#2563eb;text-decoration:none;">info@nailedit.ee</a>
            </p>
            <p style="margin:0;font-size:12px;color:#bbb;">
                Nailedit Store &nbsp;|&nbsp; pood.nailedit.ee
            </p>
        </td>
    </tr>

</table>
</td></tr>
</table>

</body>
</html>
