# Progress Backend SchoolCanteen - Day 3

**Tanggal:** 13 Agustus 2026  
**Bagian:** Backend

## Yang Dikerjakan Hari Ini

Hari ini saya melanjutkan backend SchoolCanteen dari bagian database ke data awal dan API katalog.

### 1. Menambahkan Data Demo

Saya membuat seeder untuk mengisi data awal SchoolCanteen.

Data demo ini digunakan supaya API bisa langsung dites tanpa harus memasukkan data satu per satu dari database.

Commit:

```text
feat(seed): tambah data demo SchoolCanteen
```

### 2. Membuat Product API

Saya membuat API untuk menampilkan daftar produk dan detail produk.

Endpoint yang dibuat:

```text
GET /api/v1/products
GET /api/v1/products/{product}
```

Saya juga membuat `ProductController` dan `ProductResource` supaya data produk yang dikirim lewat API lebih rapi.

Route kemudian dicek menggunakan:

```bash
php artisan route:list --path=api/v1/products
```

Hasil akhirnya kedua route produk sudah berhasil terbaca.

### 3. Membuat Merchant API

Setelah Product API berjalan, saya lanjut membuat API merchant.

Endpoint yang dibuat:

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

Hasil akhirnya route daftar merchant dan detail merchant sudah berhasil terbaca.

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

Di Linux nama folder bersifat case-sensitive. Folder Laravel yang benar adalah:

```text
app/Models
app/Http
```

Setelah path diperbaiki, file bisa ditambahkan ke commit.

### 2. ProductController Tidak Ditemukan

Saat mengecek route dengan:

```bash
php artisan route:list --path=api/v1/products
```

sempat muncul error:

```text
ReflectionException
Class "ProductController" does not exist
```

Masalah diperbaiki dengan mengecek kembali controller, namespace, import, dan route pada `api.php`.

Setelah diperbaiki, route produk berhasil muncul:

```text
GET|HEAD api/v1/products
GET|HEAD api/v1/products/{product}
```

### 3. MerchantController Tidak Ditemukan

Setelah itu sempat muncul error:

```text
Class "App\Http\Controllers\Api\V1\MerchantController" does not exist
```

Controller merchant sebelumnya belum berada pada lokasi atau namespace yang sesuai dengan route.

Setelah diperbaiki, route merchant berhasil terbaca:

```text
GET|HEAD api/v1/merchants
GET|HEAD api/v1/merchants/{merchant}
```

## Progress Git Hari Ini

Beberapa commit yang dibuat selama pengerjaan:

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

- [x] Setup Laravel
- [x] API foundation
- [x] Koneksi PostgreSQL
- [x] Semua migration database
- [x] Seeder data demo
- [x] Product API
- [x] Product Resource
- [x] Merchant API
- [x] Merchant Resource
- [x] Route produk berhasil dites
- [x] Route merchant berhasil dites
- [ ] Filter dan pagination katalog
- [ ] Supabase Authentication
- [ ] Role Authorization
- [ ] Student Wallet API

## Next Step

Selanjutnya saya akan melanjutkan API katalog dengan filter dan pagination, lalu masuk ke integrasi Supabase Authentication untuk endpoint yang membutuhkan login.
