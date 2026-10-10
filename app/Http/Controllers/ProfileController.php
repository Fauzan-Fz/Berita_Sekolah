<?php

namespace App\Http\Controllers;

use App\Support\ImageStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'profile_photo' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('profile_photo')) {
            $photo = ImageStore::store($request->file('profile_photo'), 'profile');

            if ($photo) {
                ImageStore::delete($user->profile_photo);
                $data['profile_photo'] = $photo;
            }
        } elseif ($request->boolean('remove_photo')) {
            ImageStore::delete($user->profile_photo);
            $data['profile_photo'] = null;
        }

        $user->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Profil berhasil diperbarui.',
                'photo_url' => $user->profile_photo ? Storage::disk('public')->url($user->profile_photo) : null,
                'name' => $user->name,
            ]);
        }

        return back()->with('status', 'Profil berhasil diperbarui.');
    }
}
