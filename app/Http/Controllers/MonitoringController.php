<?php

namespace App\Http\Controllers;

use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
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

                $details = $this->load_detail($host, 'server', $init);
                // Log::info('memory', [$this->parseServerRam($details['memory'], $init)]);
                $res = [
                    'status'    => 'success',
                    'summary'   => $myinfo,
                    // 'data'      => [
                    //     'uptime'    => $this->load_detail($host, 'system.uptime'),
                    //     'load'      => $this->load_detail($host, 'system.load'),
                    //     'cpu'       => $this->load_detail($host, 'system.cpu'),
                    //     'memory'    => $this->load_detail($host, 'system.ram'),
                    //     'disk'      => $this->load_detail($host, 'system.io'),
                    //     'network'   => $this->load_detail($host, 'system.ip'),
                    // ],

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

        // return response()->json($data);
        return Redirect::route('monitoring.device')->with('message', $data);
    }

    public function load_detail($host, $type, $init = false)
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
}
