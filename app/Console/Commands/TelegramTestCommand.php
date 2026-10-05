<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TelegramTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test {--message= : Pesan kustom untuk dikirim}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim pesan uji coba untuk memverifikasi integrasi Telegram Bot';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegram): int
    {
        $this->info('Memeriksa konfigurasi Telegram Bot...');

        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');
        $enabled = config('services.telegram.enabled');

        $this->line("• TELEGRAM_ALERTS_ENABLED: " . ($enabled ? '<info>true</info>' : '<comment>false</comment>'));
        $this->line("• TELEGRAM_BOT_TOKEN: " . ($token ? '<info>' . substr($token, 0, 8) . '...' . substr($token, -4) . '</info>' : '<error>KOSONG</error>'));
        $this->line("• TELEGRAM_CHAT_ID: " . ($chatId ? '<info>' . $chatId . '</info>' : '<error>KOSONG</error>'));

        if (!$telegram->isConfigured()) {
            $this->error('Gagal: Pastikan TELEGRAM_BOT_TOKEN dan TELEGRAM_CHAT_ID telah diisi di file .env dan TELEGRAM_ALERTS_ENABLED=true.');
            return self::FAILURE;
        }

        $customMessage = $this->option('message');
        $now = Carbon::now()->format('d-m-Y H:i:s');

        if ($customMessage) {
            $text = "🔔 <b>[BOOKKEEPER TEST]</b>\n" . e($customMessage);
        } else {
            $text = "🔔 <b>[BOOKKEEPER NOTIFICATION TEST]</b>\n";
            $text .= "━━━━━━━━━━━━━━━━━━━━━━\n";
            $text .= "✅ Integrasi Telegram Bot berhasil terhubung!\n";
            $text .= "⏱️ Waktu Server: <code>{$now}</code>\n";
            $text .= "🚀 Sistem notifikasi peringatan dini siap digunakan.\n";
            $text .= "━━━━━━━━━━━━━━━━━━━━━━";
        }

        $this->info('Mengirim pesan pengujian ke Telegram...');
        $success = $telegram->sendMessage($text);

        if ($success) {
            $this->info('Berhasil! Pesan pengujian telah terkirim ke Telegram.');
            return self::SUCCESS;
        } else {
            $this->error('Gagal mengirim pesan ke Telegram. Periksa token bot, chat ID, atau koneksi internet.');
            return self::FAILURE;
        }
    }
}
