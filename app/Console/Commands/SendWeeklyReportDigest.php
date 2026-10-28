<?php

namespace App\Console\Commands;

use App\Exports\ReportExport;
use App\Mail\ReportEmailMail;
use App\Models\User;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendWeeklyReportDigest extends Command
{
    protected $signature = 'haarsingharr:weekly-report';

    protected $description = 'Email the past week\'s sales report (XLSX attached) to super-admins.';

    public function handle(): int
    {
        $from = now()->subDays(7)->startOfDay();
        $to = now()->subMinutes(1);

        $summary = [
            'orders'   => \App\Models\Order::whereBetween('created_at', [$from, $to])->count(),
            'revenue'  => (float) \App\Models\Order::whereBetween('created_at', [$from, $to])->sum('grand_total'),
            'paid'     => (float) \App\Models\Order::whereBetween('created_at', [$from, $to])->sum('paid_amount'),
            'refunded' => (float) \App\Models\Order::whereBetween('created_at', [$from, $to])->sum('refunded_amount'),
        ];

        $rows = $this->salesRows($from, $to);
        $heads = [['Order', 'Date', 'Status', 'Payment Status', 'Grand Total', 'Paid', 'Refunded']];
        $attachment = (string) Excel::raw(
            new ReportExport($heads[0], $rows, 'Sales'),
            'Xlsx',
        );

        $recipients = User::where('type', 'admin')->get()
            ->filter(fn ($u) => $u->isSuperAdmin());

        foreach ($recipients as $user) {
            Mail::to($user->email)->send(new ReportEmailMail('sales', $from, $to, $attachment));
        }

        $this->info(sprintf(
            'Weekly report sent to %d super-admin(s) — %s orders, Rs. %s revenue.',
            count($recipients),
            number_format($summary['orders']),
            number_format($summary['revenue']),
        ));

        return self::SUCCESS;
    }

    /** Mirror of ReportController::rowsFor('sales', ...) */
    private function salesRows(Carbon $from, Carbon $to): array
    {
        return \App\Models\Order::whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get()
            ->map(fn ($order) => [
                $order->number,
                $order->created_at?->toDateTimeString(),
                is_object($order->status) ? $order->status->value : $order->status,
                is_object($order->payment_status) ? $order->payment_status->value : $order->payment_status,
                (float) $order->grand_total,
                (float) $order->paid_amount,
                (float) $order->refunded_amount,
            ])
            ->all();
    }
}
