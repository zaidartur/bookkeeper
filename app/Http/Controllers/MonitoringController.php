<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class MonitoringController extends Controller
{
    private $baseUrl;

    public function __construct()
    {
        $this->baseUrl = 'http://127.0.0.1:19999';
    }

    public function view_server()
    {
        $list_device = "{$this->baseUrl}/api/v1/info";
        try {
            $response    = Http::timeout(3)->get($list_device);
            // Log::info("message", [$response]);
            $res = $response->json();
            
            $data = [
                'devices'   => $res['mirrored_hosts_status'],
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
            'host'  => 'required|string'
        ]);

        $detail     = "{$this->baseUrl}/host/{$request->host}/api/v1/charts";
        $req_info   = "{$this->baseUrl}/host/{$request->host}/api/v1/info";
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

            $groupedMetrics = collect($availableMetrics)->groupBy('family');

            $res = [
                'status'    => 'success',
                'summary'   => $myinfo,
                'data'      => [
                    'uptime'    => $this->load_detail($request->host, 'system.uptime'),
                    'load'      => $this->load_detail($request->host, 'system.load'),
                    'cpu'       => $this->load_detail($request->host, 'system.cpu'),
                    'memory'    => $this->load_detail($request->host, 'system.ram'),
                    // 'mem_ava'   => $this->load_detail($request->host, 'mem.available'),
                    'disk'      => $this->load_detail($request->host, 'system.io'),
                    // 'disk_w'    => $this->load_detail($request->host, 'mem.available'),
                    'network'   => $this->load_detail($request->host, 'system.ip'),
                    // 'net_out'   => $this->load_detail($request->host, 'mem.available'),
                ],
            ];
        } catch (\Exception $e) {
            $res = [
                'status'    => 'failed',
                'message'   => 'Gagal terhubung ke Netdata'
            ];
        }

        // return response()->json($res);
        return Redirect::route('monitoring.device')->with('message', $res);
    }

    public function load_detail($host, $chart)
    {
        $detail     = "{$this->baseUrl}/host/{$host}/api/v1/data";
        $response   = Http::timeout(3)->get($detail, [
            'chart'     => $chart,
            'format'    => 'json',
            'after'     => -60,
        ]);

        $res = $response->json();

        return $res;
    }
}
