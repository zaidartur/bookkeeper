<?php

namespace App\Http\Controllers;

use App\Models\BukuTamu;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Ramsey\Uuid\Uuid;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard');
    }

    public function guestbook()
    {
        $data = [
            'total' => BukuTamu::count(),
            'today' => BukuTamu::whereDate('created_at', date('Y-m-d'))->count(),
        ];
        return Inertia::render('Guestbook', $data);
    }

    public function save_guest(Request $request)
    {
        $request->validate([
            'nama'      => 'string|required',
            'instansi'  => 'string|required',
            'tanggal'   => 'date|required',
            'masuk'     => 'required',
            'keluar'    => 'required',
            'keperluan' => 'string|required',
        ]);

        $uuid = Uuid::uuid4()->toString();
        $data = [
            'uuid'          => $uuid,
            'nama'          => $request->nama,
            'instansi'      => $request->instansi,
            'tanggal'       => $request->tanggal,
            'jam_masuk'     => $request->masuk,
            'jam_keluar'    => $request->keluar,
            'keperluan'     => $request->keperluan,
            'created_at'    => date('Y-m-d H:i:s'),
        ];

        $save = BukuTamu::insert($data);
         if ($save) {
            $status = 'success';
            $msg = 'Data berhasil di simpan';
        } else {
            $status = 'error';
            $msg = 'Data gagal di simpan';
        }

        $res = ['status' => $status, 'msg' => $msg];
        // return Redirect::route('guestbook')->with('message', $res);
        return Redirect::back()->with('message', $res);
    }
}
