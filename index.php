    <?php

require_once "classes.php";

// Nama session baru agar session dari versi lama tidak ikut terbaca.
session_name("kasir_pbo_v2");
session_start();

if (isset($_GET["reset"])) {
    $_SESSION = [];
    session_destroy();
    header("Location: index.php");
    exit;
}

if (isset($_GET["logout"])) {
    // Hanya menghapus data login.
    // Data menu dan transaksi tetap disimpan agar
    // perubahan kasir/admin tetap terlihat saat berganti role.
    unset($_SESSION["role"]);
    unset($_SESSION["username"]);
    unset($_SESSION["nama"]);
    unset($_SESSION["pendingOrder"]);

    header("Location: index.php");
    exit;
}

if (!isset($_SESSION["menu"])) {
    $_SESSION["menu"] = [
        new Menu("Nasi Goreng", 15000, 20, "Makanan"),
        new Menu("Mie Goreng", 14000, 15, "Makanan"),
        new Menu("Es Teh", 5000, 30, "Minuman"),
        new Menu("Kopi", 8000, 20, "Minuman")
    ];
}

if (!isset($_SESSION["transaksi"])) {
    $_SESSION["transaksi"] = [];
}

$akun = [
    "admin" => [
        "password" => "12345",
        "role" => "admin",
        "nama" => "Administrator"
    ],
    "kasir" => [
        "password" => "12345",
        "role" => "kasir",
        "nama" => "Kasir"
    ]
];

$error = "";
$success = "";
$receipt = null;

// Login
if (!isset($_SESSION["role"]) && isset($_POST["login"])) {
    $username = $_POST["username"];
    $password = $_POST["password"];

    if (isset($akun[$username]) && $akun[$username]["password"] == $password) {
        $_SESSION["username"] = $username;
        $_SESSION["nama"] = $akun[$username]["nama"];
        $_SESSION["role"] = $akun[$username]["role"];
        $success = "Login berhasil.";
    } else {
        $error = "Akun tidak valid, silakan lakukan validasi ulang.";
    }
}

// Object user berdasarkan role.
$user = null;

if (isset($_SESSION["role"])) {
    if ($_SESSION["role"] == "admin") {
        $user = new Admin($_SESSION["username"], $_SESSION["nama"]);
    } else {
        $user = new Kasir($_SESSION["username"], $_SESSION["nama"]);
    }
}

// Kasir: validasi pesanan.
if ($user != null && $user->getRole() == "Kasir" && isset($_POST["buat_pesanan"])) {
    try {
        $indexMenu = $_POST["menu"];
        $jumlah = $_POST["jumlah"];

        if (!isset($_SESSION["menu"][$indexMenu])) {
            throw new Exception("Menu yang dipilih tidak tersedia!");
        }

        $nomorPesanan = count($_SESSION["transaksi"]) + 1;

        $pesanan = new Pesanan(
            $nomorPesanan,
            $_SESSION["menu"][$indexMenu],
            $jumlah
        );

        $_SESSION["pendingOrder"] = [
            "indexMenu" => $indexMenu,
            "pesanan" => $pesanan
        ];

        $success = "Pesanan valid. Silakan masukkan pembayaran.";
    } catch (Exception $e) {
        $error = "Pesanan tidak valid, input ulang! " . $e->getMessage();
    }
}

