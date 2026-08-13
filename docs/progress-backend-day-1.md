# Progress Backend SchoolCanteen - Day 1

**Tanggal:** 11 Agustus 2026  
**Bagian:** Backend  
**Framework:** Laravel  
**OS:** Linux  

## Yang Dikerjakan Hari Ini

Hari ini saya mulai membuat backend untuk aplikasi **SchoolCanteen**.

### 1. Setup Project Laravel

Saya membuat project Laravel baru di Linux dan memastikan project bisa dijalankan dengan:

```bash
php artisan serve
```

Laravel berhasil berjalan di:

```text
http://127.0.0.1:8000
```

### 2. Setup GitHub

Saya menghubungkan project backend lokal ke repository GitHub:

```text
schoolcanteen-ecommers/SchoolCanteen-Backend
```

Setelah itu project mulai di-push secara bertahap supaya progress pengerjaan lebih mudah dilihat.

### 3. Membuat API Awal

Saya membuat file:

```text
routes/api.php
```

Lalu mendaftarkan route API di:

```text
bootstrap/app.php
```

Saya juga membuat endpoint untuk mengecek apakah backend berjalan:

```text
GET /api/health
```

Response:

```json
{
  "success": true,
  "message": "SchoolCanteen API is running",
  "version": "v1"
}
```

### 4. Setup `.env`

Saya sudah mengatur file `.env` untuk kebutuhan backend dan database.

File `.env` tidak akan di-push ke GitHub karena berisi data penting seperti password dan credential.

## Kendala

Saat membuat API, `routes/api.php` perlu didaftarkan terlebih dahulu di `bootstrap/app.php` agar route API bisa terbaca oleh Laravel.

Setelah diperbaiki, endpoint `/api/health` berhasil dijalankan.

## Status Saat Ini

- [x] Membuat project Laravel
- [x] Menjalankan Laravel di Linux
- [x] Menghubungkan project ke GitHub
- [x] Membuat `routes/api.php`
- [x] Mendaftarkan API route di `bootstrap/app.php`
- [x] Membuat endpoint `/api/health`
- [x] Setup `.env`
- [ ] Menghubungkan Laravel ke Supabase PostgreSQL
- [ ] Membuat migration database SchoolCanteen
- [ ] Membuat data demo
- [ ] Membuat API produk dan merchant

## Next Step

Selanjutnya saya akan menghubungkan Laravel dengan **Supabase PostgreSQL** dan mulai membuat tabel database yang dibutuhkan SchoolCanteen.
