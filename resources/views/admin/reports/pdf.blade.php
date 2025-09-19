<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        .header h1 {
            margin: 0;
            color: #333;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .period {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            text-align: center;
        }
        .period h3 {
            margin: 0;
            color: #333;
            font-size: 16px;
        }
        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 15px;
        }
        .summary-item {
            text-align: center;
            flex: 1;
            background-color: #e8f4fd;
            padding: 15px;
            border-radius: 5px;
        }
        .summary-item h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 12px;
            text-transform: uppercase;
        }
        .summary-item p {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            color: #0066cc;
        }
        .breakdown {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 30px;
        }
        .breakdown-section {
            flex: 1;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
        }
        .breakdown-section h3 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 14px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .breakdown-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dotted #eee;
        }
        .breakdown-item:last-child {
            border-bottom: none;
            border-top: 1px solid #333;
            font-weight: bold;
            margin-top: 10px;
            padding-top: 10px;
        }
        .currency {
            font-weight: bold;
            color: #0066cc;
        }
        .positive {
            color: #28a745;
        }
        .negative {
            color: #dc3545;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 10px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Financial Report</h1>
        <p>Housing Scheme Management System</p>
        <p>Generated on {{ \Carbon\Carbon::now()->format('F j, Y \a\t g:i A') }}</p>
    </div>

    <div class="period">
        <h3>Report Period: {{ $from->format('F j, Y') }} to {{ $to->format('F j, Y') }}</h3>
    </div>

    <div class="summary">
        <div class="summary-item">
            <h4>Total Income (Cash)</h4>
            <p class="currency positive">₹{{ number_format($total_income, 2) }}</p>
        </div>
        <div class="summary-item">
            <h4>Total Expenses</h4>
            <p class="currency negative">₹{{ number_format($total_expenses, 2) }}</p>
        </div>
        <div class="summary-item">
            <h4>Pending Amount</h4>
            <p class="currency" style="color: #ffc107;">₹{{ number_format($total_pending, 2) }}</p>
        </div>
        <div class="summary-item">
            <h4>Net Profit/Loss</h4>
            <p class="currency {{ $net >= 0 ? 'positive' : 'negative' }}">₹{{ number_format($net, 2) }}</p>
        </div>
    </div>

    <div style="margin-bottom: 30px;">
        <h3 style="margin: 0 0 15px 0; color: #333; font-size: 16px; text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px;">Billing Summary (All Time up to {{ $to->format('M j, Y') }})</h3>
        
        <div class="breakdown">
            <div class="breakdown-section">
                <h3>House Rentals</h3>
                <div class="breakdown-item">
                    <span>Total Billed</span>
                    <span class="currency">₹{{ number_format($house_total_billed, 2) }}</span>
                </div>
                <div class="breakdown-item">
                    <span>Total Received</span>
                    <span class="currency">₹{{ number_format($house_total_received, 2) }}</span>
                </div>
                <div class="breakdown-item">
                    <span>Pending Amount</span>
                    <span class="currency" style="color: #ffc107;">₹{{ number_format($house_pending, 2) }}</span>
                </div>
            </div>

            <div class="breakdown-section">
                <h3>Shop Rentals</h3>
                <div class="breakdown-item">
                    <span>Total Billed</span>
                    <span class="currency">₹{{ number_format($shop_total_billed, 2) }}</span>
                </div>
                <div class="breakdown-item">
                    <span>Total Received</span>
                    <span class="currency">₹{{ number_format($shop_total_received, 2) }}</span>
                </div>
                <div class="breakdown-item">
                    <span>Pending Amount</span>
                    <span class="currency" style="color: #ffc107;">₹{{ number_format($shop_pending, 2) }}</span>
                </div>
            </div>

            <div class="breakdown-section">
                <h3>Grand Totals</h3>
                <div class="breakdown-item">
                    <span>Total Billed</span>
                    <span class="currency">₹{{ number_format($grand_total_billed, 2) }}</span>
                </div>
                <div class="breakdown-item">
                    <span>Total Received</span>
                    <span class="currency">₹{{ number_format($grand_total_received, 2) }}</span>
                </div>
                <div class="breakdown-item">
                    <span>Total Pending</span>
                    <span class="currency" style="color: #ffc107;">₹{{ number_format($total_pending, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div style="margin-bottom: 30px;">
        <h3 style="margin: 0 0 15px 0; color: #333; font-size: 16px; text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px;">Period Summary ({{ $from->format('M j, Y') }} to {{ $to->format('M j, Y') }})</h3>
        
        <div class="breakdown">
            <div class="breakdown-section">
                <h3>Period Collections</h3>
                <div class="breakdown-item">
                    <span>House Collections</span>
                    <span class="currency">₹{{ number_format($income_house, 2) }}</span>
                </div>
                <div class="breakdown-item">
                    <span>Shop Collections</span>
                    <span class="currency">₹{{ number_format($income_shop, 2) }}</span>
                </div>
                <div class="breakdown-item">
                    <span>Inventory Sales</span>
                    <span class="currency">₹{{ number_format($income_inv, 2) }}</span>
                </div>
                <div class="breakdown-item">
                    <span>Total Collections</span>
                    <span class="currency">₹{{ number_format($total_income, 2) }}</span>
                </div>
            </div>

            <div class="breakdown-section">
                <h3>Period Billing</h3>
                <div class="breakdown-item">
                    <span>House Bills</span>
                    <span class="currency">₹{{ number_format($house_billed, 2) }}</span>
                </div>
                <div class="breakdown-item">
                    <span>Shop Bills</span>
                    <span class="currency">₹{{ number_format($shop_billed, 2) }}</span>
                </div>
                <div class="breakdown-item">
                    <span>Total Bills</span>
                    <span class="currency">₹{{ number_format($total_billed, 2) }}</span>
                </div>
            </div>

            <div class="breakdown-section">
                <h3>Period Expenses</h3>
                <div class="breakdown-item">
                    <span>Payroll</span>
                    <span class="currency">₹{{ number_format($exp_payroll, 2) }}</span>
                </div>
                <div class="breakdown-item">
                    <span>Other Expenses</span>
                    <span class="currency">₹{{ number_format($exp_other, 2) }}</span>
                </div>
                <div class="breakdown-item">
                    <span>Total Expenses</span>
                    <span class="currency">₹{{ number_format($total_expenses, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; text-align: center;">
        <h3 style="margin: 0 0 10px 0; color: #333;">Financial Summary</h3>
        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
            <div style="flex: 1; padding: 0 10px;">
                <p style="margin: 0; font-size: 14px; color: #666;">Cash Flow (Period)</p>
                <p style="margin: 5px 0; font-size: 18px;">
                    <span class="currency {{ $net >= 0 ? 'positive' : 'negative' }}">
                        ₹{{ number_format($net, 2) }}
                    </span>
                </p>
            </div>
            <div style="flex: 1; padding: 0 10px;">
                <p style="margin: 0; font-size: 14px; color: #666;">Outstanding (Total)</p>
                <p style="margin: 5px 0; font-size: 18px;">
                    <span class="currency" style="color: #ffc107;">
                        ₹{{ number_format($total_pending, 2) }}
                    </span>
                </p>
            </div>
            <div style="flex: 1; padding: 0 10px;">
                <p style="margin: 0; font-size: 14px; color: #666;">Collection Rate</p>
                <p style="margin: 5px 0; font-size: 18px;">
                    <span class="currency" style="color: #17a2b8;">
                        {{ $grand_total_billed > 0 ? number_format(($grand_total_received / $grand_total_billed) * 100, 1) : '0.0' }}%
                    </span>
                </p>
            </div>
        </div>
        <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">
            Report Period: {{ $from->format('M j, Y') }} to {{ $to->format('M j, Y') }}
        </p>
    </div>

    <div class="footer">
        <p>This report was generated automatically by the Housing Scheme Management System</p>
        <p>Report Period: {{ $from->format('F j, Y') }} - {{ $to->format('F j, Y') }}</p>
    </div>
</body>
</html>