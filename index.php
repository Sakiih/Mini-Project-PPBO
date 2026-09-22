<?php
session_start();

// =====================================================
// 1. DATA AWAL
// =====================================================

// Object user dibuat dari class Admin dan Kasir.
$admin = new Admin("admin", "123");
$kasir = new Kasir("kasir", "123");
$daftarPengguna = [$admin, $kasir];

// Menu disimpan di session agar perubahan tetap ada selama demo.
if (!isset($_SESSION['menu'])) {
    $_SESSION['menu'] = [
        1 => new Menu(1, "Nasi Goreng", 15000, 10),
        2 => new Menu(2, "Mie Ayam", 12000, 10),
        3 => new Menu(3, "Es Teh", 5000, 15)
    ];
}

if (!isset($_SESSION['transaksi'])) {
    $_SESSION['transaksi'] = [];
}

$pesan = "";
$jenisPesan = "";
$strukTerakhir = null;

// =====================================================
// 2. CLASS OOP
// =====================================================

// ABSTRACTION
abstract class Pengguna
{
    protected $username;
    protected $password;

    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function cekLogin($username, $password)
    {
        return $this->username == $username && $this->password == $password;
    }

    abstract public function getRole();
}

// INHERITANCE + POLYMORPHISM
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

// ENCAPSULATION
class Menu
{
    private $id;
    private $nama;
    private $harga;
    private $stok;

    public function __construct($id, $nama, $harga, $stok)
    {
        $this->id = $id;
        $this->nama = $nama;
        $this->harga = $harga;
        $this->stok = $stok;
    }

    public function getId()
    {
        return $this->id;
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

    public function tambahStok($jumlah)
    {
        $this->stok = $this->stok + $jumlah;
    }

    public function kurangiStok($jumlah)
    {
        $this->stok = $this->stok - $jumlah;
    }
}

// CLASS PESANAN
class Pesanan
{
    private $menu;
    private $jumlah;
    private $total;

    public function __construct($menu, $jumlah)
    {
        $this->menu = $menu;
        $this->jumlah = $jumlah;
        $this->total = $menu->getHarga() * $jumlah;
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
}

// =====================================================
// 3. PROSES LOGIN
// =====================================================

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $loginBerhasil = false;

    foreach ($daftarPengguna as $pengguna) {
        if ($pengguna->cekLogin($username, $password)) {
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $pengguna->getRole();
            $loginBerhasil = true;
            break;
        }
    }

