<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class FacilityController extends Controller
{
    public function index(Request $request): View
    {
        $role = (string)$request->session()->get('role', 'CTSV');
        $isAdmin = $role === 'ADMIN';

        return view('facilities.index', [
            'pageTitle' => 'Camera & khu vực',
            'isAdmin' => $isAdmin,
        ]);
    }
}
