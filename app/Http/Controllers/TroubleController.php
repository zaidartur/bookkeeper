<?php

namespace App\Http\Controllers;

use App\Models\Trouble;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Ramsey\Uuid\Uuid;

class TroubleController extends Controller
{
    public function view()
    {
        // status = progress & finish
        $data = [
            'lists' => Trouble::where('status', 'progress')->orderBy('created_at')->get(),
        ];
        return Inertia::render('Trouble', $data);
    }

    public function report()
    {
        // status = progress & finish
        $data = [
            'lists' => Trouble::where('status', 'finished')->orderBy('tgl_trouble', 'desc')->get(),
            'dates' => Trouble::where('status', 'finished')->groupBy('tgl_trouble', 'desc')->select('tgl_trouble')->get(),
        ];
        return Inertia::render('ReportTrouble', $data);
    }

    public function save(Request $request)
    {
        $request->validate([
            'mulai'     => 'required|date',
            'jam'       => 'required',
            'lokasi'    => 'required|string',
            'kategori'  => 'required|string',
            'petugas'   => 'required|string',
            'deskripsi' => 'required|string',
        ]);

        $uuid = Uuid::uuid4()->toString();
        $data = [
            'uuid'          => $uuid,
            'tgl_trouble'   => date_format(date_create($request->mulai), 'Y-m-d'),
            'jam_trouble'   => date_format(date_create($request->jam), 'H:i'),
            'lokasi'        => $request->lokasi,
            'kategori'      => $request->kategori,
            'problem'       => $request->deskripsi,
            'petugas'       => $request->petugas,
            'foto_awal'     => $request->foto,
            'status'        => 'progress',
            'created_by'    => Auth::user()->uuid,
            'created_at'    => date('Y-m-d'),
        ];

        $save = Trouble::insert($data);
        if ($save) {
            $res = ['status' => 'success', 'msg' => 'Data berhasil disimpan'];
        } else {
            $res = ['status' => 'failed', 'msg' => 'Data gagal disimpan'];
        }

        return Redirect::route('trouble')->with('message', $res);
    }

    public function update(Request $request)
    {
        $request->validate([
            'uuid'      => 'required|string',
            'mulai'     => 'required|date',
            'jam'       => 'required',
            'lokasi'    => 'required|string',
            'kategori'  => 'required|string',
            'petugas'   => 'required|string',
            'deskripsi' => 'required|string',
        ]);

        $uuid = Uuid::uuid4()->toString();
        $data = [
            'tgl_trouble'   => date_format(date_create($request->mulai), 'Y-m-d'),
            'jam_trouble'   => date_format(date_create($request->jam), 'H:i'),
            'lokasi'        => $request->lokasi,
            'kategori'      => $request->kategori,
            'problem'       => $request->deskripsi,
            'petugas'       => $request->petugas,
            'foto_awal'     => (!empty($request->foto) ? $request->foto : $request->old_foto),
            'status'        => 'progress',
            'created_by'    => Auth::user()->uuid,
            'updated_at'    => date('Y-m-d'),
        ];

        $save = Trouble::where('uuid', $request->uuid)->update($data);
        if ($save) {
            $res = ['status' => 'success', 'msg' => 'Data berhasil diupdate'];
        } else {
            $res = ['status' => 'failed', 'msg' => 'Data gagal diupdate'];
        }

        return Redirect::route('trouble')->with('message', $res);
    }

    public function confirm(Request $request)
    {
        $request->validate([    
            'uuid'      => 'required|string',
            'selesai'   => 'required|date',
            'jam'       => 'required',
            'solusi'    => 'required|string',
        ]);

        $data = [
            'tgl_selesai'   => date_format(date_create($request->mulai), 'Y-m-d'),
            'jam_selesai'   => date_format(date_create($request->jam), 'H:i'),
            'solusi'        => $request->solusi,
            'foto_akhir'    => $request->foto,
            'status'        => 'finished',
            'confirmed_by'  => Auth::user()->uuid,
            'updated_at'    => date('Y-m-d'),
        ];

        $save = Trouble::where('uuid', $request->uuid)->update($data);
        if ($save) {
            $res = ['status' => 'success', 'msg' => 'Data berhasil di konfirmasi'];
        } else {
            $res = ['status' => 'failed', 'msg' => 'Data gagal di konfirmasi'];
        }

        return Redirect::route('trouble')->with('message', $res);
    }

    public function delete(Request $request)
    {
        $request->validate(['uuid' => 'required|string']);

        $del = Trouble::where('uuid', $request->uuid)->delete();
        if ($del) {
            $res = ['status' => 'success', 'msg' => 'Data berhasil dihapus'];
        } else {
            $res = ['status' => 'failed', 'msg' => 'Data gagal dihapus'];
        }

        return Redirect::route('trouble')->with('message', $res);
    }
}
