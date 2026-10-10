<?php

namespace App\Http\Controllers;

use App\Support\ImageStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

            if (! $photo) {
                // Lolos validasi `image` (mis. BMP) tapi GD tidak bisa
                // merekonstruksi binernya — tolak dengan 422, bukan 500
                // saat save (validated data masih berisi UploadedFile).
                throw ValidationException::withMessages([
                    'profile_photo' => 'Foto profil tidak dapat diproses. Gunakan file JPEG, PNG, GIF, atau WebP.',
                ]);
            }

            ImageStore::delete($user->profile_photo);
            $data['profile_photo'] = $photo;
        } elseif ($request->boolean('remove_photo')) {
            ImageStore::delete($user->profile_photo);
            $data['profile_photo'] = null;
        } else {
            // Tanpa upload: jangan sentuh kolom foto (termasuk bila
            // klien mengirim profile_photo: null secara eksplisit).
            unset($data['profile_photo']);
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
