<?php

namespace App\Http\Controllers;

use App\Models\Petugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Ramsey\Uuid\Uuid;

class PetugasController extends Controller
{
    /**
     * Tampilkan data seluruh petugas.
     */
    public function index(Request $request)
    {
        // Auto-seed data awal jika masih kosong
        if (Petugas::count() === 0) {
            $defaultPetugas = [
                [
                    'uuid_petugas' => Uuid::uuid4()->toString(),
                    'nama_petugas' => 'Ahmad Rifai, S.Kom',
                    'bidang'       => 'Infrastruktur & Jaringan',
                    'nip'          => '198804152011011002',
                    'phone'        => '081234567890',
                    'alamat'       => 'Gedung Diskominfo Lt. 2, Ruang NOC',
                    'foto_profile' => null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
                [
                    'uuid_petugas' => Uuid::uuid4()->toString(),
                    'nama_petugas' => 'Budi Santoso, S.T.',
                    'bidang'       => 'Operasional Data Center',
                    'nip'          => '199009122015021004',
                    'phone'        => '081398765432',
                    'alamat'       => 'NOC & Ruang Server Gedung Utama',
                    'foto_profile' => null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
                [
                    'uuid_petugas' => Uuid::uuid4()->toString(),
                    'nama_petugas' => 'Siti Nurhaliza, A.Md.',
                    'bidang'       => 'Helpdesk & Tiket Gangguan',
                    'nip'          => '199502282019032008',
                    'phone'        => '082155667788',
                    'alamat'       => 'Front Office Pelayanan IT',
                    'foto_profile' => null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
                [
                    'uuid_petugas' => Uuid::uuid4()->toString(),
                    'nama_petugas' => 'Eko Prasetyo',
                    'bidang'       => 'Infrastruktur & Jaringan',
                    'nip'          => '199207042016011005',
                    'phone'        => '085712349876',
                    'alamat'       => 'Teknisi Lapangan & Kabel FO',
                    'foto_profile' => null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
            ];
            Petugas::insert($defaultPetugas);
        }

        $list = Petugas::orderBy('id', 'desc')->get();

        // Metrik ringkasan
        $total = $list->count();
        $bidangJaringan = $list->where('bidang', 'Infrastruktur & Jaringan')->count();
        $bidangDataCenter = $list->where('bidang', 'Operasional Data Center')->count();
        $bidangHelpdesk = $list->where('bidang', 'Helpdesk & Tiket Gangguan')->count();

        $data = [
            'petugas' => $list,
            'summary' => [
                'total'       => $total,
                'jaringan'    => $bidangJaringan,
                'datacenter'  => $bidangDataCenter,
                'helpdesk'    => $bidangHelpdesk,
            ],
        ];

        return Inertia::render('Petugas', $data);
    }

    /**
     * Simpan data petugas baru.
     */
    public function save(Request $request)
    {
        $request->validate([
            'nama_petugas' => 'required|string|max:150',
            'nip'          => 'nullable|string|max:25',
            'bidang'       => 'nullable|string|max:150',
            'phone'        => 'nullable|string|max:20',
            'alamat'       => 'nullable|string|max:255',
            'foto_profile' => 'nullable|string',
        ]);

        $uuid = Uuid::uuid4()->toString();

        $petugas = Petugas::create([
            'uuid_petugas' => $uuid,
            'nama_petugas' => $request->nama_petugas,
            'nip'          => $request->nip,
            'bidang'       => $request->bidang,
            'phone'        => $request->phone,
            'alamat'       => $request->alamat,
            'foto_profile' => $request->foto_profile,
        ]);

        if ($petugas) {
            $res = ['status' => 'success', 'msg' => 'Data Petugas berhasil disimpan'];
        } else {
            $res = ['status' => 'failed', 'msg' => 'Gagal menyimpan data Petugas'];
        }

        return Redirect::route('petugas')->with('message', $res);
    }

    /**
     * Perbarui data petugas.
     */
    public function update(Request $request, $uuid)
    {
        $request->validate([
            'nama_petugas' => 'required|string|max:150',
            'nip'          => 'nullable|string|max:25',
            'bidang'       => 'nullable|string|max:150',
            'phone'        => 'nullable|string|max:20',
            'alamat'       => 'nullable|string|max:255',
            'foto_profile' => 'nullable|string',
        ]);

        $petugas = Petugas::where('uuid_petugas', $uuid)->first();
        if (!$petugas) {
            return Redirect::route('petugas')->with('message', [
                'status' => 'failed',
                'msg'    => 'Data Petugas tidak ditemukan'
            ]);
        }

        $petugas->update([
            'nama_petugas' => $request->nama_petugas,
            'nip'          => $request->nip,
            'bidang'       => $request->bidang,
            'phone'        => $request->phone,
            'alamat'       => $request->alamat,
            'foto_profile' => $request->foto_profile ?? $petugas->foto_profile,
        ]);

        return Redirect::route('petugas')->with('message', [
            'status' => 'success',
            'msg'    => 'Data Petugas berhasil diperbarui'
        ]);
    }

    /**
     * Hapus data petugas.
     */
    public function delete($uuid)
    {
        $petugas = Petugas::where('uuid_petugas', $uuid)->first();
        if (!$petugas) {
            return Redirect::route('petugas')->with('message', [
                'status' => 'failed',
                'msg'    => 'Data Petugas tidak ditemukan'
            ]);
        }

        $petugas->delete();

        return Redirect::route('petugas')->with('message', [
            'status' => 'success',
            'msg'    => 'Data Petugas berhasil dihapus'
        ]);
    }
}
