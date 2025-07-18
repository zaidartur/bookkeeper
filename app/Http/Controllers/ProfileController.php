<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Ramsey\Uuid\Uuid;

class ProfileController extends Controller
{

    public function index()
    {
        $data = [
            'activity'  => DB::table('sessions')->where('user_id', Auth::user()->id)->orderBy('id', 'desc')->first(),
            'lists'     => User::select('id', 'uuid', 'name', 'email', 'created_at', 'updated_at')->get(),
        ];
        return Inertia::render('Profile', $data);
    }

    public function create_user(Request $request) 
    {
        $request->validate([
            'name'      => 'required|string|max:25',
            'email'     => 'required|string|email|max:50|unique:users',
            'password'  => 'required|string',
        ]);

        $uuid = Uuid::uuid4()->toString();
        $data = [
            'uuid'      => $uuid,
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'email_verified_at' => date('Y-m-d H:i:s'),
            'created_at'=> date('Y-m-d H:i:s'),
        ];

        $save = User::insert($data);
        if ($save) {
            $res = ['status' => 'success', 'msg' => 'User berhasil ditambahkan'];
        } else {
            $res = ['status' => 'failed', 'msg' => 'User gagal disimpan'];
        }

        return Redirect::route('profile')->with('message', $res);
    }

    public function update_profile(Request $request) 
    {
        $request->validate([
            'uuid'  => 'required|string|max:40',
            'name'  => 'required|string|max:25',
            'email' => 'required|email'
        ]);

        $data = [
            'name'          => $request->name,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        $save = User::where('uuid', $request->uuid)->update($data);

        if ($save) {
            $res = ['status' => 'success', 'msg' => 'Data berhasil di update'];
        } else {
            $res = ['status' => 'failed', 'msg' => 'Data gagal di update'];
        }

        return Redirect::route('profile')->with('message', $res);
    }

    public function update_password(Request $request)
    {
        $request->validate([
            'uuid'      => 'required|string|max:40',
            'password'  => 'required|string',
        ]);

        $data = [
            'password'  => Hash::make(base64_decode($request->password)),
        ];

        $save = User::where('uuid', $request->uuid)->update($data);

        if ($save) {
            $res = ['status' => 'success', 'msg' => 'Password berhasil di update'];
        } else {
            $res = ['status' => 'failed', 'msg' => 'Password gagal di update'];
        }

        return Redirect::route('profile')->with('message', $res);
    }

    public function check_password(Request $request)
    {
        $request->validate([
            'uuid'      => 'required|string|max:40',
            'password'  => 'required|string',
        ]);

        $user = User::where('uuid', $request->uuid)->first();

        if ($user && Hash::check(base64_decode($request->password), $user->password)) {
            $res = ['status' => 'success', 'msg' => 'Password sesuai'];
        } else {
            $res = ['status' => 'failed', 'msg' => 'Password tidak sesuai'];
        }

        return json_encode($res);
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
