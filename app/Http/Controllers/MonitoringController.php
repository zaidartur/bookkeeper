<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;

use function PHPSTORM_META\map;

class MonitoringController extends Controller
{
    private $baseUrl;

    public function __construct()
    {
        // $this->baseUrl = 'http://127.0.0.1:19999';
        $this->baseUrl = 'http://10.20.33.235:19999';
    }

    public function view_server()
    {
        $list_device = "{$this->baseUrl}/api/v3/nodes";
        try {
            $response    = Http::timeout(3)->get($list_device);
            // Log::info("message", [$response]);
            $res = $response->json();
            $devices = [];
            if ($res['nodes'] && count($res['nodes']) > 0) {
                $i = 0;
                foreach ($res['nodes'] as $key => $value) {
                    $devices[$i] = [
                        'hostname'  => $value['nm'],
                        'uid'       => $value['mg'],
                        'reachable' => $value['state'] == 'reachable' ? true : false,
                        'ip'        => $value['labels']['_net_default_iface_ip'] ?? '',
                    ];

                    if (isset($value['labels']['_os']) && !empty($value['labels']['_os'])) {
                        $devices[$i] += [
                            'type'  => 'server',
                            'label' => $value['os']['nm'] .' '. $value['os']['v'],
                        ];
                    } else {
                        $devices[$i] += [
                            'type'  => 'router',
                            'label' => $value['labels']['description'],
                        ];
                    }

                    $i++;
                }
            }
            
            $data = [
                'devices'   => $devices,
            ];

            return Inertia::render('Server', $data);
        } catch (\Exception $e) {
            return Inertia::render('Server', [
                'devices' => [],
                'message' => 'Gagal terhubung ke Netdata.'
            ]);
        }
    }

    public function detail_device(Request $request)
    {
        $request->validate([
            'host'  => 'required|string',
            'type'  => 'required|string|in:server,router',
            'init'  => 'required',
        ]);

        $host = $request->host;
        $init = $request->init;
        $type = $request->type;

        if ($type == 'server') {
            $data = Cache::remember("netdata_{$host}", 5, function () use ($host, $init) {
                $detail     = "{$this->baseUrl}/host/{$host}/api/v1/charts";
                $req_info   = "{$this->baseUrl}/host/{$host}/api/v1/info";
                try {
                    $get_info   = Http::timeout(3)->get($req_info);
                    $myinfo     = $get_info->json();

                    $response   = Http::timeout(3)->get($detail);
                    $fetch = $response->json();
                    $rawCharts = $fetch['charts'] ?? [];

                    $availableMetrics = [];

                    foreach ($rawCharts as $key => $chart) {
                        $availableMetrics[] = [
                            'id' => $chart['id'],        
                            'title' => $chart['title'],  
                            'family' => $chart['family'] 
                        ];
                    }

                    // $groupedMetrics = collect($availableMetrics)->groupBy('family');

                    $details = $this->load_detail_server($host, $init);
                    // Log::info('memory', [$this->parseServerRam($details['memory'], $init)]);
                    $res = [
                        'status'    => 'success',
                        'summary'   => $myinfo,
                        'data'      => [
                            'uptime'    => $this->parseUptime($details['uptime'], $init),
                            'load'      => $this->parseServerLoad($details['load'], $init),
                            'cpu'       => $this->parseServerCpu($details['cpu'], $init),
                            'memory'    => $this->parseServerRam($details['memory'], $init),
                            'disk'      => $this->parseServerDisk($details['disk'], $init),
                            'network'   => $this->parseServerNetwork($details['network'], $init),
                            'proccess'  => $this->getSystemdProcesses($details['systemd']),
                            'alarm'     => $details['alarms']->json(),
                        ],
                    ];
                } catch (\Exception $e) {
                    $res = [
                        'status'    => 'failed',
                        'message'   => 'Gagal terhubung ke Netdata'
                    ];
                }

                return $res;
            });
        } elseif ($type == 'router') {
            $data = Cache::remember("netdata_router_detail_{$host}", 5, function () use ($host, $init) {
                $baseUrl = "{$this->baseUrl}/api/v3/nodes?nodes={$host}";
                $context = "{$this->baseUrl}/api/v3/contexts?nodes={$host}";

                try {
                    $get_info   = Http::timeout(3)->get($baseUrl);
                    $myinfo     = $get_info->json('nodes');

                    $response   = Http::timeout(3)->get($context);
                    $fetch = $response->json();
                    $rawCharts = $fetch['contexts'] ?? [];

                    $availableMetrics = [];

                    foreach ($rawCharts as $key => $chart) {
                        $availableMetrics[] = [
                            'id' => $key,      
                            'units' => $chart['units'],  
                            'family' => $chart['family'] 
                        ];
                    }
                    // Log::info('router', [$availableMetrics[0]]);
                    $details = $this->load_detail_router($host, $init);
                    $traffic = $this->parseLiveInterfaces($details['interface']);
                    // Log::info('traffic', $details['interface']->json());

                    $res = [
                        'status'    => 'success',
                        'summary'   => $myinfo,
                        'data'      => [
                            'uptime'    => $this->parseUptime($details['uptime']),
                            // 'cpu'       => ['result' => $details['cpu']->json('result'), 'units' => $details['cpu']->json('view.units')],
                            'cpu'       => $this->parseCpuRouter($details['cpu']),
                            'cpu_temp'  => $this->parseDynamicRouter($details['cpu_temp']),
                            'ram_used'  => $this->parseDynamicRouter($details['ram_used']),
                            'ram_total' => $this->parseDynamicRouter($details['ram_total']),
                            'disk_used' => $this->parseDynamicRouter($details['disk_used']),
                            'disk_total'=> $this->parseDynamicRouter($details['disk_total']),
                            'port_speed'=> $this->parseNetworkRouter($details['port_speed']),
                            'volume'    => $this->parseNetworkRouter($details['volume_daily']),
                            'monthly'   => $this->parseNetworkRouter($details['volume_monthly']),
                            'ports'     => $this->parseLiveInterfaces($details['interface']),
                        ],
                    ];
                } catch (\Exception $e) {
                    $res = [
                        'status'    => 'failed',
                        'message'   => 'Gagal terhubung ke Netdata'
                    ];
                }

                return $res;
            });
        }

        // return response()->json($data);
        return Redirect::route('monitoring.device')->with('message', $data);
    }

