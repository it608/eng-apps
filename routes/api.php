<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MasterBarangController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;


Route::get('/master/barang', [MasterBarangController::class, 'index']);

Route::post('/login', function (Request $request) {

    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    $user = DB::table('users')
        ->where('email', $request->email)
        ->first();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'User tidak ditemukan'
        ], 401);
    }

    if (!Hash::check($request->password, $user->password)) {
        return response()->json([
            'status' => false,
            'message' => 'Password salah'
        ], 401);
    }

    return response()->json([
        'status' => true,
        'message' => 'Login berhasil',
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]
    ]);
});
