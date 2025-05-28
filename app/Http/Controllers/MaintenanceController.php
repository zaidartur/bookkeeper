<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class MaintenanceController extends Controller
{
    public function __construct() {
        //
    }

    public function input_form()
    {
        return Inertia::render('Maintenance');
    }
}
