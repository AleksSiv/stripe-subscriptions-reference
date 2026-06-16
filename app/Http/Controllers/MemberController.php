<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MemberController extends Controller
{
    // Reached only through the EnsureActiveSubscription middleware.
    public function index(Request $request)
    {
        return view('member', ['tier' => $request->user()->currentTier()]);
    }
}
