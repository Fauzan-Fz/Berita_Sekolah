<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Sekretariat Sekolah',
            'email' => 'admin@sekolah.sch.id',
            'password' => 'password',
            'role' => 'admin',
            'bio' => 'Admin resmi portal berita sekolah.',
        ]);

        $guru = User::create([
            'name' => 'Bapak Ahmad Fauzi, S.Pd.',
            'email' => 'guru@sekolah.sch.id',
            'password' => 'password',
            'role' => 'guru',
            'bio' => 'Guru Bahasa Indonesia & moderator konten siswa.',
        ]);

        $siswas = collect([
            ['name' => 'Rina Kartika', 'email' => 'rina@sekolah.sch.id', 'bio' => 'Siswi kelas XI IPA 1, anggota jurnalistik sekolah.', 'password' => 'password'],
            ['name' => 'Bagas Pratama', 'email' => 'bagas@sekolah.sch.id', 'bio' => 'Siswa kelas X IPS 2, suka fotografi dan menulis.', 'password' => 'password'],
            ['name' => 'Dewi Lestari', 'email' => 'dewi@sekolah.sch.id', 'bio' => 'Siswi kelas XII IPA 2, ketua ekstrakurikuler pramuka.', 'password' => 'password'],
        ])->map(fn ($u) => User::create($u));

        // ── Kategori Resmi ─────────────────────────────────────────────
        $categories = collect([
            ['name' => 'Pengumuman', 'slug' => 'pengumuman'],
            ['name' => 'Prestasi', 'slug' => 'prestasi'],
            ['name' => 'Ekstrakurikuler', 'slug' => 'ekstrakurikuler'],
            ['name' => 'Kegiatan Sekolah', 'slug' => 'kegiatan-sekolah'],
            ['name' => 'Karya Siswa', 'slug' => 'karya-siswa'],
        ])->map(fn ($c) => Category::create($c));

        // ── Hashtag Bebas ─────────────────────────────────────────────
        $tags = collect(['sekolah', 'lomba', 'olahraga', 'seni', 'teknologi', 'pramuka', 'ekskul', 'juara', 'futsal', 'robotik'])
            ->map(fn ($t) => Tag::create(['name' => $t, 'slug' => $t]));

        // ── Konten Berita ─────────────────────────────────────────────
        $now = now();
        $posts = [
            [
                'author' => $admin, 'category' => $categories[0], 'status' => 'approved',
                'title' => 'Pengumuman Penerimaan Peserta Didik Baru Tahun Ajaran 2025/2026',
                'content' => self::content('penerimaan', 'Admin'),
                'cover' => 'cover/ppdb.jpg',
                'tags' => ['sekolah'], 'views' => 1450, 'published' => now()->subHours(3),
            ],
            [
                'author' => $admin, 'category' => $categories[1], 'status' => 'approved',
                'title' => 'Tim Robotik SMAN 1 Meraih Juara 1 Kompetisi Robotik Nasional',
                'content' => self::content('robotik', 'Tim Robotik'),
                'cover' => 'cover/robotik.jpg',
                'tags' => ['robotik', 'juara', 'teknologi'], 'views' => 2310, 'published' => now()->subDay(),
            ],
            [
                'author' => $guru, 'category' => $categories[2], 'status' => 'approved',
                'title' => 'Festival Seni dan Budaya: Panggung Kreativitas Siswa',
                'content' => self::content('festival', 'Panitia FSB'),
                'cover' => 'cover/festival-seni.jpg',
                'tags' => ['seni', 'ekskul'], 'views' => 980, 'published' => now()->subDays(2),
            ],
            [
                'author' => $siswas[1], 'category' => $categories[2], 'status' => 'approved',
                'title' => 'Latihan Rutin Futsal: Menuju Turnamen Antarsekolah',
                'content' => self::content('futsal', 'Bagas'),
                'cover' => 'cover/futsal.jpg',
                'tags' => ['futsal', 'olahraga', 'ekskul'], 'views' => 620, 'published' => now()->subDays(3),
            ],
            [
                'author' => $siswas[0], 'category' => $categories[4], 'status' => 'approved',
                'title' => 'Cerpen: Senja di Perpustakaan Sekolah',
                'content' => self::content('cerpen', 'Rina'),
                'cover' => 'cover/perpustakaan.jpg',
                'tags' => ['seni', 'sekolah'], 'views' => 410, 'published' => now()->subDays(4),
            ],
            [
                'author' => $admin, 'category' => $categories[3], 'status' => 'approved',
                'title' => 'Kegiatan Bakti Sosial dan Donor Darah Bersama PMI',
                'content' => self::content('baksos', 'Admin'),
                'cover' => 'cover/donor-darah.jpg',
                'tags' => ['sekolah', 'ekskul'], 'views' => 1540, 'published' => now()->subDays(5),
            ],
            [
                'author' => $siswas[2], 'category' => $categories[2], 'status' => 'approved',
                'title' => 'Jambore Pramuka: Pengalaman Seru di Bumi Perkemahan',
                'content' => self::content('pramuka', 'Dewi'),
                'cover' => 'cover/pramuka.jpg',
                'tags' => ['pramuka', 'ekskul'], 'views' => 870, 'published' => now()->subDays(6),
            ],
            [
                'author' => $admin, 'category' => $categories[1], 'status' => 'approved',
                'title' => 'Siswa Berprestasi: Raih Medali Emas Olimpiade Sains',
                'content' => self::content('olimpiade', 'Admin'),
                'cover' => 'cover/prestasi.jpg',
                'tags' => ['juara', 'lomba'], 'views' => 3200, 'published' => now()->subDays(7),
            ],
            [
                'author' => $siswas[0], 'category' => $categories[4], 'status' => 'pending',
                'title' => 'Puisi: Kelas Kita, Rumah Kedua',
                'content' => self::content('puisi', 'Rina'),
                'cover' => 'cover/kelas-menulis.jpg',
                'tags' => ['seni'], 'views' => 0, 'published' => null,
            ],
            [
                'author' => $siswas[1], 'category' => $categories[4], 'status' => 'rejected',
                'title' => 'Kisah Pribadi: Perjalanan Panjang Menuju Sekolah',
                'content' => self::content('kisah', 'Bagas'),
                'tags' => ['sekolah'], 'views' => 0, 'published' => null,
                'reason' => 'Tulisan belum sesuai topik berita sekolah. Mohon revisi dan fokus pada informasi yang relevan untuk warga sekolah.',
            ],
            [
                'author' => $siswas[2], 'category' => $categories[4], 'status' => 'pending',
                'title' => 'Review Film: Pesan Moral dari Film Edukasi',
                'content' => self::content('review', 'Dewi'),
                'tags' => ['seni', 'teknologi'], 'views' => 0, 'published' => null,
            ],
        ];

        foreach ($posts as $i => $p) {
            $post = Post::create([
                'user_id' => $p['author']->id,
                'category_id' => $p['category']->id,
                'title' => $p['title'],
                'slug' => Str::slug($p['title']).'-'.Str::random(5),
                'content' => $p['content'],
                'status' => $p['status'],
                'cover_image' => $p['cover'] ?? null,
                'rejection_reason' => $p['reason'] ?? null,
                'views_count' => $p['views'],
                'published_at' => $p['published'],
                'created_at' => $p['published'] ?? $now->subHours($i + 1),
                'updated_at' => $p['published'] ?? $now->subHours($i + 1),
            ]);
            $post->tags()->attach(
                $tags->whereIn('slug', $p['tags'])->pluck('id')
            );
        }
    }

    private static function content(string $key, string $author): string
    {
        $bodies = [
            'penerimaan' => [
                '<p>Dengan hormat, kami sampaikan kepada seluruh wali murid dan calon peserta didik bahwa <strong>Penerimaan Peserta Didik Baru (PPDB)</strong> Tahun Ajaran 2025/2026 akan segera dibuka.</p>',
                '<p>Pendaftaran dilaksanakan secara <em>online</em> melalui situs resmi sekolah. Berikut adalah jadwal lengkap kegiatan:</p>',
                '<h2>Jadwal PPDB</h2>',
                '<ul><li><strong>1–15 Februari 2025:</strong> Pembukaan pendaftaran online</li><li><strong>16–20 Februari 2025:</strong> Verifikasi berkas</li><li><strong>22 Februari 2025:</strong> Pengumuman hasil seleksi</li><li><strong>1 Maret 2025:</strong> Daftar ulang peserta didik baru</li></ul>',
                '<p>Pastikan seluruh berkas seperti <em>akte kelahiran</em>, <em>kartu keluarga</em>, dan <em>rapor semester terakhir</em> telah disiapkan dalam bentuk digital.</p>',
                '<blockquote>Untuk informasi lebih lanjut, silakan hubungi sekretariat sekolah pada jam kerja.</blockquote>',
            ],
            'robotik' => [
                '<p>Kabar membanggakan datang dari <strong>Tim Robotik SMAN 1</strong> yang berhasil meraih <strong>Juara 1</strong> pada Kompetisi Robotik Nasional yang diselenggarakan di Jakarta.</p>',
                '<p>Tim yang beranggotakan lima siswa ini bersaing dengan lebih dari seratus tim dari seluruh Indonesia. Robot yang diberi nama <em>"Si Cerdas"</em> berhasil menyelesaikan misi dengan waktu tercepat dan akurasi tertinggi.</p>',
                '<h2>Pencapaian Membanggakan</h2>',
                '<p>Ini merupakan kemenangan kedua kalinya bagi sekolah kami di ajang ini. Pembina tim menyampaikan bahwa keberhasilan ini adalah buah dari latihan rutin selama enam bulan terakhir.</p>',
                '<p>Kepala sekolah mengucapkan selamat dan mengapresiasi kerja keras seluruh anggota tim serta pembimbing.</p>',
                '<blockquote>"Kami bangga dengan prestasi anak-anak. Semoga ini menjadi inspirasi bagi siswa lain untuk berkarya di bidang teknologi."</blockquote>',
            ],
            'festival' => [
                '<p>Suasana meriah menyelimuti lapangan sekolah pada gelaran <strong>Festival Seni dan Budaya</strong> yang menampilkan kreativitas siswa dari berbagai ekstrakurikuler.</p>',
                '<p>Berbagai penampilan memukau para penonton, mulai dari tari tradisional, paduan suara, hingga drama musikal yang digarap oleh siswa kelas XI.</p>',
                '<h2>Panggung Kreativitas</h2>',
                '<p>Acara yang digelar selama dua hari ini juga menampilkan pameran karya seni rupa dan fotografi hasil karya siswa.</p>',
                '<p>Ketua panitia menyampaikan bahwa festival ini bertujuan menumbuhkan rasa percaya diri dan kecintaan siswa terhadap seni dan budaya lokal.</p>',
            ],
            'futsal' => [
                '<p>Tim futsal sekolah kembali menggelar latihan rutin dalam rangka persiapan menghadapi <strong>Turnamen Futsal Antarsekolah</strong> yang akan digelar bulan depan.</p>',
                '<p>Latihan dilaksanakan setiap hari Selasa dan Kamis sore di lapangan sekolah, dipimpin oleh pelatih sekaligus alumni sekolah kami.</p>',
                '<h2>Target Turnamen</h2>',
                '<p>Manajer tim menargetkan posisi tiga besar pada turnamen tahun ini. Semangat latihan para pemain sangat tinggi dan kompak.</p>',
                '<p>Bagi siswa yang berminat bergabung, pendaftaran masih dibuka hingga akhir bulan ini.</p>',
            ],
            'cerpen' => [
                '<p>Senja merambat perlahan di sudut perpustakaan sekolah. Di balik tumpukan buku, seorang siswi menemukan dunia yang selama ini hanya ia impikan.</p>',
                '<p>Karya cerita pendek ini ditulis oleh <strong>Rina Kartika</strong>, siswi kelas XI, sebagai bagian dari proyek mata pelajaran Bahasa Indonesia.</p>',
                '<h2>Karya Siswa</h2>',
                '<p>Cerpen ini menggambarkan indahnya kebersamaan dan semangat belajar di sekolah, serta menghadirkan pesan bahwa setiap buku menyimpan seribu cerita.</p>',
                '<p>Selamat membaca, dan jangan lupa terus berkarya!</p>',
            ],
            'baksos' => [
                '<p>Kegiatan <strong>Bakti Sosial dan Donor Darah</strong> yang diselenggarakan bekerjasama dengan PMI Cabang setempat berjalan lancar dan meriah.</p>',
                '<p>Acara yang digelar di aula sekolah ini dihadiri oleh siswa, guru, dan warga sekitar. Sebanyak 85 kantong darah berhasil terkumpul dan disalurkan ke PMI.</p>',
                '<h2>Solidaritas Warga Sekolah</h2>',
                '<p>Selain donor darah, kegiatan ini juga diisi dengan santunan untuk anak yatim serta pembagian sembako kepada warga yang membutuhkan.</p>',
                '<p>Kepala sekolah berharap kegiatan sosial semacam ini dapat menjadi agenda rutin tahunan sekolah.</p>',
            ],
            'pramuka' => [
                '<p>Anggota Pramuka sekolah kembali merasakan pengalaman seru melalui <strong>Jambore Pramuka</strong> yang dilaksanakan di Bumi Perkemahan Cibubur.</p>',
                '<p>Kegiatan selama empat hari ini diisi dengan berbagai permainan keterampilan, penjelajahan, hingga lomba ketangkasan antarregu.</p>',
                '<h2>Pengalaman Berharga</h2>',
                '<p>Para anggota mengaku mendapatkan banyak pelajaran berharga tentang kemandirian, kebersamaan, dan cinta alam.</p>',
                '<p>"Ini pengalaman paling berkesan selama masa sekolah," ujar salah satu peserta.</p>',
            ],
            'olimpiade' => [
                '<p>Siswa sekolah kami kembali menorehkan prestasi gemilang dengan meraih <strong>Medali Emas</strong> pada Olimpiade Sains tingkat provinsi.</p>',
                '<p>Prestasi ini diraih oleh salah satu siswa kelas XII yang menguasai bidang biologi dengan sangat baik.</p>',
                '<h2>Raihan Membanggakan</h2>',
                '<p>Keberhasilan ini merupakan hasil dari persiapan intensif bersama guru pembimbing selama hampir satu tahun.</p>',
                '<p>Semoga prestasi ini dapat menginspirasi siswa lain untuk terus berprestasi dan mengharumkan nama sekolah.</p>',
            ],
            'puisi' => [
                '<p>Sebuah puisi berjudul <em>Kelas Kita, Rumah Kedua</em> karya Rina Kartika, siswi kelas XI.</p>',
                '<p><em>Di sela tawa dan coretan papan tulis,<br>kita belajar arti sebuah usaha,<br>kelas kita, rumah kedua,<br>tempat mimpi mulai terukir.</em></p>',
                '<p>Puisi ini sedang dalam proses kurasi dan akan segera diterbitkan setelah disetujui oleh guru moderator.</p>',
            ],
            'kisah' => [
                '<p>Setiap pagi, bagas menempuh perjalanan panjang menuju sekolah demi mengejar cita-citanya.</p>',
                '<p>Catatan pribadi ini menceritakan perjuangan seorang siswa yang tidak pernah menyerah pada keadaan.</p>',
                '<p>Tulisan ini telah ditinjau dan sedang menunggu proses kurasi oleh guru.</p>',
            ],
            'review' => [
                '<p>Film edukasi yang baru saja tayang menyimpan banyak pesan moral yang layak untuk dijadikan bahan renungan bersama.</p>',
                '<p>Dalam ulasan singkat ini, kami mengajak warga sekolah untuk menyaksikan film tersebut dan memetik hikmah di balik ceritanya.</p>',
                '<p>Review ini sedang dalam proses kurasi oleh guru moderator.</p>',
            ],
        ];

        $intro = '<p>'.$author.' — Salah satu berita terbaru dari lingkungan sekolah kami. Simak informasi selengkapnya di bawah ini.</p>';

        return $intro.implode("\n\n", $bodies[$key] ?? $bodies['penerimaan']);
    }
}