// Kasir: pembayaran tunai.
if ($user != null && $user->getRole() == "Kasir" && isset($_POST["bayar_pesanan"])) {
    if (isset($_SESSION["pendingOrder"])) {
        $pesanan = $_SESSION["pendingOrder"]["pesanan"];
        $indexMenu = $_SESSION["pendingOrder"]["indexMenu"];
        $jumlahBayar = $_POST["jumlah_bayar"];

        $payment = new PembayaranTunai($pesanan->getTotal());

        if (prosesPembayaran($payment, $jumlahBayar)) {
            try {
                $_SESSION["menu"][$indexMenu]->kurangiStok($pesanan->getJumlah());

                $_SESSION["transaksi"][] = [
                    "nomor" => $pesanan->getNomorPesanan(),
                    "menu" => $pesanan->getMenu()->getNama(),
                    "jumlah" => $pesanan->getJumlah(),
                    "total" => $pesanan->getTotal(),
                    "bayar" => $payment->getJumlahBayar(),
                    "kembalian" => $payment->getKembalian(),
                    "status" => $payment->getStatus(),
                    "waktu" => date("Y-m-d H:i:s")
                ];

                $receipt = [
                    "pesanan" => $pesanan,
                    "payment" => $payment
                ];

                unset($_SESSION["pendingOrder"]);
                $success = "Pembayaran berhasil. Pesanan selesai.";
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = "Pembayaran salah, ulangi! " . $payment->getStatus();
        }
    } else {
        $error = "Belum ada pesanan yang valid.";
    }
}

// Admin: tambah menu.
if ($user != null && $user->getRole() == "Admin" && isset($_POST["tambah_menu"])) {
    try {
        $menuBaru = new Menu(
            $_POST["nama_menu"],
            $_POST["harga_menu"],
            $_POST["stok_menu"],
            $_POST["kategori_menu"]
        );

        $_SESSION["menu"][] = $menuBaru;
        $success = "Menu berhasil ditambahkan.";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Admin: tambah stok.
if ($user != null && $user->getRole() == "Admin" && isset($_POST["tambah_stok"])) {
    try {
        $indexMenu = $_POST["menu_stok"];
        $jumlahStok = $_POST["jumlah_stok"];

        if (!isset($_SESSION["menu"][$indexMenu])) {
            throw new Exception("Menu tidak ditemukan!");
        }

        $_SESSION["menu"][$indexMenu]->tambahStok($jumlahStok);
        $success = "Stok berhasil ditambahkan.";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

function rupiah($angka)
{
    return "Rp " . number_format($angka, 0, ',', '.');
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Kasir - Mini Project PBO</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f2f5f8; margin: 0; color: #222; }
        .header { background: #0b3d66; color: white; padding: 20px; }
        .header h1 { margin: 0; }
        .container { max-width: 1050px; margin: 25px auto; padding: 0 15px 40px; }
        .card { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        input, select, button { width: 100%; padding: 10px; margin-top: 6px; margin-bottom: 12px; box-sizing: border-box; }
        button { border: none; background: #0b3d66; color: white; cursor: pointer; border-radius: 6px; }
        button:hover { opacity: 0.9; }
        .success { background: #dff5e3; padding: 12px; border-radius: 6px; margin-bottom: 15px; }
        .error { background: #ffdede; padding: 12px; border-radius: 6px; margin-bottom: 15px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #eef3f7; }
        .logout { display: inline-block; background: #0b3d66; color: white; padding: 10px 18px; text-decoration: none; border-radius: 5px; }
        .receipt { border: 1px dashed #555; padding: 15px; }
        @media (max-width: 700px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="header">
    <h1>Sistem Kasir Sederhana</h1>
    <p>Mini Project Pemrograman Berorientasi Objek</p>
</div>

<div class="container">

<?php if ($user == null) { ?>

    <div class="card">
        <h2>Login</h2>

        <?php if ($error != "") { ?>
            <div class="error"><?php echo $error; ?></div>
        <?php } ?>

        <form method="post">
            <label>Username</label>
            <input type="text" name="username" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit" name="login">Login</button>
        </form>

        <p><strong>Akun demo:</strong></p>
        <p>Admin: admin / 12345</p>
        <p>Kasir: kasir / 12345</p>
    </div>

<?php } else { ?>

    <div class="card">
        <h2>Dashboard <?php echo $user->getRole(); ?></h2>
        <p>Login sebagai: <strong><?php echo $user->getNama(); ?></strong></p>
        <a class="logout" href="index.php?logout=1">Logout</a>
    </div>

    <?php if ($success != "") { ?>
        <div class="success"><?php echo $success; ?></div>
    <?php } ?>

    <?php if ($error != "") { ?>
        <div class="error"><?php echo $error; ?></div>
    <?php } ?>

    <?php if ($receipt != null) { ?>
        <div class="card">
            <h2>Output / Struk</h2>
            <div class="receipt">
                <p><strong>Nomor Pesanan:</strong> #<?php echo $receipt["pesanan"]->getNomorPesanan(); ?></p>
                <p><strong>Menu:</strong> <?php echo $receipt["pesanan"]->getMenu()->getNama(); ?></p>
                <p><strong>Jumlah:</strong> <?php echo $receipt["pesanan"]->getJumlah(); ?></p>
                <p><strong>Total Pesanan:</strong> <?php echo rupiah($receipt["pesanan"]->getTotal()); ?></p>
                <p><strong>Pembayaran:</strong> <?php echo rupiah($receipt["payment"]->getJumlahBayar()); ?></p>
                <p><strong>Kembalian:</strong> <?php echo rupiah($receipt["payment"]->getKembalian()); ?></p>
                <p><strong>Status:</strong> <?php echo $receipt["payment"]->getStatus(); ?></p>
            </div>
        </div>
    <?php } ?>

    <?php if ($user->getRole() == "Kasir") { ?>

        <div class="card">
            <h2>Menu Tersedia</h2>
            <table>
                <tr><th>No</th><th>Menu</th><th>Kategori</th><th>Harga</th><th>Stok</th></tr>
                <?php foreach ($_SESSION["menu"] as $index => $menu) { ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo $menu->getNama(); ?></td>
                        <td><?php echo $menu->getKategori(); ?></td>
                        <td><?php echo rupiah($menu->getHarga()); ?></td>
                        <td><?php echo $menu->getStok(); ?></td>
                    </tr>
                <?php } ?>
            </table>
        </div>

        <div class="grid">
            <div class="card">
                <h2>Input Pesanan</h2>
                <form method="post">
                    <label>Pilih Menu</label>
                    <select name="menu" required>
                        <?php foreach ($_SESSION["menu"] as $index => $menu) { ?>
                            <option value="<?php echo $index; ?>"><?php echo ($index + 1) . ". " . $menu->getNama(); ?></option>
                        <?php } ?>
                    </select>

                    <label>Jumlah</label>
                    <input type="number" name="jumlah" min="1" required>

                    <button type="submit" name="buat_pesanan">Tambah Pesanan</button>
                </form>
            </div>

            <div class="card">
                <h2>Pembayaran</h2>
                <?php if (isset($_SESSION["pendingOrder"])) { ?>
                    <p><strong>Menu:</strong> <?php echo $_SESSION["pendingOrder"]["pesanan"]->getMenu()->getNama(); ?></p>
                    <p><strong>Jumlah:</strong> <?php echo $_SESSION["pendingOrder"]["pesanan"]->getJumlah(); ?></p>
                    <p><strong>Total Pesanan:</strong> <?php echo rupiah($_SESSION["pendingOrder"]["pesanan"]->getTotal()); ?></p>

                    <form method="post">
                        <label>Jumlah Pembayaran</label>
                        <input type="number" name="jumlah_bayar" min="1" required>
                        <button type="submit" name="bayar_pesanan">Bayar</button>
                    </form>
                <?php } else { ?>
                    <p>Input pembayaran akan muncul setelah pesanan dinyatakan valid.</p>
                <?php } ?>
            </div>
        </div>

        <div class="card">
            <h2>Laporan Transaksi</h2>
            <?php if (count($_SESSION["transaksi"]) == 0) { ?>
                <p>Belum ada transaksi.</p>
            <?php } else { ?>
                <table>
                    <tr><th>No</th><th>Menu</th><th>Jumlah</th><th>Total</th><th>Pembayaran</th><th>Kembalian</th></tr>
                    <?php foreach ($_SESSION["transaksi"] as $transaksi) { ?>
                        <tr>
                            <td>#<?php echo $transaksi["nomor"]; ?></td>
                            <td><?php echo $transaksi["menu"]; ?></td>
                            <td><?php echo $transaksi["jumlah"]; ?></td>
                            <td><?php echo rupiah($transaksi["total"]); ?></td>
                            <td><?php echo rupiah($transaksi["bayar"]); ?></td>
                            <td><?php echo rupiah($transaksi["kembalian"]); ?></td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } ?>
        </div>

    <?php } else { ?>

        <div class="card">
            <h2>Tampilkan Menu</h2>
            <table>
                <tr><th>No</th><th>Menu</th><th>Kategori</th><th>Harga</th><th>Stok</th></tr>
                <?php foreach ($_SESSION["menu"] as $index => $menu) { ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo $menu->getNama(); ?></td>
                        <td><?php echo $menu->getKategori(); ?></td>
                        <td><?php echo rupiah($menu->getHarga()); ?></td>
                        <td><?php echo $menu->getStok(); ?></td>
                    </tr>
                <?php } ?>
            </table>
        </div>

        <div class="grid">
            <div class="card">
                <h2>Kelola Menu</h2>
                <form method="post">
                    <label>Nama Menu</label>
                    <input type="text" name="nama_menu" required>

                    <label>Harga</label>
                    <input type="number" name="harga_menu" min="1" required>

                    <label>Stok</label>
                    <input type="number" name="stok_menu" min="0" required>

                    <label>Kategori</label>
                    <input type="text" name="kategori_menu" required>

                    <button type="submit" name="tambah_menu">Tambah Menu</button>
                </form>
            </div>

            <div class="card">
                <h2>Kelola Stok</h2>
                <form method="post">
                    <label>Pilih Menu</label>
                    <select name="menu_stok" required>
                        <?php foreach ($_SESSION["menu"] as $index => $menu) { ?>
                            <option value="<?php echo $index; ?>"><?php echo ($index + 1) . ". " . $menu->getNama(); ?></option>
                        <?php } ?>
                    </select>

                    <label>Tambahan Stok</label>
                    <input type="number" name="jumlah_stok" min="1" required>

                    <button type="submit" name="tambah_stok">Tambah Stok</button>
                </form>
            </div>
        </div>

        <div class="card">
            <h2>Laporan</h2>
            <?php if (count($_SESSION["transaksi"]) == 0) { ?>
                <p>Belum ada transaksi.</p>
            <?php } else {
                $totalPendapatan = 0;
                foreach ($_SESSION["transaksi"] as $transaksi) {
                    $totalPendapatan += $transaksi["total"];
                }
            ?>
                <p><strong>Total transaksi:</strong> <?php echo count($_SESSION["transaksi"]); ?></p>
                <p><strong>Total pendapatan:</strong> <?php echo rupiah($totalPendapatan); ?></p>

                <table>
                    <tr><th>No</th><th>Menu</th><th>Jumlah</th><th>Total</th><th>Pembayaran</th><th>Kembalian</th><th>Waktu</th></tr>
                    <?php foreach ($_SESSION["transaksi"] as $transaksi) { ?>
                        <tr>
                            <td>#<?php echo $transaksi["nomor"]; ?></td>
                            <td><?php echo $transaksi["menu"]; ?></td>
                            <td><?php echo $transaksi["jumlah"]; ?></td>
                            <td><?php echo rupiah($transaksi["total"]); ?></td>
                            <td><?php echo rupiah($transaksi["bayar"]); ?></td>
                            <td><?php echo rupiah($transaksi["kembalian"]); ?></td>
                            <td><?php echo $transaksi["waktu"]; ?></td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } ?>
        </div>

    <?php } ?>

<?php } ?>

</div>
</body>
</html>
