<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_seluruh_role_dapat_memperbarui_foto_profil(): void
    {
        Storage::fake('public');

        foreach (['siswa', 'guru', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $f = $this->makeJpegFile();

            $response = $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
                'name' => 'Nama Baru',
                'bio' => 'Bio baru.',
                'profile_photo' => $f,
            ]);

            $response->assertOk()
                ->assertJson(['message' => 'Profil berhasil diperbarui.'])
                ->assertJsonStructure(['photo_url', 'name']);

            $user->refresh();
            $this->assertNotNull($user->profile_photo);
            $this->assertSame('Nama Baru', $user->name);
            // file tersimpan di disk public
            Storage::disk('public')->assertExists($user->profile_photo);
        }
    }

    public function test_update_tanpa_foto_tidak_menghapus_foto_yang_ada(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'siswa', 'profile_photo' => 'profile/lama.webp']);

        $response = $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => $user->name,
            'bio' => '',
        ]);

        $response->assertOk();
        $user->refresh();
        $this->assertSame('profile/lama.webp', $user->profile_photo);
    }

    public function test_remove_photo_menghapus_foto_server_side(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'guru', 'profile_photo' => 'profile/lama.webp']);

        $response = $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => $user->name,
            'bio' => '',
            'remove_photo' => true,
        ]);

        $response->assertOk()->assertJsonPath('photo_url', null);
        $user->refresh();
        $this->assertNull($user->profile_photo);
    }

    public function test_validasi_menolak_tanpa_nama(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => '',
            'bio' => '',
        ])->assertStatus(422);
    }

    public function test_upload_mengganti_foto_lama_dan_menyimpan_webp(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'siswa']);

        // Upload pertama.
        $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => 'Nama Baru',
            'bio' => 'Bio baru.',
            'profile_photo' => $this->makeJpegFile(),
        ])->assertOk();
        $user->refresh();
        $lama = $user->profile_photo;
        $this->assertNotNull($lama);
        $this->assertStringEndsWith('.webp', $lama);
        Storage::disk('public')->assertExists($lama);

        // Upload kedua: file lama harus terhapus, path baru berbeda.
        $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => 'Nama Baru',
            'bio' => 'Bio baru.',
            'profile_photo' => $this->makeJpegFile(),
        ])->assertOk();
        $user->refresh();
        $this->assertNotNull($user->profile_photo);
        $this->assertStringEndsWith('.webp', $user->profile_photo);
        $this->assertNotSame($lama, $user->profile_photo);
        Storage::disk('public')->assertMissing($lama);
        Storage::disk('public')->assertExists($user->profile_photo);
    }

    public function test_validasi_menolak_nama_terlalu_panjang_bio_terlalu_panjang_dan_foto_bukan_gambar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'siswa']);

        // Nama > 255 karakter.
        $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => str_repeat('a', 256),
            'bio' => 'Bio.',
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);

        // Bio > 1000 karakter.
        $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => 'Nama Valid',
            'bio' => str_repeat('b', 1001),
        ])->assertStatus(422)->assertJsonValidationErrors(['bio']);

        // File teks bukan gambar.
        $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => 'Nama Valid',
            'bio' => 'Bio.',
            'profile_photo' => UploadedFile::fake()->create('dokumen.txt', 100, 'text/plain'),
        ])->assertStatus(422)->assertJsonValidationErrors(['profile_photo']);
    }

    public function test_validasi_menolak_foto_melebihi_4mb(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'guru']);

        $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => 'Nama Valid',
            'bio' => 'Bio.',
            // 4097 KB > batas max:4096.
            'profile_photo' => UploadedFile::fake()->image('besar.jpg')->size(4097),
        ])->assertStatus(422)->assertJsonValidationErrors(['profile_photo']);
    }

    public function test_nama_dan_bio_disanitasi_dari_xss(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => '<script>alert("xss")</script>Nama Bersih',
            'bio' => '<img src=x onerror="alert(1)">Bio bersih.',
        ])->assertOk();

        $user->refresh();
        $this->assertStringNotContainsString('<script>', $user->name);
        $this->assertStringNotContainsString('onerror', $user->bio);
        $this->assertStringContainsString('Nama Bersih', $user->name);
        $this->assertStringContainsString('Bio bersih.', $user->bio);
    }

    public function test_request_biasa_redirect_kembali_dengan_status(): void
    {
        $user = User::factory()->create(['role' => 'guru']);

        $this->actingAs($user)
            ->from(route('dashboard.guru'))
            ->put(route('dashboard.profile.update'), [
                'name' => 'Nama Redirect',
                'bio' => 'Bio.',
            ])
            ->assertRedirect(route('dashboard.guru'))
            ->assertSessionHas('status', 'Profil berhasil diperbarui.');

        $user->refresh();
        $this->assertSame('Nama Redirect', $user->name);
    }

    public function test_guest_diblokir(): void
    {
        $this->putJson(route('dashboard.profile.update'), [
            'name' => 'X',
            'bio' => '',
        ])->assertUnauthorized();
    }

    public function test_remove_photo_diabaikan_saat_upload_baru_dikirim(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'siswa', 'profile_photo' => 'profile/lama.webp']);

        $response = $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => 'Nama Baru',
            'bio' => 'Bio.',
            'profile_photo' => $this->makeJpegFile(),
            'remove_photo' => true,
        ]);

        $response->assertOk();
        $user->refresh();
        $this->assertNotNull($user->profile_photo);
        $this->assertNotSame('profile/lama.webp', $user->profile_photo);
        $this->assertStringEndsWith('.webp', $user->profile_photo);
    }

    public function test_request_non_json_tanpa_referer_redirect_kembali(): void
    {
        $user = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($user)
            ->put(route('dashboard.profile.update'), [
                'name' => 'Nama Fallback',
                'bio' => 'Bio.',
            ])
            ->assertRedirect('/')
            ->assertSessionHas('status', 'Profil berhasil diperbarui.');

        $user->refresh();
        $this->assertSame('Nama Fallback', $user->name);
    }

    public function test_gambar_tak_terbaca_menolak_dengan_422_bukan_500(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'guru']);

        // BMP lolos aturan `image` tapi GD tidak bisa merekonstruksinya.
        $bmp = base64_decode('Qk06AAAAAAAAADYAAAAoAAAAAQAAAAEAAAABABgAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==');
        $path = sys_get_temp_dir().'/bmp-test-'.bin2hex(random_bytes(6)).'.bmp';
        file_put_contents($path, $bmp);
        $file = new UploadedFile($path, 'avatar.bmp', 'image/bmp', null, true);

        $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => 'Nama Valid',
            'bio' => 'Bio.',
            'profile_photo' => $file,
        ])->assertStatus(422)->assertJsonValidationErrors(['profile_photo']);

        $user->refresh();
        $this->assertNull($user->profile_photo);
    }

    /**
     * Menulis JPEG asli ke disk sementara & membungkusnya sebagai UploadedFile.
     */
    private function makeJpegFile(): UploadedFile
    {
        $img = imagecreatetruecolor(40, 40);
        imagefill($img, 0, 0, imagecolorallocate($img, 220, 220, 220));
        $path = sys_get_temp_dir().'/jpeg-test-'.bin2hex(random_bytes(6)).'.jpg';
        imagejpeg($img, $path, 80);
        imagedestroy($img);

        return new UploadedFile($path, 'avatar.jpg', 'image/jpeg', null, true);
    }
}
