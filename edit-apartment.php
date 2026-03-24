<?php
require_once 'auth_check.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM apartments WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$apartment = $stmt->fetch();

if (!$apartment) {
    header("Location: dashboard.php");
    exit;
}

if ($apartment['status'] === 'approved') {
    header("Location: dashboard.php?msg=Cannot+edit+approved+listings.");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $type = $_POST['type'];
    $price = $_POST['price'];
    $beds = $_POST['beds'];
    $baths = $_POST['baths'];
    $address = $_POST['address'];
    $description = $_POST['description'];
    $features = isset($_POST['features']) ? implode(', ', $_POST['features']) : '';

    $stmt = $pdo->prepare("UPDATE apartments SET title = ?, type = ?, price = ?, bedrooms = ?, baths = ?, address = ?, description = ?, features = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$title, $type, $price, $beds, $baths, $address, $description, $features, $id, $user_id]);
    
    header("Location: dashboard.php?msg=Listing+Updated");
    exit;
}

$selected_features = explode(', ', $apartment['features']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <title>Edit Apartment - MyHomeMyLand.LK</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

<?php include 'get-theme.php'; ?>

</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="logo" style="text-decoration: none;"> MyHomeMyLand.LK </a>
        <div class="nav-links">
            <a href="dashboard.php" class="btn-primary" style="padding: 0.4rem 1rem;">&larr; Back to Dashboard</a>
        </div>
    </nav>
    <main class="page-container form-page">
        <div class="form-wrapper">
            <h2>Edit Listing</h2>
            <form method="POST" class="listing-form">
                <div class="input-group">
                    <label>Listing Title</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($apartment['title']) ?>" required>
                </div>
                <div class="input-row">
                    <div class="input-group">
                        <label>Property Type</label>
                        <select name="type" required>
                            <option value="Apartment" <?= $apartment['type'] == 'Apartment' ? 'selected' : '' ?>>Apartment</option>
                            <option value="House" <?= $apartment['type'] == 'House' ? 'selected' : '' ?>>House</option>
                            <option value="Villa" <?= $apartment['type'] == 'Villa' ? 'selected' : '' ?>>Villa</option>
                            <option value="Commercial" <?= $apartment['type'] == 'Commercial' ? 'selected' : '' ?>>Commercial</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Monthly Rent (Rs.)</label>
                        <input type="number" name="price" value="<?= htmlspecialchars($apartment['price']) ?>" required>
                    </div>
                </div>
                <div class="input-row">
                    <div class="input-group">
                        <label>Bedrooms</label>
                        <select name="beds" required>
                            <option value="1" <?= $apartment['bedrooms'] == '1' ? 'selected' : '' ?>>1 Bedroom</option>
                            <option value="2" <?= $apartment['bedrooms'] == '2' ? 'selected' : '' ?>>2 Bedrooms</option>
                            <option value="3" <?= $apartment['bedrooms'] == '3' ? 'selected' : '' ?>>3+ Bedrooms</option>
                            <option value="studio" <?= $apartment['bedrooms'] == 'studio' ? 'selected' : '' ?>>Studio</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Bathrooms</label>
                        <select name="baths" required>
                            <option value="1" <?= $apartment['baths'] == '1' ? 'selected' : '' ?>>1 Bath</option>
                            <option value="2" <?= $apartment['baths'] == '2' ? 'selected' : '' ?>>2 Baths</option>
                            <option value="3" <?= $apartment['baths'] == '3' ? 'selected' : '' ?>>3+ Baths</option>
                        </select>
                    </div>
                </div>
                <div class="input-group">
                    <label>Features</label>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.5rem; color: var(--text-secondary);">
                        <?php 
                        $all_feats = ['A/C', 'Pool', 'Gym', 'Parking', 'Furnished'];
                        foreach($all_feats as $f):
                            $checked = in_array($f, $selected_features) ? 'checked' : '';
                        ?>
                            <label><input type="checkbox" name="features[]" value="<?= $f ?>" <?= $checked ?>> <?= $f ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="input-group">
                    <label>Full Address</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($apartment['address']) ?>" required>
                </div>
                <div class="input-group">
                    <label>Description</label>
                    <textarea name="description" rows="4" required><?= htmlspecialchars($apartment['description']) ?></textarea>
                </div>
                <button type="submit" class="btn-primary w-100 mt-3">Save Changes</button>
            </form>
        </div>
    </main>
</body>
</html>
