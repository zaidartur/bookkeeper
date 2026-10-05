<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use App\Models\Trouble;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Ramsey\Uuid\Uuid;

class MaintenanceController extends Controller
{
    public function __construct() {
        //
    }

    public function view()
    {
        $data = [
            // 'lists' => Maintenance::orderBy('tanggal_mulai', 'desc')->get(),
        ];

        return Inertia::render('Maintenance', $data);
    }

    public function report()
    {
        $data = [
            'lists' => Maintenance::orderBy('tanggal_mulai', 'desc')->get(),
        ];
        return Inertia::render('ReportMaintenance', $data);
    }

    public function export_pdf(Request $request)
    {
        $query = Maintenance::orderBy('tanggal_mulai', 'desc');

        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->where('tanggal_mulai', '>=', $request->start_date);
        }
        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->where('tanggal_mulai', '<=', $request->end_date);
        }

        $lists = $query->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.maintenance', [
            'lists' => $lists,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('Laporan_Pemeliharaan_Jaringan_' . date('Ymd_His') . '.pdf');
    }

    public function save(Request $request)
    {
        $request->validate([
            'tanggal'   => 'required|date',
            'jam'       => 'required',
            'judul'     => 'required|string|max:100',
            'lokasi'    => 'required|string|max:100',
            'alur'      => 'required|string',
            'petugas'   => 'required|string|max:50',
        ]);

        $uuid = Uuid::uuid4()->toString();
        $data = [
            'uuid'          => $uuid,
            'tanggal_mulai' => date_format(date_create($request->tanggal), 'Y-m-d'),
            'jam_mulai'     => date_format(date_create($request->jam), 'H:i'),
            'judul'         => $request->judul,
            'deskripsi'     => $request->deskripsi,
            'lokasi'        => $request->lokasi,
            'alur_perawatan'=> $request->alur,
            'problem'       => $request->problem,
            'petugas'       => $request->petugas,
            'foto_sebelum'  => $request->foto_awal,
            'foto_setelah'  => $request->foto_akhir,
            'user_id'       => Auth::user()->uuid,
            'created_at'    => date('Y-m-d H:i:s'),
        ];

        $save = Maintenance::insert($data);
        if ($save) {
            $res = ['status' => 'success', 'msg' => 'Data berhasil disimpan'];
        } else {
            $res = ['status' => 'failed', 'msg' => 'Data gagal disimpan'];
        }

        return Redirect::route('maintenance')->with('message', $res);
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
