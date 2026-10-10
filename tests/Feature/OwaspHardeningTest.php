<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Support\ImageStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwaspHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api_login|127.0.0.1|hacker@evil.com');
        RateLimiter::clear('register|127.0.0.1');
    }

    public function test_owasp_a03_stored_xss_stripped_pada_konten_dan_judul_artikel(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $cat = Category::create(['name' => 'Umum '.uniqid(), 'slug' => 'umum-'.uniqid()]);

        $xssTitle = '<script>alert("hack")</script>Prestasi Robotik';
        $xssContent = '<p onmouseover="alert(1)">Paragraf aman.</p><script>alert("xss")</script><img src=x onerror="alert(document.cookie)"><strong>Tebal</strong><a href="javascript:alert(1)">Klik bahaya</a>';

        $this->actingAs($siswa)->post(route('dashboard.posts.store'), [
            'title' => $xssTitle,
            'category_id' => $cat->id,
            'content' => $xssContent,
        ]);

        $post = Post::where('category_id', $cat->id)->first();
        $this->assertNotNull($post);

        // Judul harus bersih dari tag script
        $this->assertSame('Prestasi Robotik', $post->title);
        $this->assertStringNotContainsString('<script>', $post->title);

        // Konten harus bersih dari tag script, event handler onerror/onmouseover, dan pseudo protocol javascript:
        $this->assertStringNotContainsString('<script>', $post->content);
        $this->assertStringNotContainsString('onerror', $post->content);
        $this->assertStringNotContainsString('onmouseover', $post->content);
        $this->assertStringNotContainsString('javascript:', $post->content);

        // Format yang sah tetap dipertahankan
        $this->assertStringContainsString('Paragraf aman.', $post->content);
        $this->assertStringContainsString('<strong>Tebal</strong>', $post->content);
    }

    public function test_owasp_a05_http_security_headers_diterapkan_pada_setiap_respons(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $this->assertTrue($response->headers->has('Content-Security-Policy'));
    }

    public function test_owasp_a05_security_headers_pada_endpoint_api(): void
    {
        $response = $this->getJson(route('api.posts.index'));

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertTrue($response->headers->has('Content-Security-Policy'));
    }

    public function test_owasp_a04_rate_limiting_pada_brute_force_login_web(): void
    {
        User::factory()->create([
            'email' => 'korban@sekolah.sch.id',
            'password' => 'password123',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.attempt'), [
                'email' => 'korban@sekolah.sch.id',
                'password' => 'passwordsalah',
            ]);
        }

        $response = $this->post(route('login.attempt'), [
            'email' => 'korban@sekolah.sch.id',
            'password' => 'passwordsalah',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Terlalu banyak percobaan masuk', session('errors')->first('email'));
    }

    public function test_owasp_a04_rate_limiting_pada_brute_force_login_api(): void
    {
        User::factory()->create([
            'email' => 'api.target@sekolah.sch.id',
            'password' => 'password123',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('api.login'), [
                'email' => 'api.target@sekolah.sch.id',
                'password' => 'passwordsalah',
            ]);
        }

        $response = $this->postJson(route('api.login'), [
            'email' => 'api.target@sekolah.sch.id',
            'password' => 'passwordsalah',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('status', 'error');
    }

    public function test_owasp_a01_broken_access_control_siswa_tidak_bisa_edit_post_siswa_lain(): void
    {
        $siswaA = User::factory()->create(['role' => 'siswa']);
        $siswaB = User::factory()->create(['role' => 'siswa']);
        $cat = Category::create(['name' => 'Umum '.uniqid(), 'slug' => 'umum-'.uniqid()]);

        $postA = Post::create([
            'user_id' => $siswaA->id,
            'category_id' => $cat->id,
            'title' => 'Milik A',
            'content' => '<p>Konten A</p>',
            'status' => 'pending',
        ]);

        $this->actingAs($siswaB)->put(route('dashboard.posts.update', $postA), [
            'title' => 'Judul Dibajak',
            'category_id' => $cat->id,
            'content' => '<p>Konten Bajakan</p>',
        ])->assertForbidden();

        $this->assertSame('Milik A', $postA->fresh()->title);
    }

    public function test_owasp_a01_path_traversal_pada_penghapusan_berkas_ditolak(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('secret.txt', 'vital-data');

        // Percobaan path traversal berbahaya
        ImageStore::delete('../../../secret.txt');
        ImageStore::delete('..\\..\\secret.txt');
        ImageStore::delete('secret.txt'); // di luar cover/ atau profile/

        // Berkas di luar folder upload sah harus tetap utuh
        Storage::disk('public')->assertExists('secret.txt');

        // Berkas sah di dalam cover/ dapat dihapus normal
        Storage::disk('public')->put('cover/valid.webp', 'image-content');
        ImageStore::delete('cover/valid.webp');
        Storage::disk('public')->assertMissing('cover/valid.webp');
    }

    public function test_owasp_a04_rate_limiting_pada_registrasi_mencegah_bot_flooding(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('register.attempt'), [
                'name' => "User {$i}",
                'email' => "user{$i}@sekolah.sch.id",
                'role' => 'siswa',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);
            $this->flushSession();
            auth()->logout();
        }

        // Percobaan ke-6 harus ditahan
        $response = $this->post(route('register.attempt'), [
            'name' => 'User Bot',
            'email' => 'bot@sekolah.sch.id',
            'role' => 'siswa',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Terlalu banyak percobaan pendaftaran', session('errors')->first('email'));
    }

    public function test_iso27001_a820_strict_cors_configuration_aktif(): void
    {
        $this->assertTrue(file_exists(config_path('cors.php')));
        $this->assertSame(['api/*', 'sanctum/csrf-cookie'], config('cors.paths'));
        $this->assertTrue(config('cors.supports_credentials'));
    }

    public function test_owasp_a03_sql_injection_auth_bypass_pada_login_web_gagal(): void
    {
        User::factory()->create([
            'email' => 'admin@sekolah.sch.id',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        // Berbagai payload SQL Injection klasik untuk bypass otentikasi
        $payloads = [
            "' OR '1'='1",
            "' OR 1=1 --",
            "admin@sekolah.sch.id' --",
            "admin@sekolah.sch.id' /*",
            "' UNION SELECT 'admin@sekolah.sch.id', 'password' --",
        ];

        foreach ($payloads as $payload) {
            $response = $this->post(route('login.attempt'), [
                'email' => $payload,
                'password' => "' OR '1'='1",
            ]);

            // Harus gagal login dan tidak boleh bypass
            $this->assertGuest();
        }
    }

    public function test_owasp_a03_sql_injection_pada_parameter_slug_dan_filter_api(): void
    {
        $maliciousSlugs = [
            "' OR 1=1 --",
            "' UNION SELECT id, name, email FROM users --",
            "1'; DROP TABLE posts; --",
        ];

        foreach ($maliciousSlugs as $slug) {
            // Pengujian pada route publik berita, kategori, dan tag
            $this->get(route('news.show', $slug))->assertNotFound();
            $this->get(route('category', $slug))->assertNotFound();
            $this->get(route('tag', $slug))->assertNotFound();

            // Pengujian pada route API
            $this->getJson(route('api.posts.show', $slug))->assertNotFound();
            $this->getJson(route('api.posts.index', ['category' => $slug]))->assertOk();
        }

        // Pastikan tabel posts dan users tetap utuh dan tidak terhapus
        $this->assertTrue(Schema::hasTable('posts'));
        $this->assertTrue(Schema::hasTable('users'));
    }

    public function test_owasp_a03_sql_injection_pada_penyimpanan_data_diperlakukan_sebagai_string_literal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cat = Category::create(['name' => 'Umum '.uniqid(), 'slug' => 'umum-'.uniqid()]);

        // Input berisi sintaks SQL berbahaya
        $sqlPayload = "Judul'; DROP TABLE categories; --";

        $this->actingAs($admin)->post(route('dashboard.posts.store'), [
            'title' => $sqlPayload,
            'category_id' => $cat->id,
            'content' => '<p>Konten aman</p>',
        ]);

        // Tabel categories harus tetap ada dan tidak ter-DROP
        $this->assertTrue(Schema::hasTable('categories'));

        // Payload tersimpan aman sebagai string biasa (parameterized via PDO)
        $post = Post::where('category_id', $cat->id)->first();
        $this->assertNotNull($post);
        $this->assertSame($sqlPayload, $post->title);
    }
}
