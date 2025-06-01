<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use App\Models\Trouble;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MaintenanceController extends Controller
{
    public function __construct() {
        //
    }

    public function view()
    {
        $data = [];

        return Inertia::render('Maintenance', $data);
    }

    public function trouble()
    {
        $data = [
            'lists' => [
                [
                'starttime' => 'A',
                'country'   => 'B',
                'trouble'   => 'C',
                'category'  => 'D',
                'status'    => 'progress',
                ],
                [
                'starttime' => 'A1',
                'country'   => 'B1',
                'trouble'   => 'C1',
                'category'  => 'D1',
                'status'    => 'E1',
                ],
            ],
        ];
        return Inertia::render('Trouble', $data);
    }

    public function input_form()
    {
        return Inertia::render('Maintenance');
    }
}
