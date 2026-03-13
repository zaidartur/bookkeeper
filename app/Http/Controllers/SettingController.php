<?php

namespace App\Http\Controllers;

use App\Models\CardContent;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function show_card()
    {
        $data = [
            'list'  => CardContent::all(),
        ];

        return view('', $data);
    }
}
