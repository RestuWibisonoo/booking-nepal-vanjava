<?php
session_start();
include '../config.php';
include 'sidebar.php';

// Check login and admin role
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] != 'admin') {
    header('Location: index.php');
    exit();
}

// Handle form actions
if (isset($_POST['add_homestay'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $address = $conn->real_escape_string($_POST['address']);
    $owner_id = intval($_POST['owner_id']);
    $payment_url = $conn->real_escape_string($_POST['payment_url']);
    
    $query = "INSERT INTO homestays (name, address, owner_id, payment_url) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssis", $name, $address, $owner_id, $payment_url);
    
    if ($stmt->execute()) {
        $success = "Homestay berhasil ditambahkan!";
    } else {
        $error = "Error: " . $stmt->error;
    }
}

if (isset($_POST['update_homestay'])) {
    $id = intval($_POST['id']);
    $name = $conn->real_escape_string($_POST['name']);
    $address = $conn->real_escape_string($_POST['address']);
    $owner_id = intval($_POST['owner_id']);
    $payment_url = $conn->real_escape_string($_POST['payment_url']);
    
    $query = "UPDATE homestays SET name = ?, address = ?, owner_id = ?, payment_url = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssisi", $name, $address, $owner_id, $payment_url, $id);
    
    if ($stmt->execute()) {
        $success = "Homestay berhasil diupdate!";
    } else {
        $error = "Error: " . $stmt->error;
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    $query = "DELETE FROM homestays WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $success = "Homestay berhasil dihapus!";
    } else {
        $error = "Error: " . $stmt->error;
    }
}

// Get all homestays with owner info
$homestays_query = "SELECT h.*, u.username as owner_name 
                   FROM homestays h 
                   JOIN users u ON h.owner_id = u.id 
                   ORDER BY h.created_at DESC";
$homestays_result = $conn->query($homestays_query);

// Get all owners for dropdown
$owners_query = "SELECT id, username FROM users WHERE role = 'owner'";
$owners_result = $conn->query($owners_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Homestay - Admin Nepal Van Java</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Kelola Homestay</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHomestayModal">
                    <i class="fas fa-plus"></i> Tambah Homestay
                </button>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5>Daftar Homestay</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Homestay</th>
                                    <th>Alamat</th>
                                    <th>Pemilik</th>
                                    <th>Jumlah Kamar</th>
                                    <th>Payment URL</th>
                                    <th>Tanggal Dibuat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($homestay = $homestays_result->fetch_assoc()): 
                                    // Count rooms for this homestay
                                    $rooms_count_query = "SELECT COUNT(*) as count FROM rooms WHERE homestay_id = ?";
                                    $stmt = $conn->prepare($rooms_count_query);
                                    $stmt->bind_param("i", $homestay['id']);
                                    $stmt->execute();
                                    $rooms_count = $stmt->get_result()->fetch_assoc()['count'];
                                ?>
                                <tr>
                                    <td><?php echo $homestay['id']; ?></td>
                                    <td><?php echo htmlspecialchars($homestay['name']); ?></td>
                                    <td><?php echo htmlspecialchars($homestay['address']); ?></td>
                                    <td><?php echo htmlspecialchars($homestay['owner_name']); ?></td>
                                    <td><?php echo $rooms_count; ?> kamar</td>
                                    <td>
                                        <?php if ($homestay['payment_url']): ?>
                                            <a href="<?php echo $homestay['payment_url']; ?>" target="_blank">
                                                Lihat
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($homestay['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" 
                                                onclick="editHomestay(<?php echo $homestay['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete=<?php echo $homestay['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Yakin hapus homestay ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <a href="rooms.php?homestay_id=<?php echo $homestay['id']; ?>" 
                                           class="btn btn-sm btn-info">
                                            <i class="fas fa-bed"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <!-- Add Homestay Modal -->
    <div class="modal fade" id="addHomestayModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Homestay Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Homestay</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Alamat</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="owner_id" class="form-label">Pemilik</label>
                            <select class="form-control" id="owner_id" name="owner_id" required>
                                <option value="">Pilih Pemilik</option>
                                <?php while ($owner = $owners_result->fetch_assoc()): ?>
                                    <option value="<?php echo $owner['id']; ?>">
                                        <?php echo htmlspecialchars($owner['username']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="payment_url" class="form-label">Payment URL</label>
                            <input type="url" class="form-control" id="payment_url" name="payment_url" 
                                   placeholder="https://nepal-vanjava.com/pembayaran-homestay-...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_homestay" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Homestay Modal -->
    <div class="modal fade" id="editHomestayModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Homestay</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body" id="editHomestayForm">
                        <!-- Form will be loaded via AJAX -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_homestay" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function editHomestay(homestayId) {
        fetch(`get_homestay_details.php?id=${homestayId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('editHomestayForm').innerHTML = data;
                new bootstrap.Modal(document.getElementById('editHomestayModal')).show();
            });
    }
    </script>
</body>
</html>