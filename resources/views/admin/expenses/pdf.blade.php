<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses Report</title>
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
        .filters {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .filters h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 14px;
        }
        .filter-item {
            margin-bottom: 5px;
        }
        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            background-color: #e8f4fd;
            padding: 15px;
            border-radius: 5px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-item h4 {
            margin: 0;
            color: #333;
            font-size: 12px;
            text-transform: uppercase;
        }
        .summary-item p {
            margin: 5px 0 0 0;
            font-size: 16px;
            font-weight: bold;
            color: #0066cc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
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
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            background-color: #e8f4fd !important;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 10px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .currency {
            font-weight: bold;
            color: #0066cc;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Expenses Report</h1>
        <p>Generated on {{ \Carbon\Carbon::now()->format('F j, Y \a\t g:i A') }}</p>
    </div>

    <div class="filters">
        <h3>Applied Filters:</h3>
        <div class="filter-item">
            <strong>Date Range:</strong> 
            @if(request('from_date') || request('to_date'))
                {{ request('from_date') ? \Carbon\Carbon::parse(request('from_date'))->format('M j, Y') : 'Beginning' }} 
                to 
                {{ request('to_date') ? \Carbon\Carbon::parse(request('to_date'))->format('M j, Y') : 'Present' }}
            @else
                All Records
            @endif
        </div>
        @if(request('name'))
            <div class="filter-item">
                <strong>Expense Name:</strong> {{ request('name') }}
            </div>
        @endif
    </div>

    <div class="summary">
        <div class="summary-item">
            <h4>Total Expenses</h4>
            <p class="currency">₹{{ number_format($expenses->sum('amount'), 2) }}</p>
        </div>
        <div class="summary-item">
            <h4>Total Records</h4>
            <p>{{ $expenses->count() }}</p>
        </div>
        <div class="summary-item">
            <h4>Average Expense</h4>
            <p class="currency">₹{{ $expenses->count() > 0 ? number_format($expenses->avg('amount'), 2) : '0.00' }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 8%;">#</th>
                <th style="width: 15%;">Date</th>
                <th style="width: 25%;">Expense Name</th>
                <th style="width: 15%;">Amount</th>
                <th style="width: 37%;">Note</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $index => $expense)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($expense->date)->format('M j, Y') }}</td>
                    <td>{{ $expense->name }}</td>
                    <td class="text-right currency">₹{{ number_format($expense->amount, 2) }}</td>
                    <td>{{ $expense->note ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #666; font-style: italic;">
                        No expenses found for the selected criteria
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($expenses->count() > 0)
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" class="text-right"><strong>Total:</strong></td>
                    <td class="text-right currency"><strong>₹{{ number_format($expenses->sum('amount'), 2) }}</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">
        <p>This report was generated automatically by the Housing Scheme Management System</p>
        <p>Report includes {{ $expenses->count() }} expense record(s)</p>
    </div>
</body>
</html>