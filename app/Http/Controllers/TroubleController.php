<?php

namespace App\Http\Controllers;

use App\Models\Trouble;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Ramsey\Uuid\Uuid;

class TroubleController extends Controller
{
    public function view()
    {
        $data = [
            'lists' => Trouble::where('status', 'progress')->orderBy('created_at')->get(),
        ];
        return Inertia::render('Trouble', $data);
    }

    public function report()
    {
        $finished = Trouble::where('status', 'finished')->orderBy('tgl_trouble', 'desc')->get();
        $totalFinished = $finished->count();
        $avgMinutes = $finished->whereNotNull('durasi_menit')->avg('durasi_menit') ?? 0;
        $slaCompliant = $finished->where('sla_status', 'compliant')->count();
        $slaRate = $totalFinished > 0 ? round(($slaCompliant / $totalFinished) * 100, 1) : 100;

        $mttrFormatted = floor($avgMinutes / 60) . ' jam ' . ($avgMinutes % 60) . ' menit';

        $data = [
            'lists'   => $finished,
            'dates'   => Trouble::where('status', 'finished')->groupBy('tgl_trouble')->select('tgl_trouble')->get(),
            'metrics' => [
                'total_resolved'  => $totalFinished,
                'mttr_minutes'    => round($avgMinutes),
                'mttr_formatted'  => $mttrFormatted,
                'sla_rate'        => $slaRate,
                'sla_compliant'   => $slaCompliant,
                'sla_breached'    => $totalFinished - $slaCompliant,
            ],
        ];
        return Inertia::render('ReportTrouble', $data);
    }

    public function export_pdf(Request $request)
    {
        $query = Trouble::where('status', 'finished')->orderBy('tgl_trouble', 'desc');

        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->where('tgl_trouble', '>=', $request->start_date);
        }
        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->where('tgl_trouble', '<=', $request->end_date);
        }

        $lists = $query->get();
        $totalFinished = $lists->count();
        $avgMinutes = $lists->whereNotNull('durasi_menit')->avg('durasi_menit') ?? 0;
        $slaCompliant = $lists->where('sla_status', 'compliant')->count();
        $slaRate = $totalFinished > 0 ? round(($slaCompliant / $totalFinished) * 100, 1) : 100;

        $mttrFormatted = floor($avgMinutes / 60) . ' jam ' . ($avgMinutes % 60) . ' menit';

        $metrics = [
            'total_resolved' => $totalFinished,
            'mttr_minutes'   => round($avgMinutes),
            'mttr_formatted' => $mttrFormatted,
            'sla_rate'       => $slaRate,
            'sla_compliant'  => $slaCompliant,
        ];

        $pdf = Pdf::loadView('pdf.trouble', [
            'lists'   => $lists,
            'metrics' => $metrics,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('Laporan_Trouble_Ticket_' . date('Ymd_His') . '.pdf');
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
        $tglTrouble = date_format(date_create($request->mulai), 'Y-m-d');
        $jamTrouble = date_format(date_create($request->jam), 'H:i');

        // Target penyelesaian standar SLA 4 jam
        $targetSelesai = Carbon::parse($tglTrouble . ' ' . $jamTrouble)->addHours(4);

        $data = [
            'uuid'           => $uuid,
            'tgl_trouble'    => $tglTrouble,
            'jam_trouble'    => $jamTrouble,
            'lokasi'         => $request->lokasi,
            'kategori'       => $request->kategori,
            'problem'        => $request->deskripsi,
            'petugas'        => $request->petugas,
            'foto_awal'      => $request->foto,
            'status'         => 'progress',
            'target_selesai' => $targetSelesai,
            'created_by'     => Auth::user()->uuid,
            'created_at'     => date('Y-m-d H:i:s'),
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
            'updated_at'    => date('Y-m-d H:i:s'),
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

        $trouble = Trouble::where('uuid', $request->uuid)->first();
        if (!$trouble) {
            return Redirect::route('trouble')->with('message', ['status' => 'failed', 'msg' => 'Data tiket tidak ditemukan']);
        }

        $tglSelesai = date_format(date_create($request->selesai), 'Y-m-d');
        $jamSelesai = date_format(date_create($request->jam), 'H:i');

        // MTTR & SLA Calculation (SLA target: <= 4 jam / 240 menit)
        $start = Carbon::parse($trouble->tgl_trouble . ' ' . ($trouble->jam_trouble ?: '00:00'));
        $end = Carbon::parse($tglSelesai . ' ' . $jamSelesai);
        $durasiMenit = max(0, $start->diffInMinutes($end));
        $slaStatus = ($durasiMenit <= 240) ? 'compliant' : 'breached';

        $data = [
            'tgl_selesai'   => $tglSelesai,
            'jam_selesai'   => $jamSelesai,
            'solusi'        => $request->solusi,
            'foto_akhir'    => $request->foto,
            'durasi_menit'  => $durasiMenit,
            'sla_status'    => $slaStatus,
            'status'        => 'finished',
            'confirmed_by'  => Auth::user()->uuid,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        $save = Trouble::where('uuid', $request->uuid)->update($data);
        if ($save) {
            $res = ['status' => 'success', 'msg' => 'Data berhasil dikonfirmasi'];
        } else {
            $res = ['status' => 'failed', 'msg' => 'Data gagal dikonfirmasi'];
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
