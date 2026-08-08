# Company Profile CMS – PT. Reka Mitra Teknologi

Repository ini dikembangkan berdasarkan [Rewire Starter Kit](https://github.com/Recodex-ID/rewire). Starter kit sudah menyediakan fondasi aplikasi seperti autentikasi, role & permission, layout admin, serta struktur Livewire.

Pada tugas ini (branch `assignment-rani`) saya mengembangkan starter kit tersebut menjadi CMS Company Profile, mulai dari pengelolaan konten (pages, services, gallery, blog), pengaturan perusahaan, halaman publik yang terhubung ke database, hingga perbaikan berdasarkan hasil code review dari CTO.

## Fitur yang sudah tersedia dari Starter Kit

- Authentication (Login, Register, Reset Password) — Fortify
- Role & Permission (Spatie Permission)
- Dashboard Admin
- Layout Admin menggunakan Flux UI
- Blog CMS dasar
- Activity Log (Spatie Activitylog)
- Sitemap generator & halaman admin sitemap (Spatie Sitemap)
- Testing dengan Pest

## Pengembangan yang saya lakukan

### 1. Content Management

Modul CMS baru sehingga konten website dapat dikelola tanpa hardcode.

**Pages**

- Edit konten halaman built-in (mis. About) — single-instance by design, terhubung ke landing page via slug
- Publish / Draft
- SEO slug otomatis (tidak berubah saat title diedit ulang)
- Rich text content, featured image

**Services**

- CRUD layanan
- Ordering (dengan index database)
- Status aktif/nonaktif
- Ditampilkan di landing page

**Gallery**

- CRUD gallery
- Upload gambar & caption
- Ordering
- Ditampilkan pada landing page (6 item terbaru)

**Blog**

- Publish/Draft dengan kolom `published_at`
- Slug otomatis (tidak berubah saat title diedit ulang)
- Penyempurnaan form & test CRUD
- Meta description dinamis dari excerpt setiap post

### 2. Company Settings

Menambahkan field profil perusahaan pada halaman Settings admin, sehingga bisa diubah tanpa mengubah kode:

- Company name, tagline, vision, mission
- Years of experience, total clients, total projects
- Contact (address, email, phone)
- Social media (LinkedIn, Twitter, GitHub, Instagram)
- SEO description & Google Analytics ID

Semua field di atas terhubung end-to-end ke landing page (stats section, vision/mission, footer, CTA), bukan sekadar tersimpan di database. Key setting memakai enum `SettingKey` (bukan string literal) untuk mencegah typo silent-fail.

### 3. User Management

Menyatukan form create/edit user menjadi satu komponen (unified form) dan memperbaiki alur pengelolaan user beserta cakupan test-nya. Role yang bisa ditugaskan dikurasi eksplisit (`admin`, `member`), dan sistem tidak bisa kehabisan admin — ada proteksi agar admin terakhir tidak bisa dihapus atau diturunkan rolenya.

### 4. Activity Log

Menyempurnakan halaman Activity Log:

- Search (berdasarkan deskripsi, log name, dan nama causer)
- Kolom subject & event — subject menunjukkan record yang terkena aksi (termasuk penanganan saat record sudah terhapus), event kini terisi konsisten untuk seluruh aksi User Management dan event autentikasi (register, login, logout, password reset)
- Log name
- Penambahan test coverage

### 5. Sitemap

Halaman admin sitemap (`/admin/sitemap`) sudah tersedia dari starter kit. Yang saya kerjakan:

- Menghubungkan entry "Home" pada viewer dengan konten CMS (Page/Service/GalleryItem) — waktu "last modified" mencerminkan update terbaru dari ketiganya, karena Page/Service/GalleryItem tidak punya URL publik tersendiri (ditampilkan sebagai section di homepage, bukan halaman terpisah)
- Mengembalikan test otorisasi non-admin yang sempat hilang

### 6. Halaman Publik

Menghubungkan landing page (`MainController`) ke data CMS di atas, sehingga konten berasal dari database, bukan hardcode:

- Hero, About (dari Page pertama yang published)
- Services (dari Service aktif, terurut)
- Gallery (6 item terbaru)
- Blog (list & detail, dengan meta description dinamis)
- Footer (company name, tagline, social links) & navbar
- Contact (address, email dengan `mailto:`, phone dengan `tel:`)
- Query homepage (Service/Gallery/Page) di-cache untuk mengurangi beban database

### 7. Security & Reliability (hasil code review)

Perbaikan berdasarkan code review CTO (6 Agustus 2026):

- **Broken Access Control (Critical)** — group route `content-management` sebelumnya hanya memerlukan login, sehingga role `member` (default hasil self-register) bisa mengelola seluruh konten CMS. Ditambahkan middleware `role:admin` dan pengecekan otorisasi di level action pada keempat komponen CMS
- **Upload gambar** — rule validasi ditambahkan `mimes:jpg,jpeg,png,webp` untuk mengeksklusi SVG (potensi XSS via file SVG berisi script)
- **Soft delete** ditambahkan pada Page, Service, GalleryItem, dan Post — mencegah kehilangan data permanen
- **Activity log** pada penghapusan user kini ditulis setelah delete benar-benar berhasil (bukan sebelumnya), agar log tidak mengklaim sukses saat operasi gagal
- Seluruh test yang sebelumnya menamai akses `member` ke CMS sebagai perilaku yang diharapkan telah ditulis ulang menjadi test negatif (403 Forbidden), dan `ServiceManagementTest.php` ditulis dari nol (sebelumnya tidak ada sama sekali)

### 8. Testing

Menambahkan/menyempurnakan Feature Test (Pest) untuk:

- Page, Service, Gallery, Blog management (termasuk test otorisasi dan penolakan upload SVG)
- Settings edit (termasuk contact fields & social links)
- User management (termasuk allow-list role dan proteksi last-admin)
- Activity log (termasuk assertion nilai event)
- Sitemap page (termasuk otorisasi dan lastModified)
- Landing page (termasuk gallery section, vision/mission, stats)
- Blog page (termasuk meta description)

## Teknologi

|              |                      |
| ------------ | -------------------- |
| PHP          | 8.4                  |
| Laravel      | 13                   |
| Frontend     | Livewire 4 + Flux UI |
| Styling      | Tailwind CSS v4      |
| Roles        | Spatie Permission    |
| Activity Log | Spatie Activitylog   |
| Sitemap      | Spatie Sitemap       |
| Slug         | Spatie Sluggable     |
| Testing      | Pest 4               |

## Menjalankan project

```bash
composer install
npm install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
composer run dev
```

Akun default (password `password`):

| Email              | Role   |
| ------------------ | ------ |
| `admin@mail.test`  | admin  |
| `member@mail.test` | member |

## Menjalankan test

```bash
php artisan test --compact
```
