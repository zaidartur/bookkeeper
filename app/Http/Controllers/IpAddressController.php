<?php

namespace App\Http\Controllers;

use App\Models\Cidr;
use App\Models\IpAddress;
use App\Models\IpAssignments;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IpAddressController extends Controller
{
    public function view()
    {
        $data = [
            'subnets'   => Cidr::orderBy('cidr')->get(),
            'networks'  => IpAddress::orderBy('network_ip', 'asc')->get(),
            'lists'     => IpAssignments::with(['ip_address', 'user'])->orderBy('assigned_ip')->get(),
        ];

        return Inertia::render('Network', $data);
    }

    public function save(Request $request)
    {
        //
    }

    public function update(Request $request)
    {
        //
    }

    public function delete(Request $request)
    {
        //
    }
}
