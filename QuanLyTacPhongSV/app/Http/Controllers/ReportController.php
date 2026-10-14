<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index', [
            'pageTitle' => 'Thống kê & báo cáo',
            'filterExtended' => true,
        ]);
    }
}
