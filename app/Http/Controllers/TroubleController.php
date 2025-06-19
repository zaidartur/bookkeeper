<?php

namespace App\Http\Controllers;

use App\Models\Trouble;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
}
