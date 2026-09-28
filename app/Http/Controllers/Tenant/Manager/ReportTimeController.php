<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportTimeController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Tenant/Manager/Reports/TimeReport');
    }
}
