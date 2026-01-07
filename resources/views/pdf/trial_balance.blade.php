<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Trial Balance</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .sub {
            color: #666;
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
        }

        th {
            background: #f5f5f5;
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .muted {
            color: #777;
            font-size: 11px;
        }
    </style>
</head>

<body>
    <div class="title">{{ $companyName }} — Trial Balance</div>
    <div class="sub">
        Period:
        {{ $filters['from'] ?: '—' }} → {{ $filters['to'] ?: '—' }}
        | Status: {{ $filters['status'] ?: '—' }}
        @if (!empty($filters['show_zero']))
            | Show zero accounts
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 16%;">Code</th>
                <th>Account</th>
                <th style="width: 18%;">Type</th>
                <th class="right" style="width: 16%;">Ending Debit (RM)</th>
                <th class="right" style="width: 16%;">Ending Credit (RM)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r['account_code'] }}</td>
                    <td>
                        {{ $r['name'] }}
                        @if (empty($r['is_active']))
                            <span class="muted">(inactive)</span>
                        @endif
                    </td>
                    <td>{{ $r['type'] }}</td>
                    <td class="right">{{ number_format((float) $r['ending_debit'], 2) }}</td>
                    <td class="right">{{ number_format((float) $r['ending_credit'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="muted">No data found.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3">Total</th>
                <th class="right">{{ number_format((float) $totals['ending_debit'], 2) }}</th>
                <th class="right">{{ number_format((float) $totals['ending_credit'], 2) }}</th>
            </tr>
        </tfoot>
    </table>
</body>

</html>
