<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inventory Sales Report</title>
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
        <h1>Housing Scheme - Inventory Sales Report</h1>
        <h2>Generated on {{ now()->format('F j, Y g:i A') }}</h2>
    </div>

    <div class="filters">
        <strong>Applied Filters:</strong> {{ $filtersText }}
    </div>

    @if($rows->count() > 0)
        <div class="summary">
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="label">Total Sales Amount</div>
                    <div class="value">₹{{ number_format($totalAmount, 2) }}</div>
                </div>
                <div class="summary-item">
                    <div class="label">Total Quantity Sold</div>
                    <div class="value">{{ number_format($totalQty) }}</div>
                </div>
                <div class="summary-item">
                    <div class="label">Number of Sales</div>
                    <div class="value">{{ $rows->count() }}</div>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Item</th>
                    <th class="text-right">Quantity</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total Amount</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $sale)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($sale->date)->format('M j, Y') }}</td>
                        <td>{{ $sale->item }}</td>
                        <td class="text-right">{{ $sale->qty }}</td>
                        <td class="text-right">₹{{ number_format($sale->unit_price, 2) }}</td>
                        <td class="text-right">₹{{ number_format($sale->total, 2) }}</td>
                        <td>{{ $sale->note ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <td colspan="2">TOTALS</td>
                    <td class="text-right">{{ number_format($totalQty) }}</td>
                    <td class="text-right">-</td>
                    <td class="text-right">₹{{ number_format($totalAmount, 2) }}</td>
                    <td>-</td>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top: 20px; font-size: 11px;">
            <strong>Total Records:</strong> {{ $rows->count() }}
        </div>
    @else
        <div class="no-data">
            <h3>No inventory sales found matching the selected criteria.</h3>
            <p>Please adjust your filters and try again.</p>
        </div>
    @endif

    <div class="footer">
        <p>Housing Scheme Management System - Inventory Sales Report</p>
        <p>This is a computer-generated document. No signature required.</p>
    </div>
</body>
</html>