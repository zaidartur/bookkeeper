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
                    Log::alert('iface', [$traffic]);

                    $res = [
                        'status'    => 'success',
                        'summary'   => $myinfo,
                        'data'      => [
                            'uptime'    => $this->parseUptime($details['uptime']),
                            // 'cpu'       => ['result' => $details['cpu']->json('result'), 'units' => $details['cpu']->json('view.units')],
                            'cpu'       => $this->parseCpuRouter($details['cpu']),
                            'ram_used'  => $this->parseDynamicRouter($details['ram_used']),
                            'ram_total' => $this->parseDynamicRouter($details['ram_total']),
                            'disk_used' => $this->parseDynamicRouter($details['disk_used']),
                            'disk_total'=> $this->parseDynamicRouter($details['disk_total']),
                            'port_speed'=> $this->parseNetworkRouter($details['port_speed']),
                            'volume'    => $this->parseNetworkRouter($details['volume_daily']),
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
            $pool->as('ram_used')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'snmp.device_prof_hrStorageUsed_ram', 'points' => ($init ? 0 : 1), 'after' => -60]),
            $pool->as('ram_total')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'snmp.device_prof_hrStorageSize_ram', 'points' => ($init ? 0 : 1), 'after' => -60]),
            $pool->as('disk_used')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'snmp.device_prof_hrStorageUsed_fixed_disk', 'points' => ($init ? 0 : 1), 'after' => -60]),
            $pool->as('disk_total')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'snmp.device_prof_hrStorageSize_fixed_disk', 'points' => ($init ? 0 : 1), 'after' => -60]),
            $pool->as('port_speed')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'snmp.device_prof_ifTraffic', 'points' => ($init ? 0 : 1), 'after' => -60]),
            $pool->as('total_speed')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'snmp.device_prof_ifTotalTraffic', 'points' => ($init ? 0 : 1), 'after' => -60]),
            $pool->as('volume_daily')->timeout(5)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'net.*,snmp.device_prof_ifTraffic', 'points' => 1, 'after' => $startDay, 'group' => 'sum']),
            $pool->as('volume_monthly')->timeout(10)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'net.*,snmp.device_prof_ifTraffic', 'points' => 1, 'after' => $startMonth, 'group' => 'sum']),
            $pool->as('interface')->timeout(5)->get($v1, ['format' => 'json']),
            // $pool->as('interface')->timeout(3)->get("{$detail}/data", ['nodes' => $host, 'contexts' => 'snmp.device_prof_ifTraffic*', 'points' => 1, 'after' => -60, 'grouped_by' => 'interface,dimension']),
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
            Log::info('load', [$loadData]);
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
    private function parseLiveInterfaces($response)
    {
        if (!$response->ok()) return [];
        $metrics = $response->json() ?? [];
        $interfaces = [];

        // ---------------------------------------------------------
        // LOOP 1: Ambil Status Port (UP/DOWN) TERLEBIH DAHULU
        // Kita butuh statusnya dulu untuk membersihkan 'Ghost Traffic' nanti
        // ---------------------------------------------------------
        foreach ($metrics as $chartId => $data) {
            $portName = null;
            $isUp = true;

            if (str_starts_with($chartId, 'net_operstatus.')) {
                $portName = substr($chartId, 15);
                $val = current($data['dimensions'])['value'] ?? 1;
                $isUp = ($val == 1);
            } elseif (stripos($chartId, 'ifOperStatus_') !== false) {
                $parts = explode('ifOperStatus_', $chartId);
                if (count($parts) > 1) {
                    $portName = trim($parts[1], '_');
                    $val = current($data['dimensions'])['value'] ?? 1;
                    $isUp = ($val == 1);
                }
            }

            if ($portName) {
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

                // ==========================================
                // FITUR PINTAR: DETEKSI SATUAN OTOMATIS
                // ==========================================
                $unit = strtolower($data['units'] ?? '');
                $divisor = 1000; // Default: Asumsi kilobits/s ke Mbps
                
                // if (strpos($unit, 'kilobit') !== false || $unit === 'kbps') {
                //     $divisor = 1000;
                // } elseif (strpos($unit, 'bit') !== false && strpos($unit, 'mega') === false) {
                //     $divisor = 1000000; // Jika murni 'bits/s' (SNMP), bagi 1 juta
                // } elseif (strpos($unit, 'megabit') !== false || $unit === 'mbps') {
                //     $divisor = 1; // Sudah Mbps, tidak perlu dibagi
                // } elseif (strpos($unit, 'byte') !== false || $unit === 'b/s') {
                //     $divisor = 125000; // Rumus: (Bytes * 8) / 1.000.000
                // } elseif (strpos($unit, 'kilobyte') !== false || $unit === 'kb/s') {
                //     $divisor = 125; // Rumus: (Kilobytes * 8) / 1.000
                // }

                if (str_starts_with($chartId, 'net.')) {
                    // Plugin bawaan (MikroTik/Server lokal) selalu Kilobits
                    $divisor = 1000;
                } elseif (stripos($chartId, 'ifTraffic_') !== false) {
                    // Plugin SNMP (Ruijie) memuntahkan murni Bits
                    $divisor = 1000000; 
                }

                $in = abs($data['dimensions']['in']['value'] ?? 0);
                $out = abs($data['dimensions']['out']['value'] ?? 0);

                // ==========================================
                // FITUR PINTAR: ZEROING DOWN PORTS
                // ==========================================
                // Jika port mati, paksa trafik menjadi 0 (Hapus Ghost Traffic)
                if ($interfaces[$cleanName]['status'] === 'down') {
                    $interfaces[$cleanName]['in_mbps'] = 0;
                    $interfaces[$cleanName]['out_mbps'] = 0;
                } else {
                    // Jika port hidup, hitung normal sesuai deteksi satuan
                    $interfaces[$cleanName]['in_mbps'] = round($in / $divisor, 2);
                    $interfaces[$cleanName]['out_mbps'] = round($out / $divisor, 2);
                }
            }
        }

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
                    Log::info('exists');
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

    public function getMonthlyBandwidthVolume($hostname, $interface = 'net.ether1')
    {
        // 1. Tentukan rentang waktu (Misal: 30 Hari Terakhir)
        // 2592000 detik = 30 Hari
        $timeRange = 2592000; 

        // 2. Tembak API v3 Netdata
        // Kita gunakan group=sum untuk menjumlahkan seluruh data dalam rentang waktu tersebut
        $baseUrl = "{$this->baseUrl}/host/{$hostname}/api/v3";
        $response = Http::timeout(5)->get("{$baseUrl}/data", [
            'contexts' => $interface,
            'after'    => -$timeRange, // Tarik dari 30 hari lalu
            'group'    => 'sum',       // JUMLAHKAN semua titik data
            'points'   => 1            // Jadikan 1 angka raksasa
        ]);

        if ($response->ok() && !empty($response->json('result.data'))) {
            // Data biasanya dalam format Kilobit, kita ubah ke Gigabyte (GB)
            $dataRow = $response->json('result.data')[0] ?? [];
            
            // Perhatikan index datanya (tergantung respons netdata, biasanya index 1=in, 2=out)
            $inKilobits = abs($dataRow[1][0] ?? 0);
            $outKilobits = abs($dataRow[2][0] ?? 0);

            // Konversi Kilobit ke Gigabyte (Rumus: Kb / 8 / 1024 / 1024)
            $inGB = round($inKilobits / 8388608, 2);
            $outGB = round($outKilobits / 8388608, 2);

            return response()->json([
                'interface' => $interface,
                'period' => '30 Hari Terakhir',
                'total_in_gb' => $inGB,
                'total_out_gb' => $outGB,
                'total_combined_gb' => $inGB + $outGB
            ]);
        }

        return response()->json(['error' => 'Data tidak tersedia'], 404);
    }
}
