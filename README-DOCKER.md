# Panduan Menjalankan Project dengan Docker

Project ini telah dikonfigurasi dengan Docker & Docker Compose menggunakan:
- **PHP 8.3 FPM** (Lengkap dengan ekstensi: `pdo_mysql`, `gd`, `zip`, `intl`, `bcmath`, `opcache`, `redis`, serta Composer & Node.js).
- **Nginx** (Web Server port 8080).
- **MySQL 8.0** (Database port 3307 pada host, port 3306 internal).
- **phpMyAdmin** (Database GUI port 8081).

---

## 1. Persiapan Awal

1. Pastikan aplikasi **Docker Desktop** sudah terinstall dan **sedang berjalan** (Running).
2. Jika Anda ingin menggunakan database terpisah khusus Docker, salin `.env.docker` ke `.env`:
   ```bash
   cp .env.docker .env
   ```
   *(Catatan: `docker-compose.yml` sudah secara otomatis mengarahkan koneksi database ke container `db`)*.

---

## 2. Cara Menjalankan

Buka terminal di folder project (`C:\laragon\www\absensi`), lalu jalankan:

```bash
# Build dan jalankan seluruh container di latar belakang (detached mode)
docker compose up -d --build
```

Setelah semua container running, Anda dapat mengakses:
- **Aplikasi Web**: [http://localhost:8080](http://localhost:8080)
- **phpMyAdmin**: [http://localhost:8081](http://localhost:8081)
  - **Server**: `db`
  - **Username**: `root`
  - **Password**: `root`

---

## 3. Menjalankan Migrasi & Database Seeder

Untuk membuat tabel dan data awal (seperti akun Operator dan Karyawan):

```bash
docker compose exec app php artisan migrate --seed
```

> **Akun Default dari Seeder:**
> - **Operator HRD**: NIP `12345678` | Password: `password`
> - **Karyawan**: NIP `1001` | Password: `password`

---

## 4. Perintah Berguna Lainnya

### Compile Frontend (Vite / Tailwind)
```bash
# Menjalankan build asset frontend di dalam container
docker compose exec app npm run build
```

### Membersihkan Cache Laravel
```bash
docker compose exec app php artisan optimize:clear
```

### Masuk ke Terminal Container (Bash)
```bash
docker compose exec app bash
```

### Melihat Log Container
```bash
# Melihat log semua container secara realtime
docker compose logs -f

# Atau log khusus aplikasi
docker compose logs -f app
```

### Menghentikan Container
```bash
# Menghentikan container tanpa menghapus data
docker compose stop

# Menghentikan dan menghapus container
docker compose down

# Menghentikan dan menghapus data database (volume)
docker compose down -v
```

---

## 5. Mengapa Port MySQL Host Menggunakan 3307?
Karena project ini berada di `C:\laragon\www\absensi`, biasanya MySQL dari Laragon berjalan di port `3306`. Agar tidak terjadi bentrok port (*port conflict*), MySQL Docker diexpose ke port `3307` di komputer Anda, sedangkan di dalam container Laravel tetap terhubung ke `db:3306`.
