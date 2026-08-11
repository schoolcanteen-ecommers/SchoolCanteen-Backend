# SchoolCanteen Backend

Backend untuk aplikasi **SchoolCanteen**, aplikasi e-commerce sekolah yang digunakan untuk pemesanan kantin dan koperasi sekolah.

Backend dibuat menggunakan Laravel dan akan terhubung dengan Supabase untuk database dan authentication.

## Tech Stack

- Laravel
- PHP
- Supabase PostgreSQL
- Supabase Auth
- Wasmer untuk deployment backend

## Menjalankan Project

Clone repository:

```bash
git clone https://github.com/schoolcanteen-ecommers/SchoolCanteen-Backend.git
```

Masuk ke folder project:

```bash
cd SchoolCanteen-Backend
```

Install dependency Laravel:

```bash
composer install
```

Buat file `.env` dari `.env.example`:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Setelah itu isi konfigurasi yang dibutuhkan di file `.env`, terutama bagian database dan Supabase.

Jalankan backend:

```bash
php artisan serve
```

Secara default backend berjalan di:

```text
http://127.0.0.1:8000
```

## Cek API

Untuk memastikan backend berjalan, buka:

```text
http://127.0.0.1:8000/api/health
```

Response yang diharapkan:

```json
{
  "success": true,
  "message": "SchoolCanteen API is running",
  "version": "v1"
}
```

## Database

Setelah koneksi Supabase PostgreSQL sudah dikonfigurasi di `.env`, migration dapat dijalankan dengan:

```bash
php artisan migrate
```

Untuk mengecek status migration:

```bash
php artisan migrate:status
```

Jika project sudah memiliki seeder, data awal dapat dimasukkan dengan:

```bash
php artisan db:seed
```

## Command yang Sering Dipakai

Melihat semua route API:

```bash
php artisan route:list --path=api
```

Membersihkan cache config:

```bash
php artisan config:clear
```

Masuk ke Laravel Tinker:

```bash
php artisan tinker
```

Menjalankan server:

```bash
php artisan serve
```

## Git Workflow

Branch utama:

```text
main
develop
```

Untuk mulai mengerjakan fitur baru:

```bash
git checkout develop
git pull origin develop
git checkout -b feature/nama-fitur
```

Setelah fitur selesai:

```bash
git status
git add .
git commit -m "feat: nama fitur"
git push -u origin feature/nama-fitur
```

Setelah itu buat Pull Request dari branch fitur ke `develop`.

## Environment

File `.env` tidak boleh di-push ke GitHub karena berisi credential dan konfigurasi penting.

Gunakan:

```text
.env.example
```

sebagai contoh konfigurasi yang dibutuhkan oleh project.

## Progress

Progress pengerjaan backend disimpan di folder:

```text
docs/
```

Contoh:

```text
docs/progress-backend-day-1.md
```

## Deployment

Backend SchoolCanteen akan dijalankan menggunakan **Wasmer**.

Konfigurasi deployment akan ditambahkan setelah API dan koneksi database sudah siap untuk diuji secara online.
