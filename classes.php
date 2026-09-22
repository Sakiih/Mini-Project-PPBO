<?php

abstract class Pengguna
{
    protected $username;
    protected $nama;

    public function __construct($username, $nama)
    {
        $this->username = $username;
        $this->nama = $nama;
    }

    abstract public function getRole();

    public function getUsername()
    {
        return $this->username;
    }

    public function getNama()
    {
        return $this->nama;
    }
}

class Admin extends Pengguna
{
    public function getRole()
    {
        return "Admin";
    }
}

class Kasir extends Pengguna
{
    public function getRole()
    {
        return "Kasir";
    }
}

class Menu
{
    private $nama;
    private $harga;
    private $stok;
    private $kategori;

    public function __construct($nama, $harga, $stok, $kategori)
    {
        $this->setNama($nama);
        $this->setHarga($harga);
        $this->setStok($stok);
        $this->setKategori($kategori);
    }

    public function getNama()
    {
        return $this->nama;
    }

    public function getHarga()
    {
        return $this->harga;
    }

    public function getStok()
    {
        return $this->stok;
    }

    public function getKategori()
    {
        return $this->kategori;
    }

    public function setNama($nama)
    {
        if ($nama == "") {
            throw new Exception("Nama menu tidak boleh kosong!");
        }
        $this->nama = $nama;
    }

    public function setHarga($harga)
    {
        if (!is_numeric($harga) || $harga <= 0) {
            throw new Exception("Harga menu harus lebih dari 0!");
        }
        $this->harga = $harga;
    }

    public function setStok($stok)
    {
        if (!is_numeric($stok) || $stok < 0) {
            throw new Exception("Stok tidak boleh negatif!");
        }
        $this->stok = $stok;
    }

    public function setKategori($kategori)
    {
        if ($kategori == "") {
            throw new Exception("Kategori tidak boleh kosong!");
        }
        $this->kategori = $kategori;
    }

    public function tambahStok($jumlah)
    {
        if (!is_numeric($jumlah) || $jumlah <= 0) {
            throw new Exception("Jumlah stok harus lebih dari 0!");
        }
        $this->stok += $jumlah;
    }

    public function kurangiStok($jumlah)
    {
        if (!is_numeric($jumlah) || $jumlah <= 0) {
            throw new Exception("Jumlah pembelian harus lebih dari 0!");
        }
        if ($jumlah > $this->stok) {
            throw new Exception("Stok menu tidak mencukupi!");
        }
        $this->stok -= $jumlah;
    }

    public function getInfo()
    {
        return $this->nama . " - " . $this->kategori .
            " - Rp " . number_format($this->harga, 0, ',', '.') .
            " - Stok: " . $this->stok;
    }
}

class Pesanan
{
    private $nomorPesanan;
    private $menu;
    private $jumlah;
    private $total;

    public function __construct($nomorPesanan, $menu, $jumlah)
    {
        if (!is_numeric($jumlah) || $jumlah <= 0) {
            throw new Exception("Jumlah pesanan harus lebih dari 0!");
        }
        if ($jumlah > $menu->getStok()) {
            throw new Exception("Stok menu tidak mencukupi!");
        }

        $this->nomorPesanan = $nomorPesanan;
        $this->menu = $menu;
        $this->jumlah = $jumlah;
        $this->total = $menu->getHarga() * $jumlah;
    }

    public function getNomorPesanan()
    {
        return $this->nomorPesanan;
    }

    public function getMenu()
    {
        return $this->menu;
    }

    public function getJumlah()
    {
        return $this->jumlah;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function getInfo()
    {
        return "Pesanan #" . $this->nomorPesanan .
            " - " . $this->menu->getNama() .
            " x " . $this->jumlah .
            " = Rp " . number_format($this->total, 0, ',', '.');
    }
}

abstract class Pembayaran
{
    protected $total;
    protected $jumlahBayar;
    protected $kembalian;
    protected $status;

    public function __construct($total)
    {
        $this->total = $total;
        $this->jumlahBayar = 0;
        $this->kembalian = 0;
        $this->status = "Belum Dibayar";
    }

    abstract public function bayar($jumlahBayar);

    public function getTotal()
    {
        return $this->total;
    }

    public function getJumlahBayar()
    {
        return $this->jumlahBayar;
    }

    public function getKembalian()
    {
        return $this->kembalian;
    }

    public function getStatus()
    {
        return $this->status;
    }
}

class PembayaranTunai extends Pembayaran
{
    public function bayar($jumlahBayar)
    {
        if (!is_numeric($jumlahBayar) || $jumlahBayar <= 0) {
            $this->status = "Pembayaran Tidak Valid";
            return false;
        }

        if ($jumlahBayar < $this->total) {
            $this->status = "Pembayaran Tidak Cukup";
            return false;
        }

        $this->jumlahBayar = $jumlahBayar;
        $this->kembalian = $jumlahBayar - $this->total;
        $this->status = "Pembayaran Berhasil";
        return true;
    }
}

function prosesPembayaran(Pembayaran $payment, $jumlahBayar)
{
    return $payment->bayar($jumlahBayar);
}

?>
