<?php

namespace App\Console\Commands;

use App\Models\IpAddress;
use App\Models\IpAssignments;
use App\Models\Router;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class NetworkSyncIpamCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'network:sync-ipam {--router= : Specific router ID to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync active IP leases and ARP table from MikroTik routers into IPAM records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting MikroTik IPAM synchronization...');

        $routerModel = new Router();
        $routerId = $this->option('router') !== null ? intval($this->option('router')) : null;

        $leases = $routerModel->dhcp_leases($routerId);
        $arps = $routerModel->arp_table($routerId);

        $this->line("Found " . count($leases) . " DHCP leases and " . count($arps) . " ARP entries.");

        $subnets = IpAddress::all();
        if ($subnets->isEmpty()) {
            $this->warn('No subnets registered in IPAM. Please create a subnet in Bookkeeper first.');
            return 0;
        }

        // Merge discovered devices keyed by IP
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
            // Check which subnet this IP falls into
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
                    'user_id'     => 'system',
                ]);
                $newCount++;
            }
        }

        $this->info("Synchronization finished: {$newCount} new devices assigned, {$syncedCount} existing records updated.");
        return 0;
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
