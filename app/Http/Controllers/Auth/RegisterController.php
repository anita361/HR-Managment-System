<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;
use App\Models\roleTypeUser;
// use Hash;
use DB;

class RegisterController extends Controller
{
    // public function register()
    // {
    //     return view('auth.register');
    // }
    public function register()
    {
       
        $roles = roleTypeUser::all(); 

        // Pass roles to the view
        return view('auth.register', compact('roles'));
    }

   public function storeUser(Request $request)
{
    $validated = $request->validate([
        'name'     => 'required|string|max:255',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required|confirmed',
    ]);

    try {
        DB::beginTransaction();

        $user = User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'org_password' => $validated['password'], // only if column exists
            'status'       => 'Active',               // only if column exists
            'password'     => Hash::make($validated['password']),
        ]);

        // optionally: event(new Registered($user)); or login the user: Auth::login($user);

        DB::commit();

        session()->flash('success', 'Account created successfully :)');
        return redirect()->route('login');

    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('RegisterController@storeUser error: ' . $e->getMessage(), [
            'exception' => $e,
            'input'     => $request->except(['password', 'password_confirmation']),
        ]);

        // For debugging locally you can return the message:
        // return back()->with('error', 'Failed to create account: ' . $e->getMessage())->withInput();

        session()->flash('error', 'Failed to create account. Please try again.');
        return redirect()->back()->withInput();
    }
}
}