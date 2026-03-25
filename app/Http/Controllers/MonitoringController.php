<?php

namespace App\Http\Controllers;

use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

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
            'type'  => 'required|string|in:server,router'
        ]);

        $host = $request->host;
        $data = Cache::remember("netdata_{$host}", 5, function () use ($host) {
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

                $details = $this->load_detail($host, 'server');
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
                        'uptime'    => $this->parseUptime($details['uptime']),
                        'load'      => $this->parseServerLoad($details['load']),
                        'cpu'       => $this->parseServerCpu($details['cpu']),
                        'memory'    => $this->parseServerRam($details['memory']),
                        'disk'      => $this->parseServerDisk($details['disk']),
                        'network'   => $this->parseServerNetwork($details['network']),
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

    public function load_detail($host, $type)
    {
        $detail     = "{$this->baseUrl}/host/{$host}/api/v3";
        // $response   = Http::timeout(3)->get($detail, [
        //     'chart'     => $chart,
        //     'format'    => 'json',
        //     'after'     => -60,
        // ]);
        $response = Http::pool(fn ($pool) => [
            $pool->as('uptime')->timeout(3)->get("{$detail}/data", ['contexts' => 'system.uptime', 'points' => 1, 'after' => -10]),
            $pool->as('load')->timeout(3)->get("{$detail}/data", ['contexts' => 'system.load', 'points' => 1, 'after' => -10]),
            $pool->as('cpu')->timeout(3)->get("{$detail}/data", ['contexts' => 'system.cpu', 'points' => 1, 'after' => -10]),
            $pool->as('memory')->timeout(3)->get("{$detail}/data", ['contexts' => 'system.ram', 'points' => 1, 'after' => -10]),
            $pool->as('disk')->timeout(3)->get("{$detail}/data", ['contexts' => 'system.io', 'points' => 1, 'after' => -10]),
            $pool->as('network')->timeout(3)->get("{$detail}/data", ['contexts' => 'system.ip', 'points' => 1, 'after' => -10]),
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

    private function parseUptime($response)
    {
        if (!$response->ok() || empty($response->json('result.data'))) {
            return ['time' => 0];
        }

        $dataRow = $response->json('result.data')[0] ?? [];
        $seconds = $dataRow[1][0] ?? 0;
        
        $interval = CarbonInterval::seconds($seconds);
        return $interval->cascade()->forHumans();
    }

    private function parseServerLoad($response)
    {
        $time = 0;
        if (!$response->ok() || empty($response->json('result.data'))) {
            return ['time' => $time, 'total' => 0, 'unit' => 'load'];
        }
        
        // 1. Ambil Satuan Dinamis dari blok 'view' (Contoh: "MiB" atau "GiB")
        $unit = $response->json('view.units') ?? '';
        
        // 2. Ambil Label dan Angka dari blok 'result'
        $labels = $response->json('result.labels') ?? [];
        $dataRow = $response->json('result.data')[0] ?? [];
        
        $loadData = [];

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

        return [
            'time'      => $time,
            'total'     => abs($load),
            'unit'      => $unit,
        ];
    }

    private function parseServerCpu($response)
    {
        $time = 0;
        if (!$response->ok() || empty($response->json('result.data'))) {
            return ['time' => $time, 'total' => 0, 'unit' => '%'];
        }
        
        // 1. Ambil Satuan Dinamis dari blok 'view' (Contoh: "MiB" atau "GiB")
        $unit = $response->json('view.units') ?? '%';
        
        // 2. Ambil Label dan Angka dari blok 'result'
        $labels = $response->json('result.labels') ?? [];
        $dataRow = $response->json('result.data')[0] ?? [];
        
        $cpuData = [];

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

        return [
            'time'      => $time,
            'total'     => abs($totalCpu),
            'unit'      => $unit,
            'datas'     => $cpuData,
        ];
    }

    private function parseServerRam($response)
    {
        $time = 0;
        if (!$response->ok() || empty($response->json('result.data'))) {
            return ['time' => $time, 'used' => 0, 'total' => 0, 'avail' => 0, 'unit' => 'MB'];
        }
        
        // 1. Ambil Satuan Dinamis dari blok 'view' (Contoh: "MiB" atau "GiB")
        $unit = $response->json('view.units') ?? 'MB';
        
        // 2. Ambil Label dan Angka dari blok 'result'
        $labels = $response->json('result.labels') ?? [];
        $dataRow = $response->json('result.data')[0] ?? [];
        
        $ramData = [];

        foreach ($labels as $index => $label) {
            if ($index === 0) {
                $time = $dataRow[$index];
                continue; 
            } else {
                // Ambil index ke-0 dari array bersarang [1457.49, 0, 0]
                $ramData[$label] = abs($dataRow[$index][0] ?? 0); 
            }
        }

        $used = $ramData['used'] ?? 0;
        $totalFisik = array_sum($ramData);
        $percent = $totalFisik > 0 ? round(($used / $totalFisik) * 100) : 0;

        return [
            'time'      => $time,
            'used'      => abs($used),
            'total'     => abs($totalFisik),
            'avail'     => $totalFisik - $used,
            'unit'      => $unit,
            'datas'     => $ramData,
        ];
    }

    private function parseServerDisk($response)
    {
        $time = 0;
        if (!$response->ok() || empty($response->json('result.data'))) {
            return ['time' => $time, 'read' => 0, 'write' => 0, 'unit' => ''];
        }
        
        // 1. Ambil Satuan Dinamis dari blok 'view' (Contoh: "MiB" atau "GiB")
        $unit = $response->json('view.units') ?? '';
        
        // 2. Ambil Label dan Angka dari blok 'result'
        $labels = $response->json('result.labels') ?? [];
        $dataRow = $response->json('result.data')[0] ?? [];
        
        $diskData = [];

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

        return [
            'time'      => $time,
            'read'      => abs($reads),
            'write'     => abs($writes),
            'unit'      => $unit
        ];
    }

    private function parseServerNetwork($response)
    {
        $time = 0;
        if (!$response->ok() || empty($response->json('result.data'))) {
            return ['time' => $time, 'in' => 0, 'out' => 0, 'unit' => ''];
        }
        
        // 1. Ambil Satuan Dinamis dari blok 'view' (Contoh: "MiB" atau "GiB")
        $unit = $response->json('view.units') ?? '';
        
        // 2. Ambil Label dan Angka dari blok 'result'
        $labels = $response->json('result.labels') ?? [];
        $dataRow = $response->json('result.data')[0] ?? [];
        
        $networkData = [];

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

        return [
            'time'      => $time,
            'in'        => abs($in),
            'out'       => abs($out),
            'unit'      => $unit
        ];
    }
}
