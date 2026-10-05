<?php

namespace App\Services;

use App\Models\NetworkMonitor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected ?string $botToken;
    protected ?string $chatId;
    protected bool $enabled;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->chatId   = config('services.telegram.chat_id');
        $this->enabled  = (bool) config('services.telegram.enabled', true);
    }

    /**
     * Check if Telegram notifications are configured and active.
     */
    public function isConfigured(): bool
    {
        return $this->enabled && !empty($this->botToken) && !empty($this->chatId);
    }

    /**
     * Send raw text message via Telegram Bot API.
     */
    public function sendMessage(string $text, string $parseMode = 'HTML'): bool
    {
        if (!$this->isConfigured()) {
            Log::debug('Telegram notification skipped: Bot token or Chat ID is not configured.');
            return false;
        }

        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
            $response = Http::timeout(5)->post($url, [
                'chat_id'                  => $this->chatId,
                'text'                     => $text,
                'parse_mode'               => $parseMode,
                'disable_web_page_preview' => true,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('Failed to send Telegram message', [
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception while sending Telegram message: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send alert when a monitored network device is DOWN.
     */
    public function sendDeviceDownAlert(NetworkMonitor $device, ?string $reason = null): bool
    {
        if (!$device->notify_telegram) {
            return false;
        }

        $now = Carbon::now()->format('d-m-Y H:i:s');
        $type = strtoupper($device->type ?? 'DEVICE');
        $failCount = $device->fail_count ?: 1;
        $reasonText = $reason ?: "Tidak ada respon setelah {$failCount}x percobaan ping berturut-turut.";

        $msg = "🚨 <b>[ALERT] PERANGKAT DOWN!</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "📡 <b>Perangkat:</b> {$device->name}\n";
        $msg .= "🌐 <b>IP Target:</b> <code>{$device->ip_address}</code>\n";
        $msg .= "🏷️ <b>Tipe:</b> {$type}\n";
        $msg .= "⏱️ <b>Waktu Kejadian:</b> {$now}\n";
        $msg .= "⚠️ <b>Detail:</b> {$reasonText}\n";
        if (!empty($device->keterangan)) {
            $msg .= "📝 <b>Catatan:</b> {$device->keterangan}\n";
        }
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "<i>Sistem Monitoring Proaktif Bookkeeper</i>";

        return $this->sendMessage($msg);
    }

    /**
     * Send recovery alert when a monitored network device is back UP.
     */
    public function sendDeviceRecoveryAlert(NetworkMonitor $device, ?float $responseTimeMs = null, ?string $downtimeDuration = null): bool
    {
        if (!$device->notify_telegram) {
            return false;
        }

        $now = Carbon::now()->format('d-m-Y H:i:s');
        $type = strtoupper($device->type ?? 'DEVICE');
        $latency = $responseTimeMs !== null ? "{$responseTimeMs} ms" : 'Normal';

        $msg = "✅ <b>[RECOVERY] PERANGKAT NORMAL KEMBALI!</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "📡 <b>Perangkat:</b> {$device->name}\n";
        $msg .= "🌐 <b>IP Target:</b> <code>{$device->ip_address}</code>\n";
        $msg .= "🏷️ <b>Tipe:</b> {$type}\n";
        $msg .= "⏱️ <b>Waktu Pulih:</b> {$now}\n";
        $msg .= "⚡ <b>Latensi:</b> {$latency}\n";
        if ($downtimeDuration) {
            $msg .= "⌛ <b>Estimasi Durasi Down:</b> {$downtimeDuration}\n";
        }
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "<i>Sistem Monitoring Proaktif Bookkeeper</i>";

        return $this->sendMessage($msg);
    }
}