    if (!$loginBerhasil) {
        $pesan = "Akun tidak valid! Silakan login kembali.";
        $jenisPesan = "error";
    }
}

// =====================================================
// 4. LOGOUT
// =====================================================

if (isset($_GET['logout'])) {
    unset($_SESSION['username']);
    unset($_SESSION['role']);
    unset($_SESSION['pesanan']);
    header("Location: index.php");
    exit;
}

// =====================================================
// 5. AKSI ADMIN
// =====================================================

if (isset($_SESSION['role']) && $_SESSION['role'] == "Admin") {

    // Tambah menu
    if (isset($_POST['tambah_menu'])) {
        $nama = $_POST['nama'];
        $harga = $_POST['harga'];
        $stok = $_POST['stok'];

        if ($nama == "" || $harga <= 0 || $stok < 0) {
            $pesan = "Data menu tidak valid!";
            $jenisPesan = "error";
        } else {
            $idBaru = count($_SESSION['menu']) + 1;
            $_SESSION['menu'][$idBaru] = new Menu($idBaru, $nama, $harga, $stok);
            $pesan = "Menu berhasil ditambahkan.";
            $jenisPesan = "success";
        }
    }

    // Tambah stok
    if (isset($_POST['tambah_stok'])) {
        $idMenu = $_POST['id_menu'];
        $jumlah = $_POST['jumlah_stok'];

        if ($jumlah <= 0 || !isset($_SESSION['menu'][$idMenu])) {
            $pesan = "Data stok tidak valid!";
            $jenisPesan = "error";
        } else {
            $_SESSION['menu'][$idMenu]->tambahStok($jumlah);
            $pesan = "Stok berhasil ditambahkan.";
            $jenisPesan = "success";
        }
    }
}

// =====================================================
// 6. AKSI KASIR - PESANAN
// =====================================================

if (isset($_SESSION['role']) && $_SESSION['role'] == "Kasir") {

    // Input pesanan
    if (isset($_POST['buat_pesanan'])) {
        $idMenu = $_POST['id_menu'];
        $jumlah = $_POST['jumlah'];

        if (!isset($_SESSION['menu'][$idMenu]) || $jumlah <= 0) {
            $pesan = "Pesanan tidak valid! Silakan input ulang.";
            $jenisPesan = "error";
        } else {
            $menuDipilih = $_SESSION['menu'][$idMenu];

            if ($jumlah > $menuDipilih->getStok()) {
                $pesan = "Stok tidak cukup! Silakan input ulang.";
                $jenisPesan = "error";
            } else {
                $pesanan = new Pesanan($menuDipilih, $jumlah);
                $_SESSION['pesanan'] = $pesanan;
                $pesan = "Pesanan berhasil dibuat. Silakan lakukan pembayaran.";
                $jenisPesan = "success";
            }
        }
    }

    // Pembayaran
    if (isset($_POST['bayar'])) {
        if (!isset($_SESSION['pesanan'])) {
            $pesan = "Belum ada pesanan.";
            $jenisPesan = "error";
        } else {
            $pesanan = $_SESSION['pesanan'];
            $uang = $_POST['uang'];
            $total = $pesanan->getTotal();

            if ($uang < $total) {
                $pesan = "Pembayaran kurang! Silakan masukkan nominal yang cukup.";
                $jenisPesan = "error";
            } else {
                $pesanan->getMenu()->kurangiStok($pesanan->getJumlah());

                $transaksi = [
                    "nama" => $pesanan->getMenu()->getNama(),
                    "jumlah" => $pesanan->getJumlah(),
                    "total" => $total,
                    "uang" => $uang,
                    "kembalian" => $uang - $total,
                    "waktu" => date("d-m-Y H:i")
                ];

                $_SESSION['transaksi'][] = $transaksi;
                $_SESSION['struk_terakhir'] = $transaksi;
                unset($_SESSION['pesanan']);

                $pesan = "Pembayaran berhasil.";
                $jenisPesan = "success";
                $strukTerakhir = $transaksi;
            }
        }
    }
}

// Ambil struk terakhir untuk ditampilkan setelah pembayaran.
if (isset($_SESSION['struk_terakhir'])) {
    $strukTerakhir = $_SESSION['struk_terakhir'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Mini Kasir OOP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background: #f4f4f4;
        }

        .container {
            max-width: 900px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        h1, h2 {
            margin-top: 0;
        }

        input, select, button {
            padding: 10px;
            margin: 5px 0;
        }

        button {
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        .success {
            background: #dff0d8;
            padding: 10px;
            margin: 10px 0;
        }

        .error {
            background: #f2dede;
            padding: 10px;
            margin: 10px 0;
        }

        .menu-box {
            border: 1px solid #ccc;
            padding: 15px;
            margin: 15px 0;
        }

        .struk {
            border: 1px dashed #333;
            padding: 20px;
            margin-top: 20px;
        }

        @media print {
            body * {
                visibility: hidden;
            }

            .struk, .struk * {
                visibility: visible;
            }

            .struk {
                position: absolute;
                left: 0;
                top: 0;
                width: 300px;
            }
        }
    </style>
</head>
<body>
<div class="container">

<?php if (!isset($_SESSION['role'])): ?>

    <!-- ================= LOGIN ================= -->
    <h1>Mini Kasir OOP</h1>
    <p>Silakan login untuk masuk ke sistem.</p>

    <?php if ($pesan != ""): ?>
        <div class="<?php echo $jenisPesan; ?>">
            <?php echo $pesan; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <label>Username</label><br>
        <input type="text" name="username" required><br>

        <label>Password</label><br>
        <input type="password" name="password" required><br>

        <button type="submit" name="login">Login</button>
    </form>

    <p><b>Akun demo:</b></p>
    <p>Admin: admin / 123</p>
    <p>Kasir: kasir / 123</p>

<?php else: ?>

    <!-- ================= HEADER ================= -->
    <h1>Mini Kasir OOP</h1>
    <p>
        Login sebagai: <b><?php echo $_SESSION['username']; ?></b>
        (<?php echo $_SESSION['role']; ?>)
        | <a href="?logout=1">Logout</a>
    </p>

    <?php if ($pesan != ""): ?>
        <div class="<?php echo $jenisPesan; ?>">
            <?php echo $pesan; ?>
        </div>
    <?php endif; ?>

    <?php if ($_SESSION['role'] == "Admin"): ?>

        <!-- ================= ADMIN ================= -->
        <h2>Dashboard Admin</h2>

        <div class="menu-box">
            <h3>1. Kelola Menu</h3>
            <form method="post">
                <input type="text" name="nama" placeholder="Nama menu" required>
                <input type="number" name="harga" placeholder="Harga" required>
                <input type="number" name="stok" placeholder="Stok" required>
                <button type="submit" name="tambah_menu">Tambah Menu</button>
            </form>
        </div>

        <div class="menu-box">
            <h3>2. Kelola Stok</h3>
            <form method="post">
                <select name="id_menu">
                    <?php foreach ($_SESSION['menu'] as $menu): ?>
                        <option value="<?php echo $menu->getId(); ?>">
                            <?php echo $menu->getNama(); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="number" name="jumlah_stok" placeholder="Jumlah stok" required>
                <button type="submit" name="tambah_stok">Tambah Stok</button>
            </form>
        </div>

        <h3>Daftar Menu</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>Harga</th>
                <th>Stok</th>
            </tr>

            <?php foreach ($_SESSION['menu'] as $menu): ?>
                <tr>
                    <td><?php echo $menu->getId(); ?></td>
                    <td><?php echo $menu->getNama(); ?></td>
                    <td>Rp <?php echo $menu->getHarga(); ?></td>
                    <td><?php echo $menu->getStok(); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- ================= LAPORAN ================= -->
        <h2 style="margin-top:30px;">3. Laporan Transaksi</h2>

        <?php if (count($_SESSION['transaksi']) == 0): ?>
            <p>Belum ada transaksi.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>No</th>
                    <th>Menu</th>
                    <th>Jumlah</th>
                    <th>Total</th>
                    <th>Uang</th>
                    <th>Kembalian</th>
                    <th>Waktu</th>
                </tr>

                <?php $no = 1; ?>
                <?php foreach ($_SESSION['transaksi'] as $transaksi): ?>
                    <tr>
                        <td><?php echo $no; ?></td>
                        <td><?php echo $transaksi['nama']; ?></td>
                        <td><?php echo $transaksi['jumlah']; ?></td>
                        <td>Rp <?php echo $transaksi['total']; ?></td>
                        <td>Rp <?php echo $transaksi['uang']; ?></td>
                        <td>Rp <?php echo $transaksi['kembalian']; ?></td>
                        <td><?php echo $transaksi['waktu']; ?></td>
                    </tr>
                    <?php $no++; ?>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

    <?php else: ?>

        <!-- ================= KASIR ================= -->
        <h2>Dashboard Kasir</h2>

        <h3>Daftar Menu</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>Harga</th>
                <th>Stok</th>
            </tr>

            <?php foreach ($_SESSION['menu'] as $menu): ?>
                <tr>
                    <td><?php echo $menu->getId(); ?></td>
                    <td><?php echo $menu->getNama(); ?></td>
                    <td>Rp <?php echo $menu->getHarga(); ?></td>
                    <td><?php echo $menu->getStok(); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- ================= PESANAN ================= -->
        <div class="menu-box">
            <h3>Input Pesanan</h3>
            <form method="post">
                <select name="id_menu">
                    <?php foreach ($_SESSION['menu'] as $menu): ?>
                        <option value="<?php echo $menu->getId(); ?>">
                            <?php echo $menu->getNama(); ?> - Rp <?php echo $menu->getHarga(); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="number" name="jumlah" placeholder="Jumlah" required>
                <button type="submit" name="buat_pesanan">Pesan</button>
            </form>
        </div>

        <?php if (isset($_SESSION['pesanan'])): ?>
            <?php $pesanan = $_SESSION['pesanan']; ?>

            <div class="menu-box">
                <h3>Pembayaran</h3>
                <p>Menu: <?php echo $pesanan->getMenu()->getNama(); ?></p>
                <p>Jumlah: <?php echo $pesanan->getJumlah(); ?></p>
                <p>Total: <b>Rp <?php echo $pesanan->getTotal(); ?></b></p>

                <form method="post">
                    <input type="number" name="uang" placeholder="Uang pembayaran" required>
                    <button type="submit" name="bayar">Bayar</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($strukTerakhir != null && $pesan == "Pembayaran berhasil."): ?>
            <div class="struk">
                <h2>STRUK PEMBAYARAN</h2>
                <p>Mini Kasir OOP</p>
                <hr>
                <p>Menu: <?php echo $strukTerakhir['nama']; ?></p>
                <p>Jumlah: <?php echo $strukTerakhir['jumlah']; ?></p>
                <p>Total: Rp <?php echo $strukTerakhir['total']; ?></p>
                <p>Bayar: Rp <?php echo $strukTerakhir['uang']; ?></p>
                <p>Kembalian: Rp <?php echo $strukTerakhir['kembalian']; ?></p>
                <p>Waktu: <?php echo $strukTerakhir['waktu']; ?></p>
                <hr>
                <button onclick="window.print()">Print Struk</button>
            </div>
        <?php endif; ?>

    <?php endif; ?>

<?php endif; ?>

</div>
</body>
</html>
