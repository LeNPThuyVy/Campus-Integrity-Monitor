<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

class ViolationController extends Controller
{
    public function index(): View
    {
        return view('violations.index', [
            'pageTitle' => 'Bản ghi vi phạm',
        ]);
    }
}
