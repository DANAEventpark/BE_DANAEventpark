<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function updateInfo(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'phone'  => 'nullable|string|max:15',
            'avatar' => 'nullable|string',
        ]);

        $user->update($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'Cập nhật thông tin thành công',
            'data'    => ['user' => $user->load('role')]
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        if (!$user->password || !Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Mật khẩu hiện tại không đúng',
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Đổi mật khẩu thành công',
        ]);
    }
}
