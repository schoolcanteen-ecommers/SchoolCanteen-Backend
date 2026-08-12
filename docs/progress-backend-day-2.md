# Progress Backend SchoolCanteen - Day 2

**Tanggal:** 12 Agustus 2026
**Bagian:** Backend
**Framework:** Laravel
**OS:** Linux

## Yang Dikerjakan Hari Ini

Hari ini saya melanjutkan backend SchoolCanteen sampai tahap pembuatan struktur database utama.

### 1. Konfigurasi PostgreSQL

Saya menyiapkan konfigurasi PostgreSQL agar Laravel bisa terhubung dengan database Supabase.

Konfigurasi penting disimpan di file `.env` dan tidak di-push ke GitHub.

### 2. Membuat Tabel Profile

Saya membuat migration untuk:

- `profiles`
- `student_profiles`

Pada tabel `profiles`, ID user disesuaikan dengan UUID dari Supabase Auth.

### 3. Membuat Tabel Merchant dan Katalog

Saya membuat migration untuk:

- `merchants`
- `categories`
- `products`
- `pickup_slots`

Tabel ini digunakan untuk data kantin, koperasi, kategori produk, produk, dan jadwal pengambilan.

### 4. Membuat Tabel Order

Saya membuat migration untuk:

- `orders`
- `order_items`

Tabel ini digunakan untuk menyimpan data pesanan dan detail produk yang dipesan.

### 5. Membuat Tabel Wallet dan Transaksi

Saya membuat migration untuk:

- `wallets`
- `wallet_transactions`
- `payment_transactions`

Bagian ini digunakan untuk saldo siswa, riwayat transaksi, dan data pembayaran.

### 6. Membuat Tabel Keuangan Merchant

Saya juga membuat migration untuk:

- `merchant_wallets`
- `merchant_wallet_transactions`
- `escrow_transactions`
- `pickups`
- `merchant_payment_accounts`
- `withdrawal_requests`

Bagian ini digunakan untuk saldo merchant, escrow pembayaran, pickup pesanan, rekening merchant, dan request penarikan saldo.

### 7. Menjalankan Migration

Semua migration yang sudah dibuat berhasil dijalankan ke database menggunakan:

```bash
php artisan migrate
```

Hasil migration berhasil dan seluruh tabel yang dibuat sudah berstatus selesai.

## Progress Git

Progress database dibuat bertahap supaya riwayat commit lebih mudah dibaca.

Progress utama hari ini sudah sampai pada:

```text
feat(database): configure PostgreSQL environment
feat(database): add user profile schema
feat(database): tambah tabel merchant dan katalog
feat(database): tambah order table dan order item
feat(database): tambah table transaksi dan table saldo
feat(database): tambah table keuangan dan penarikan merchant
```

## Status Saat Ini

- [x] Setup Laravel
- [x] API foundation
- [x] Setup `.env`
- [x] Konfigurasi PostgreSQL
- [x] Migration profile
- [x] Migration merchant dan katalog
- [x] Migration order
- [x] Migration wallet dan transaksi
- [x] Migration keuangan merchant
- [x] Menjalankan semua migration
- [ ] Membuat model dan relationship
- [ ] Membuat seeder
- [ ] Membuat API produk dan merchant

## Next Step

Selanjutnya saya akan membuat model Laravel dan relationship antar tabel, lalu dilanjutkan ke seeder dan API katalog.
