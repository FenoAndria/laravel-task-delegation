<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoLoginController extends Controller
{
    /**
     * Log in as one of the demo personas. Only exists in demo mode.
     */
    public function store(Request $request, string $persona): RedirectResponse
    {
        abort_unless(config('delegation.demo') && isset(DemoSeeder::PERSONAS[$persona]), 404);

        $user = User::where('email', DemoSeeder::PERSONAS[$persona])->firstOrFail();

        Auth::login($user);
        $request->session()->regenerate();

        return to_route('dashboard');
    }
}
