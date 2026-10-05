<?php

namespace App\Console\Commands;

use App\Models\NetworkMonitor;
use App\Models\NetworkUptimeLog;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NetworkHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'network:health-check {--seed : Seed default monitoring devices if empty} {--target= : Check specific IP or device ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pemeriksaan status berkala (health check) perangkat jaringan dan pengiriman peringatan Telegram';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegram): int
    {
        $this->info('[' . Carbon::now()->format('Y-m-d H:i:s') . '] Memulai pemeriksaan kesehatan jaringan...');

        // Auto-seed defaults if table is empty or --seed is provided
        if (NetworkMonitor::count() === 0 || $this->option('seed')) {
            $this->seedDefaults();
        }

        $query = NetworkMonitor::query()->where('is_active', true);
        if ($target = $this->option('target')) {
            $query->where(function ($q) use ($target) {
                $q->where('ip_address', $target)->orWhere('id', $target);
            });
        }

        $monitors = $query->get();

        if ($monitors->isEmpty()) {
            $this->warn('Tidak ada perangkat aktif untuk dimonitor.');
            return self::SUCCESS;
        }

        $failThreshold = 2; // Threshold consecutive failures before marking DOWN

        foreach ($monitors as $monitor) {
            $this->checkDevice($monitor, $telegram, $failThreshold);
        }

        // Prune logs older than 7 days to keep database lightweight
        $deleted = NetworkUptimeLog::where('checked_at', '<', Carbon::now()->subDays(7))->delete();
        if ($deleted > 0) {
            $this->line("Dibersihkan {$deleted} catatan uptime lama (> 7 hari).");
        }

        $this->info('[' . Carbon::now()->format('Y-m-d H:i:s') . '] Pemeriksaan kesehatan jaringan selesai.');
        return self::SUCCESS;
    }

    /**
     * Perform ping or socket check on a device and handle state transitions.
     */
    protected function checkDevice(NetworkMonitor $monitor, TelegramService $telegram, int $failThreshold): void
    {
        $ip = trim($monitor->ip_address);
        $port = $monitor->port;
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        $startTime = microtime(true);
        $isReachable = false;
        $responseTimeMs = null;

        if ($port) {
            // Socket connectivity check
            $errno = 0;
            $errstr = '';
            $fp = @fsockopen($ip, $port, $errno, $errstr, 1.5);
            if ($fp) {
                $responseTimeMs = round((microtime(true) - $startTime) * 1000, 2);
                $isReachable = true;
                fclose($fp);
            }
        } else {
            // ICMP Ping check
            $command = $isWindows
                ? "ping -n 1 -w 1000 " . escapeshellarg($ip)
                : "ping -c 1 -W 1 " . escapeshellarg($ip);

            $output = [];
            $resultCode = 1;
            exec($command, $output, $resultCode);

            if ($resultCode === 0) {
                $isReachable = true;
                $outputStr = implode(" ", $output);

                // Extract latency if available
                if (preg_match('/(?:time|waktu)[=<]([0-9.]+)\s*ms/i', $outputStr, $matches)) {
                    $responseTimeMs = floatval($matches[1]);
                } else {
                    $responseTimeMs = round((microtime(true) - $startTime) * 1000, 2);
                }
            }
        }

        $now = Carbon::now();
        $previousStatus = $monitor->status;

        if ($isReachable) {
            $downtimeDuration = null;
            if ($previousStatus === 'DOWN') {
                // Recovery state transition (DOWN -> UP)
                if ($monitor->last_status_change_at) {
                    $downtimeDuration = $monitor->last_status_change_at->diffForHumans($now, true);
                }

                $monitor->status = 'UP';
                $monitor->fail_count = 0;
                $monitor->response_time_ms = $responseTimeMs;
                $monitor->last_checked_at = $now;
                $monitor->last_status_change_at = $now;
                $monitor->save();

                $this->info("✅ [UP] {$monitor->name} ({$ip}) kembali ONLINE ({$responseTimeMs} ms)");
                Log::info("Network Health Check: Device {$monitor->name} ({$ip}) restored UP", [
                    'device_id' => $monitor->id,
                    'latency_ms' => $responseTimeMs,
                ]);

                $telegram->sendDeviceRecoveryAlert($monitor, $responseTimeMs, $downtimeDuration);
            } else {
                $monitor->status = 'UP';
                $monitor->fail_count = 0;
                $monitor->response_time_ms = $responseTimeMs;
                $monitor->last_checked_at = $now;
                if (!$monitor->last_status_change_at) {
                    $monitor->last_status_change_at = $now;
                }
                $monitor->save();

                $this->line("  ✓ {$monitor->name} ({$ip}): UP ({$responseTimeMs} ms)");
            }

            // Log uptime record
            NetworkUptimeLog::create([
                'network_monitor_id' => $monitor->id,
                'status'             => 'UP',
                'response_time_ms'   => $responseTimeMs,
                'checked_at'         => $now,
            ]);
        } else {
            $newFailCount = $monitor->fail_count + 1;
            $monitor->fail_count = $newFailCount;
            $monitor->last_checked_at = $now;
            $monitor->response_time_ms = null;

            if ($newFailCount >= $failThreshold && $previousStatus !== 'DOWN') {
                // Failure state transition (UP/PENDING -> DOWN)
                $monitor->status = 'DOWN';
                $monitor->last_status_change_at = $now;
                $monitor->save();

                $this->error("🚨 [DOWN] {$monitor->name} ({$ip}) tidak merespon (Percobaan ke-{$newFailCount})");
                Log::warning("Network Health Check: Device {$monitor->name} ({$ip}) is DOWN", [
                    'device_id' => $monitor->id,
                    'fail_count' => $newFailCount,
                ]);

                $telegram->sendDeviceDownAlert($monitor);
            } else {
                $monitor->save();
                $this->warn("  ✗ {$monitor->name} ({$ip}): Unreachable (Gagal {$newFailCount}x)");
            }

            // Log downtime record
            NetworkUptimeLog::create([
                'network_monitor_id' => $monitor->id,
                'status'             => 'DOWN',
                'response_time_ms'   => null,
                'checked_at'         => $now,
            ]);
        }

        // Recalculate 24-hour uptime percentage
        $monitor->calculateUptimePercentage(24);
    }

    /**
     * Auto-seed default monitoring targets from config and environment.
     */
    protected function seedDefaults(): void
    {
        $this->line('Menyiapkan daftar perangkat bawaan untuk monitoring...');

        // 1. Routers from config('services.router')
        $routers = config('services.router', []);
        if (is_array($routers)) {
            foreach ($routers as $router) {
                if (!empty($router['host'])) {
                    NetworkMonitor::firstOrCreate(
                        ['ip_address' => $router['host']],
                        [
                            'name'            => $router['name'] ?? 'MikroTik Router',
                            'type'            => 'router',
                            'port'            => null,
                            'status'          => 'PENDING',
                            'is_active'       => true,
                            'notify_telegram' => true,
                            'keterangan'      => 'Router MikroTik dari config services.router',
                        ]
                    );
                }
            }
        }

        // 2. Netdata Server from NETDATA_URL
        $netdataUrl = env('NETDATA_URL');
        if ($netdataUrl) {
            $parsedHost = parse_url($netdataUrl, PHP_URL_HOST);
            if ($parsedHost) {
                NetworkMonitor::firstOrCreate(
                    ['ip_address' => $parsedHost],
                    [
                        'name'            => 'Server Netdata',
                        'type'            => 'server',
                        'port'            => parse_url($netdataUrl, PHP_URL_PORT) ?: 19999,
                        'status'          => 'PENDING',
                        'is_active'       => true,
                        'notify_telegram' => true,
                        'keterangan'      => 'Server Monitoring Netdata',
                    ]
                );
            }
        }

        // 3. Internet Gateway / DNS Indicator
        NetworkMonitor::firstOrCreate(
            ['ip_address' => '8.8.8.8'],
            [
                'name'            => 'Internet Gateway (DNS Google)',
                'type'            => 'gateway',
                'port'            => null,
                'status'          => 'PENDING',
                'is_active'       => true,
                'notify_telegram' => true,
                'keterangan'      => 'Indikator ketersediaan koneksi internet',
            ]
        );

        $this->info('Daftar perangkat bawaan siap dipantau.');
    }
}
