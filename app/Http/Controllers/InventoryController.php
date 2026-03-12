<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Ramsey\Uuid\Uuid;

class InventoryController extends Controller
{
    public function show()
    {
        $data = [
            'category'  => Category::all(),
            'brand'     => Brand::all(),
            'location'  => Location::all(),
            'lists'     => Inventory::with(['category', 'brand', 'location', 'user'])->orderBy('id')->get(),
        ];
        return Inertia::render('DataInventory', $data);
    }

    public function master()
    {
        $data = [
            'category'  => Category::all(),
            'brand'     => Brand::all(),
            'location'  => Location::all(),
        ];
        return Inertia::render('MasterData', $data);
    }

    public function save_inventory(Request $request)
    {
        $request->validate([
            'uid'       => 'nullable|string|max:40',
            'cat'       => 'required|string|max:40',
            'brand'     => 'required|string|max:40',
            'type'      => 'required|string|max:100',
            'serial'    => 'nullable|string|max:100',
            'date_in'   => 'required|date',
            'method'    => 'required|string|in:pengadaan,pemeliharaan',
            'status'    => 'required|string|in:idle,terpasang,backup',
            'loc'       => 'required|string|max:40',
            // 'condition' => 'required|string|max:100',
            'notes'     => 'nullable|string|max:100',
            'user'      => 'nullable|string|max:40',
            'mode'      => 'required|string|in:new,edit',
        ]);

        $uuid = Uuid::uuid4()->toString();
        if ($request->mode == 'new') {
            $init = new Inventory();
            $init->uid = $uuid;
        } elseif ($request->mode == 'edit' && !empty($request->uid)) {
            $init = Inventory::where('uid', $request->uid)->first();
        } else {
            $res = ['status' => 'failed', 'msg' => 'Data gagal disimpan, inputan tidak sesuai'];
        }

        $init->uid_category = $request->cat;
        $init->uid_brand    = $request->brand;
        $init->type         = $request->type;
        $init->serial       = $request->serial;
        $init->date_in      = $request->date_in;
        $init->method       = $request->method;
        $init->status       = $request->status;
        $init->uid_location = $request->loc;
        $init->notes        = $request->notes;
        $init->user_id      = Auth::user()->uuid;

        $save = $init->save();
        if ($save) {
            $res = ['status' => 'success', 'msg' => 'Data berhasil disimpan'];
        } else {
            $res = ['status' => 'failed', 'msg' => 'Data gagal disimpan'];
        }

        return Redirect::route('inventory.list')->with('message', $res);
    }

    public function update_inventory(Request $request)
    {
        //
    }

    public function drop_inventory(Request $request)
    {
        //
    }

    public function save_master(Request $request)
    {
        $request->validate([
            'mode'  => 'required|string|max:20',
            'name'  => 'required|string|max:100',
        ]);

        if ($request->mode == 'location') {
            $request->validate([
                'name'      => 'required|string|max:100',
                'parent'    => 'nullable|string|max:100'
            ]);
        }

        if ($request->mode == 'category') {
            $save = $this->save_category($request);
        }
        if ($request->mode == 'brand') {
            $save = $this->save_brand($request);
        }
        if ($request->mode == 'location') {
            $save = $this->save_location($request);
        }

        if ($save) {
            $res = ['status' => 'success', 'msg' => 'Data berhasil disimpan'];
        } else {
            $res = ['status' => 'failed', 'msg' => 'Data gagal disimpan'];
        }
        return Redirect::route('inventory.master')->with('message', $res);
    }

    public function save_category($data)
    {
        if (isset($data->uid) && !empty($data->uid)) {
            $new = Category::where('uid', $data->uid)->first();
        } else {
            $uuid   = Uuid::uuid4()->toString();
            $new    = new Category();
            $new->uid = $uuid;
        }

        $new->name = $data->name;
        return $new->save();
    }

    public function save_brand($data)
    {
        if (isset($data->uid) && !empty($data->uid)) {
            $new    = Brand::where('uid', $data->uid)->first();
        } else {
            $uuid       = Uuid::uuid4()->toString();
            $new        = new Brand();
            $new->uid   = $uuid;
        }

        $new->brand = $data->name;
        return $new->save();
    }

    public function save_location($data)
    {
        if (isset($data->uid) && !empty($data->uid)) {
            $new = Location::where('uid', $data->uid)->first();
        } else {
            $uuid   = Uuid::uuid4()->toString();
            $new    = new Location();
            $new->uid = $uuid;
        }

        $new->location  = $data->name;
        $new->parent    = $data->parent;
        return $new->save();
    }
}
