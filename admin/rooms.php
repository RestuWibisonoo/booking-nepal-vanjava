<?php
session_start();
include '../config.php';
include 'sidebar.php';

// Check login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['admin_id'];
$user_role = $_SESSION['admin_role'];
$homestay_id = isset($_GET['homestay_id']) ? intval($_GET['homestay_id']) : 0;

// Handle form actions
if (isset($_POST['add_room'])) {
    $homestay_id = intval($_POST['homestay_id']);
    $room_number = intval($_POST['room_number']);
    $room_name = $conn->real_escape_string($_POST['room_name']);
    $code_room = $conn->real_escape_string($_POST['code_room']);
    $capacity = intval($_POST['capacity']);
    
    // Check if user has access to this homestay
    if ($user_role == 'owner') {
        $check_query = "SELECT id FROM homestays WHERE id = ? AND owner_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ii", $homestay_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            die("Unauthorized access");
        }
    }
    
    $query = "INSERT INTO rooms (homestay_id, room_number, room_name, code_room, capacity) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iissi", $homestay_id, $room_number, $room_name, $code_room, $capacity);
    
    if ($stmt->execute()) {
        $success = "Kamar berhasil ditambahkan!";
    } else {
        $error = "Error: " . $stmt->error;
    }
}

if (isset($_POST['update_room'])) {
    $room_id = intval($_POST['room_id']);
    $room_number = intval($_POST['room_number']);
    $room_name = $conn->real_escape_string($_POST['room_name']);
    $code_room = $conn->real_escape_string($_POST['code_room']);
    $capacity = intval($_POST['capacity']);
    
    $query = "UPDATE rooms SET room_number = ?, room_name = ?, code_room = ?, capacity = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issii", $room_number, $room_name, $code_room, $capacity, $room_id);
    
    if ($stmt->execute()) {
        $success = "Kamar berhasil diupdate!";
    } else {
        $error = "Error: " . $stmt->error;
    }
}

if (isset($_GET['delete_room'])) {
    $room_id = intval($_GET['delete_room']);
    
    $query = "DELETE FROM rooms WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $room_id);
    
    if ($stmt->execute()) {
        $success = "Kamar berhasil dihapus!";
    } else {
        $error = "Error: " . $stmt->error;
    }
}

// Get homestays for dropdown (based on role)
if ($user_role == 'admin') {
    $homestays_query = "SELECT id, name FROM homestays ORDER BY name";
} else {
    $homestays_query = "SELECT id, name FROM homestays WHERE owner_id = $user_id ORDER BY name";
}
$homestays_result = $conn->query($homestays_query);

// Get rooms for selected homestay or all rooms
if ($homestay_id > 0) {
    // Check access for owner
    if ($user_role == 'owner') {
        $check_query = "SELECT id FROM homestays WHERE id = ? AND owner_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ii", $homestay_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            die("Unauthorized access");
        }
    }
    
    $rooms_query = "SELECT r.*, h.name as homestay_name 
                   FROM rooms r 
                   JOIN homestays h ON r.homestay_id = h.id 
                   WHERE r.homestay_id = ? 
                   ORDER BY r.room_number";
    $stmt = $conn->prepare($rooms_query);
    $stmt->bind_param("i", $homestay_id);
    $stmt->execute();
    $rooms_result = $stmt->get_result();
} else {
    if ($user_role == 'admin') {
        $rooms_query = "SELECT r.*, h.name as homestay_name 
                       FROM rooms r 
                       JOIN homestays h ON r.homestay_id = h.id 
                       ORDER BY h.name, r.room_number";
        $rooms_result = $conn->query($rooms_query);
    } else {
        $rooms_query = "SELECT r.*, h.name as homestay_name 
                       FROM rooms r 
                       JOIN homestays h ON r.homestay_id = h.id 
                       WHERE h.owner_id = $user_id 
                       ORDER BY h.name, r.room_number";
        $rooms_result = $conn->query($rooms_query);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kamar - Admin Nepal Van Java</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Kelola Kamar</h2>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                        <i class="fas fa-plus"></i> Tambah Kamar
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Homestay Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <label for="homestay_id" class="form-label">Filter by Homestay</label>
                            <select class="form-control" id="homestay_id" name="homestay_id" onchange="this.form.submit()">
                                <option value="0">Semua Homestay</option>
                                <?php while ($homestay = $homestays_result->fetch_assoc()): ?>
                                    <option value="<?php echo $homestay['id']; ?>" 
                                        <?php echo $homestay['id'] == $homestay_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($homestay['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Daftar Kamar</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Kode Kamar</th>
                                    <th>Nama Kamar</th>
                                    <th>Nomor Kamar</th>
                                    <th>Homestay</th>
                                    <th>Kapasitas</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($room = $rooms_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $room['code_room']; ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($room['room_name']); ?></td>
                                    <td><?php echo $room['room_number']; ?></td>
                                    <td><?php echo htmlspecialchars($room['homestay_name']); ?></td>
                                    <td><?php echo $room['capacity']; ?> orang</td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" 
                                                onclick="editRoom(<?php echo $room['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete_room=<?php echo $room['id']; ?>&homestay_id=<?php echo $homestay_id; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Yakin hapus kamar ini?')">
                                            <i class="fas fa-trash"></i>
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

    <!-- Add Room Modal -->
    <div class="modal fade" id="addRoomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kamar Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="homestay_id" class="form-label">Homestay</label>
                            <select class="form-control" id="homestay_id" name="homestay_id" required>
                                <option value="">Pilih Homestay</option>
                                <?php 
                                $homestays_result->data_seek(0); // Reset pointer
                                while ($homestay = $homestays_result->fetch_assoc()): ?>
                                    <option value="<?php echo $homestay['id']; ?>" 
                                        <?php echo $homestay['id'] == $homestay_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($homestay['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="room_number" class="form-label">Nomor Kamar</label>
                            <input type="number" class="form-control" id="room_number" name="room_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="room_name" class="form-label">Nama Kamar</label>
                            <input type="text" class="form-control" id="room_name" name="room_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="code_room" class="form-label">Kode Kamar</label>
                            <input type="text" class="form-control" id="code_room" name="code_room" required>
                            <small class="text-muted">Contoh: DP001, DP002 (unik untuk setiap kamar)</small>
                        </div>
                        <div class="mb-3">
                            <label for="capacity" class="form-label">Kapasitas</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_room" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Room Modal -->
    <div class="modal fade" id="editRoomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Kamar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body" id="editRoomForm">
                        <!-- Form will be loaded via AJAX -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_room" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function editRoom(roomId) {
        fetch(`get_room_details.php?id=${roomId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('editRoomForm').innerHTML = data;
                new bootstrap.Modal(document.getElementById('editRoomModal')).show();
            });
    }
    </script>
</body>
</html>