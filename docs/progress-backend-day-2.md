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

Semua migration berhasil dijalankan tanpa ada yang masih pending.

## Progress Git

Progress database dibuat bertahap supaya riwayat commit lebih mudah dibaca.

```text
feat(database): configure PostgreSQL environment
feat(database): add user profile schema
feat(database): tambah tabel merchant dan katalog
feat(database): tambah order table dan order item
feat(database): tambah table transaksi dan table saldo
feat(database): tambah table keuangan dan penarikan merchant
```

## Status Saat Ini

- [x] Setup Laravel dan API awal
- [x] Setup `.env`
- [x] Menghubungkan Laravel ke Supabase PostgreSQL
- [x] Membuat migration profile
- [x] Membuat migration merchant dan katalog
- [x] Membuat migration order dan order item
- [x] Membuat migration wallet dan transaksi
- [x] Membuat migration keuangan merchant
- [x] Menjalankan seluruh migration
- [ ] Membuat data demo dengan seeder
- [ ] Membuat API produk
- [ ] Membuat API merchant

## Next Step

Selanjutnya saya akan membuat data demo menggunakan seeder, lalu mulai membuat API produk dan merchant agar data katalog bisa dites lewat backend.
