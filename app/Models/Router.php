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
        Log::info('router ', [(config('services.router'))]);
        try {
            $config = new Config([
                'host'  => ($router['host']),
                'user'  => ($router['user']),
                'pass'  => ($router['pass']),
                'port'  => intval(($router['port'])),
            ]);
            Log::debug('config', [$config]);

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

    public function default()
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

                // // Monitoring
                // $query  = (new Query('/interface/monitor-traffic'))->equal('interface', 'ether1')->equal('once');
                
                // IP Address print
                $query = new Query('/ip/address/print');
                
                // // Export
                // $query = new Query('/export');

                
                $out   = $client->query($query)->read();

                // return json_encode($out);
                $res[$key] = [
                    'id'    => $value['id'],
                    'name'  => $value['name'],
                    'data'  => $out,
                ];
            } else {
                $res[$key] = false;
            }
        }

        return json_encode($res);
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

                // // Monitoring
                // $query  = (new Query('/interface/monitor-traffic'))->equal('interface', 'ether1')->equal('once');
                
                // IP Address print
                $query = new Query('/interface/getall');
                
                // // Export
                // $query = new Query('/export');

                
                $out   = $client->query($query)->read();

                // return json_encode($out);
                $res[$key] = [
                    'id'    => $value['id'],
                    'name'  => $value['name'],
                    'data'  => $out,
                ];
            } else {
                $res[$key] = false;
            }
        }

        return json_encode($res);
    }

    public function monitoring($id)
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
                $query  = (new Query('/interface/monitor-traffic'))->equal('interface', 'ether5')->equal('once');
                $out    = $client->query($query)->read();
                $res    = [
                    'tx'        => $out[0]['tx-bits-per-second'],
                    'rx'        => $out[0]['rx-bits-per-second'],
                    'name'      => $out[0]['name'],
                    'measure'   => 'bits per second (bps)',
                    'time'      => date('H:i:s'),
                ];

                break;
            }
        }

        return response()->json($res);
    }

}
