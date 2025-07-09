<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Query;

class Router extends Model
{
    public function connecting($router)
    {
        // Log::info('router ', [count(config('services.router'))]);
        try {
            $config = new Config([
                'host'  => ($router['host']),
                'user'  => ($router['user']),
                'pass'  => ($router['pass']),
                'port'  => intval(($router['port'])),
            ]);
            // Log::debug('config', count([$config]));

            $client = new Client($config);

            $conn = $client->connect();

            if ($conn) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    public function interface()
    {
        $router = config('services.router');
        if (count($router) < 1) return json_encode(false);

        $res = [];
        foreach ($router as $key => $value) {
            $connection = $this->connecting($value);
            if ($connection) {
                $config = new Config([
                    'host'  => ($value['host']),
                    'user'  => ($value['user']),
                    'pass'  => ($value['pass']),
                    'port'  => intval(($value['port'])),
                ]);

                $client = new Client($config);
                
                // IP Address print
                $query = new Query('/interface/getall');
                
                // // Export
                // $query = new Query('/export');

                // $out = $client->query($query)->read();
                $out = [];
                $gets   = $client->query($query)->read();
                if (count($gets) > 0) {
                    foreach ($gets as $item) {
                        $out[] = [
                            'id'            => $item['.id'],
                            'actual_mtu'    => $item['actual-mtu'],
                            'default_name'  => $item['default-name'],
                            'disabled'      => $item['disabled'],
                            'last_uptime'   => $item['last-link-up-time'],
                            'link_down'     => $item['link-downs'],
                            'mac'           => $item['mac-address'],
                            'mtu'           => $item['mtu'],
                            'name'          => $item['name'],
                            'running'       => $item['running'],
                            'type'          => $item['type'],
                        ];
                    }
                }

                // return json_encode($out);
                $res[$key] = [
                    'id'      => $value['id'],
                    'name'    => $value['name'],
                    'data'    => $out,
                    'address' => $this->address($config),
                ];
            } else {
                $res[$key] = false;
            }
        }

        return json_encode($res);
    }

    public function address($config)
    {
        // showing ip address
        $client = new Client($config);
        
        // IP Address print
        $query = new Query('/ip/address/print');

        $out   = $client->query($query)->read();

        if ($out) {
            return $out;
        } else {
            return [];
        }
    }

    public function monitoring($id, $name)
    {
        $router = config('services.router');
        
        $res = [];
        foreach ($router as $key => $value) {
            if ($value['id'] == intval($id)) {
                $config = new Config([
                    'host'  => ($value['host']),
                    'user'  => ($value['user']),
                    'pass'  => ($value['pass']),
                    'port'  => intval(($value['port'])),
                ]);

                $client = new Client($config);

                // traffic monitor
                empty($name) ? ($name = 'ether1') : null;
                $query  = (new Query('/interface/monitor-traffic'))->equal('interface', $name)->equal('once');
                $out    = $client->query($query)->read();

                // system resource
                $squery = (new Query('/system/resource/print'));
                $system = $client->query($squery)->read();


                $res    = [
                    'tx'        => $out[0]['tx-bits-per-second'],
                    'rx'        => $out[0]['rx-bits-per-second'],
                    'name'      => $out[0]['name'],
                    'measure'   => 'bits per second (bps)',
                    'time'      => date('H:i:s'),
                    'resource'  => [
                        // 'uptime'    => format_date($system[0]['uptime']),
                        'uptime'    => $system[0]['uptime'],
                        'cpu_load'  => $system[0]['cpu-load'],
                        'memory'    => formatBytes($system[0]['free-memory']),
                        'hdd'       => formatBytes($system[0]['free-hdd-space']),
                        'platform'  => $system[0]['platform'],
                        'board_name'=> $system[0]['board-name'],
                        'version'   => $system[0]['version'],
                        'architect' => $system[0]['architecture-name'],
                        'cpu'       => $system[0]['cpu'],
                        'cpu-count' => $system[0]['cpu-count'],
                        'cpu-freq'  => $system[0]['cpu-frequency'],
                        'total_memory'  => formatBytes($system[0]['total-memory']),
                        'total_hdd' => formatBytes($system[0]['total-hdd-space']),
                        'build_time'=> $system[0]['build-time'],
                    ],
                ];

                break;
            }
        }

        return response()->json($res);
    }

}
