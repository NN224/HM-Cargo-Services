<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كشف حساب - {{ $customer->name }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 40px;
            background: #f3f4f6;
            color: #111827;
        }
        @media print {
            @page {
                size: A4;
                margin: 0; /* Removes browser headers and footers (like URL) */
            }
            body {
                background: white;
                padding: 1.5cm; /* Add padding here so content doesn't touch edges */
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        @media print {
            .container {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
        }
        
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logo-container img {
            height: 60px;
            width: auto;
        }
        .company-title {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-box {
            background: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        .info-label {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 16px;
            font-weight: 700;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 40px;
        }
        .summary-box {
            background: #ffffff;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        .summary-box.highlight {
            background: #fef2f2;
            border-color: #fecaca;
        }
        .summary-box.highlight .info-value {
            color: #dc2626;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: right;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        .amount-col {
            text-align: left;
            font-family: monospace;
            font-size: 15px;
            direction: ltr;
        }
        .balance-col {
            text-align: left;
            font-family: monospace;
            font-size: 15px;
            font-weight: 700;
            direction: ltr;
        }
        
        .text-red { color: #dc2626; }
        .text-green { color: #059669; }
        .text-gray { color: #6b7280; }
        
        .controls {
            margin-bottom: 20px;
            text-align: center;
        }
        .btn-print {
            padding: 10px 20px;
            background: #111827;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-close {
            padding: 10px 20px;
            background: #ef4444;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    @php
        function formatUsd(int $cents): string {
            $sign = $cents < 0 ? '-' : '';
            $abs = abs($cents);
            return $sign . '$' . intdiv($abs, 100) . '.' . str_pad((string)($abs % 100), 2, '0', STR_PAD_LEFT);
        }
    @endphp

    <div class="no-print controls">
        <button onclick="window.print()" class="btn-print">
            🖨️ طباعة كشف الحساب
        </button>
        <a href="{{ route('filament.admin.resources.customers.statement', $customer) }}" class="btn-close">
            ❌ إغلاق / العودة
        </a>
    </div>

    <div class="container">
        <div class="header-section">
            <div class="logo-container">
                <img src="{{ asset('images/logo.png') }}" alt="Logo">
                <div>
                    <h1 class="company-title">HM Cargo Services</h1>
                    <div style="color: #6b7280; margin-top: 5px;">كشف حساب عميل</div>
                </div>
            </div>
            <div style="text-align: left; color: #6b7280; font-size: 14px;">
                تاريخ الإصدار: <span style="direction: ltr; display: inline-block;">{{ now()->format('Y-m-d') }}</span>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <div class="info-label">معلومات العميل</div>
                <div class="info-value">{{ $customer->name }}</div>
                <div style="color: #4b5563; margin-top: 5px; direction: ltr; text-align: right;">{{ $customer->phone }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">معلومات الشركة</div>
                <div class="info-value">HM Cargo Services</div>
                <div style="color: #4b5563; margin-top: 5px; direction: ltr; text-align: right;">+971521616814</div>
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-box">
                <div class="info-label">إجمالي الفوترة</div>
                <div class="info-value">{{ formatUsd($summary['total_charged_cents']) }}</div>
            </div>
            <div class="summary-box">
                <div class="info-label">إجمالي المدفوع</div>
                <div class="info-value">{{ formatUsd($summary['total_paid_cents']) }}</div>
            </div>
            <div class="summary-box highlight">
                <div class="info-label">المستحق</div>
                <div class="info-value">{{ formatUsd($summary['outstanding_cents']) }}</div>
            </div>
            <div class="summary-box">
                <div class="info-label">رصيد غير مخصّص</div>
                <div class="info-value">{{ formatUsd($summary['unapplied_credit_cents']) }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>البيان</th>
                    <th class="amount-col" style="text-align: left;">المبلغ</th>
                    <th class="balance-col" style="text-align: left;">الرصيد التراكمي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lines as $line)
                <tr>
                    <td style="direction: ltr; text-align: right;">{{ \Carbon\Carbon::parse($line['date'])->format('Y-m-d H:i') }}</td>
                    <td style="font-weight: 600;">{{ $line['description'] }}</td>
                    <td class="amount-col {{ $line['type'] === 'charge' ? 'text-red' : ($line['type'] === 'allocation' ? 'text-gray' : 'text-green') }}">
                        {{ formatUsd($line['amount_cents']) }}
                    </td>
                    <td class="balance-col">
                        {{ formatUsd($line['running_balance_cents']) }}
                    </td>
                </tr>
                @endforeach
                @if(empty($lines))
                <tr>
                    <td colspan="4" style="text-align: center; padding: 30px; color: #6b7280;">لا توجد حركات مالية لهذا العميل</td>
                </tr>
                @endif
            </tbody>
        </table>
        
        <div style="margin-top: 50px; text-align: center; color: #9ca3af; font-size: 12px; border-top: 1px solid #e5e7eb; padding-top: 15px;">
            نظام إدارة الشحنات - HM Cargo Services
        </div>
    </div>
</body>
</html>