    public function load_detail_server($host, $init = false)
    {
        $detail     = "{$this->baseUrl}/api/v3";
        // $response   = Http::timeout(3)->get($detail, [
        //     'chart'     => $chart,
        //     'format'    => 'json',
        //     'after'     => -60,
        // ]);
        $response = Http::pool(fn ($pool) => [
            $pool->as('uptime')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'system.uptime', 'points' => ($init ? 0 : 1), 'after' => -10]),
            $pool->as('load')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'system.load', 'points' => ($init ? 0 : 1), 'after' => -10]),
            $pool->as('cpu')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'system.cpu', 'points' => ($init ? 0 : 1), 'after' => -10]),
            $pool->as('memory')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'system.ram', 'points' => ($init ? 0 : 1), 'after' => -10]),
            $pool->as('disk')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'system.io', 'points' => ($init ? 0 : 1), 'after' => -10]),
            $pool->as('network')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'system.ip', 'points' => ($init ? 0 : 1), 'after' => -10]),
            $pool->as('alarms')->timeout(3)->get("{$detail}/alerts", ['status' => 'RAISED', 'vnode' => $host]),
            $pool->as('systemd')->timeout(5)->get("{$this->baseUrl}/host/{$host}/api/v3/function", ['function' => 'systemd-services', 'timeout' => 120000, 'last' => 200]),
        ]);

        // $res = $response->json();

        return $response;
    }

    public function load_detail_router($host, $init = false)
    {
        $detail   = "{$this->baseUrl}/api/v3";
        $v1       = "{$this->baseUrl}/host/{$host}/api/v1/allmetrics";
        $startDay = Carbon::now()->startOfDay()->timestamp;
        $startMonth = Carbon::now()->startOfMonth()->timestamp;

        $response = Http::pool(fn ($pool) => [
            $pool->as('uptime')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'snmp.device_prof_systemUptime', 'points' => ($init ? 0 : 1), 'after' => -60]),
            $pool->as('cpu')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'snmp.device_prof_cpu_usage', 'points' => ($init ? 0 : 1), 'after' => -60]),
            $pool->as('cpu_temp')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'snmp.device_prof_mtxrHlProcessorTemperature', 'points' => ($init ? 0 : 1), 'after' => -60]),
            $pool->as('ram_used')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'snmp.device_prof_hrStorageUsed_ram', 'points' => ($init ? 0 : 1), 'after' => -60]),
            $pool->as('ram_total')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'snmp.device_prof_hrStorageSize_ram', 'points' => ($init ? 0 : 1), 'after' => -60]),
            $pool->as('disk_used')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'snmp.device_prof_hrStorageUsed_fixed_disk', 'points' => ($init ? 0 : 1), 'after' => -60]),
            $pool->as('disk_total')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'snmp.device_prof_hrStorageSize_fixed_disk', 'points' => ($init ? 0 : 1), 'after' => -60]),
            $pool->as('port_speed')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'snmp.device_prof_ifTraffic', 'points' => ($init ? 0 : 1), 'after' => -60]),
            $pool->as('total_speed')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'snmp.device_prof_ifTotalTraffic', 'points' => ($init ? 0 : 1), 'after' => -60]),
            $pool->as('volume_daily')->timeout(5)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'net.*,snmp.device_prof_ifTraffic', 'points' => 1, 'after' => $startDay, 'group' => 'sum']),
            $pool->as('volume_monthly')->timeout(10)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'net.*,snmp.device_prof_ifTraffic', 'points' => 1, 'after' => $startMonth, 'group' => 'sum']),
            $pool->as('interface')->timeout(5)->get($v1, ['format' => 'json']),
            // $pool->as('alarms')->timeout(3)->get("{$detail}/alerts", ['status' => 'RAISED', 'vnode' => $host]),
        ]);

        return $response;
    }

    public function load_snmp()
    {
        $endpoint = "{$this->baseUrl}/api/v1/info";
        $response = Http::timeout(3)->get($endpoint);
        // $response = Http::timeout(3)->get("{$this->baseUrl}/api/charts", [
        //     'chart' => 'snmp_mikrotik_utama.bandwidth_ether1', // ID port router
        //     'format' => 'json',
        //     'points' => 1
        // ]);

        $res = $response->json();

        return response()->json($res);

        // try {
        //     // Saya naikkan timeout jadi 10 detik sementara untuk melihat apakah server lambat merespon
        //     $response = Http::timeout(10)->get($endpoint, [
        //         'host' => 'CORE-ROUTER-DISKOMINFO-KRA', 
        //         'format' => 'json',
        //         'points' => 1
        //     ]);

        //     if ($response->successful()) {
        //         // Jika berhasil, tampilkan isi JSON-nya di layar
        //         dd($response->json()); 
        //     } else {
        //         // Jika Netdata menjawab tapi error (misal 404 Chart Not Found)
        //         dd("Netdata Error Code: " . $response->status(), "Pesan: " . $response->body());
        //     }

        // } catch (\Exception $e) {
        //     // Jika Laravel sama sekali gagal menghubungi IP Ubuntu (Timeout / Connection Refused)
        //     dd("Koneksi Gagal: " . $e->getMessage());
        // }
    }

    public function test_snmp()
    {
        $endpoint = "{$this->baseUrl}/host/CORE-ROUTER-DISKOMINFO-KRA/api/v1/data";

        $response = Http::timeout(3)->get($endpoint, [
            // Perhatikan ID chart-nya sekarang! 
            // Formatnya adalah "family.id" sesuai yang kita buat di YAML tadi
            'chart' => 'snmp.cpu_usage', 
            'format' => 'json',
            'points' => 1
        ]);

        if ($response->successful()) {
            // Jika sukses, layar akan menampilkan array berisi persentase CPU!
            dd("SUKSES MASUK!", $response->json('data')); 
        } else {
            dd("BELUM MUNCUL", $response->status(), $response->body());
        }
    }

    private function parseUptime($response, $init = false)
    {
        if (!$response->ok() || empty($response->json('result.data'))) {
            return ['time' => 0];
        }

        $dataRow = $response->json('result.data')[0] ?? [];
        $seconds = $dataRow[1][0] ?? 0;
        
        $interval = CarbonInterval::seconds($seconds);
        return $interval->cascade()->forHumans();
    }

    private function parseServerLoad($response, $init = false)
    {
        if ($init) {
            $time = [];
            if (!$response->ok() || empty($response->json('result.data'))) {
                return ['time' => $time, 'total' => [0], 'unit' => 'load'];
            }
        } else {
            $time = 0;
            if (!$response->ok() || empty($response->json('result.data'))) {
                return ['time' => $time, 'total' => 0, 'unit' => 'load'];
            }
        }
        
        // 1. Ambil Satuan Dinamis dari blok 'view' (Contoh: "MiB" atau "GiB")
        $unit = $response->json('view.units') ?? '';
        
        // 2. Ambil Label dan Angka dari blok 'result'
        $dataRow = ($init ? $response->json('result.data') : $response->json('result.data')[0]) ?? [];
        
        $loadData = [];

        if ($init) {
            $labels = $response['result']['labels'];

            foreach ($labels as $label) {
                $loadData[$label] = [];
            }

            foreach ($response['result']['data'] as $entry) {
                $rowData = [];
                foreach ($labels as $index => $label) {
                    $value = ($index === 0) ? $entry[$index] : $entry[$index][0];
                    $loadData[$label][] = $value;
                    $rowData[$label] = $value;
                }
            }

            $time = $loadData['time'];
            $load = $loadData['load1'];
        } else {
            $labels = $response->json('result.labels') ?? [];
            foreach ($labels as $index => $label) {
                if ($index === 0) {
                    // timestamp
                    $time = $dataRow[$index];
                    continue; 
                } else {
                    $loadData[$label] = abs($dataRow[$index][0] ?? 0); 
                }
            }

            $load = $loadData['load1'] ?? 0;
        }

        return [
            'time'      => $time,
            'total'     => $load,
            'unit'      => $unit,
        ];
    }

    private function parseServerCpu($response, $init = false)
    {
        if ($init) {
            $time = [];
            if (!$response->ok() || empty($response->json('result.data'))) {
                return ['time' => $time, 'total' => [0], 'unit' => '%'];
            }
        } else {
            $time = 0;
            if (!$response->ok() || empty($response->json('result.data'))) {
                return ['time' => $time, 'total' => 0, 'unit' => '%'];
            }
        }
        
        // 1. Ambil Satuan Dinamis dari blok 'view' (Contoh: "MiB" atau "GiB")
        $unit = $response->json('view.units') ?? '%';
        
        // 2. Ambil Label dan Angka dari blok 'result'
        $dataRow = $response->json('result.data')[0] ?? [];
        
        $cpuData = [];

        if ($init) {
            $labels = $response['result']['labels'];

            foreach ($labels as $label) {
                $cpuData[$label] = [];
            }

            $cpuData['total'] = [];
            foreach ($response['result']['data'] as $entry) {
                $rowData = [];
                foreach ($labels as $index => $label) {
                    $value = ($index === 0) ? $entry[$index] : $entry[$index][0];
                    $cpuData[$label][] = abs($value);
                    $rowData[$label] = abs($value);
                }

                // Total = used + available
                $total = $rowData['guest_nice'] + $rowData['guest'] + $rowData['steal'] + $rowData['softirq'] + $rowData['irq'] + $rowData['user'] + $rowData['system'] + $rowData['nice'] + $rowData['iowait'];

                // 3. Push calculated values to the new keys
                $cpuData['total'][] = round($total, 2);
            }

            $time = $cpuData['time'];
            $totalCpu = $cpuData['total'];
        } else {
            $labels = $response->json('result.labels') ?? [];
            foreach ($labels as $index => $label) {
                if ($index === 0) {
                    $time = $dataRow[$index];
                    continue; 
                } else {
                    // Ambil index ke-0 dari array bersarang [1457.49, 0, 0]
                    $cpuData[$label] = abs($dataRow[$index][0] ?? 0); 
                }
            }

            $totalCpu = array_sum($cpuData);
        }

        return [
            'time'      => $time,
            'total'     => $totalCpu,
            'unit'      => $unit,
            'datas'     => $cpuData,
        ];
    }

    private function parseServerRam($response, $init = false)
    {
        if ($init) {
            $time = [];
            if (!$response->ok() || empty($response->json('result.data'))) {
                return ['time' => $time, 'used' => [0], 'total' => [0], 'avail' => [0], 'unit' => 'MB'];
            }
        } else {
            $time = 0;
            if (!$response->ok() || empty($response->json('result.data'))) {
                return ['time' => $time, 'used' => 0, 'total' => 0, 'avail' => 0, 'unit' => 'MB'];
            }
        }
        
        // 1. Ambil Satuan Dinamis dari blok 'view' (Contoh: "MiB" atau "GiB")
        $unit = $response->json('view.units') ?? '';
        
        // 2. Ambil Label dan Angka dari blok 'result'
        $dataRow = ($init ? $response->json('result.data') : $response->json('result.data')[0]) ?? [];
        
        $ramData = [];

        if ($init) {
            $labels = $response['result']['labels'];

            foreach ($labels as $label) {
                $ramData[$label] = [];
            }

            $ramData['available'] = [];
            $ramData['total'] = [];

            foreach ($response['result']['data'] as $entry) {
                $rowData = [];
                foreach ($labels as $index => $label) {
                    $value = ($index === 0) ? $entry[$index] : $entry[$index][0];
                    $ramData[$label][] = $value;
                    $rowData[$label] = $value;
                }

                $available = $rowData['free'] + $rowData['cached'] + $rowData['buffers'];

                // Total = used + available
                $total = $rowData['used'] + $available;

                // 3. Push calculated values to the new keys
                $ramData['available'][] = round($available, 2);
                $ramData['total'][]     = round($total, 2);
            }

            $time = $ramData['time'];
            $used = $ramData['used'];
            $totalFisik = $ramData['total'];
            $avail = $ramData['available'];
        } else {
            $labels = $response->json('result.labels') ?? [];
            foreach ($labels as $index => $label) {
                if ($index === 0) {
                    $time = $dataRow[$index];
                    continue; 
                } else {
                    $ramData[$label] = abs($dataRow[$index][0] ?? 0);
                }
            }

            $used  = abs($ramData['used']) ?? 0;
            $totalFisik = abs(array_sum($ramData));
            $avail = abs($totalFisik - $used);
        }

        return [
            'time'      => $time,
            'used'      => $used,
            'total'     => $totalFisik,
            'avail'     => $avail,
            'unit'      => $unit,
            'datas'     => $ramData,
        ];
    }

    private function parseServerDisk($response, $init = false)
    {
        if ($init) {
            $time = [];
            if (!$response->ok() || empty($response->json('result.data'))) {
                return ['time' => $time, 'read' => [0], 'write' => [0], 'unit' => ''];
            }
        } else {
            $time = 0;
            if (!$response->ok() || empty($response->json('result.data'))) {
                return ['time' => $time, 'read' => 0, 'write' => 0, 'unit' => ''];
            }
        }
        
        // 1. Ambil Satuan Dinamis dari blok 'view' (Contoh: "MiB" atau "GiB")
        $unit = $response->json('view.units') ?? '';
        
        // 2. Ambil Label dan Angka dari blok 'result'
        $dataRow = $response->json('result.data')[0] ?? [];
        
        $diskData = [];

        if ($init) {
            $labels = $response['result']['labels'];

            foreach ($labels as $label) {
                $diskData[$label] = [];
            }

            foreach ($response['result']['data'] as $entry) {
                $rowData = [];
                foreach ($labels as $index => $label) {
                    $value = ($index === 0) ? $entry[$index] : $entry[$index][0];
                    $diskData[$label][] = abs($value);
                    $rowData[$label] = abs($value);
                }
            }

            $time   = $diskData['time'];
            $reads  = $diskData['reads'];
            $writes = $diskData['writes'];
        } else {
            $labels = $response->json('result.labels') ?? [];
            foreach ($labels as $index => $label) {
                if ($index === 0) {
                    $time = $dataRow[$index];
                    continue; 
                } else {
                    // Ambil index ke-0 dari array bersarang [1457.49, 0, 0]
                    $diskData[$label] = abs($dataRow[$index][0] ?? 0); 
                }
            }
            $reads  = $diskData['reads'] ?? 0;
            $writes = $diskData['writes'] ?? 0;
        }

        return [
            'time'      => $time,
            'read'      => $reads,
            'write'     => $writes,
            'unit'      => $unit
        ];
    }

    private function parseServerNetwork($response, $init = false)
    {
        if ($init) {
            $time = [];
            if (!$response->ok() || empty($response->json('result.data'))) {
                return ['time' => $time, 'in' => [0], 'out' => [0], 'unit' => ''];
            }
        } else {
            $time = 0;
            if (!$response->ok() || empty($response->json('result.data'))) {
                return ['time' => $time, 'in' => 0, 'out' => 0, 'unit' => ''];
            }
        }
        
        // 1. Ambil Satuan Dinamis dari blok 'view' (Contoh: "MiB" atau "GiB")
        $unit = $response->json('view.units') ?? '';
        
        // 2. Ambil Label dan Angka dari blok 'result'
        $dataRow = $response->json('result.data')[0] ?? [];
        
        $networkData = [];

        if ($init) {
            $labels = $response['result']['labels'];

            foreach ($labels as $label) {
                $diskData[$label] = [];
            }

            foreach ($response['result']['data'] as $entry) {
                $rowData = [];
                foreach ($labels as $index => $label) {
                    $value = ($index === 0) ? $entry[$index] : $entry[$index][0];
                    $diskData[$label][] = abs($value);
                    $rowData[$label] = abs($value);
                }
            }

            $time   = $diskData['time'];
            $in  = $diskData['received'];
            $out = $diskData['sent'];
        } else {
            $labels = $response->json('result.labels') ?? [];
            foreach ($labels as $index => $label) {
                if ($index === 0) {
                    $time = $dataRow[$index];
                    continue; 
                } else {
                    // Ambil index ke-0 dari array bersarang [1457.49, 0, 0]
                    $networkData[$label] = abs($dataRow[$index][0] ?? 0); 
                }
            }

            $in  = $networkData['received'] ?? 0;
            $out = $networkData['sent'] ?? 0;
        }

        return [
            'time'      => $time,
            'in'        => $in,
            'out'       => $out,
            'unit'      => $unit
        ];
    }


    //================================================ ROUTER ================================================//

    // ethernet interface
    // private function parseLiveInterfaces($response)
    // {
    //     if (!$response->ok()) return [];
    //     $metrics = $response->json() ?? [];
    //     $interfaces = [];

    //     // ---------------------------------------------------------
    //     // LOOP 1: Ambil Status Port (UP/DOWN) TERLEBIH DAHULU
    //     // Kita butuh statusnya dulu untuk membersihkan 'Ghost Traffic' nanti
    //     // ---------------------------------------------------------
    //     foreach ($metrics as $chartId => $data) {
    //         $portName = null;
    //         $isUp = true;

    //         if (str_starts_with($chartId, 'net_operstatus.')) {
    //             $portName = substr($chartId, 15);
    //             $val = current($data['dimensions'])['value'] ?? 1;
    //             $isUp = ($val == 1);
    //         } elseif (stripos($chartId, 'ifOperStatus_') !== false) {
    //             $parts = explode('ifOperStatus_', $chartId);
    //             if (count($parts) > 1) {
    //                 $portName = trim($parts[1], '_');
    //                 $val = current($data['dimensions'])['value'] ?? 1;
    //                 $isUp = ($val == 1);
    //             }
    //         }

    //         if ($portName) {
    //             $cleanName = preg_replace('/(\d)_(\d)/', '$1/$2', $portName);
    //             if (!isset($interfaces[$cleanName])) {
    //                 $interfaces[$cleanName] = ['name' => $cleanName, 'in_mbps' => 0, 'out_mbps' => 0, 'status' => 'down', 'drops' => 0, 'units' => $data['units'] ?? ''];
    //             }
    //             $interfaces[$cleanName]['status'] = $isUp ? 'up' : 'down';
    //         }
    //     }

    //     // ---------------------------------------------------------
    //     // LOOP 2: Ambil Kecepatan Trafik & Konversi Satuan Dinamis
    //     // ---------------------------------------------------------
    //     foreach ($metrics as $chartId => $data) {
    //         $portName = null;

    //         if (str_starts_with($chartId, 'net.') && !str_starts_with($chartId, 'net.drops') && !str_starts_with($chartId, 'net.operstatus')) {
    //             $portName = substr($chartId, 4); 
    //         } elseif (stripos($chartId, 'ifTraffic_') !== false) {
    //             $parts = explode('ifTraffic_', $chartId);
    //             if (count($parts) > 1) {
    //                 $portName = trim($parts[1], '_');
    //             }
    //         }

    //         if ($portName) {
    //             $cleanName = preg_replace('/(\d)_(\d)/', '$1/$2', $portName);
                
    //             // Pastikan wadah arraynya ada
    //             if (!isset($interfaces[$cleanName])) {
    //                 $interfaces[$cleanName] = ['name' => $cleanName, 'in_mbps' => 0, 'out_mbps' => 0, 'status' => 'up', 'drops' => 0, 'units' => $data['units'] ?? ''];
    //             }

    //             // ==========================================
    //             // FITUR PINTAR: DETEKSI SATUAN OTOMATIS
    //             // ==========================================
    //             $unit = strtolower($data['units'] ?? '');
    //             $divisor = 1000; // Default: Asumsi kilobits/s ke Mbps
                
    //             // if (strpos($unit, 'kilobit') !== false || $unit === 'kbps') {
    //             //     $divisor = 1000;
    //             // } elseif (strpos($unit, 'bit') !== false && strpos($unit, 'mega') === false) {
    //             //     $divisor = 1000000; // Jika murni 'bits/s' (SNMP), bagi 1 juta
    //             // } elseif (strpos($unit, 'megabit') !== false || $unit === 'mbps') {
    //             //     $divisor = 1; // Sudah Mbps, tidak perlu dibagi
    //             // } elseif (strpos($unit, 'byte') !== false || $unit === 'b/s') {
    //             //     $divisor = 125000; // Rumus: (Bytes * 8) / 1.000.000
    //             // } elseif (strpos($unit, 'kilobyte') !== false || $unit === 'kb/s') {
    //             //     $divisor = 125; // Rumus: (Kilobytes * 8) / 1.000
    //             // }

    //             if (str_starts_with($chartId, 'net.')) {
    //                 // Plugin bawaan (MikroTik/Server lokal) selalu Kilobits
    //                 $divisor = 1000;
    //             } elseif (stripos($chartId, 'ifTraffic_') !== false) {
    //                 // Plugin SNMP (Ruijie) memuntahkan murni Bits
    //                 $divisor = 1000000; 
    //             }

    //             $in = abs($data['dimensions']['in']['value'] ?? 0);
    //             $out = abs($data['dimensions']['out']['value'] ?? 0);

    //             // ==========================================
    //             // FITUR PINTAR: ZEROING DOWN PORTS
    //             // ==========================================
    //             // Jika port mati, paksa trafik menjadi 0 (Hapus Ghost Traffic)
    //             if ($interfaces[$cleanName]['status'] === 'down') {
    //                 $interfaces[$cleanName]['in_mbps'] = 0;
    //                 $interfaces[$cleanName]['out_mbps'] = 0;
    //             } else {
    //                 // Jika port hidup, hitung normal sesuai deteksi satuan
    //                 $interfaces[$cleanName]['in_mbps'] = round($in / $divisor, 2);
    //                 $interfaces[$cleanName]['out_mbps'] = round($out / $divisor, 2);
    //             }
    //         }
    //     }

    //     return array_values($interfaces);
    // }
    private function parseLiveInterfaces($response)
    {
        if (!$response->ok()) return [];
        $metrics = $response->json() ?? [];
        $interfaces = [];

        // ---------------------------------------------------------
        // LOOP 1: Ambil Status Port (UP/DOWN) TERLEBIH DAHULU
        // *REVISI: Ditambahkan Toleransi Status Mikrotik (Unknown/Dormant)*
        // ---------------------------------------------------------
        foreach ($metrics as $chartId => $data) {
            $portName = null;
            $isUp = false; // Default kita anggap mati dulu untuk keamanan

            if (str_starts_with($chartId, 'net_operstatus.')) {
                $portName = substr($chartId, 15);
            } elseif (stripos($chartId, 'ifOperStatus_') !== false) {
                $parts = explode('ifOperStatus_', $chartId);
                if (count($parts) > 1) {
                    $portName = trim($parts[1], '_');
                }
            }

            if ($portName) {
                $dimensions = $data['dimensions'] ?? [];
                
                // SKENARIO A: Netdata memecah status jadi label array (up, down, unknown)
                // Ini biasanya terjadi jika menggunakan agen Netdata Linux/Modern
                if (isset($dimensions['up']) || isset($dimensions['down'])) {
                    // Cek label mana yang sedang bernilai 1 (>0)
                    $stateUp      = ($dimensions['up']['value'] ?? 0) > 0;
                    $stateUnknown = ($dimensions['unknown']['value'] ?? 0) > 0; // Toleransi Bridge/VLAN Mikrotik
                    $stateDormant = ($dimensions['dormant']['value'] ?? 0) > 0; // Toleransi STP/LACP
                    
                    // Port dianggap UP jika salah satu dari state di atas aktif
                    $isUp = ($stateUp || $stateUnknown || $stateDormant);
                } 
                // SKENARIO B: Netdata mengirim 1 angka mentah standar SNMP (1=Up, 2=Down, 4=Unknown, 5=Dormant)
                // Ini biasanya terjadi jika menarik data murni via SNMP Mikrotik
                else {
                    $val = current($dimensions)['value'] ?? 2; // Jika tidak ada data, asumsikan 2 (Down)
                    // Kita izinkan angka 1, 4, dan 5 sebagai status UP
                    $isUp = in_array((int)$val, [1, 4, 5]); 
                }

                $cleanName = preg_replace('/(\d)_(\d)/', '$1/$2', $portName);
                
                if (!isset($interfaces[$cleanName])) {
                    $interfaces[$cleanName] = ['name' => $cleanName, 'in_mbps' => 0, 'out_mbps' => 0, 'status' => 'down', 'drops' => 0, 'units' => $data['units'] ?? ''];
                }
                
                $interfaces[$cleanName]['status'] = $isUp ? 'up' : 'down';
            }
        }

        // ---------------------------------------------------------
        // LOOP 2: Ambil Kecepatan Trafik & Konversi Satuan Dinamis
        // ---------------------------------------------------------
        foreach ($metrics as $chartId => $data) {
            $portName = null;

            if (str_starts_with($chartId, 'net.') && !str_starts_with($chartId, 'net.drops') && !str_starts_with($chartId, 'net.operstatus')) {
                $portName = substr($chartId, 4); 
            } elseif (stripos($chartId, 'ifTraffic_') !== false) {
                $parts = explode('ifTraffic_', $chartId);
                if (count($parts) > 1) {
                    $portName = trim($parts[1], '_');
                }
            }

            if ($portName) {
                $cleanName = preg_replace('/(\d)_(\d)/', '$1/$2', $portName);
                
                // Pastikan wadah arraynya ada
                if (!isset($interfaces[$cleanName])) {
                    $interfaces[$cleanName] = ['name' => $cleanName, 'in_mbps' => 0, 'out_mbps' => 0, 'status' => 'up', 'drops' => 0, 'units' => $data['units'] ?? ''];
                }

                // Deteksi Satuan
                $divisor = 1000; // Default Kilobits
                if (str_starts_with($chartId, 'net.')) {
                    $divisor = 1000;
                } elseif (stripos($chartId, 'ifTraffic_') !== false) {
                    $divisor = 1000000; 
                }

                $in = abs($data['dimensions']['in']['value'] ?? 0);
                $out = abs($data['dimensions']['out']['value'] ?? 0);

                // Zeroing Ghost Traffic jika port mati
                if ($interfaces[$cleanName]['status'] === 'down') {
                    $interfaces[$cleanName]['in_mbps'] = 0;
                    $interfaces[$cleanName]['out_mbps'] = 0;
                } else {
                    $interfaces[$cleanName]['in_mbps'] = round($in / $divisor, 2);
                    $interfaces[$cleanName]['out_mbps'] = round($out / $divisor, 2);
                }
            }
        }

        // ---------------------------------------------------------
        // LOOP 3: (Opsional) Menggabungkan Drops jika diperlukan
        // ---------------------------------------------------------
        // ... (Biarkan kode drops Anda yang sudah ada jika ada)

        return array_values($interfaces);
    }

    private function parseCpuRouter($response)
    {
        $time = 0;
        if (!$response->ok() || empty($response->json('result.data'))) {
            return ['time' => $time, 'value' => 0, 'unit' => ''];
        }

        $unit = $response->json('view.units') ?? '';
        $dataRow = $response->json('result.data')[0] ?? [];
        
        $rawData = [];

        $labels = $response->json('result.labels') ?? [];
        foreach ($labels as $index => $label) {
            if ($index === 0) {
                $time = $dataRow[$index];
                continue; 
            } else {
                // Ambil index ke-0 dari array bersarang [1457.49, 0, 0]
                $rawData[$label] = abs($dataRow[$index][0] ?? 0); 
            }
        }

        $val  = $rawData['cpu.usage'] ?? 0;

        return [
            'time'      => $time,
            'value'     => $val,
            'unit'      => $unit
        ];
    }

    private function parseDynamicRouter($response)
    {
        $time = 0;
        if (!$response->ok() || empty($response->json('result.data'))) {
            return ['time' => $time, 'value' => 0, 'unit' => ''];
        }

        $unit = $response->json('view.units') ?? '';
        $dataRow = $response->json('result.data')[0] ?? [];
        
        $rawData = [];
        $value = null;
        $context = $response->json('summary.contexts');
        $labels = $response->json('result.labels') ?? [];
        foreach ($labels as $index => $label) {
            if ($index === 0) {
                $time = $dataRow[$index];
                continue; 
            } else {
                // $rawData[$label] = abs($dataRow[$index][0] ?? 0);
                $clean = Str::after($context[0]['id'], 'snmp.device_prof_');
                if ($label == $clean) {
                    $rawData['value'] = abs($dataRow[$index][0] ?? 0);
                }
            }
        }

        return [
            'time'      => $time,
            'value'     => $rawData['value'] ?? 0,
            'unit'      => $unit
        ];
    }

    private function parseNetworkRouter($response)
    {
        $time = 0;
        if (!$response->ok() || empty($response->json('result.data'))) {
            return ['time' => $time, 'in' => 0, 'out' => 0, 'unit' => ''];
        }
        
        // 1. Ambil Satuan Dinamis dari blok 'view' (Contoh: "MiB" atau "GiB")
        $unit = $response->json('view.units') ?? '';
        
        // 2. Ambil Label dan Angka dari blok 'result'
        $dataRow = $response->json('result.data')[0] ?? [];
        
        $networkData = [];

        $labels = $response->json('result.labels') ?? [];
        foreach ($labels as $index => $label) {
            if ($index === 0) {
                $time = $dataRow[$index];
                continue; 
            } else {
                // Ambil index ke-0 dari array bersarang [1457.49, 0, 0]
                $networkData[$label] = abs($dataRow[$index][0] ?? 0); 
            }
        }

        $in  = $networkData['in'] ?? 0;
        $out = $networkData['out'] ?? 0;
        
        return [
            'time'      => $time,
            'in'        => $in,
            'out'       => $out,
            'unit'      => $unit
        ];
    }

    public function getSystemdProcesses($json)
    {
        // ... (Kode pemanggilan HTTP Get ke Netdata API v3 milik Anda) ...
        // Anggaplah hasil response json() disimpan di variabel $json

        $rows = $json['data'] ?? [];
        $columns = $json['columns'] ?? [];

        // Ambil indeks dinamis berdasarkan definisi kolom Netdata
        // Ini menjaga kode Anda tetap aman meskipun Netdata mengubah urutan kolom di update berikutnya
        $idxName  = $columns['Name']['index'] ?? 0;
        $idxPid   = $columns['PIDs']['index'] ?? 1;
        $idxCpu   = $columns['CPU']['index'] ?? 2;
        $idxRam   = $columns['RAM']['index'] ?? 3;
        $idxRead  = $columns['Reads']['index'] ?? 4;
        $idxWrite = $columns['Writes']['index'] ?? 5;

        $processes = [];

        foreach ($rows as $row) {
            $cpuValue = (float)($row[$idxCpu] ?? 0);
            $ramValue = (float)($row[$idxRam] ?? 0); // null akan otomatis menjadi 0

            // Filter NOC: Tampilkan layanan yang memang sedang berjalan (punya PID) 
            // atau sedang memakan CPU/RAM
            if (($row[$idxPid] > 0) || $cpuValue > 0 || $ramValue > 0) {
                $processes[] = [
                    'nama'  => $row[$idxName] ?? 'Unknown',
                    'pid'   => (int)($row[$idxPid] ?? 0),
                    'cpu'   => round($cpuValue, 2),
                    'ram'   => round($ramValue, 2),
                    'read'  => round((float)($row[$idxRead] ?? 0), 2),
                    'write' => round((float)($row[$idxWrite] ?? 0), 2),
                ];
            }
        }

        // Urutkan berdasarkan CPU tertinggi
        usort($processes, fn($a, $b) => $b['cpu'] <=> $a['cpu']);

        return response()->json([
            // Ambil 15 aplikasi teratas agar tabel di UI tidak terlalu panjang
            'processes' => array_slice($processes, 0, 15) 
        ]);
    }
}
