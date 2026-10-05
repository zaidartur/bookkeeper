<?php

namespace App\Http\Controllers;

use App\Imports\ImportTamu;
use App\Models\BukuTamu;
use App\Models\Inventory;
use App\Models\NetworkMonitor;
use App\Models\Trouble;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $driver = DB::connection()->getDriverName();
        $monthTrouble = match ($driver) {
            'sqlite' => "strftime('%m', tgl_trouble)",
            'pgsql'  => "to_char(tgl_trouble, 'MM')",
            default  => "DATE_FORMAT(tgl_trouble, '%m')", // mysql, mariadb
        };

        $monthGuest = match ($driver) {
            'sqlite' => "strftime('%m', tanggal)",
            'pgsql'  => "to_char(tanggal, 'MM')",
            default  => "DATE_FORMAT(tanggal, '%m')", // mysql, mariadb
        };

        // 1 Query untuk seluruh gangguan in-progress (menggantikan 4 kueri terpisah)
        $activeTroubles = Trouble::where('status', 'progress')->get()->groupBy('kategori');

        // 1 Query untuk rekapitulasi bulanan seluruh kategori (menggantikan 5 kueri terpisah)
        $rawTroubleStats = Trouble::select(
            DB::raw('count(id) as total'),
            DB::raw("{$monthTrouble} AS bulan"),
            DB::raw('MAX(kategori) as kategori')
        )
        ->whereYear('tgl_trouble', '<=', date('Y'))
        ->groupBy('bulan', 'kategori')
        ->orderBy('bulan')
        ->get();

        $data = [
            'troubles'  => [
                'lokal'    => $activeTroubles->get('lokal', collect()),
                'intra'    => $activeTroubles->get('opd', collect()),
                'metro'    => $activeTroubles->get('metro', collect()),
                'internet' => $activeTroubles->get('internet', collect()),
            ],
            'grafik'    => [
                'lokal'    => $rawTroubleStats->where('kategori', 'lokal')->values(),
                'intra'    => $rawTroubleStats->where('kategori', 'opd')->values(),
                'metro'    => $rawTroubleStats->where('kategori', 'metro')->values(),
                'internet' => $rawTroubleStats->where('kategori', 'internet')->values(),
                'bulan'    => $rawTroubleStats->pluck('bulan')->unique()->values()->map(fn($b) => ['bulan' => $b]),
            ],
            'guest'     => [
                'total'  => BukuTamu::count(),
                'months' => BukuTamu::whereMonth('tanggal', date('m'))->count(),
                'today'  => BukuTamu::where('tanggal', date('Y-m-d'))->count(),
                'chart'  => BukuTamu::select(DB::raw('count(id) as total'), DB::raw("{$monthGuest} AS bulan"))->whereYear('tanggal', '<=', date('Y'))->groupBy('bulan')->orderBy('bulan')->get(),
            ],
            'inventory' => Inventory::with(['category', 'brand', 'location'])->get(),
            'network_monitors' => [
                'total' => NetworkMonitor::where('is_active', true)->count(),
                'up'    => NetworkMonitor::where('is_active', true)->where('status', 'UP')->count(),
                'down'  => NetworkMonitor::where('is_active', true)->where('status', 'DOWN')->count(),
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

    public function guest_scan()
    {
        return Inertia::render('GuestScan');
    }

    public function report_guest()
    {
        $data = [
            'lists'     => BukuTamu::orderBy('tanggal', 'desc')->get(),
            'dates'     => BukuTamu::groupBy('tanggal')->select('tanggal')->get(),
        ];

        return Inertia::render('ReportGuest', $data);
    }

    public function export_guest_pdf(Request $request)
    {
        $query = BukuTamu::orderBy('tanggal', 'desc');

        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->where('tanggal', '>=', $request->start_date);
        }
        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->where('tanggal', '<=', $request->end_date);
        }

        $lists = $query->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.guest', [
            'lists' => $lists,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('Laporan_Buku_Tamu_' . date('Ymd_His') . '.pdf');
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
