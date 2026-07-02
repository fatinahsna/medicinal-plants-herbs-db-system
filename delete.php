<?php

require 'includes/db.php'; // provides $conn (mysqli, procedural API)
include 'includes/header.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$plant_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

/* ------------------------------------------------------------
   Handle the actual delete (POST from the confirmation modal)
------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {

    $post_id = isset($_POST['plant_id']) ? (int) $_POST['plant_id'] : 0;
    $plant_name = 'This plant';

    if ($post_id > 0) {

        // Get the plant name (for the alert message) and its images (to remove the files)
        $stmt = mysqli_prepare($conn, "SELECT common_name FROM plants WHERE plant_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $post_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            $plant_name = $row['common_name'];
        }
        mysqli_stmt_close($stmt);

        $imgStmt = mysqli_prepare($conn, "SELECT image_path FROM images WHERE plant_id = ?");
        mysqli_stmt_bind_param($imgStmt, 'i', $post_id);
        mysqli_stmt_execute($imgStmt);
        $imgResult = mysqli_stmt_get_result($imgStmt);
        $filesToRemove = [];
        while ($row = mysqli_fetch_assoc($imgResult)) {
            $filesToRemove[] = $row['image_path'];
        }
        mysqli_stmt_close($imgStmt);

        // Delete the plant row (images are removed automatically via ON DELETE CASCADE)
        $delStmt = mysqli_prepare($conn, "DELETE FROM plants WHERE plant_id = ?");
        mysqli_stmt_bind_param($delStmt, 'i', $post_id);
        mysqli_stmt_execute($delStmt);
        mysqli_stmt_close($delStmt);

        // Clean up the actual image files on disk
        foreach ($filesToRemove as $path) {
            $fullPath = __DIR__ . '/' . $path;
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }
    }

    $safeName = htmlspecialchars($plant_name, ENT_QUOTES);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>PlantMedX</title>
        <link rel="stylesheet" href="assets/css/style.css">
    </head>
    <body>
        <script>
            alert("\"<?php echo $safeName; ?>\" has been deleted.");
            window.location.href = "view.php";
        </script>
        <noscript>
            <p>"<?php echo $safeName; ?>" has been deleted. <a href="view.php">Back to all plants</a></p>
        </noscript>
    </body>
    </html>
    <?php
    exit;
}

/* ------------------------------------------------------------
   GET request — load the plant so we can show it behind the
   confirmation popup, same layout as view_details.php
------------------------------------------------------------ */
$plant = null;
$images = [];

if ($plant_id > 0) {
    $sql = "
        SELECT
            p.plant_id,
            p.category_id,
            p.common_name,
            p.scientific_name,
            p.local_name,
            p.family,
            p.origin,
            p.description,
            p.medicinal_uses,
            p.preparation,
            p.parts_used,
            p.active_compounds,
            p.side_effects,
            p.status,
            c.name AS category_name
        FROM plants p
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE p.plant_id = ?
        LIMIT 1
    ";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $plant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $plant = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($plant) {
        $imgStmt = mysqli_prepare($conn, "SELECT image_path FROM images WHERE plant_id = ? ORDER BY image_id ASC");
        mysqli_stmt_bind_param($imgStmt, 'i', $plant_id);
        mysqli_stmt_execute($imgStmt);
        $imgResult = mysqli_stmt_get_result($imgStmt);
        while ($row = mysqli_fetch_assoc($imgResult)) {
            $images[] = $row['image_path'];
        }
        mysqli_stmt_close($imgStmt);
    }
}

