<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Services\TelegramService;
use Carbon\Carbon;

class SendDailyReport extends Command
{
    protected $signature = 'report:daily';
    protected $description = 'Send daily sales report to Telegram';

    protected $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    public function handle()
    {
        $today = Carbon::today();
        
        $orders = Order::whereDate('created_at', $today)->get();
        $totalRevenue = $orders->sum('total');
        $totalOrders = $orders->count();
        $totalPaid = $orders->where('payment_status', 'paid')->count();
        $totalPending = $orders->where('payment_status', 'pending')->count();

        $message = "<b>📊 Daily Report - " . $today->format('Y-m-d') . "</b>\n\n";
        $message .= "💰 <b>Total Revenue:</b> \${$totalRevenue}\n";
        $message .= "📝 <b>Total Orders:</b> {$totalOrders}\n";
        $message .= "✅ <b>Paid Orders:</b> {$totalPaid}\n";
        $message .= "⏳ <b>Pending Orders:</b> {$totalPending}\n";

        // Send via Telegram
        $this->telegramService->sendReportNotification($message);

        $this->info('Daily report sent successfully.');
    }
}
