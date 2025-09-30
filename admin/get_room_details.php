<?php
session_start();
include '../config.php';

// Check login
if (!isset($_SESSION['admin_logged_in'])) {
    die("Unauthorized");
}

$room_id = intval($_GET['id']);

// Get room details
$query = "SELECT r.*, h.name as homestay_name 
          FROM rooms r 
          JOIN homestays h ON r.homestay_id = h.id 
          WHERE r.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();

if (!$room) {
    die("Kamar tidak ditemukan");
}

// Get homestays for dropdown (based on role)
$user_id = $_SESSION['admin_id'];
$user_role = $_SESSION['admin_role'];

if ($user_role == 'admin') {
    $homestays_query = "SELECT id, name FROM homestays ORDER BY name";
} else {
    $homestays_query = "SELECT id, name FROM homestays WHERE owner_id = $user_id ORDER BY name";
}
$homestays_result = $conn->query($homestays_query);
?>

<input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">

<div class="mb-3">
    <label for="edit_homestay_id" class="form-label">Homestay</label>
    <select class="form-control" id="edit_homestay_id" name="homestay_id" required>
        <option value="">Pilih Homestay</option>
        <?php while ($homestay = $homestays_result->fetch_assoc()): ?>
            <option value="<?php echo $homestay['id']; ?>" 
                <?php echo $homestay['id'] == $room['homestay_id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($homestay['name']); ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>

<div class="mb-3">
    <label for="edit_room_number" class="form-label">Nomor Kamar</label>
    <input type="number" class="form-control" id="edit_room_number" name="room_number" 
           value="<?php echo $room['room_number']; ?>" required>
</div>

<div class="mb-3">
    <label for="edit_room_name" class="form-label">Nama Kamar</label>
    <input type="text" class="form-control" id="edit_room_name" name="room_name" 
           value="<?php echo htmlspecialchars($room['room_name']); ?>" required>
</div>

<div class="mb-3">
    <label for="edit_code_room" class="form-label">Kode Kamar</label>
    <input type="text" class="form-control" id="edit_code_room" name="code_room" 
           value="<?php echo htmlspecialchars($room['code_room']); ?>" required>
</div>

<div class="mb-3">
    <label for="edit_capacity" class="form-label">Kapasitas</label>
    <input type="number" class="form-control" id="edit_capacity" name="capacity" 
           value="<?php echo $room['capacity']; ?>" min="1" required>
</div>