# Progress Backend SchoolCanteen - Day 5

**Tanggal:** 16 Agustus 2026  
**Bagian:** Backend  
**Framework:** Laravel  
**OS:** Linux  

## Yang Dikerjakan Hari Ini

Hari ini saya melanjutkan backend SchoolCanteen ke bagian transaksi utama, pengelolaan pesanan, produk merchant, pembayaran, dan API admin.

### 1. Checkout dan Order Siswa

Backend sudah dapat menangani proses checkout siswa dan menyimpan data pesanan.

Bagian yang sudah tersedia:

- membuat order
- menyimpan order items
- menyimpan snapshot nama, harga, dan gambar produk
- mengurangi stok produk
- mengurangi saldo wallet siswa
- membuat wallet transaction
- membuat escrow
- menambah pending balance merchant
- membuat pickup token dan pickup code

Endpoint utama:

```text
POST /api/v1/student/orders
GET  /api/v1/student/orders
GET  /api/v1/student/orders/{order}
```

### 2. Pickup Slot

Siswa dapat mengambil daftar jadwal pengambilan yang tersedia berdasarkan merchant.

Endpoint:

```text
GET /api/v1/merchants/{merchant}/pickup-slots
```

### 3. Merchant Order Management

Merchant dapat melihat pesanan yang masuk dan mengubah status pesanan.

Endpoint:

```text
GET   /api/v1/merchant/orders
GET   /api/v1/merchant/orders/{order}
PATCH /api/v1/merchant/orders/{order}/status
```

Alur status pesanan:

```text
waiting
→ confirmed
→ preparing
→ ready
```

### 4. Production Summary

Merchant sudah memiliki API ringkasan produksi untuk melihat total jumlah produk yang perlu disiapkan dari pesanan aktif.

Endpoint:

```text
GET /api/v1/merchant/production-summary
```

### 5. Merchant Product Management dan Cloudinary

Merchant sudah dapat mengelola produk dan kategori.

Endpoint:

```text
GET    /api/v1/merchant/products
POST   /api/v1/merchant/products
PATCH  /api/v1/merchant/products/{product}
DELETE /api/v1/merchant/products/{product}

GET    /api/v1/merchant/categories
```

Upload gambar produk menggunakan Cloudinary dan produk sudah mendukung soft delete.

### 6. Student Dashboard dan Profile

API untuk kebutuhan dashboard dan profile siswa sudah ditambahkan.

Endpoint:

```text
GET /api/v1/student/dashboard
GET /api/v1/student/profile
```

Dashboard mengembalikan data pesanan aktif, pesanan selesai, dan wallet siswa.

Profile mengembalikan data akun serta data tambahan siswa seperti NIS, kelas, dan jurusan.

### 7. Registrasi Siswa

Ditambahkan provisioning untuk akun siswa baru dari Supabase Auth.

Saat akun siswa dibuat, backend akan menyiapkan:

```text
profile
role student
wallet awal
```

### 8. Pickup dan Escrow

Merchant sudah dapat memverifikasi pengambilan pesanan.

Endpoint:

```text
POST /api/v1/merchant/pickups/verify
```

Saat pickup berhasil:

```text
order → completed
escrow → released
merchant pending balance → available balance
```

### 9. Merchant Finance

Merchant sudah memiliki API untuk melihat wallet, transaksi, rekening pembayaran, dan withdrawal.

Endpoint:

```text
GET  /api/v1/merchant/wallet
GET  /api/v1/merchant/wallet/transactions

GET  /api/v1/merchant/payment-accounts
POST /api/v1/merchant/payment-accounts

GET  /api/v1/merchant/withdrawals
POST /api/v1/merchant/withdrawals
```

### 10. Midtrans Top Up

Top up wallet siswa sudah terhubung ke Midtrans.

Endpoint:

```text
POST /api/v1/student/wallet/top-ups
POST /api/v1/payments/midtrans/notification
```

Saldo siswa diproses berdasarkan status pembayaran yang diterima backend.

### 11. Admin API

API admin sudah tersedia untuk dashboard, merchant, siswa, transaksi, withdrawal, dan report.

Beberapa endpoint utama:

```text
GET /api/v1/admin/dashboard

GET /api/v1/admin/merchants
GET /api/v1/admin/students

GET /api/v1/admin/transactions/student
GET /api/v1/admin/transactions/merchant

GET /api/v1/admin/withdrawals
GET /api/v1/admin/reports/summary
```

### 12. Security dan Error Response

Ditambahkan test untuk memastikan pembatasan role, ownership data, UUID route, dan format error API tetap konsisten.

Test yang ditambahkan mencakup:

- student tidak dapat mengakses route merchant/admin
- merchant tidak dapat mengakses route student/admin
- admin tidak dapat mengakses route student/merchant
- student tidak dapat melihat order siswa lain
- merchant tidak dapat mengakses order merchant lain
- invalid UUID ditolak
- validation error memakai format API
- error server tidak menampilkan informasi sensitif

## Status Day 5

- [x] Checkout siswa
- [x] Student order history dan detail
- [x] Pickup slot
- [x] Merchant order management
- [x] Production summary
- [x] Merchant product management
- [x] Cloudinary
- [x] Student dashboard
- [x] Student profile
- [x] Registrasi siswa
- [x] Pickup verification
- [x] Escrow settlement
- [x] Merchant wallet
- [x] Withdrawal
- [x] Midtrans top up
- [x] Admin API
- [x] Security regression test
- [x] API error contract

## Hasil Pengecekan

Route utama backend sudah terdaftar dan test backend berhasil dijalankan.

Hasil test:

```text
34 passed
86 assertions
```

## Next Step

Selanjutnya saya akan fokus ke finalisasi data demo, pengecekan akhir backend, deployment, dan perbaikan bug jika ditemukan saat integrasi.
