<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JwtApiTest extends TestCase
{
    use RefreshDatabase;

    protected JwtService $jwtService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jwtService = app(JwtService::class);
    }

    public function test_login_api_dengan_kredensial_valid_mengembalikan_token_jwt_short_lived_dan_refresh_token(): void
    {
        $user = User::factory()->create([
            'email' => 'siswa.api@sekolah.sch.id',
            'password' => 'password123',
            'role' => 'siswa',
        ]);

        $response = $this->postJson(route('api.login'), [
            'email' => 'siswa.api@sekolah.sch.id',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.expires_in', 15 * 60) // Short-lived 15 minutes
            ->assertJsonPath('data.user.email', 'siswa.api@sekolah.sch.id')
            ->assertJsonPath('data.user.role', 'siswa');

        $accessToken = $response->json('data.access_token');
        $this->assertNotEmpty($accessToken);
        $this->assertCount(3, explode('.', $accessToken));

        $refreshToken = $response->json('data.refresh_token');
        $this->assertNotEmpty($refreshToken);

        $userId = $response->json('data.user.id');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $userId,
            'User ID pada API harus berupa format UUID v4 valid'
        );

        // Verifikasi token hash tersimpan di database (Zero raw token in DB)
        $this->assertDatabaseHas('jwt_refresh_tokens', [
            'user_id' => $userId,
            'token_hash' => hash('sha256', $refreshToken),
            'revoked_at' => null,
        ]);
    }

    public function test_decoder_eksternal_tidak_bisa_membaca_klaim_sensitif_karena_terenkripsi(): void
    {
        $user = User::factory()->create([
            'name' => 'Nama Rahasia',
            'email' => 'rahasia@sekolah.sch.id',
            'role' => 'admin',
        ]);

        $token = $this->jwtService->generateToken($user);
        $parts = explode('.', $token);

        // Mensimulasikan pihak luar yang mendecode base64 payload via jwt.io / decoder
        $wirePayload = json_decode($this->jwtService->base64UrlDecode($parts[1]), true);

        // Decoder umum TIDAK BISA melihat user id, role, email, atau nama
        $this->assertArrayNotHasKey('sub', $wirePayload, 'Payload kawat tidak boleh membocorkan sub / user UUID');
        $this->assertArrayNotHasKey('role', $wirePayload, 'Payload kawat tidak boleh membocorkan role');
        $this->assertArrayNotHasKey('email', $wirePayload, 'Payload kawat tidak boleh membocorkan email');
        $this->assertArrayNotHasKey('name', $wirePayload, 'Payload kawat tidak boleh membocorkan nama');

        // Seluruh data terbungkus dalam ciphertext enkripsi opaque
        $this->assertArrayHasKey('enc', $wirePayload);
        $this->assertNotEmpty($wirePayload['enc']);

        // Tetapi server resmi dengan kunci rahasia dapat mendekripsi klaim dengan sempurna
        $verifiedClaims = $this->jwtService->verifyToken($token);
        $this->assertSame((string) $user->id, $verifiedClaims['sub']);
        $this->assertSame('admin', $verifiedClaims['role']);
        $this->assertSame('rahasia@sekolah.sch.id', $verifiedClaims['email']);
    }

    public function test_rotasi_refresh_token_menghasilkan_access_token_dan_refresh_token_baru(): void
    {
        $user = User::factory()->create(['role' => 'siswa']);
        $loginRes = $this->postJson(route('api.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $oldRefreshToken = $loginRes->json('data.refresh_token');

        // Minta rotasi token baru
        $refreshRes = $this->postJson(route('api.refresh'), [
            'refresh_token' => $oldRefreshToken,
        ]);

        $refreshRes->assertOk()
            ->assertJsonPath('status', 'success');

        $newAccessToken = $refreshRes->json('data.access_token');
        $newRefreshToken = $refreshRes->json('data.refresh_token');

        $this->assertNotEmpty($newAccessToken);
        $this->assertNotEmpty($newRefreshToken);
        $this->assertNotSame($oldRefreshToken, $newRefreshToken, 'Refresh token wajib dirotasi menjadi baru');

        // Verifikasi token lama sudah di-revoke (single-use)
        $this->assertDatabaseHas('jwt_refresh_tokens', [
            'token_hash' => hash('sha256', $oldRefreshToken),
            'revoked_at' => now(),
        ]);

        // Verifikasi token baru aktif
        $this->assertDatabaseHas('jwt_refresh_tokens', [
            'token_hash' => hash('sha256', $newRefreshToken),
            'revoked_at' => null,
        ]);
    }

    public function test_deteksi_replay_attack_mencabut_semua_sesi_user_bila_refresh_token_lama_digunakan_kembali(): void
    {
        $user = User::factory()->create();
        $loginRes = $this->postJson(route('api.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $firstRefreshToken = $loginRes->json('data.refresh_token');

        // Putaran 1: Rotasi pertama sah
        $rotate1 = $this->postJson(route('api.refresh'), ['refresh_token' => $firstRefreshToken]);
        $rotate1->assertOk();
        $secondRefreshToken = $rotate1->json('data.refresh_token');

        // Putaran 2 (Simulasi Pencurian Token): Penyerang mencoba memakai token pertama yang sudah hangus
        $attackRes = $this->postJson(route('api.refresh'), ['refresh_token' => $firstRefreshToken]);
        $attackRes->assertStatus(401);

        // Pertahanan Proaktif: Seluruh refresh token aktif user tersebut dicabut demi keamanan
        $this->assertDatabaseMissing('jwt_refresh_tokens', [
            'user_id' => $user->id,
            'revoked_at' => null,
        ]);

        // Token kedua yang sah sebelumnya juga otomatis dicabut akibat deteksi pencurian token
        $rotate2 = $this->postJson(route('api.refresh'), ['refresh_token' => $secondRefreshToken]);
        $rotate2->assertStatus(401);
    }

    public function test_login_api_dengan_password_salah_mengembalikan_401(): void
    {
        User::factory()->create([
            'email' => 'siswa.salah@sekolah.sch.id',
            'password' => 'password123',
        ]);

        $response = $this->postJson(route('api.login'), [
            'email' => 'siswa.salah@sekolah.sch.id',
            'password' => 'passwordsalah',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('status', 'error');
    }

    public function test_login_api_akun_nonaktif_mengembalikan_403(): void
    {
        User::factory()->create([
            'email' => 'nonaktif@sekolah.sch.id',
            'password' => 'password123',
            'is_active' => false,
        ]);

        $response = $this->postJson(route('api.login'), [
            'email' => 'nonaktif@sekolah.sch.id',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('status', 'error');
    }

    public function test_akses_me_dengan_jwt_valid_mengembalikan_profil_user(): void
    {
        $user = User::factory()->create(['role' => 'siswa']);
        $token = $this->jwtService->generateToken($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.me'));

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', (string) $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_akses_me_tanpa_token_mengembalikan_401(): void
    {
        $response = $this->getJson(route('api.me'));

        $response->assertStatus(401)
            ->assertJsonPath('status', 'error');
    }

    public function test_akses_me_dengan_token_dimanipulasi_mengembalikan_401(): void
    {
        $user = User::factory()->create();
        $token = $this->jwtService->generateToken($user);

        // Ubah satu karakter di signature
        $parts = explode('.', $token);
        $tamperedSignature = substr($parts[2], 0, -4).'fake';
        $tamperedToken = $parts[0].'.'.$parts[1].'.'.$tamperedSignature;

        $response = $this->withHeader('Authorization', 'Bearer '.$tamperedToken)
            ->getJson(route('api.me'));

        $response->assertStatus(401);
    }

    public function test_akses_me_dengan_token_kedaluwarsa_mengembalikan_401(): void
    {
        $user = User::factory()->create();
        // Token kadaluwarsa (TTL negatif)
        $expiredToken = $this->jwtService->generateToken($user, [], -10);

        $response = $this->withHeader('Authorization', 'Bearer '.$expiredToken)
            ->getJson(route('api.me'));

        $response->assertStatus(401);
    }

    public function test_siswa_bisa_submit_draf_post_via_api_dan_tersimpan_dengan_uuid(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $cat = Category::create(['name' => 'Kategori API '.uniqid(), 'slug' => 'kategori-api-'.uniqid()]);
        $token = $this->jwtService->generateToken($siswa);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson(route('api.posts.store'), [
                'title' => 'Judul Berita dari API',
                'category_id' => $cat->id,
                'content' => '<p>Konten artikel melalui mobile API client.</p>',
                'tags' => 'api, mobile, siswa',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'pending');

        $postId = $response->json('data.id');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $postId
        );

        $this->assertDatabaseHas('posts', [
            'id' => $postId,
            'title' => 'Judul Berita dari API',
            'status' => 'pending',
            'user_id' => $siswa->id,
        ]);
    }

    public function test_logout_api_mencabut_refresh_token(): void
    {
        $user = User::factory()->create();
        $loginRes = $this->postJson(route('api.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $accessToken = $loginRes->json('data.access_token');
        $refreshToken = $loginRes->json('data.refresh_token');

        $logoutRes = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson(route('api.logout'), [
                'refresh_token' => $refreshToken,
            ]);

        $logoutRes->assertOk();

        // Refresh token lama harus berstatus dicabut
        $this->assertDatabaseHas('jwt_refresh_tokens', [
            'token_hash' => hash('sha256', $refreshToken),
            'revoked_at' => now(),
        ]);
    }
}
