<?php

namespace App\Http\Controllers;

use App\Models\Cidr;
use App\Models\IpAddress;
use App\Models\IpAssignments;
use App\Models\Router;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IpAddressController extends Controller
{
    private $router;
    public function __construct() {
        $this->router = new Router();
    }

    public function view()
    {
        // $data = [
        //     'subnets'   => Cidr::orderBy('cidr')->get(),
        //     'networks'  => IpAddress::orderBy('network_ip', 'asc')->get(),
        //     'lists'     => IpAssignments::with(['ip_address', 'user'])->orderBy('assigned_ip')->get(),
        //     'monitor'   => $this->router->default(),
        // ];

        $data = [
            'router'   => $this->router->interface(),
        ];

        return Inertia::render('Network', $data);
    }

    public function monitoring(Request $request)
    {
        // $request = Request();
        return $this->router->monitoring($request->id, $request->name);
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
