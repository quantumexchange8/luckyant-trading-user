<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class TradingController extends Controller
{
    public function master_configuration()
    {
        return Inertia::render('Trading/MasterConfiguration');
    }
}
