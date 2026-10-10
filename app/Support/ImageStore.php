<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Menyimpan gambar upload (cover berita / foto profil) sebagai file .webp
 * dengan nama acak, di-downscale bila terlalu besar, lalu diletakkan pada
 * disk "public" (storage/app/public -> public/storage via symlink).
 *
 * OWASP A01 / Path Traversal & File Upload Hardening:
 * - Sanitasi path pada delete untuk mencegah manipulasi directory traversal (../).
 * - Rekonstruksi biner gambar via GD untuk menghapus payload PHP / polyglot tersembunyi.
 */
class ImageStore
{
    /**
     * Simpan file upload sebagai .webp di folder $dir pada disk public.
     * Mengembalikan path relatif (mis. "cover/abc123.webp") atau null bila gagal.
     */
    public static function store(?UploadedFile $file, string $dir): ?string
    {
        if (! $file) {
            return null;
        }

        $image = self::read($file);
        if (! $image) {
            return null;
        }

        $image = self::toTrueColor($image);

        // Downscale bila lebar melebihi batas agar tidak boros penyimpanan.
        $maxWidth = 1600;
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $maxWidth) {
            $scaled = imagescale($image, $maxWidth, (int) round($height * ($maxWidth / $width)));
            if ($scaled) {
                imagedestroy($image);
                $image = $scaled;
            }
        }

        $name = Str::random(40).'.webp';
        $tmp = $dir.'-'.Str::random(20).'.webp';
        $tmpPath = self::tmpPath($tmp);

        if (! imagewebp($image, $tmpPath, 82)) {
            imagedestroy($image);
            @unlink($tmpPath);

            return null;
        }

        imagedestroy($image);

        $stored = Storage::disk('public')->put($dir.'/'.$name, file_get_contents($tmpPath));
        @unlink($tmpPath);

        return $stored ? $dir.'/'.$name : null;
    }

    /**
     * Hapus file lama dari disk public bila masih ada (dipanggil saat update).
     * Mencegah eksploitasi Path Traversal (CWE-22 / OWASP A01).
     */
    public static function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        // Sanitasi path: hilangkan traversal "../" dan batasi pada direktori upload yang sah
        $cleanPath = ltrim(str_replace(['../', '..\\'], '', $path), '/\\');
        if (! str_starts_with($cleanPath, 'cover/') && ! str_starts_with($cleanPath, 'profile/')) {
            return; // Tolak penghapusan berkas di luar folder upload yang diizinkan
        }

        Storage::disk('public')->delete($cleanPath);
    }

    private static function read(UploadedFile $file): \GdImage|false
    {
        $type = $file->getMimeType();
        $real = $file->getRealPath();

        return match (true) {
            $type === 'image/jpeg', $type === 'image/jpg' => @imagecreatefromjpeg($real),
            $type === 'image/png' => @imagecreatefrompng($real),
            $type === 'image/gif' => @imagecreatefromgif($real),
            $type === 'image/webp' => @imagecreatefromwebp($real),
            default => false,
        };
    }

    private static function toTrueColor(\GdImage $image): \GdImage
    {
        if (imageistruecolor($image)) {
            return $image;
        }

        $w = imagesx($image);
        $h = imagesy($image);
        $true = imagecreatetruecolor($w, $h);
        imagealphablending($true, false);
        imagesavealpha($true, true);
        imagecopy($true, $image, 0, 0, 0, 0, $w, $h);
        imagedestroy($image);

        return $true;
    }

    private static function tmpPath(string $name): string
    {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name;
    }
}
