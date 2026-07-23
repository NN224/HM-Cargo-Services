<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة الملصقات - {{ $shipment->reference }}</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f3f4f6;
            color: #000;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .no-print {
                display: none;
            }
            .page-break {
                page-break-after: always;
            }
        }
        .label-card {
            background: white;
            border: 2px solid #000;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            width: 100mm;
            height: 140mm;
            margin-left: auto;
            margin-right: auto;
            page-break-inside: avoid;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .text-center { text-align: center; }
        .barcode-container { margin: 15px 0; text-align: center; }
        .barcode-container svg { width: 100%; max-height: 100px; }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
            border-top: 2px solid #000;
            padding-top: 15px;
        }
        .detail-item { font-size: 16px; }
        .detail-label { font-weight: normal; color: #333; display: block; font-size: 14px; margin-bottom: 4px; }
        .detail-value { font-weight: bold; font-size: 18px; }
        .big-destination { 
            font-size: 28px; 
            font-weight: bold; 
            text-align: center; 
            margin-top: 20px; 
            border: 3px solid #000; 
            padding: 10px;
            border-radius: 4px;
        }
        .barcode-text {
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 2px;
        }
        .qr-container { margin: 12px 0; }
        .qr-code { width: 150px; height: 150px; margin: 0 auto; }
        .qr-code svg { width: 100%; height: 100%; }
        .tracking-url {
            font-size: 12px;
            direction: ltr;
            word-break: break-all;
            margin-top: 6px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="no-print text-center" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #000; color: #fff; border: none; border-radius: 4px;">طباعة الملصقات (A4)</button>
    </div>

    @foreach($labels as $label)
    <div class="label-card {{ !$loop->last ? 'page-break' : '' }}">
        <div>
            <div class="text-center">
                <h2 style="margin: 0; font-size: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px;">HM Cargo Services</h2>
            </div>
            
            <div class="barcode-container">
                <svg class="barcode" jsbarcode-value="{{ $label['package']->barcode }}" jsbarcode-displayvalue="false"></svg>
            </div>

            <div class="text-center barcode-text">
                {{ $label['package']->barcode }}
            </div>

            <div class="qr-container text-center">
                <div class="qr-code">{!! $label['qr'] !!}</div>
                <div class="tracking-url">{{ $label['tracking_url'] }}</div>
            </div>

            <div class="details-grid">
                <div class="detail-item">
                    <span class="detail-label">رقم الطرد</span>
                    <div class="detail-value">{{ $label['sequence'] }}</div>
                </div>
                <div class="detail-item">
                    <span class="detail-label">المرجع</span>
                    <div class="detail-value">{{ $shipment->reference }}</div>
                </div>
                <div class="detail-item" style="grid-column: span 2;">
                    <span class="detail-label">المستلم</span>
                    <div class="detail-value">{{ $shipment->recipient_name }}</div>
                </div>
            </div>
        </div>

        <div class="big-destination">
            {{ $destination }}
        </div>
    </div>
    @endforeach

    <script>
        JsBarcode(".barcode").init();
    </script>
</body>
</html>
