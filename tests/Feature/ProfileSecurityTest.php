<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Independent SECURITY review of the Edit User Profile changes
 * (branch feature/edit-user-profile, ProfileController + profile-dialog + app.js).
 *
 * Style mirrors OwaspHardeningTest: one attack surface per test, FAIL = genuine vuln.
 */
class ProfileSecurityTest extends TestCase
{
    use RefreshDatabase;

    // ── (3) Mass assignment ──────────────────────────────────────────

    public function test_profile_mass_assignment_menolak_role_email_password_is_active(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'role' => 'siswa',
            'email' => 'siswa@sekolah.sch.id',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => 'Nama Valid',
            'bio' => 'Bio.',
            'role' => 'admin',
            'email' => 'diambil@evil.com',
            'password' => 'password-bajak',
            'is_active' => false,
        ]);

        $response->assertOk();
        $user->refresh();

        $this->assertSame('siswa', $user->role);
        $this->assertSame('siswa@sekolah.sch.id', $user->email);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    // ── (5) Auth: IDOR-by-design ─────────────────────────────────────

    public function test_profile_tidak_bisa_mengubah_profil_pengguna_lain(): void
    {
        Storage::fake('public');
        $korban = User::factory()->create(['role' => 'siswa', 'name' => 'Korban']);
        $penyerang = User::factory()->create(['role' => 'siswa', 'name' => 'Penyerang']);

        $this->actingAs($penyerang)->putJson(route('dashboard.profile.update'), [
            'name' => 'Dibajak',
            'bio' => 'Bio.',
            'id' => $korban->id,
            'user_id' => $korban->id,
        ])->assertOk();

        // Korban tidak berubah; hanya penyerang yang terupdate (endpoint memakai $request->user()).
        $this->assertSame('Korban', $korban->fresh()->name);
        $this->assertSame('Dibajak', $penyerang->fresh()->name);
    }

    public function test_profile_guest_diblokir_json(): void
    {
        $this->putJson(route('dashboard.profile.update'), [
            'name' => 'X',
            'bio' => '',
        ])->assertUnauthorized();
    }

    // ── (1) Avatar upload bypass ─────────────────────────────────────

    public function test_profile_svg_ditolak(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'siswa']);

        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';
        $path = sys_get_temp_dir().'/evil-'.bin2hex(random_bytes(6)).'.svg';
        file_put_contents($path, $svg);
        $file = new UploadedFile($path, 'avatar.svg', 'image/svg+xml', null, true);

        $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => 'Nama Valid',
            'bio' => 'Bio.',
            'profile_photo' => $file,
        ])->assertStatus(422)->assertJsonValidationErrors(['profile_photo']);

        $this->assertNull($user->fresh()->profile_photo);
    }

    public function test_profile_php_disamarkan_sebagai_jpg_ditolak(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'siswa']);

        // Konten PHP polos dengan nama .jpg — finfo mendeteksi text/plain.
        $file = UploadedFile::fake()->create('shell.jpg', 50, 'image/jpeg');

        $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => 'Nama Valid',
            'bio' => 'Bio.',
            'profile_photo' => $file,
        ])->assertStatus(422)->assertJsonValidationErrors(['profile_photo']);

        $this->assertNull($user->fresh()->profile_photo);
    }

    public function test_profile_bmp_ditolak_422_bukan_500(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'guru']);

        // BMP asli lolos rule `image` Laravel tapi GD reader ImageStore tidak menanganinya.
        $img = imagecreatetruecolor(20, 20);
        imagefill($img, 0, 0, imagecolorallocate($img, 200, 200, 200));
        $path = sys_get_temp_dir().'/bmp-test-'.bin2hex(random_bytes(6)).'.bmp';
        imagebmp($img, $path);
        imagedestroy($img);
        $file = new UploadedFile($path, 'avatar.bmp', 'image/bmp', null, true);

        $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => 'Nama Valid',
            'bio' => 'Bio.',
            'profile_photo' => $file,
        ])->assertStatus(422)->assertJsonValidationErrors(['profile_photo']);

        $this->assertNull($user->fresh()->profile_photo);
    }

    public function test_profile_polyglot_php_dalam_jpeg_dinetralkan_gd_rebuild(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'siswa']);

        // JPEG valid + payload PHP ditempel setelah EOI (finfo tetap image/jpeg).
        $img = imagecreatetruecolor(40, 40);
        imagefill($img, 0, 0, imagecolorallocate($img, 220, 220, 220));
        $path = sys_get_temp_dir().'/polyglot-'.bin2hex(random_bytes(6)).'.jpg';
        imagejpeg($img, $path, 80);
        imagedestroy($img);
        file_put_contents($path, '<?php echo "pwned"; system($_GET["c"]); ?>', FILE_APPEND);
        $file = new UploadedFile($path, 'avatar.jpg', 'image/jpeg', null, true);

        $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => 'Nama Valid',
            'bio' => 'Bio.',
            'profile_photo' => $file,
        ])->assertOk();

        $user->refresh();
        $this->assertNotNull($user->profile_photo);
        $this->assertStringEndsWith('.webp', $user->profile_photo);

        $stored = Storage::disk('public')->get($user->profile_photo);
        $this->assertStringNotContainsString('<?php', $stored);
        $this->assertStringNotContainsString('pwned', $stored);
    }

    public function test_profile_batas_tepat_4mb_diterima(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'siswa']);

        // JPEG valid di-pad hingga tepat 4096 KB — batas max:4096 bersifat inklusif.
        $file = $this->makePaddedJpeg(4096 * 1024);

        $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => 'Nama Valid',
            'bio' => 'Bio.',
            'profile_photo' => $file,
        ])->assertOk();

        $this->assertNotNull($user->fresh()->profile_photo);
    }

    // ── (2) XSS ──────────────────────────────────────────────────────

    public function test_profile_xss_dibersihkan_dari_db_dan_respons_json(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'siswa']);

        $response = $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => '<script>alert("xss")</script>Nama Bersih<img src=x onerror=alert(1)>',
            'bio' => '<svg onload=alert(1)>Bio <a href="javascript:alert(1)">bersih</a></svg>',
        ]);

        $response->assertOk()->assertHeader('Content-Type', 'application/json');
        $this->assertStringNotContainsString('<script>', $response->getContent());
        $this->assertStringNotContainsString('onerror', $response->getContent());
        $this->assertStringNotContainsString('javascript:', $response->getContent());

        $user->refresh();
        $this->assertStringNotContainsString('<', $user->name);
        $this->assertStringNotContainsString('<', $user->bio);
        $this->assertStringContainsString('Nama Bersih', $user->name);
        $this->assertStringContainsString('bersih', $user->bio);
    }

    // ── (4) Path traversal ───────────────────────────────────────────

    public function test_profile_remove_photo_dengan_nilai_db_traversal_tidak_menghapus_di_luar_folder(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('secret.txt', 'vital-data');

        // Simulasikan nilai profile_photo beracun yang sudah ada di DB.
        $user = User::factory()->create([
            'role' => 'siswa',
            'profile_photo' => 'profile/../../secret.txt',
        ]);

        $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => $user->name,
            'bio' => '',
            'remove_photo' => true,
        ])->assertOk();

        $this->assertNull($user->fresh()->profile_photo);
        Storage::disk('public')->assertExists('secret.txt');
    }

    public function test_profile_upload_baru_dengan_foto_lama_traversal_tetap_aman(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('secret.txt', 'vital-data');

        $user = User::factory()->create([
            'role' => 'siswa',
            'profile_photo' => '../../../secret.txt',
        ]);

        $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => 'Nama Baru',
            'bio' => 'Bio.',
            'profile_photo' => $this->makeJpegFile(),
        ])->assertOk();

        Storage::disk('public')->assertExists('secret.txt');
        $this->assertStringStartsWith('profile/', $user->fresh()->profile_photo);
    }

    public function test_profile_nested_traversal_tidak_bisa_menghapus_file_di_luar_folder(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('secret.txt', 'vital-data');

        // Vektor traversal bersarang: satu pass str_replace menyisakan "../"
        // yang valid, sehingga prefix check terkecoh tapi path lolos ke disk.
        $user = User::factory()->create([
            'role' => 'siswa',
            'profile_photo' => 'profile/....//secret.txt',
        ]);

        $this->actingAs($user)->putJson(route('dashboard.profile.update'), [
            'name' => $user->name,
            'bio' => '',
            'remove_photo' => true,
        ])->assertOk();

        $this->assertNull($user->fresh()->profile_photo);
        Storage::disk('public')->assertExists('secret.txt');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function makeJpegFile(): UploadedFile
    {
        $img = imagecreatetruecolor(40, 40);
        imagefill($img, 0, 0, imagecolorallocate($img, 220, 220, 220));
        $path = sys_get_temp_dir().'/jpeg-test-'.bin2hex(random_bytes(6)).'.jpg';
        imagejpeg($img, $path, 80);
        imagedestroy($img);

        return new UploadedFile($path, 'avatar.jpg', 'image/jpeg', null, true);
    }

    private function makePaddedJpeg(int $targetBytes): UploadedFile
    {
        $img = imagecreatetruecolor(40, 40);
        imagefill($img, 0, 0, imagecolorallocate($img, 220, 220, 220));
        $path = sys_get_temp_dir().'/pad-test-'.bin2hex(random_bytes(6)).'.jpg';
        imagejpeg($img, $path, 80);
        imagedestroy($img);

        // Pad dengan byte NUL setelah EOI — JPEG tetap valid & terbaca GD.
        $pad = $targetBytes - filesize($path);
        if ($pad > 0) {
            file_put_contents($path, str_repeat("\0", $pad), FILE_APPEND);
        }
        clearstatcache(true, $path);

        return new UploadedFile($path, 'avatar.jpg', 'image/jpeg', null, true);
    }
}
