# Company Profile CMS – PT. Reka Mitra Teknologi

Repository ini dikembangkan berdasarkan [Rewire Starter Kit](https://github.com/Recodex-ID/rewire). Starter kit sudah menyediakan fondasi aplikasi seperti autentikasi, role & permission, layout admin, serta struktur Livewire.

Pada tugas ini (branch `assignment-rani`) saya mengembangkan starter kit tersebut menjadi CMS Company Profile, mulai dari pengelolaan konten (pages, services, gallery, blog) hingga pengaturan perusahaan dan halaman publik yang terhubung ke database.

## Fitur yang sudah tersedia dari Starter Kit

- Authentication (Login, Register, Reset Password) — Fortify
- Role & Permission (Spatie Permission)
- Dashboard Admin
- Layout Admin menggunakan Flux UI
- Blog CMS dasar
- Activity Log (Spatie Activitylog)
- Sitemap generator (Spatie Sitemap)
- Testing dengan Pest

## Pengembangan yang saya lakukan

### 1. Content Management

Modul CMS baru sehingga konten website dapat dikelola tanpa hardcode.

**Pages**
- CRUD halaman (mis. About)
- Publish / Draft
- SEO slug otomatis
- Rich text content

**Services**
- CRUD layanan
- Ordering
- Status aktif/nonaktif
- Ditampilkan di landing page

**Gallery**
- CRUD gallery
- Upload gambar & caption
- Ordering
- Ditampilkan pada landing page (6 item terbaru)

**Blog**
- Publish/Draft dengan kolom `published_at`
- Slug otomatis
- Penyempurnaan form & test CRUD

### 2. Company Settings

Menambahkan field profil perusahaan pada halaman Settings admin, sehingga bisa diubah tanpa mengubah kode:

- Company name, tagline, vision, mission
- Years of experience, total clients, total projects
- Contact (address, email, phone)
- Social media (LinkedIn, Twitter, GitHub, Instagram)
- SEO description & Google Analytics ID

### 3. User Management

Menyatukan form create/edit user menjadi satu komponen (unified form) dan memperbaiki alur pengelolaan user beserta cakupan test-nya.

### 4. Activity Log

Menyempurnakan halaman Activity Log:
- Search
- Kolom subject & event
- Log name
- Penambahan test coverage

### 5. Sitemap

Menambahkan halaman **admin sitemap** (`/admin/sitemap`):
- Melihat seluruh URL publik yang terdaftar
- Menampilkan jumlah URL
- Link menuju `sitemap.xml`
- Hanya artikel/halaman yang published yang muncul

### 6. Halaman Publik

Menghubungkan landing page (`MainController`) ke data CMS di atas, sehingga konten berasal dari database, bukan hardcode:

- Hero, About (dari Page pertama yang published)
- Services (dari Service aktif, terurut)
- Gallery (6 item terbaru)
- Blog (list & detail)
- Footer (company name, tagline, social links)
- Contact (address, email, phone dari Settings)

### 7. Testing

Menambahkan/menyempurnakan Feature Test (Pest) untuk:
- Page, Service, Gallery, Blog management
- Settings edit
- User management
- Activity log
- Sitemap page
- Landing page

## Teknologi

| | |
|---|---|
| PHP | 8.4 |
| Laravel | 13 |
| Frontend | Livewire 4 + Flux UI |
| Styling | Tailwind CSS v4 |
| Roles | Spatie Permission |
| Activity Log | Spatie Activitylog |
| Sitemap | Spatie Sitemap |
| Slug | Spatie Sluggable |
| Testing | Pest 4 |

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

| Email | Role |
|---|---|
| `admin@mail.test` | admin |
| `member@mail.test` | member |

## Menjalankan test

```bash
php artisan test --compact
```
