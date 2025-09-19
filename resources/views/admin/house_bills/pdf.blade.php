<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>House Bills Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 16px;
            color: #666;
        }
        .filters {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .summary {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-item .label {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .summary-item .value {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .status-partpayment { background-color: #cff4fc; color: #055160; }
        .status-extrapayment { background-color: #e2e3e5; color: #383d41; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        @page {
            margin: 15mm;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Housing Scheme - House Bills Report</h1>
        <h2>Generated on {{ now()->format('F j, Y g:i A') }}</h2>
    </div>

    <div class="filters">
        <strong>Applied Filters:</strong> {{ $filtersText }}
    </div>

    @if($bills->count() > 0)
        <div class="summary">
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="label">Total Bill Amount</div>
                    <div class="value">₹{{ number_format($totalBillAmount, 2) }}</div>
                </div>
                <div class="summary-item">
                    <div class="label">Total Paid Amount</div>
                    <div class="value">₹{{ number_format($totalPaidAmount, 2) }}</div>
                </div>
                <div class="summary-item">
                    <div class="label">Total Outstanding</div>
                    <div class="value">₹{{ number_format($totalBalance, 2) }}</div>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>House No</th>
                    <th>Month</th>
                    <th>Reading</th>
                    <th class="text-right">Usage</th>
                    <th class="text-right">Bill Amount</th>
                    <th class="text-right">Paid Amount</th>
                    <th class="text-right">Balance</th>
                    <th class="text-center">Method</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bills as $bill)
                    @php
                        $usage = max(0, ($bill->readingUnit - $bill->openingReadingUnit));
                        $balance = max(0, (float)$bill->billAmount - (float)$bill->paidAmount);
                        $statusClass = 'status-' . strtolower($bill->status);
                    @endphp
                    <tr>
                        <td>{{ $bill->houseNo }}</td>
                        <td>{{ $bill->month }}</td>
                        <td>{{ $bill->openingReadingUnit }} → {{ $bill->readingUnit }}</td>
                        <td class="text-right">{{ $usage }}</td>
                        <td class="text-right">₹{{ number_format($bill->billAmount, 2) }}</td>
                        <td class="text-right">₹{{ number_format($bill->paidAmount, 2) }}</td>
                        <td class="text-right">₹{{ number_format($balance, 2) }}</td>
                        <td class="text-center">{{ $bill->paymentMethod ? strtoupper($bill->paymentMethod) : '-' }}</td>
                        <td class="text-center">
                            <span class="status-badge {{ $statusClass }}">{{ $bill->status }}</span>
                        </td>
                        <td class="text-center">{{ $bill->timestamp ? $bill->timestamp->format('M j, Y') : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 20px; font-size: 11px;">
            <strong>Total Records:</strong> {{ $bills->count() }} |
            <strong>Water Unit Price:</strong> ₹{{ number_format($unitPrice, 2) }} per unit |
            <strong>Sewerage Charge:</strong> ₹{{ number_format($sewerage, 2) }} |
            <strong>Service Charge:</strong> ₹{{ number_format($service, 2) }}
        </div>
    @else
        <div class="no-data">
            <h3>No bills found matching the selected criteria.</h3>
            <p>Please adjust your filters and try again.</p>
        </div>
    @endif

    <div class="footer">
        <p>Housing Scheme Management System - House Bills Report</p>
        <p>This is a computer-generated document. No signature required.</p>
    </div>
</body>
</html>