<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected $token;
    protected $chatId;

    public function __construct()
    {
        $this->token = config('services.telegram.token');
        $this->chatId = config('services.telegram.chat_id');
    }

    public function sendOrderNotification($order, $title = 'New Order')
    {
        if (!$this->token || !$this->chatId) {
            Log::warning('Telegram notification not sent: Token or Chat ID missing.');
            return;
        }

        $order->load(['items.variant.product', 'table']);
        
        $isPaid = ($title === 'Order Paid');
        $icon = $isPaid ? '✅' : '🔔';
        $statusLabel = $isPaid ? 'PAID' : 'NEW';

        $message = "<b>{$icon} ORDER {$statusLabel} #{$order->id}</b>\n\n";
        $message .= "🏢 <b>Location:</b> Floor " . ($order->table->floor ?? 'N/A') . " - Table " . ($order->table->number ?? 'N/A') . "\n";
        $message .= "🍽 <b>Type:</b> " . ucfirst(str_replace('_', ' ', $order->type)) . "\n\n";
        $message .= "📋 <b>Items:</b>\n";
        
        foreach ($order->items as $item) {
            $name = $item->variant->product->name ?? 'Unknown Product';
            $message .= "• {$item->quantity}x {$name} ({$item->variant->size_name}) | \${$item->subtotal}\n";
        }
        
        $message .= "\n💰 <b>Total Amount:</b> \${$order->total}";
        
        if ($isPaid) {
            $message .= "\n\n<i>Thank you for your business!</i>";
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            if ($response->failed()) {
                Log::error('Telegram notification failed: ' . $response->body());
            } else {
                Log::info('Telegram notification sent successfully.');
            }
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram notification: ' . $e->getMessage());
        }
    }

    public function sendReportNotification($message)
    {
        if (!$this->token || !$this->chatId) {
            Log::warning('Telegram report not sent: Token or Chat ID missing.');
            return;
        }

        try {
            Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram report: ' . $e->getMessage());
        }
    }
}
