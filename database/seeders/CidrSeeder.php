<?php

namespace Database\Seeders;

use App\Models\Cidr;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CidrSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // (8,  '255.0.0.0',      '0.255.255.255', 16777216, 16777214, 'A', 24),
        // (16, '255.255.0.0',    '0.0.255.255',   65536,    65534,    'B', 16),
        // (24, '255.255.255.0',  '0.0.0.255',      256,      254,      'C', 8),
        // (25, '255.255.255.128','0.0.0.127',      128,      126,      NULL, 7),
        // (26, '255.255.255.192','0.0.0.63',       64,       62,       NULL, 6),
        // (30, '255.255.255.252','0.0.0.3',        4,        2,        NULL, 2);

        $data = [
            [
                'cidr'          => 8,
                'subnet_mask'   => '255.0.0.0',
                'wildcard_mask' => '0.255.255.255',
                'total_ip'      => 16777216,
                'usable_host'   => 16777214,
                'ip_class'      => 'A',
                'host_bits'     => 24,
                'created_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'cidr'          => 16,
                'subnet_mask'   => '255.255.0.0',
                'wildcard_mask' => '0.0.255.255',
                'total_ip'      => 65536,
                'usable_host'   => 65534,
                'ip_class'      => 'B',
                'host_bits'     => 16,
                'created_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'cidr'          => 24,
                'subnet_mask'   => '255.255.255.0',
                'wildcard_mask' => '0.0.0.255',
                'total_ip'      => 256,
                'usable_host'   => 254,
                'ip_class'      => 'C',
                'host_bits'     => 8,
                'created_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'cidr'          => 25,
                'subnet_mask'   => '255.255.255.128',
                'wildcard_mask' => '0.0.0.127',
                'total_ip'      => 128,
                'usable_host'   => 126,
                'ip_class'      => '-',
                'host_bits'     => 7,
                'created_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'cidr'          => 26,
                'subnet_mask'   => '255.255.255.192',
                'wildcard_mask' => '0.0.0.63',
                'total_ip'      => 64,
                'usable_host'   => 62,
                'ip_class'      => '-',
                'host_bits'     => 6,
                'created_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'cidr'          => 30,
                'subnet_mask'   => '255.255.255.252',
                'wildcard_mask' => '0.0.0.3',
                'total_ip'      => 4,
                'usable_host'   => 2,
                'ip_class'      => '-',
                'host_bits'     => 2,
                'created_at'    => date('Y-m-d H:i:s'),
            ]
        ];

        // Cidr::create($data);
        DB::table('cidrs')->insert($data);
    }
}
