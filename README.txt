MINI PROJECT PBO - SISTEM KASIR

File:
1. index.php
2. classes.php

Cara menjalankan:
- Simpan folder ini di folder www/hotdocs Laragon.
- Jalankan Apache.
- Buka http://localhost/MiniProject_PBO_Sistem_Kasir_Final/

Akun demo:
Admin: admin / 12345
Kasir: kasir / 12345

Alur Kasir:
Login -> Validasi akun -> Dashboard Kasir -> Tambah Pesanan -> Validasi Pesanan ->
Input Pembayaran -> Validasi Pembayaran -> Hitung Kembalian -> Struk-> Laporan Transaksi.

Jika pembayaran kurang, pesanan tetap tersimpan dan kasir diminta memasukkan pembayaran lagi.

Alur Admin:
Login -> Validasi akun -> Dashboard Admin -> Kelola Menu / Kelola Stok / Laporan.

Konsep PBO yang digunakan:
- Class dan Object
- Property dan Method
- Constructor
- Encapsulation (private, getter, setter)
- Inheritance (Admin dan Kasir extends Pengguna)
- Abstraction (Pengguna dan Pembayaran sebagai abstract class)
- Polymorphism (object Admin/Kasir diperlakukan sebagai Pengguna dan getRole() berbeda)
