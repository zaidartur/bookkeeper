<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PollMonitoringCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:poll';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll Netdata nodes and devices in the background and cache the result';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $baseUrl = env('NETDATA_URL', 'http://10.20.33.235:19999');
        $endpoint = "{$baseUrl}/api/v3/nodes";

        $this->info("Fetching Netdata nodes from {$endpoint}...");

        try {
            $response = Http::timeout(4)->get($endpoint);

            if ($response->successful()) {
                $res = $response->json();
                $devices = [];

                if (isset($res['nodes']) && is_array($res['nodes']) && count($res['nodes']) > 0) {
                    $i = 0;
                    foreach ($res['nodes'] as $value) {
                        $devices[$i] = [
                            'hostname'  => $value['nm'] ?? '',
                            'uid'       => $value['mg'] ?? '',
                            'reachable' => ($value['state'] ?? '') === 'reachable',
                            'ip'        => $value['labels']['_net_default_iface_ip'] ?? '',
                        ];

                        if (!empty($value['labels']['_os'])) {
                            $devices[$i] += [
                                'type'  => 'server',
                                'label' => trim(($value['os']['nm'] ?? '') . ' ' . ($value['os']['v'] ?? '')),
                            ];
                        } else {
                            $devices[$i] += [
                                'type'  => 'router',
                                'label' => $value['labels']['description'] ?? 'Router',
                            ];
                        }

                        $i++;
                    }
                }

                // Cache the device nodes for 60 seconds
                Cache::put('netdata_device_nodes', $devices, now()->addSeconds(60));
                $count = count($devices);
                $this->info("Successfully cached {$count} Netdata devices.");
                return Command::SUCCESS;
            } else {
                $this->warn("Netdata responded with status {$response->status()}");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("Failed to connect to Netdata: {$e->getMessage()}");
            Log::warning("PollMonitoringCommand failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
