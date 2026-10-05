<?php

namespace App\Http\Controllers;

use App\Models\Cidr;
use App\Models\IpAddress;
use App\Models\IpAssignments;
use App\Models\NetworkMonitor;
use App\Models\Router;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class IpAddressController extends Controller
{
    private $router;

    public function __construct()
    {
        $this->router = new Router();
    }

    public function view()
    {
        // Seed default subnet if completely empty so the visualizer is never blank
        if (IpAddress::count() === 0) {
            $defaultSubnet = IpAddress::create([
                'uuid'         => (string) Str::uuid(),
                'network_ip'   => '192.168.1.0',
                'subnet_mask'  => '255.255.255.0',
                'cidr'         => 24,
                'total_ip'     => 256,
                'usable_hosts' => 254,
                'keterangan'   => 'Local Office Subnet (Default)',
                'user_id'      => Auth::user()->uuid ?? 'system',
            ]);

            // Seed default gateway
            IpAssignments::create([
                'uuid_ip'     => $defaultSubnet->uuid,
                'assigned_ip' => '192.168.1.1',
                'device'      => 'Router / Default Gateway',
                'kategori'    => 'Switch/Router',
                'status'      => 'Static',
                'mac_address' => '00:11:22:33:44:55',
                'source'      => 'manual',
                'keterangan'  => 'Gateway Jaringan Utama',
                'user_id'     => Auth::user()->uuid ?? 'system',
            ]);
        }

        $subnets = IpAddress::withCount('ip_assignment')
            ->orderBy('network_ip', 'asc')
            ->get();

        $cidrs = Cidr::orderBy('cidr', 'asc')->get();

        $categories = [
            'PC/Laptop',
            'Server',
            'Access Point',
            'CCTV',
            'Printer',
            'Switch/Router',
            'MikroTik Discovered',
            'Lainnya',
        ];

        Router::seedDefaultRoutersIfEmpty();
        $routersList = Router::orderBy('id', 'asc')->get();

        return Inertia::render('Network', [
            'router'       => $this->router->interface(),
            'routers_list' => $routersList,
            'subnets'      => $subnets,
            'cidrs'        => $cidrs,
            'categories'   => $categories,
        ]);
    }

    public function get_grid(Request $request, $uuid)
    {
        $subnet = IpAddress::where('uuid', $uuid)->first();
        if (!$subnet) {
            return response()->json(['error' => 'Subnet tidak ditemukan'], 404);
        }

        $cidr = intval($subnet->cidr);
        $networkIp = $subnet->network_ip;

        $ipLong = ip2long($networkIp);
        $totalIps = pow(2, 32 - $cidr);

        // Cap grid calculation for massive subnets (/16 has 65k IPs) to max 512 for smooth browser rendering
        $renderCount = min($totalIps, 512);

        $mask = ~($totalIps - 1) & 0xFFFFFFFF;
        $baseIpLong = $ipLong & $mask;

        // Fetch all assignments for this subnet
        $assignments = IpAssignments::where('uuid_ip', $uuid)
            ->get()
            ->keyBy('assigned_ip');

        $cells = [];
        $usedCount = 0;
        $reservedCount = 0;

        for ($i = 0; $i < $renderCount; $i++) {
            $currentIp = long2ip($baseIpLong + $i);
            $assignment = $assignments->get($currentIp);

            $type = 'available';
            if ($i === 0) {
                $type = 'network';
                $reservedCount++;
            } elseif ($i === ($totalIps - 1)) {
                $type = 'broadcast';
                $reservedCount++;
            } elseif ($i === 1 && !$assignment) {
                $type = 'gateway';
                $reservedCount++;
            } elseif ($assignment) {
                $type = 'assigned';
                $usedCount++;
            }

            $isLive = false;
            if ($assignment && ($assignment->source !== 'manual' || ($assignment->last_seen && $assignment->last_seen->diffInHours(now()) < 24))) {
                $isLive = true;
            }

            $cells[] = [
                'host'       => $i,
                'ip'         => $currentIp,
                'type'       => $type,
                'assignment' => $assignment,
                'is_live'    => $isLive,
            ];
        }

        // Available count is total usable hosts minus used
        $usableHosts = max(0, $totalIps - 2);
        $freeCount = max(0, $usableHosts - $usedCount);
        $utilization = $usableHosts > 0 ? round(($usedCount / $usableHosts) * 100, 1) : 0;

        return response()->json([
            'subnet' => $subnet,
            'stats'  => [
                'total'       => $totalIps,
                'usable'      => $usableHosts,
                'used'        => $usedCount,
                'free'        => $freeCount,
                'reserved'    => $reservedCount,
                'utilization' => $utilization,
            ],
            'cells' => $cells,
        ]);
    }

    public function save_subnet(Request $request)
    {
        $validated = $request->validate([
            'network_ip' => 'required|ip',
            'cidr'       => 'required|integer|between:8,30',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $cidr = intval($validated['cidr']);
        $totalIp = pow(2, 32 - $cidr);
        $usableHosts = max(0, $totalIp - 2);
        $mask = ~($totalIp - 1) & 0xFFFFFFFF;
        $subnetMask = long2ip($mask);

        // Normalize network IP to base IP of that subnet
        $baseIp = long2ip(ip2long($validated['network_ip']) & $mask);

        $exists = IpAddress::where('network_ip', $baseIp)
            ->where('cidr', $cidr)
            ->first();

        if ($exists) {
            return response()->json(['error' => 'Subnet dengan rentang IP tersebut sudah terdaftar.'], 422);
        }

        $subnet = IpAddress::create([
            'uuid'         => (string) Str::uuid(),
            'network_ip'   => $baseIp,
            'subnet_mask'  => $subnetMask,
            'cidr'         => $cidr,
            'total_ip'     => $totalIp,
            'usable_hosts' => $usableHosts,
            'keterangan'   => $validated['keterangan'] ?? 'Subnet IP',
            'user_id'      => Auth::user()->uuid ?? 'system',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subnet berhasil ditambahkan.',
            'subnet'  => $subnet,
        ]);
    }

    public function delete_subnet($uuid)
    {
        $subnet = IpAddress::where('uuid', $uuid)->first();
        if (!$subnet) {
            return response()->json(['error' => 'Subnet tidak ditemukan'], 404);
        }

        IpAssignments::where('uuid_ip', $uuid)->delete();
        $subnet->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subnet beserta alokasi IP di dalamnya berhasil dihapus.',
        ]);
    }

    public function assign_ip(Request $request)
    {
        $validated = $request->validate([
            'uuid_ip'     => 'required|exists:ip_addresses,uuid',
            'assigned_ip' => 'required|ip',
            'device'      => 'required|string|max:100',
            'kategori'    => 'required|string|max:100',
            'status'      => 'nullable|string|max:50',
            'mac_address' => 'nullable|string|max:30',
            'keterangan'  => 'nullable|string|max:255',
        ]);

        $assignment = IpAssignments::updateOrCreate(
            ['assigned_ip' => $validated['assigned_ip']],
            [
                'uuid_ip'     => $validated['uuid_ip'],
                'device'      => $validated['device'],
                'kategori'    => $validated['kategori'],
                'status'      => $validated['status'] ?? 'Static',
                'mac_address' => $validated['mac_address'],
                'source'      => 'manual',
                'keterangan'  => $validated['keterangan'],
                'user_id'     => Auth::user()->uuid ?? 'system',
            ]
        );

        return response()->json([
            'success'    => true,
            'message'    => "IP {$validated['assigned_ip']} berhasil ditugaskan ke {$validated['device']}.",
            'assignment' => $assignment,
        ]);
    }

    public function release_ip(Request $request)
    {
        $validated = $request->validate([
            'assigned_ip' => 'required|ip',
        ]);

        $deleted = IpAssignments::where('assigned_ip', $validated['assigned_ip'])->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted ? "Alokasi IP {$validated['assigned_ip']} berhasil dilepaskan." : "IP tidak terdaftar.",
        ]);
    }

    public function sync_mikrotik(Request $request)
    {
        $leases = $this->router->dhcp_leases();
        $arps = $this->router->arp_table();

        $subnets = IpAddress::all();
        if ($subnets->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada subnet terdaftar di Bookkeeper. Tambahkan subnet terlebih dahulu.',
            ], 400);
        }

        $devices = [];
        foreach ($leases as $lease) {
            $ip = $lease['address'];
            $devices[$ip] = [
                'ip'          => $ip,
                'mac'         => $lease['mac_address'] ?? null,
                'hostname'    => $lease['host_name'] ?? null,
                'status'      => 'DHCP',
                'source'      => 'mikrotik_dhcp',
                'router_name' => $lease['router_name'] ?? 'MikroTik',
            ];
        }

        foreach ($arps as $arp) {
            $ip = $arp['address'];
            if (!isset($devices[$ip])) {
                $devices[$ip] = [
                    'ip'          => $ip,
                    'mac'         => $arp['mac_address'] ?? null,
                    'hostname'    => null,
                    'status'      => 'Active ARP',
                    'source'      => 'mikrotik_arp',
                    'router_name' => $arp['router_name'] ?? 'MikroTik',
                ];
            } elseif (empty($devices[$ip]['mac']) && !empty($arp['mac_address'])) {
                $devices[$ip]['mac'] = $arp['mac_address'];
            }
        }

        $syncedCount = 0;
        $newCount = 0;

        foreach ($devices as $ip => $data) {
            $targetSubnet = null;
            foreach ($subnets as $subnet) {
                if ($this->ipInRange($ip, $subnet->network_ip, $subnet->cidr)) {
                    $targetSubnet = $subnet;
                    break;
                }
            }

            if (!$targetSubnet) {
                continue;
            }

            $existing = IpAssignments::where('assigned_ip', $ip)->first();

            if ($existing) {
                $existing->update([
                    'mac_address' => $data['mac'] ?: $existing->mac_address,
                    'hostname'    => $data['hostname'] ?: $existing->hostname,
                    'last_seen'   => now(),
                ]);
                $syncedCount++;
            } else {
                $deviceLabel = $data['hostname'] ?: ($data['mac'] ? 'Host-' . substr(str_replace(':', '', $data['mac']), -4) : 'Device-' . $ip);
                IpAssignments::create([
                    'uuid_ip'     => $targetSubnet->uuid,
                    'assigned_ip' => $ip,
                    'device'      => $deviceLabel,
                    'kategori'    => 'MikroTik Discovered',
                    'status'      => $data['status'],
                    'mac_address' => $data['mac'],
                    'hostname'    => $data['hostname'],
                    'source'      => $data['source'],
                    'last_seen'   => now(),
                    'keterangan'  => 'Auto-discovered via ' . $data['router_name'],
                    'user_id'     => Auth::user()->uuid ?? 'system',
                ]);
                $newCount++;
            }
        }

        return response()->json([
            'success'      => true,
            'total_found'  => count($devices),
            'new_count'    => $newCount,
            'synced_count' => $syncedCount,
            'message'      => "Sinkronisasi selesai: {$newCount} perangkat baru ditemukan, {$syncedCount} perangkat diperbarui.",
        ]);
    }

    public function export_pdf(Request $request, $uuid)
    {
        $subnet = IpAddress::where('uuid', $uuid)->firstOrFail();
        $assignments = IpAssignments::where('uuid_ip', $uuid)
            ->orderBy('assigned_ip', 'asc')
            ->get();

        $usableHosts = max(0, $subnet->total_ip - 2);
        $usedCount = $assignments->count();
        $freeCount = max(0, $usableHosts - $usedCount);
        $utilization = $usableHosts > 0 ? round(($usedCount / $usableHosts) * 100, 1) : 0;

        $stats = [
            'total'       => $subnet->total_ip,
            'usable'      => $usableHosts,
            'used'        => $usedCount,
            'free'        => $freeCount,
            'utilization' => $utilization,
        ];

        $data = [
            'subnet'      => $subnet,
            'assignments' => $assignments,
            'stats'       => $stats,
            'printed_at'  => date('d-m-Y H:i:s'),
            'printed_by'  => Auth::user()->name ?? 'Administrator Jaringan',
        ];

        $pdf = Pdf::loadView('pdf.ipam', $data)->setPaper('a4', 'landscape');
        $fileName = 'Laporan_IPAM_' . str_replace('.', '_', $subnet->network_ip) . '_' . $subnet->cidr . '.pdf';

        return $pdf->stream($fileName);
    }

    public function monitoring(Request $request)
    {
        return $this->router->monitoring($request->id, $request->name);
    }

    public function testing()
    {
        $ip = '10.20.133.137';
        $command = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? "ping -n 4 $ip" : "ping -c 4 $ip";

        exec($command, $output, $result);
        if ($result === 0) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Ping successful',
                'output'  => implode("\n", $output),
            ]);
        } else {
            return response()->json([
                'status'  => 'error',
                'message' => 'Ping failed',
                'output'  => implode("\n", $output),
            ]);
        }
    }

    public function device_status()
    {
        $monitors = NetworkMonitor::where('is_active', true)
            ->orderBy('status', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'total'   => $monitors->count(),
            'up'      => $monitors->where('status', 'UP')->count(),
            'down'    => $monitors->where('status', 'DOWN')->count(),
            'devices' => $monitors,
        ]);
    }

    public function router_list()
    {
        Router::seedDefaultRoutersIfEmpty();
        $routers = Router::orderBy('id', 'asc')->get();
        return response()->json($routers);
    }

    public function router_test_connection(Request $request)
    {
        $validated = $request->validate([
            'host' => 'required|string',
            'port' => 'required|integer',
            'user' => 'required|string',
            'pass' => 'nullable|string',
            'uuid' => 'nullable|string',
        ]);

        $pass = $validated['pass'] ?? '';

        if (empty($pass) && !empty($validated['uuid'])) {
            $existing = Router::where('uuid', $validated['uuid'])->first();
            if ($existing) {
                $pass = $existing->pass;
            }
        }

        $result = Router::testConnection(
            $validated['host'],
            intval($validated['port']),
            $validated['user'],
            $pass
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function router_store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'host'       => 'required|string|max:100',
            'port'       => 'required|integer|between:1,65535',
            'user'       => 'required|string',
            'pass'       => 'required|string',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $router = Router::create([
            'uuid'       => (string) Str::uuid(),
            'name'       => $validated['name'],
            'host'       => $validated['host'],
            'port'       => intval($validated['port']),
            'user'       => $validated['user'],
            'pass'       => $validated['pass'],
            'is_active'  => true,
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Router {$router->name} berhasil ditambahkan dengan kredensial terenkripsi.",
            'router'  => $router,
        ]);
    }

    public function router_update(Request $request, $uuid)
    {
        $router = Router::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'host'       => 'required|string|max:100',
            'port'       => 'required|integer|between:1,65535',
            'user'       => 'required|string',
            'pass'       => 'nullable|string',
            'is_active'  => 'nullable|boolean',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $router->name = $validated['name'];
        $router->host = $validated['host'];
        $router->port = intval($validated['port']);
        $router->user = $validated['user'];
        if (isset($validated['is_active'])) {
            $router->is_active = (bool) $validated['is_active'];
        }
        $router->keterangan = $validated['keterangan'] ?? $router->keterangan;

        if (!empty($validated['pass'])) {
            $router->pass = $validated['pass'];
        }

        $router->save();

        return response()->json([
            'success' => true,
            'message' => "Router {$router->name} berhasil diperbarui.",
            'router'  => $router,
        ]);
    }

    public function router_delete($uuid)
    {
        $router = Router::where('uuid', $uuid)->firstOrFail();
        \App\Models\RouterBandwidthLog::where('router_id', $router->id)->delete();
        $name = $router->name;
        $router->delete();

        return response()->json([
            'success' => true,
            'message' => "Router {$name} berhasil dihapus dari sistem.",
        ]);
    }

    public function router_toggle($uuid)
    {
        $router = Router::where('uuid', $uuid)->firstOrFail();
        $router->is_active = !$router->is_active;
        $router->save();

        return response()->json([
            'success' => true,
            'message' => "Status router {$router->name} diubah menjadi " . ($router->is_active ? 'Aktif' : 'Nonaktif'),
            'router'  => $router,
        ]);
    }

    private function ipInRange(string $ip, string $network, int $cidr): bool
    {
        $ipLong = ip2long($ip);
        $netLong = ip2long($network);
        if ($ipLong === false || $netLong === false) {
            return false;
        }

        $mask = ~((1 << (32 - $cidr)) - 1) & 0xFFFFFFFF;
        return ($ipLong & $mask) === ($netLong & $mask);
    }
}
