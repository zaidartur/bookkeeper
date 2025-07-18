<?php

namespace App\Http\Controllers;

use App\Imports\ImportTamu;
use App\Models\BukuTamu;
use App\Models\Trouble;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class DashboardController extends Controller
{
    public function index()
    {
        $data = [
            'troubles'  => [
                    'lokal' => Trouble::where('kategori', 'lokal')->where('status', 'progress')->get(),
                    'intra' => Trouble::where('kategori', 'opd')->where('status', 'progress')->get(),
                    'metro' => Trouble::where('kategori', 'metro')->where('status', 'progress')->get(),
                    'internet' => Trouble::where('kategori', 'internet')->where('status', 'progress')->get(),
                ],
            'guest'     => [
                    'total' => BukuTamu::count(),
                    'months'=> BukuTamu::whereMonth('tanggal', date('m'))->count(),
                    'today' => BukuTamu::where('tanggal', date('Y-m-d'))->count(),
            ],
        ];
        return Inertia::render('Dashboard', $data);
    }

    public function guestbook()
    {
        $data = [
            'total' => BukuTamu::count(),
            'today' => BukuTamu::whereDate('created_at', date("Y-m-d"))->count(),
        ];
        return Inertia::render('Guestbook', $data);
    }

    public function report_guest()
    {
        $data = [
            'lists'     => BukuTamu::orderBy('tanggal', 'desc')->get(),
            'dates'     => BukuTamu::groupBy('tanggal')->select('tanggal')->get(),
        ];

        return Inertia::render('ReportGuest', $data);
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

    public function import_guest(Request $request)
    {
        $request->validate(['file' => 'required']);

        $file = base64_decode($request->file);
        File::move(public_path() . '/uploads', $file);
    }

    public function view_import()
    {
        return view('tamu');
    }

    public function save_import(Request $request)
    {
        $request->validate(['file_import' => 'required']);

        $file   = $request->file('file_import');
        $ext    = $file->getClientOriginalExtension();
        if ($ext == 'xlsx') {
            $import = new ImportTamu;
            Excel::import($import, $file->store('temp'));
            $res = ['res' => 'success', 'success' => strval($import->success), 'incomplete' => strval($import->incomplete), 'duplicate' => strval($import->duplicate), 'files' => $import->object, 'total' => $import->total];
            // Log::debug('res', $res);
        } else {
            $res = ['res' => 'failed'];
        }

        return redirect()->back()->with($res);
    }
}