if (empty($images)) {
    $images[] = 'assets/images/plant-placeholder.png';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlantMedX - Delete Plant</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body>

<?php if (!$plant): ?>

    <section class="detail-not-found">
        <i class="bi bi-flower2"></i>
        <h2>Plant not found</h2>
        <p>The plant you're trying to delete doesn't exist or may have already been removed.</p>
        <a href="view.php" class="add-plant-btn"><i class="bi bi-arrow-left"></i> Back to All Plants</a>
    </section>

<?php else: ?>

    <div class="detail-topbar">
        <a href="view_details.php?id=<?php echo (int) $plant['plant_id']; ?>" class="back-arrow" title="Back to plant">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="detail-title">
            <h1><?php echo htmlspecialchars($plant['common_name']); ?> <span class="plant-id-badge">ID: <?php echo (int) $plant['plant_id']; ?></span></h1>
            <?php if (!empty($plant['scientific_name'])): ?>
                <p class="scientific-subtitle"><?php echo htmlspecialchars($plant['scientific_name']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <section class="detail-layout is-blurred">

        <div class="detail-image-col">
            <div class="detail-image-box">
                <img src="<?php echo htmlspecialchars($images[0]); ?>" alt="<?php echo htmlspecialchars($plant['common_name']); ?>">
            </div>
        </div>

        <div class="detail-info-col">

            <div class="info-card">
                <h3>Plant Information</h3>
                <div class="info-card-grid">
                    <div class="info-fields">
                        <div class="info-field">
                            <span class="info-label">Category</span>
                            <span class="info-value"><?php echo $plant['category_name'] ? htmlspecialchars($plant['category_name']) : '—'; ?></span>
                        </div>
                        <div class="info-field">
                            <span class="info-label">Family</span>
                            <span class="info-value"><?php echo $plant['family'] !== null && $plant['family'] !== '' ? htmlspecialchars($plant['family']) : '—'; ?></span>
                        </div>
                        <div class="info-field">
                            <span class="info-label">Origin</span>
                            <span class="info-value"><?php echo $plant['origin'] !== null && $plant['origin'] !== '' ? htmlspecialchars($plant['origin']) : '—'; ?></span>
                        </div>
                    </div>
                    <div class="info-divider"></div>
                    <div class="info-fields">
                        <div class="info-field">
                            <span class="info-label">Description</span>
                            <span class="info-value info-value-long"><?php echo $plant['description'] !== null && $plant['description'] !== '' ? nl2br(htmlspecialchars($plant['description'])) : '—'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <h3>Medicinal Information</h3>
                <div class="info-card-grid">
                    <div class="info-fields">
                        <div class="info-field">
                            <span class="info-label">Uses</span>
                            <span class="info-value info-value-long"><?php echo $plant['medicinal_uses'] !== null && $plant['medicinal_uses'] !== '' ? nl2br(htmlspecialchars($plant['medicinal_uses'])) : '—'; ?></span>
                        </div>
                    </div>
                    <div class="info-divider"></div>
                    <div class="info-fields">
                        <div class="info-field">
                            <span class="info-label">Preparation</span>
                            <span class="info-value info-value-long"><?php echo $plant['preparation'] !== null && $plant['preparation'] !== '' ? nl2br(htmlspecialchars($plant['preparation'])) : '—'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="info-card">
                <h3>Additional Information</h3>
                <div class="info-card-grid">
                    <div class="info-fields">
                        <div class="info-field">
                            <span class="info-label">Active Compounds</span>
                            <span class="info-value info-value-long"><?php echo $plant['active_compounds'] !== null && $plant['active_compounds'] !== '' ? nl2br(htmlspecialchars($plant['active_compounds'])) : '—'; ?></span>
                        </div>
                    </div>
                    <div class="info-divider"></div>
                    <div class="info-fields">
                        <div class="info-field">
                            <span class="info-label">Side Effects</span>
                            <span class="info-value info-value-long"><?php echo $plant['side_effects'] !== null && $plant['side_effects'] !== '' ? nl2br(htmlspecialchars($plant['side_effects'])) : '—'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-actions">
                <a href="edit.php?id=<?php echo (int) $plant['plant_id']; ?>" class="edit-plant-btn">
                    <i class="bi bi-pencil-fill"></i> Edit Plant
                </a>
                <a href="delete.php?id=<?php echo (int) $plant['plant_id']; ?>" class="delete-plant-btn">
                    <i class="bi bi-trash-fill"></i> Delete Plant
                </a>
            </div>

        </div>
    </section>

    <!-- Delete confirmation popup -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <p>Are you sure want to<br>delete this plant?</p>
            <form method="POST" action="delete.php?id=<?php echo (int) $plant['plant_id']; ?>">
                <input type="hidden" name="plant_id" value="<?php echo (int) $plant['plant_id']; ?>">
                <div class="modal-actions">
                    <a href="view_details.php?id=<?php echo (int) $plant['plant_id']; ?>" class="modal-btn modal-btn-cancel">
                        <i class="bi bi-x-lg"></i> Cancel
                    </a>
                    <button type="submit" name="confirm_delete" value="1" class="modal-btn modal-btn-delete">
                        <i class="bi bi-trash-fill"></i> Delete Plant
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php endif; ?>

</body>
</html>

<?php
include 'includes/footer.php';
?>