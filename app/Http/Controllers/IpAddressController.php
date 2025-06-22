<?php

namespace App\Http\Controllers;

use App\Models\IpAddress;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IpAddressController extends Controller
{
    public function view()
    {
        $data = [
            'lists'     => IpAddress::orderBy('ipv4', 'asc')->get(),
        ];

        return Inertia::render('IpAddress', $data);
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
