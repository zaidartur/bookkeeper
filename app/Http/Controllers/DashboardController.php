<?php

namespace App\Http\Controllers;

use App\Models\BukuTamu;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard');
    }

    public function guestbook()
    {
        $data = [];
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

        $data = [
            'nama'          => $request->nama,
            'instansi'      => $request->instansi,
            'tanggal'       => $request->tanggal,
            'jam_masuk'     => $request->masuk,
            'jam_keluar'    => $request->keluar,
            'keperluan'     => $request->keperluan,
        ];

        $save = BukuTamu::insert($data);
         if ($save) {
            $status = 'success';
            $msg = 'Data berhasil di simpan';
        } else {
            $status = 'error';
            $msg = 'Data gagal di simpan';
        }

        return ['status' => $status, 'msg' => $msg];
    }
}
