<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $document->number }}</title>
    @php
        $logo = '';
        $logoPath = public_path('images/logo.png');
        if (file_exists($logoPath)) {
            try {
                $src = @imagecreatefrompng($logoPath);
                if ($src !== false) {
                    $h = 300;
                    $w = max(1, (int) floor(imagesx($src) * ($h / max(1, imagesy($src)))));
                    $thumb = imagecreatetruecolor($w, $h);
                    imagealphablending($thumb, false);
                    imagesavealpha($thumb, true);
                    imagecopyresampled($thumb, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
                    ob_start();
                    imagepng($thumb, null, 9);
                    $logo = 'data:image/png;base64,' . base64_encode(ob_get_clean());
                    imagedestroy($src);
                    imagedestroy($thumb);
                }
            } catch (\Throwable $e) {
                $logo = '';
            }
        }
    @endphp
    <style>
        @page { margin: 60px; }
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }
        .doc-wrap { width: 100%; }
        .navy { color: #1e3a5f; }
        .navy-bg { background-color: #1e3a5f; }
        .light-bg { background-color: #f1f5f9; }

        table.header { width: 100%; border-collapse: collapse; }
        table.header td { vertical-align: top; padding: 0; }
        .company-logo { height: 70px; margin-bottom: 8px; }
        .company-name { font-size: 22px; font-weight: bold; color: #1e3a5f; letter-spacing: 1px; }
        .company-line { font-size: 11px; color: #4b5563; line-height: 1.5; }

        .doc-title {
            text-align: right;
            display: block;
            font-size: 24px;
            font-weight: bold;
            color: #1e3a5f;
            letter-spacing: 2px;
        }
        .doc-number {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            color: #2d5a87;
            margin-top: 10px;
        }

        table.billto { width: 100%; border-collapse: collapse; margin-top: 28px; }
        table.billto td { padding: 0; vertical-align: top; }
        .bill-label { font-size: 10px; font-weight: bold; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .client-name { font-size: 15px; font-weight: bold; color: #1e3a5f; }
        .date-label { font-size: 10px; font-weight: bold; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; }
        .date-value { font-size: 13px; font-weight: bold; color: #1f2937; }

        table.items { width: 100%; border-collapse: collapse; margin-top: 26px; border: 1px solid #d1d5db; }
        table.items th {
            background-color: #1e3a5f;
            color: #ffffff;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 9px 10px;
        }
        table.items th.right { text-align: right; }
        table.items td {
            padding: 8px 10px;
            font-size: 11.5px;
            vertical-align: top;
            border-bottom: 1px solid #e5e7eb;
        }
        table.items td.right { text-align: right; }
        table.items tbody tr:nth-child(even) td { background-color: #f1f5f9; }
        .ttl { font-weight: bold; }
        .desc-txt { color: #4b5563; font-size: 11px; }

        table.totals { width: 100%; margin-top: 16px; }
        table.totals td { border-collapse: collapse; padding: 0; }
        .totals-box { width: 38%; float: right; }
        .totals-row { padding: 4px 0; font-size: 12px; color: #4b5563; }
        .totals-row td:last-child { text-align: right; font-weight: bold; color: #1f2937; }
        .grand-row {
            background-color: #1e3a5f;
            color: #ffffff;
            padding: 8px 10px;
        }
        .grand-row td:last-child { color: #ffffff; font-size: 14px; }

        .note-section { margin-top: 22px; }
        .note-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1e3a5f;
            margin-bottom: 6px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 4px;
        }
        .note-text { font-size: 11.5px; color: #374151; line-height: 1.6; }
        .footer-note { margin-top: 18px; background-color: #f1f5f9; border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px 12px; }

        .thanks { text-align: center; font-size: 13px; font-weight: bold; color: #1e3a5f; margin-top: 32px; }
        .powered {
            margin-top: 22px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
            letter-spacing: 1px;
        }
        .page-break-before { page-break-before: always; }
    </style>
</head>
<body>
<div class="doc-wrap">

    <table class="header">
        <tr>
            <td style="width:55%">
                @if($logo)
                    <img class="company-logo" src="{{ $logo }}">
                @else
                    <div class="company-name">Marsha Creatives</div>
                @endif
                <div class="company-line">Company ID: BN-9PCK2LQK</div>
                <div class="company-line">Nairobi, Kenya</div>
                <div class="company-line">info@marshacreatives.co.ke</div>
                <div class="company-line">www.marshacreatives.co.ke</div>
            </td>
            <td style="width:45%">
                <div class="doc-title">{{ $document->type_title }}</div>
                <div class="doc-number">{{ $document->number }}</div>
            </td>
        </tr>
    </table>

    <table class="billto">
        <tr>
            <td style="width:55%">
                <div class="bill-label">Bill To</div>
                <div class="client-name">{{ $document->client_name }}</div>
            </td>
            <td style="width:45%; text-align:right">
                <div class="date-label">Date</div>
                <div class="date-value">{{ $document->issue_date->format('jS F Y') }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:30%">Item</th>
                <th style="width:32%">Description</th>
                <th style="width:8%" class="right">Qty</th>
                <th style="width:14%" class="right">Price</th>
                <th style="width:16%" class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->items as $line)
                <tr>
                    <td class="ttl">{{ $line->title }}</td>
                    <td class="desc-txt">{{ $line->description }}</td>
                    <td class="right">{{ $line->quantity }}</td>
                    <td class="right">{{ number_format((float) $line->unit_price, 2) }}</td>
                    <td class="right ttl">{{ number_format((float) $line->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="overflow:hidden; margin-top:16px;">
        <table class="totals">
            <tr>
                <td style="width:62%">&nbsp;</td>
                <td class="totals-box">
                    <table class="totals">
                        <tr class="totals-row"><td>Subtotal</td><td>KSh {{ number_format($document->subtotal, 2) }}</td></tr>
                        @if((float) $document->discount > 0)
                            <tr class="totals-row"><td>Discount</td><td>− KSh {{ number_format((float) $document->discount, 2) }}</td></tr>
                        @else
                            <tr class="totals-row"><td>Discount</td><td>KSh 0.00</td></tr>
                        @endif
                        <tr class="grand-row"><td>Total</td><td>KSh {{ number_format((float) $document->total, 2) }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @if($document->notes)
        <div class="note-section">
            <div class="note-title">Notes</div>
            <div class="note-text">{{ $document->notes }}</div>
        </div>
    @endif

    @if($document->type === 'quote')
        <div class="note-section">
            <div class="note-title">Terms &amp; Conditions</div>
            <div class="note-text">
                <strong>Payment Terms:</strong>&nbsp; 50% downpayment and 50% after delivery.
            </div>
        </div>
        <div class="footer-note note-text">Looking forward for your business.</div>
    @elseif($document->type === 'invoice')
        <div class="note-section">
            <div class="note-title">Payment Details</div>
            <div class="note-text">
                Paybill: <strong>522533</strong> &nbsp;·&nbsp; Business number: <strong>8065332</strong>
            </div>
        </div>
    @endif

    <div class="thanks">
        @if($document->type === 'receipt')
            Thank you for your business.
        @else
            Thanks for your business.
        @endif
    </div>

    <div class="powered">Powered by Marsha Creatives</div>

</div>
</body>
</html>