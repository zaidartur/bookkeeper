<?php

namespace App\Http\Controllers;

use App\Models\CardContent;
use App\Models\Gallery;
use App\Models\ServiceContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class SettingController extends Controller
{
    /* =========================================================================
     *  LANDING - CARD MANAGEMENT (/landing/card)
     * ========================================================================= */

    public function landing_card()
    {
        // Auto-seed default cards jika masih kosong
        if (CardContent::count() === 0) {
            $defaultCards = [
                [
                    'title'      => 'Keandalan',
                    'subtitle'   => 'Menjamin ketersediaan data dan jaringan untuk mendukung operasional pemerintah tanpa henti.',
                    'icon'       => 'pi pi-lock',
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title'      => 'Keamanan',
                    'subtitle'   => 'Melindungi data sensitif pemerintah dan masyarakat dengan standar keamanan tertinggi.',
                    'icon'       => 'pi pi-shield',
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title'      => 'Efisiensi',
                    'subtitle'   => 'Mengoptimalkan penggunaan sumber daya IT untuk pelayanan yang lebih cepat dan efektif.',
                    'icon'       => 'pi pi-hourglass',
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title'      => 'Transparansi',
                    'subtitle'   => 'Mendukung keterbukaan informasi publik melalui pengelolaan data yang terpusat dan terstandar.',
                    'icon'       => 'pi pi-eye',
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title'      => 'Inovasi',
                    'subtitle'   => 'Menerapkan teknologi jaringan dan server mutakhir guna meningkatkan akselerasi SPBE.',
                    'icon'       => 'pi pi-bolt',
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title'      => 'Kolaborasi',
                    'subtitle'   => 'Memfasilitasi integrasi antar perangkat daerah demi mewujudkan satu data terpadu.',
                    'icon'       => 'pi pi-users',
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];
            CardContent::insert($defaultCards);
        }

        $list = CardContent::orderBy('id', 'desc')->get();

        return Inertia::render('Landing/Card', [
            'cards' => $list,
            'summary' => [
                'total'  => $list->count(),
                'active' => $list->where('is_active', true)->count(),
            ],
        ]);
    }

    public function card_store(Request $request)
    {
        $request->validate([
            'title'     => 'required|string|max:100',
            'subtitle'  => 'required|string',
            'icon'      => 'required|string|max:50',
            'is_active' => 'boolean',
        ]);

        CardContent::create([
            'title'     => $request->title,
            'subtitle'  => $request->subtitle,
            'icon'      => $request->icon,
            'is_active' => $request->has('is_active') ? (bool) $request->is_active : true,
        ]);

        return Redirect::route('landing.card')->with('message', [
            'status' => 'success',
            'msg'    => 'Kartu Landing berhasil ditambahkan',
        ]);
    }

    public function card_update(Request $request, $id)
    {
        $request->validate([
            'title'     => 'required|string|max:100',
            'subtitle'  => 'required|string',
            'icon'      => 'required|string|max:50',
            'is_active' => 'boolean',
        ]);

        $card = CardContent::findOrFail($id);
        $card->update([
            'title'     => $request->title,
            'subtitle'  => $request->subtitle,
            'icon'      => $request->icon,
            'is_active' => (bool) $request->is_active,
        ]);

        return Redirect::route('landing.card')->with('message', [
            'status' => 'success',
            'msg'    => 'Kartu Landing berhasil diperbarui',
        ]);
    }

    public function card_toggle($id)
    {
        $card = CardContent::findOrFail($id);
        $card->is_active = !$card->is_active;
        $card->save();

        return Redirect::route('landing.card')->with('message', [
            'status' => 'success',
            'msg'    => 'Status Kartu Landing berhasil diubah',
        ]);
    }

    public function card_delete($id)
    {
        $card = CardContent::findOrFail($id);
        $card->delete();

        return Redirect::route('landing.card')->with('message', [
            'status' => 'success',
            'msg'    => 'Kartu Landing berhasil dihapus',
        ]);
    }

    /* =========================================================================
     *  LANDING - GALERI MANAGEMENT (/landing/gallery)
     * ========================================================================= */

    public function landing_gallery()
    {
        // Auto-seed default gallery jika masih kosong
        if (Gallery::count() === 0) {
            $defaultGalleries = [
                [
                    'title'      => 'Ruang Server & Rack Data Center',
                    'image'      => 'https://images.pexels.com/photos/17323801/pexels-photo-17323801.jpeg',
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title'      => 'Kabel Fiber Optik & Patch Panel Core',
                    'image'      => 'https://images.pexels.com/photos/159304/network-cable-ethernet-computer-159304.jpeg',
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title'      => 'Monitoring Jaringan NOC Sentral',
                    'image'      => 'https://images.pexels.com/photos/1148820/pexels-photo-1148820.jpeg',
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title'      => 'Infrastruktur Jaringan Intranet OPD',
                    'image'      => 'https://images.pexels.com/photos/13963756/pexels-photo-13963756.jpeg',
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title'      => 'Keamanan Siber & Firewall Gateway',
                    'image'      => 'https://images.pexels.com/photos/60504/security-protection-anti-virus-software-60504.jpeg',
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title'      => 'Pemeliharaan Switch Core & Router',
                    'image'      => 'https://images.pexels.com/photos/442150/pexels-photo-442150.jpeg',
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];
            Gallery::insert($defaultGalleries);
        }

        $list = Gallery::orderBy('id', 'desc')->get();

        return Inertia::render('Landing/Gallery', [
            'galleries' => $list,
            'summary'   => [
                'total'            => $list->count(),
                'active'           => $list->where('is_active', true)->count(),
                'show_title_count' => $list->where('show_title', true)->count(),
            ],
        ]);
    }

    public function gallery_store(Request $request)
    {
        $request->validate([
            'title'      => 'required|string|max:100',
            'image'      => 'required|string',
            'is_active'  => 'boolean',
            'show_title' => 'boolean',
        ]);

        Gallery::create([
            'title'      => $request->title,
            'image'      => $request->image,
            'show_title' => $request->has('show_title') ? (bool) $request->show_title : true,
            'is_active'  => $request->has('is_active') ? (bool) $request->is_active : true,
        ]);

        return Redirect::route('landing.gallery')->with('message', [
            'status' => 'success',
            'msg'    => 'Foto Galeri Landing berhasil ditambahkan',
        ]);
    }

    public function gallery_update(Request $request, $id)
    {
        $request->validate([
            'title'      => 'required|string|max:100',
            'image'      => 'required|string',
            'is_active'  => 'boolean',
            'show_title' => 'boolean',
        ]);

        $gallery = Gallery::findOrFail($id);
        $gallery->update([
            'title'      => $request->title,
            'image'      => $request->image,
            'show_title' => $request->has('show_title') ? (bool) $request->show_title : true,
            'is_active'  => (bool) $request->is_active,
        ]);

        return Redirect::route('landing.gallery')->with('message', [
            'status' => 'success',
            'msg'    => 'Foto Galeri Landing berhasil diperbarui',
        ]);
    }

    public function gallery_toggle($id)
    {
        $gallery = Gallery::findOrFail($id);
        $gallery->is_active = !$gallery->is_active;
        $gallery->save();

        return Redirect::route('landing.gallery')->with('message', [
            'status' => 'success',
            'msg'    => 'Status Foto Galeri berhasil diubah',
        ]);
    }

    public function gallery_toggle_title($id)
    {
        $gallery = Gallery::findOrFail($id);
        $gallery->show_title = !$gallery->show_title;
        $gallery->save();

        return Redirect::route('landing.gallery')->with('message', [
            'status' => 'success',
            'msg'    => $gallery->show_title ? 'Judul dokumentasi diaktifkan' : 'Judul dokumentasi disembunyikan',
        ]);
    }

    public function gallery_toggle_all_titles(Request $request)
    {
        $state = (bool) $request->input('show_title', true);
        Gallery::query()->update(['show_title' => $state]);

        return Redirect::route('landing.gallery')->with('message', [
            'status' => 'success',
            'msg'    => $state ? 'Semua judul dokumentasi ditampilkan di galeri' : 'Semua judul dokumentasi disembunyikan dari galeri',
        ]);
    }

    public function gallery_delete($id)
    {
        $gallery = Gallery::findOrFail($id);
        $gallery->delete();

        return Redirect::route('landing.gallery')->with('message', [
            'status' => 'success',
            'msg'    => 'Foto Galeri Landing berhasil dihapus',
        ]);
    }

    /* =========================================================================
     *  LANDING - LAYANAN MANAGEMENT (/landing/service)
     * ========================================================================= */

    public function landing_service()
    {
        // Auto-seed default services jika masih kosong
        if (ServiceContent::count() === 0) {
            $defaultServices = [
                [
                    'title'      => 'Pengelolaan Pusat Data',
                    'image'      => '/assets/images/landing/free.svg',
                    'content'    => "Keamanan Fisik & Logis Data Center\nStabilitas Daya UPS & Genset Berkelanjutan\nManajemen Colocation & Kapasitas Server Pemda",
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title'      => 'Infrastruktur Jaringan Pemda',
                    'image'      => '/assets/images/landing/startup.svg',
                    'content'    => "Jaringan Intra-Pemerintah Seluruh OPD\nKonektivitas Internet Publik & Metro-E\nManajemen IP Address Subnet & Bandwidth",
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title'      => 'Helpdesk & Mitigasi Gangguan',
                    'image'      => '/assets/images/landing/enterprise.svg',
                    'content'    => "Penanganan Insiden Jaringan Cepat (SLA 4 Jam)\nPemeliharaan Preventif Berkala Perangkat Jaringan\nKonsultasi Teknis & Pendampingan SPBE",
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];
            ServiceContent::insert($defaultServices);
        }

        $list = ServiceContent::orderBy('id', 'desc')->get();

        return Inertia::render('Landing/Service', [
            'services' => $list,
            'summary'  => [
                'total'  => $list->count(),
                'active' => $list->where('is_active', true)->count(),
            ],
        ]);
    }

    public function service_store(Request $request)
    {
        $request->validate([
            'title'     => 'required|string|max:100',
            'image'     => 'nullable|string',
            'content'   => 'required|string',
            'is_active' => 'boolean',
        ]);

        ServiceContent::create([
            'title'     => $request->title,
            'image'     => $request->image ?? '/assets/images/landing/free.svg',
            'content'   => $request->content,
            'is_active' => $request->has('is_active') ? (bool) $request->is_active : true,
        ]);

        return Redirect::route('landing.service')->with('message', [
            'status' => 'success',
            'msg'    => 'Layanan Landing berhasil ditambahkan',
        ]);
    }

    public function service_update(Request $request, $id)
    {
        $request->validate([
            'title'     => 'required|string|max:100',
            'image'     => 'nullable|string',
            'content'   => 'required|string',
            'is_active' => 'boolean',
        ]);

        $service = ServiceContent::findOrFail($id);
        $service->update([
            'title'     => $request->title,
            'image'     => $request->image ?? $service->image,
            'content'   => $request->content,
            'is_active' => (bool) $request->is_active,
        ]);

        return Redirect::route('landing.service')->with('message', [
            'status' => 'success',
            'msg'    => 'Layanan Landing berhasil diperbarui',
        ]);
    }

    public function service_toggle($id)
    {
        $service = ServiceContent::findOrFail($id);
        $service->is_active = !$service->is_active;
        $service->save();

        return Redirect::route('landing.service')->with('message', [
            'status' => 'success',
            'msg'    => 'Status Layanan berhasil diubah',
        ]);
    }

    public function service_delete($id)
    {
        $service = ServiceContent::findOrFail($id);
        $service->delete();

        return Redirect::route('landing.service')->with('message', [
            'status' => 'success',
            'msg'    => 'Layanan Landing berhasil dihapus',
        ]);
    }
}
