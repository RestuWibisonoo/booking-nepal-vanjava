<?php
session_start();
include '../config.php';

// Check login and admin role
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] != 'admin') {
    die("Unauthorized");
}

$homestay_id = intval($_GET['id']);

// Get homestay details
$query = "SELECT * FROM homestays WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $homestay_id);
$stmt->execute();
$result = $stmt->get_result();
$homestay = $result->fetch_assoc();

if (!$homestay) {
    die("Homestay tidak ditemukan");
}

// Get all owners for dropdown
$owners_query = "SELECT id, username FROM users WHERE role = 'owner'";
$owners_result = $conn->query($owners_query);
?>

<input type="hidden" name="id" value="<?php echo $homestay['id']; ?>">

<div class="mb-3">
    <label for="edit_name" class="form-label">Nama Homestay</label>
    <input type="text" class="form-control" id="edit_name" name="name" 
           value="<?php echo htmlspecialchars($homestay['name']); ?>" required>
</div>

<div class="mb-3">
    <label for="edit_address" class="form-label">Alamat</label>
    <textarea class="form-control" id="edit_address" name="address" rows="3" required><?php echo htmlspecialchars($homestay['address']); ?></textarea>
</div>

<div class="mb-3">
    <label for="edit_owner_id" class="form-label">Pemilik</label>
    <select class="form-control" id="edit_owner_id" name="owner_id" required>
        <option value="">Pilih Pemilik</option>
        <?php while ($owner = $owners_result->fetch_assoc()): ?>
            <option value="<?php echo $owner['id']; ?>" 
                <?php echo $owner['id'] == $homestay['owner_id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($owner['username']); ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>

<div class="mb-3">
    <label for="edit_payment_url" class="form-label">Payment URL</label>
    <input type="url" class="form-control" id="edit_payment_url" name="payment_url" 
           value="<?php echo htmlspecialchars($homestay['payment_url']); ?>"
           placeholder="https://nepal-vanjava.com/pembayaran-homestay-...">
</div>