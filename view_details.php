<?php

require 'includes/db.php'; // provides $conn (mysqli, procedural API)

$plant_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

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

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlantMedX</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php if (!$plant): ?>

    <section class="detail-not-found">
        <i class="bi bi-flower2"></i>
        <h2>Plant not found</h2>
        <p>The plant you're looking for doesn't exist or may have been removed.</p>
        <a href="view.php" class="add-plant-btn"><i class="bi bi-arrow-left"></i> Back to All Plants</a>
    </section>

<?php else: ?>

    <div class="detail-topbar">
        <a href="view.php" class="back-arrow" title="Back to all plants">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="detail-title">
            <h1><?php echo htmlspecialchars($plant['common_name']); ?> <span class="plant-id-badge">ID: <?php echo (int) $plant['plant_id']; ?></span></h1>
            <?php if (!empty($plant['scientific_name'])): ?>
                <p class="scientific-subtitle"><?php echo htmlspecialchars($plant['scientific_name']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <section class="detail-layout">

        <div class="detail-image-col">
            <div class="detail-image-box">
                <img id="mainDetailImage" src="<?php echo htmlspecialchars($images[0]); ?>" alt="<?php echo htmlspecialchars($plant['common_name']); ?>">
            </div>
            <?php if (count($images) > 1): ?>
                <div class="detail-thumbs">
                    <?php foreach ($images as $i => $img): ?>
                        <img src="<?php echo htmlspecialchars($img); ?>"
                             class="detail-thumb <?php echo $i === 0 ? 'active' : ''; ?>"
                             alt="thumbnail">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="detail-info-col">

            <div class="info-card">
                <h3>Plant Information</h3>
                <div class="info-card-grid">
                    <div class="info-fields">
                        <div class="info-field">
                            <span class="info-label">Local Name</span>
                            <span class="info-value"><?php echo $plant['local_name'] !== null && $plant['local_name'] !== '' ? htmlspecialchars($plant['local_name']) : '—'; ?></span>
                        </div>
                        <div class="info-field">
                            <span class="info-label">Category</span>
                            <span class="info-value"><?php echo $plant['category_name'] ? htmlspecialchars($plant['category_name']) . ' (ID: ' . (int) $plant['category_id'] . ')' : '—'; ?></span>
                        </div>
                        <div class="info-field">
                            <span class="info-label">Family</span>
                            <span class="info-value"><?php echo $plant['family'] !== null && $plant['family'] !== '' ? htmlspecialchars($plant['family']) : '—'; ?></span>
                        </div>
                    </div>
                    <div class="info-divider"></div>
                    <div class="info-fields">
                        <div class="info-field">
                            <span class="info-label">Origin</span>
                            <span class="info-value"><?php echo $plant['origin'] !== null && $plant['origin'] !== '' ? htmlspecialchars($plant['origin']) : '—'; ?></span>
                        </div>
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
                        <div class="info-field">
                            <span class="info-label">Parts Used</span>
                            <span class="info-value"><?php echo $plant['parts_used'] !== null && $plant['parts_used'] !== '' ? htmlspecialchars($plant['parts_used']) : '—'; ?></span>
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

            <?php if (isset($_SESSION['admin'])): ?>
            <div class="detail-actions">
                <a href="delete.php?id=<?php echo (int) $plant['plant_id']; ?>"
                   class="delete-plant-btn"
                   onclick="return confirm('Are you sure you want to delete this plant? This cannot be undone.');">
                    <i class="bi bi-trash-fill"></i> Delete Plant
                </a>
                <a href="edit.php?id=<?php echo (int) $plant['plant_id']; ?>" class="edit-plant-btn">
                    <i class="bi bi-pencil-fill"></i> Edit Plant
                </a>
            </div>
            <?php endif; ?>

        </div>
    </section>

    <?php if (count($images) > 1): ?>
    <script>
        // Simple thumbnail gallery — swap the main image on click
        document.querySelectorAll('.detail-thumb').forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                document.getElementById('mainDetailImage').src = thumb.src;
                document.querySelectorAll('.detail-thumb').forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
            });
        });
    </script>
    <?php endif; ?>

<?php endif; ?>
</body>
</html>


<?php
include 'includes/footer.php';
?>