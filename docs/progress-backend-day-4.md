# Progress Backend SchoolCanteen - Day 4

**Tanggal:** 15 Agustus 2026  
**Bagian:** Backend  
**Framework:** Laravel  
**OS:** Linux  

## Yang Dikerjakan Hari Ini

Hari ini saya melanjutkan backend SchoolCanteen pada bagian katalog, autentikasi, pembatasan role, dan wallet siswa.

### 1. Menyelesaikan Filter Katalog

API produk sudah mendukung:

- pencarian produk
- filter berdasarkan merchant
- filter berdasarkan kategori
- filter berdasarkan tipe merchant
- pagination produk

API merchant juga sudah dapat difilter berdasarkan tipe merchant seperti kantin dan koperasi.

### 2. Menambahkan Supabase Authentication

Backend sudah dapat membaca access token dari Supabase Auth melalui middleware.

Endpoint yang digunakan:

```text
GET /api/v1/me
```

Hasil yang sudah berhasil:

```text
Tanpa token       -> 401
Token tidak valid -> 401
Token valid       -> 200
```

Saat token valid, data profile siswa berhasil dibaca dari database berdasarkan UUID user Supabase.

### 3. Menambahkan Role Authorization

Middleware role sudah tersedia untuk membatasi akses berdasarkan role:

```text
student
merchant
admin
```

Dengan ini endpoint private dapat dipisahkan sesuai role pengguna.

### 4. Menambahkan Student Wallet API

API wallet siswa sudah tersedia untuk mengambil saldo dan riwayat transaksi.

Endpoint:

```text
GET /api/v1/student/wallet
GET /api/v1/student/wallet/transactions
```

Route student wallet sudah berhasil terbaca oleh Laravel.

## Kendala Hari Ini

Saat melakukan sinkronisasi file, beberapa file backend tahap berikutnya ikut masuk ke folder lokal. File tersebut tidak langsung dimasukkan ke Git dan ditahan untuk pengerjaan hari berikutnya.

Saya juga menemukan `Profile.php` belum masuk ke repository, sehingga file tersebut ditambahkan agar middleware autentikasi dapat menggunakan model profile dari source repository.

## Status Saat Ini

- [x] Laravel backend
- [x] Supabase PostgreSQL
- [x] Database migration
- [x] Seeder
- [x] Product API
- [x] Merchant API
- [x] Catalog filter dan pagination
- [x] Supabase Authentication
- [x] Role Authorization
- [x] Student Wallet API
- [ ] Checkout dan Order
- [ ] Student Order History
- [ ] Pickup Slot
- [ ] Merchant Order Management
- [ ] Production Summary
- [ ] Pickup dan Escrow
- [ ] Merchant Finance
- [ ] Midtrans Top Up
- [ ] Admin API
- [ ] Final deployment Wasmer

## Next Step

Selanjutnya saya akan melanjutkan bagian checkout dan order siswa, kemudian masuk ke pengelolaan order merchant dan production summary.
