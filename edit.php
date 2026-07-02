<?php
require_once 'includes/db.php';

$success_message = '';
$error_message   = '';

$plant_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($plant_id <= 0) {
    die('No plant specified. Go back to the plant list and choose a plant to edit.');
}

$origin_options = [
    'Africa', 'Southeast Asia', 'South Asia', 'East Asia',
    'Europe', 'Europe & Asia', 'North America', 'South America',
    'Australia & Oceania', 'Middle East', 'Other'
];

// ---------------------------------------------------------
// Load the plant (used both to pre-fill the form on GET,
// and as a fallback / title source after POST)
// ---------------------------------------------------------
function load_plant($conn, $plant_id)
{
    $stmt = mysqli_prepare($conn, "SELECT * FROM plants WHERE plant_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $plant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $plant  = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $plant;
}

function load_images($conn, $plant_id)
{
    $stmt = mysqli_prepare($conn, "SELECT * FROM images WHERE plant_id = ? ORDER BY image_id ASC");
    mysqli_stmt_bind_param($stmt, 'i', $plant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $images = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $images[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $images;
}

$plant = load_plant($conn, $plant_id);

if (!$plant) {
    die('Plant not found.');
}

$form = [
    'common_name'      => $plant['common_name'],
    'scientific_name'  => $plant['scientific_name'],
    'local_name'       => $plant['local_name'],
    'category_id'      => $plant['category_id'],
    'family'           => $plant['family'],
    'origin'           => $plant['origin'],
    'description'      => $plant['description'],
    'medicinal_uses'   => $plant['medicinal_uses'],
    'parts_used'       => $plant['parts_used'],
    'preparation'      => $plant['preparation'],
    'active_compounds' => $plant['active_compounds'],
    'side_effects'     => $plant['side_effects'],
];

// ---------------------------------------------------------
// Handle form submission
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($form as $key => $value) {
        if ($key === 'category_id') continue;
        $form[$key] = trim($_POST[$key] ?? '');
    }
    $form['category_id'] = trim($_POST['category_id'] ?? '');

    if ($form['common_name'] === '') {
        $error_message = 'Common Name is required.';
    } else {

        $category_id = ($form['category_id'] !== '') ? (int) $form['category_id'] : null;

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE plants SET
                category_id = ?, common_name = ?, scientific_name = ?, local_name = ?,
                family = ?, origin = ?, description = ?, medicinal_uses = ?,
                preparation = ?, parts_used = ?, active_compounds = ?, side_effects = ?
             WHERE plant_id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            'isssssssssssi',
            $category_id,
            $form['common_name'],
            $form['scientific_name'],
            $form['local_name'],
            $form['family'],
            $form['origin'],
            $form['description'],
            $form['medicinal_uses'],
            $form['preparation'],
            $form['parts_used'],
            $form['active_compounds'],
            $form['side_effects'],
            $plant_id
        );

        if (mysqli_stmt_execute($stmt)) {

            $upload_dir = __DIR__ . '/uploads/plants/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // ---------------------------------------------
            // Delete images the user checked for removal
            // ---------------------------------------------
            if (!empty($_POST['delete_images']) && is_array($_POST['delete_images'])) {
                foreach ($_POST['delete_images'] as $image_id) {
                    $image_id = (int) $image_id;

                    $img_stmt = mysqli_prepare($conn, "SELECT image_path FROM images WHERE image_id = ? AND plant_id = ?");
                    mysqli_stmt_bind_param($img_stmt, 'ii', $image_id, $plant_id);
                    mysqli_stmt_execute($img_stmt);
                    $img_result = mysqli_stmt_get_result($img_stmt);
                    if ($img_row = mysqli_fetch_assoc($img_result)) {
                        $file_path = __DIR__ . '/' . $img_row['image_path'];
                        if (is_file($file_path)) {
                            @unlink($file_path);
                        }
                    }
                    mysqli_stmt_close($img_stmt);

                    $del_stmt = mysqli_prepare($conn, "DELETE FROM images WHERE image_id = ? AND plant_id = ?");
                    mysqli_stmt_bind_param($del_stmt, 'ii', $image_id, $plant_id);
                    mysqli_stmt_execute($del_stmt);
                    mysqli_stmt_close($del_stmt);
                }
            }

            // ---------------------------------------------
            // Upload any newly added images
            // ---------------------------------------------
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $upload_errors = [];

            if (!empty($_FILES['images']['name'][0])) {
                foreach ($_FILES['images']['name'] as $i => $filename) {

                    if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $tmp_path  = $_FILES['images']['tmp_name'][$i];
                    $mime_type = mime_content_type($tmp_path);

                    if (!in_array($mime_type, $allowed_types, true)) {
                        $upload_errors[] = htmlspecialchars($filename) . ' was skipped (unsupported file type).';
                        continue;
                    }

                    $ext           = pathinfo($filename, PATHINFO_EXTENSION);
                    $safe_name     = 'plant_' . $plant_id . '_' . uniqid() . '.' . $ext;
                    $destination   = $upload_dir . $safe_name;
                    $relative_path = 'uploads/plants/' . $safe_name;

                    if (move_uploaded_file($tmp_path, $destination)) {
                        $img_stmt = mysqli_prepare($conn, "INSERT INTO images (plant_id, image_path) VALUES (?, ?)");
                        mysqli_stmt_bind_param($img_stmt, 'is', $plant_id, $relative_path);
                        mysqli_stmt_execute($img_stmt);
                        mysqli_stmt_close($img_stmt);
                    }
                }
            }

            $success_message = 'Changes to "' . htmlspecialchars($form['common_name']) . '" were saved successfully.';
            if (!empty($upload_errors)) {
                $success_message .= ' Note: ' . implode(' ', $upload_errors);
            }

            // Refresh plant data after save
            $plant = load_plant($conn, $plant_id);

        } else {
            $error_message = 'Something went wrong while saving changes: ' . mysqli_error($conn);
        }

        mysqli_stmt_close($stmt);
    }
}

$images = load_images($conn, $plant_id);

// ---------------------------------------------------------
// Fetch categories for the dropdown
// ---------------------------------------------------------
$categories = [];
$cat_result = mysqli_query($conn, "SELECT category_id, name FROM categories ORDER BY name ASC");
if ($cat_result) {
    while ($row = mysqli_fetch_assoc($cat_result)) {
        $categories[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<title>Edit Plant | PlantMedX</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="admin-body" data-theme="light">

    <!-- Top bar -->
    <div class="admin-topbar">
        <div class="admin-logo-box">
            <img src="assets/images/logo.png" alt="PlantMedX Logo" width="50"> <h1>PlantMedX</h1>
        </div>
        <div class="admin-topbar-right">
            <button type="button" class="icon-btn" id="themeToggle" title="Toggle dark mode">🌙</button>
            <a href="index.html" class="icon-btn" title="Home">🏠</a>
        </div>
    </div>

    <div class="admin-container">

        <div class="admin-page-title">
            <a href="plants.php" class="back-link">&larr;</a>
            <h1><?= htmlspecialchars($plant['common_name']) ?> (Plant ID: <?= $plant_id ?>)</h1>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <form action="edit.php?id=<?= $plant_id ?>" method="POST" enctype="multipart/form-data">
            <div class="form-grid">

                <!-- ================= LEFT COLUMN ================= -->
                <div class="left-column">

                    <div class="panel">
                        <h3>Plant Information</h3>

                        <div class="form-group">
                            <label for="common_name">Common Name</label>
                            <input type="text" id="common_name" name="common_name"
                                   value="<?= htmlspecialchars($form['common_name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="scientific_name">Scientific Name</label>
                            <input type="text" id="scientific_name" name="scientific_name"
                                   value="<?= htmlspecialchars($form['scientific_name']) ?>">
                        </div>

                        <div class="form-group">
                            <label for="local_name">Local Name</label>
                            <input type="text" id="local_name" name="local_name"
                                   value="<?= htmlspecialchars($form['local_name']) ?>">
                        </div>

                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select id="category_id" name="category_id">
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['category_id'] ?>"
                                        <?= ($form['category_id'] == $cat['category_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="family">Family</label>
                            <input type="text" id="family" name="family"
                                   value="<?= htmlspecialchars($form['family']) ?>">
                        </div>

                        <div class="form-group">
                            <label for="origin">Origin</label>
                            <select id="origin" name="origin">
                                <option value="">-- Select Origin --</option>
                                <?php
                                $origin_in_list = in_array($form['origin'], $origin_options, true);
                                foreach ($origin_options as $opt):
                                ?>
                                    <option value="<?= htmlspecialchars($opt) ?>"
                                        <?= ($form['origin'] === $opt) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($opt) ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if ($form['origin'] !== '' && !$origin_in_list): ?>
                                    <option value="<?= htmlspecialchars($form['origin']) ?>" selected>
                                        <?= htmlspecialchars($form['origin']) ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4"><?= htmlspecialchars($form['description']) ?></textarea>
                        </div>
                    </div>

                    <div class="panel">
                        <h3>Images</h3>
                        <button type="button" class="upload-btn" id="manageImagesBtn">🖼 Manage Images</button>

                        <div class="manage-images-panel" id="manageImagesPanel">

                            <?php if (!empty($images)): ?>
                                <div class="image-grid">
                                    <?php foreach ($images as $img): ?>
                                        <div class="image-thumb">
                                            <img src="<?= htmlspecialchars($img['image_path']) ?>" alt="Plant image">
                                            <label class="image-delete-label">
                                                <input type="checkbox" name="delete_images[]" value="<?= $img['image_id'] ?>">
                                                Delete
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="no-images-text">No images uploaded yet.</p>
                            <?php endif; ?>

                            <div class="upload-box">
                                <input type="file" id="images" name="images[]" accept="image/*" multiple>
                                <button type="button" class="upload-btn">⬆ Add New Images</button>
                                <div class="file-list" id="fileList">No files selected</div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ================= RIGHT COLUMN ================= -->
                <div class="right-column">

                    <div class="panel">
                        <h3>Medicinal Information</h3>

                        <div class="form-group">
                            <label for="medicinal_uses">Uses</label>
                            <textarea id="medicinal_uses" name="medicinal_uses" rows="3"><?= htmlspecialchars($form['medicinal_uses']) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="parts_used">Parts used</label>
                            <input type="text" id="parts_used" name="parts_used"
                                   value="<?= htmlspecialchars($form['parts_used']) ?>">
                        </div>

                        <div class="form-group">
                            <label for="preparation">Preparation</label>
                            <textarea id="preparation" name="preparation" rows="4"><?= htmlspecialchars($form['preparation']) ?></textarea>
                        </div>
                    </div>

                    <div class="panel">
                        <h3>Additional Information</h3>
                        <div class="two-col">
                            <div class="form-group">
                                <label for="active_compounds">Active compound</label>
                                <textarea id="active_compounds" name="active_compounds" rows="4"><?= htmlspecialchars($form['active_compounds']) ?></textarea>
                            </div>
                            <div class="form-group divider-col">
                                <label for="side_effects">Side effects</label>
                                <textarea id="side_effects" name="side_effects" rows="4"><?= htmlspecialchars($form['side_effects']) ?></textarea>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ================= BUTTONS ================= -->
                <div class="button-row">
                    <a href="plants.php" class="btn btn-cancel">✕ Cancel</a>
                    <button type="submit" class="btn btn-submit">✓ Save Changes</button>
                </div>

            </div>
        </form>
    </div>

    <script>
        // Theme toggle (light/dark)
        const themeToggle = document.getElementById('themeToggle');
        const htmlEl = document.documentElement;
        const bodyEl = document.body;

        themeToggle.addEventListener('click', () => {
            const isDark = htmlEl.getAttribute('data-theme') === 'dark';
            const next = isDark ? 'light' : 'dark';
            htmlEl.setAttribute('data-theme', next);
            bodyEl.setAttribute('data-theme', next);
            themeToggle.textContent = isDark ? '🌙' : '☀️';
        });

        // Show selected file names under the upload button
        const fileInput = document.getElementById('images');
        const fileList = document.getElementById('fileList');

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length === 0) {
                fileList.textContent = 'No files selected';
                return;
            }
            const names = Array.from(fileInput.files).map(f => f.name);
            fileList.textContent = names.join(', ');
        });

        // Toggle the Manage Images panel
        const manageBtn = document.getElementById('manageImagesBtn');
        const managePanel = document.getElementById('manageImagesPanel');

        manageBtn.addEventListener('click', () => {
            managePanel.classList.toggle('open');
        });
    </script>

</body>
</html>