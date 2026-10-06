<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Query;

class Router extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'host',
        'port',
        'user',
        'pass',
        'is_active',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'user'      => 'encrypted',
            'pass'      => 'encrypted',
            'is_active' => 'boolean',
            'port'      => 'integer',
        ];
    }

    protected $hidden = [
        'pass',
    ];

    public function bandwidth_logs(): HasMany
    {
        return $this->hasMany(RouterBandwidthLog::class, 'router_id', 'id');
    }

    /**
     * Auto-seed default routers from config if database is empty.
     */
    public static function seedDefaultRoutersIfEmpty(): void
    {
        if (self::count() === 0) {
            $configRouters = config('services.router');
            if (is_array($configRouters) && count($configRouters) > 0) {
                foreach ($configRouters as $cr) {
                    self::create([
                        'uuid'       => (string) Str::uuid(),
                        'name'       => $cr['name'] ?? 'MikroTik Router',
                        'host'       => $cr['host'] ?? '192.168.1.1',
                        'port'       => intval($cr['port'] ?? 8728),
                        'user'       => $cr['user'] ?? 'admin',
                        'pass'       => $cr['pass'] ?? 'admin',
                        'is_active'  => true,
                        'keterangan' => 'Imported from initial config',
                    ]);
                }
            } else {
                self::create([
                    'uuid'       => (string) Str::uuid(),
                    'name'       => 'Router Utama MikroTik',
                    'host'       => env('ROUTER_HOST', '192.168.1.1'),
                    'port'       => intval(env('ROUTER_PORT', 8728)),
                    'user'       => env('ROUTER_USER', 'admin'),
                    'pass'       => env('ROUTER_PASSWORD', 'admin'),
                    'is_active'  => true,
                    'keterangan' => 'Core Router Gateway',
                ]);
            }
        }
    }

    /**
     * Test connection to a MikroTik router using provided credentials.
     */
    public static function testConnection(string $host, int $port, string $user, string $pass): array
    {
        try {
            $config = new Config([
                'host'    => $host,
                'port'    => $port,
                'user'    => $user,
                'pass'    => $pass,
                'timeout' => 3,
            ]);

            $client = new Client($config);
            if (!$client->connect()) {
                return [
                    'success' => false,
                    'message' => 'Tidak dapat terhubung ke port API MikroTik. Pastikan IP dan port benar serta service API aktif.',
                ];
            }

            // Query identity & resources
            $identityQuery = new Query('/system/identity/print');
            $identityData = $client->query($identityQuery)->read();
            $routerName = $identityData[0]['name'] ?? 'MikroTik';

            $resourceQuery = new Query('/system/resource/print');
            $resourceData = $client->query($resourceQuery)->read();
            $res = $resourceData[0] ?? [];

            return [
                'success'    => true,
                'message'    => "Koneksi Berhasil! Terhubung ke {$routerName}.",
                'identity'   => $routerName,
                'platform'   => $res['platform'] ?? 'MikroTik',
                'board_name' => $res['board-name'] ?? '-',
                'version'    => $res['version'] ?? '-',
                'uptime'     => $res['uptime'] ?? '-',
                'cpu_load'   => ($res['cpu-load'] ?? 0) . '%',
                'free_memory'=> isset($res['free-memory']) ? formatBytes($res['free-memory']) : '-',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Gagal terhubung: ' . $e->getMessage(),
            ];
        }
    }

    public function connecting($router): bool
    {
        try {
            $host = is_array($router) ? $router['host'] : $router->host;
            $user = is_array($router) ? $router['user'] : $router->user;
            $pass = is_array($router) ? $router['pass'] : $router->pass;
            $port = intval(is_array($router) ? $router['port'] : $router->port);

            $config = new Config([
                'host'    => $host,
                'user'    => $user,
                'pass'    => $pass,
                'port'    => $port,
                'timeout' => 3,
            ]);

            $client = new Client($config);
            return (bool) $client->connect();
        } catch (Exception $e) {
            return false;
        }
    }

    public function interface(): string
    {
        self::seedDefaultRoutersIfEmpty();
        $routers = self::where('is_active', true)->get();

        if ($routers->isEmpty()) {
            return json_encode([]);
        }

        $res = [];
        foreach ($routers as $index => $router) {
            $connected = $this->connecting($router);
            if ($connected) {
                try {
                    $config = new Config([
                        'host'    => $router->host,
                        'user'    => $router->user,
                        'pass'    => $router->pass,
                        'port'    => intval($router->port),
                        'timeout' => 3,
                    ]);

                    $client = new Client($config);

                    // Fetch interfaces (canonical /interface/print for RouterOS v6 & v7)
                    $gets = [];
                    try {
                        $query = new Query('/interface/print');
                        $gets = $client->query($query)->read();
                    } catch (Exception $e) {
                        $gets = [];
                    }
                    if (!is_array($gets) || empty($gets)) {
                        try {
                            $query = new Query('/interface/getall');
                            $gets = $client->query($query)->read();
                        } catch (Exception $e) {
                            $gets = [];
                        }
                    }

                    $out = [];
                    if (is_array($gets) && count($gets) > 0) {
                        foreach ($gets as $item) {
                            $out[] = [
                                'id'           => $item['.id'] ?? null,
                                'actual_mtu'   => $item['actual-mtu'] ?? null,
                                'default_name' => $item['default-name'] ?? ($item['name'] ?? null),
                                'disabled'     => $item['disabled'] ?? 'false',
                                'last_uptime'  => $item['last-link-up-time'] ?? null,
                                'link_down'    => $item['link-downs'] ?? 0,
                                'mac'          => $item['mac-address'] ?? null,
                                'mtu'          => $item['mtu'] ?? null,
                                'name'         => $item['name'] ?? null,
                                'running'      => $item['running'] ?? 'false',
                                'type'         => $item['type'] ?? 'ether',
                                'comment'      => $item['comment'] ?? null,
                            ];
                        }
                    }

                    // Pre-calculate per-router initial consumption
                    $initialConsumption = RouterBandwidthLog::getConsumptionStats($router->id);

                    $res[] = [
                        'id'          => $router->id,
                        'uuid'        => $router->uuid,
                        'name'        => $router->name,
                        'host'        => $router->host,
                        'port'        => $router->port,
                        'data'        => $out,
                        'address'     => $this->address($config),
                        'consumption' => $initialConsumption,
                    ];
                } catch (Exception $e) {
                    $res[] = false;
                }
            } else {
                $res[] = false;
            }
        }

        return json_encode($res);
    }

    public function address($config): array
    {
        try {
            $client = new Client($config);
            $query = new Query('/ip/address/print');
            $out = $client->query($query)->read();
            return is_array($out) ? $out : [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function monitoring($id, $name)
    {
        self::seedDefaultRoutersIfEmpty();

        $router = is_numeric($id)
            ? self::find(intval($id))
            : self::where('uuid', $id)->first();

        // Fallback to first router if not found
        if (!$router) {
            $router = self::where('is_active', true)->first();
        }

        if (!$router) {
            return response()->json([
                'tx' => 0, 'rx' => 0, 'name' => $name, 'measure' => 'bps', 'time' => date('H:i:s'),
                'consumption' => RouterBandwidthLog::getConsumptionStats(0),
            ]);
        }

        try {
            $config = new Config([
                'host'    => $router->host,
                'user'    => $router->user,
                'pass'    => $router->pass,
                'port'    => intval($router->port),
                'timeout' => 3,
            ]);

            $client = new Client($config);

            // Resolusi nama interface sebenarnya di RouterOS
            $targetName = $name;
            $allQuery = new Query('/interface/print');
            $allIfs = $client->query($allQuery)->read();
            
            if (is_array($allIfs) && count($allIfs) > 0) {
                $matched = false;
                foreach ($allIfs as $ifItem) {
                    $iName = $ifItem['name'] ?? '';
                    $iDef = $ifItem['default-name'] ?? '';
                    if (!empty($targetName) && ($iName === $targetName || $iDef === $targetName)) {
                        $name = $iName;
                        $matched = true;
                        break;
                    }
                }
                // Jika tidak cocok atau name kosong, pilih interface pertama yang aktif/running atau interface pertama
                if (!$matched) {
                    $runningIf = null;
                    foreach ($allIfs as $ifItem) {
                        if (($ifItem['running'] ?? 'false') === 'true') {
                            $runningIf = $ifItem['name'];
                            break;
                        }
                    }
                    $name = $runningIf ?: ($allIfs[0]['name'] ?? 'ether1');
                }
            } else {
                empty($name) ? ($name = 'ether1') : null;
            }

            // Monitor traffic speed (Tx/Rx bps)
            $query = (new Query('/interface/monitor-traffic'))->equal('interface', $name)->equal('once');
            $out = $client->query($query)->read();

            $rxBps = isset($out[0]['rx-bits-per-second']) ? intval($out[0]['rx-bits-per-second']) : 0;
            $txBps = isset($out[0]['tx-bits-per-second']) ? intval($out[0]['tx-bits-per-second']) : 0;

            // System resources
            $squery = new Query('/system/resource/print');
            $system = $client->query($squery)->read();

            // Fetch cumulative interface bytes to record consumption delta
            $now = Carbon::now();
            $byteQuery = (new Query('/interface/print'))->equal('.proplist', 'name,rx-byte,tx-byte')->where('name', $name);
            $byteData = $client->query($byteQuery)->read();

            $currentRxBytes = isset($byteData[0]['rx-byte']) ? intval($byteData[0]['rx-byte']) : 0;
            $currentTxBytes = isset($byteData[0]['tx-byte']) ? intval($byteData[0]['tx-byte']) : 0;

            // Find previous log to calculate delta
            $lastLog = RouterBandwidthLog::where('router_id', $router->id)
                ->where('interface_name', $name)
                ->latest('recorded_at')
                ->first();

            $rxDelta = 0;
            $txDelta = 0;

            if ($lastLog && $currentRxBytes >= $lastLog->rx_bytes && $currentTxBytes >= $lastLog->tx_bytes) {
                $rxDelta = $currentRxBytes - $lastLog->rx_bytes;
                $txDelta = $currentTxBytes - $lastLog->tx_bytes;
            } elseif (!$lastLog && ($currentRxBytes > 0 || $currentTxBytes > 0)) {
                // Initial baseline: allocate a representative portion for today's initial stats
                $rxDelta = intval($currentRxBytes * 0.1);
                $txDelta = intval($currentTxBytes * 0.1);
            }

            // Record snapshot in database
            RouterBandwidthLog::create([
                'router_id'      => $router->id,
                'interface_name' => $name,
                'rx_bytes'       => $currentRxBytes,
                'tx_bytes'       => $currentTxBytes,
                'rx_delta_bytes' => $rxDelta,
                'tx_delta_bytes' => $txDelta,
                'rx_speed_bps'   => $rxBps,
                'tx_speed_bps'   => $txBps,
                'recorded_at'    => $now,
            ]);

            // Calculate aggregate consumption (Today, Weekly, Monthly)
            $consumption = RouterBandwidthLog::getConsumptionStats($router->id, $name);

            $res = [
                'tx'          => $txBps, // Upstream
                'rx'          => $rxBps, // Downstream
                'tx_fmt'      => RouterBandwidthLog::formatBytes($txBps / 8) . '/s',
                'rx_fmt'      => RouterBandwidthLog::formatBytes($rxBps / 8) . '/s',
                'name'        => $out[0]['name'] ?? $name,
                'measure'     => 'bits per second (bps)',
                'time'        => date('H:i:s'),
                'consumption' => $consumption,
                'resource'    => [
                    'uptime'       => $system[0]['uptime'] ?? '-',
                    'cpu_load'     => $system[0]['cpu-load'] ?? 0,
                    'memory'       => isset($system[0]['free-memory']) ? formatBytes($system[0]['free-memory']) : '-',
                    'hdd'          => isset($system[0]['free-hdd-space']) ? formatBytes($system[0]['free-hdd-space']) : '-',
                    'platform'     => $system[0]['platform'] ?? 'MikroTik',
                    'board_name'   => $system[0]['board-name'] ?? '-',
                    'version'      => $system[0]['version'] ?? '-',
                    'architect'    => $system[0]['architecture-name'] ?? '-',
                    'cpu'          => $system[0]['cpu'] ?? '-',
                    'cpu_count'    => $system[0]['cpu-count'] ?? 1,
                    'cpu_freq'     => ($system[0]['cpu-frequency'] ?? 0) . ' MHz',
                    'total_memory' => isset($system[0]['total-memory']) ? formatBytes($system[0]['total-memory']) : '-',
                    'total_hdd'    => isset($system[0]['total-hdd-space']) ? formatBytes($system[0]['total-hdd-space']) : '-',
                    'build_time'   => $system[0]['build-time'] ?? '-',
                ],
            ];

            return response()->json($res);
        } catch (Exception $e) {
            Log::warning("MikroTik traffic monitoring error: " . $e->getMessage());
            return response()->json([
                'tx'          => 0,
                'rx'          => 0,
                'name'        => $name,
                'measure'     => 'bps',
                'time'        => date('H:i:s'),
                'error'       => $e->getMessage(),
                'consumption' => RouterBandwidthLog::getConsumptionStats($router->id ?? 0, $name),
            ]);
        }
    }

    public function dhcp_leases($id = null): array
    {
        self::seedDefaultRoutersIfEmpty();
        $query = self::where('is_active', true);
        if ($id !== null) {
            $query->where('id', $id);
        }
        $routers = $query->get();
        $results = [];

        foreach ($routers as $router) {
            try {
                $config = new Config([
                    'host'    => $router->host,
                    'user'    => $router->user,
                    'pass'    => $router->pass,
                    'port'    => intval($router->port),
                    'timeout' => 3,
                ]);
                $client = new Client($config);
                $dquery = new Query('/ip/dhcp-server/lease/print');
                $leases = $client->query($dquery)->read();

                if (is_array($leases)) {
                    foreach ($leases as $lease) {
                        if (!empty($lease['address'])) {
                            $results[] = [
                                'router_id'   => $router->id,
                                'router_name' => $router->name,
                                'address'     => $lease['address'],
                                'mac_address' => $lease['mac-address'] ?? null,
                                'host_name'   => $lease['host-name'] ?? null,
                                'status'      => $lease['status'] ?? 'bound',
                                'dynamic'     => $lease['dynamic'] ?? 'true',
                                'last_seen'   => $lease['last-seen'] ?? null,
                                'comment'     => $lease['comment'] ?? null,
                            ];
                        }
                    }
                }
            } catch (Exception $e) {
                Log::warning("MikroTik DHCP lease fetch error for {$router->host}: " . $e->getMessage());
            }
        }

        return $results;
    }

    public function arp_table($id = null): array
    {
        self::seedDefaultRoutersIfEmpty();
        $query = self::where('is_active', true);
        if ($id !== null) {
            $query->where('id', $id);
        }
        $routers = $query->get();
        $results = [];

        foreach ($routers as $router) {
            try {
                $config = new Config([
                    'host'    => $router->host,
                    'user'    => $router->user,
                    'pass'    => $router->pass,
                    'port'    => intval($router->port),
                    'timeout' => 3,
                ]);
                $client = new Client($config);
                $aquery = new Query('/ip/arp/print');
                $arps = $client->query($aquery)->read();

                if (is_array($arps)) {
                    foreach ($arps as $arp) {
                        if (!empty($arp['address'])) {
                            $results[] = [
                                'router_id'   => $router->id,
                                'router_name' => $router->name,
                                'address'     => $arp['address'],
                                'mac_address' => $arp['mac-address'] ?? null,
                                'interface'   => $arp['interface'] ?? null,
                                'dynamic'     => $arp['dynamic'] ?? 'true',
                                'complete'    => $arp['complete'] ?? 'true',
                                'comment'     => $arp['comment'] ?? null,
                            ];
                        }
                    }
                }
            } catch (Exception $e) {
                Log::warning("MikroTik ARP table fetch error for {$router->host}: " . $e->getMessage());
            }
        }

        return $results;
    }
}
