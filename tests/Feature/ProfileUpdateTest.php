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
