# Progress Backend SchoolCanteen - Day 3

**Tanggal:** 13 Agustus 2026  
**Bagian:** Backend  
**Framework:** Laravel  
**OS:** Linux  

## Yang Dikerjakan Hari Ini

Hari ini saya melanjutkan backend SchoolCanteen dari bagian database ke data awal dan API katalog.

### 1. Menambahkan Data Demo

Saya membuat seeder untuk mengisi data awal SchoolCanteen.

Data demo digunakan supaya API bisa langsung dites tanpa harus memasukkan data satu per satu dari database.

Commit:

```text
feat(seed): tambah data demo SchoolCanteen
```

### 2. Membuat Product API

Saya membuat API untuk menampilkan daftar produk dan detail produk.

Endpoint:

```text
GET /api/v1/products
GET /api/v1/products/{product}
```

Saya juga membuat `ProductController` dan `ProductResource` untuk mengatur data produk yang dikirim lewat API.

Route dicek menggunakan:

```bash
php artisan route:list --path=api/v1/products
```

Hasil akhirnya kedua route produk berhasil terbaca.

### 3. Membuat Merchant API

Setelah Product API berjalan, saya lanjut membuat API merchant.

Endpoint:

```text
GET /api/v1/merchants
GET /api/v1/merchants/{merchant}
```

Bagian yang dibuat:

- `MerchantController`
- `MerchantResource`
- `Merchant` model
- route merchant di `routes/api.php`

Route dicek menggunakan:

```bash
php artisan route:list --path=api/v1/merchant
```

Hasil akhirnya route daftar merchant dan detail merchant berhasil terbaca.

## Kendala Hari Ini

### 1. Salah Penulisan Path Saat Git Add

Saya sempat menggunakan:

```bash
git add app/models
```

dan:

```bash
git add app/http
```

Di Linux nama folder bersifat case-sensitive. Folder yang benar adalah:

```text
app/Models
app/Http
```

Setelah path diperbaiki, file bisa ditambahkan ke commit.

### 2. ProductController Tidak Ditemukan

Saat mengecek route:

```bash
php artisan route:list --path=api/v1/products
```

sempat muncul:

```text
ReflectionException
Class "ProductController" does not exist
```

Saya mengecek kembali controller, namespace, import, dan route pada `api.php`.

Setelah diperbaiki, route produk berhasil terbaca.

### 3. MerchantController Tidak Ditemukan

Setelah itu sempat muncul:

```text
Class "App\Http\Controllers\Api\V1\MerchantController" does not exist
```

Saya memperbaiki lokasi controller dan namespace yang digunakan oleh route.

Setelah itu route merchant berhasil terbaca.

## Progress Git Hari Ini

Beberapa commit yang dibuat:

```text
feat(seed): tambah data demo SchoolCanteen
feat(products): tambah api daftar dan detail produk
feat(products): tambah api dan models
feat(products): tambah apiuntuk merchant dan logikanya
feat(products): tambah api product resource dan integrasi pada api.php
```

Semua perubahan sudah di-push ke branch:

```text
develop
```

## Status Saat Ini

- [x] Setup Laravel dan API awal
- [x] Koneksi Supabase PostgreSQL
- [x] Semua migration database
- [x] Seeder data demo
- [x] Membuat Product API
- [x] Membuat Product Resource
- [x] Route daftar dan detail produk berhasil dites
- [x] Membuat Merchant API
- [x] Membuat Merchant Resource
- [x] Route daftar dan detail merchant berhasil dites
- [ ] Menambahkan filter dan pagination katalog
- [ ] Membuat Supabase Authentication
- [ ] Membuat Role Authorization
- [ ] Membuat Student Wallet API

## Next Step

Selanjutnya saya akan melanjutkan API katalog dengan filter dan pagination. Setelah katalog selesai, pengerjaan dilanjutkan ke Supabase Authentication untuk endpoint yang membutuhkan login.
