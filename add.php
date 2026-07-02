<?php
require_once 'includes/db.php';

$success_message = '';
$error_message   = '';

// Keep submitted values so the form can be re-filled if validation fails
$form = [
    'common_name'      => '',
    'scientific_name'  => '',
    'local_name'       => '',
    'category_id'      => '',
    'family'           => '',
    'origin'           => '',
    'description'      => '',
    'medicinal_uses'   => '',
    'parts_used'       => '',
    'preparation'      => '',
    'active_compounds' => '',
    'side_effects'     => '',
];

$origin_options = [
    'Africa', 'Southeast Asia', 'South Asia', 'East Asia',
    'Europe', 'Europe & Asia', 'North America', 'South America',
    'Australia & Oceania', 'Middle East', 'Other'
];

// ---------------------------------------------------------
// Handle form submission
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($form as $key => $value) {
        $form[$key] = trim($_POST[$key] ?? '');
    }

    if ($form['common_name'] === '') {
        $error_message = 'Common Name is required.';
    } else {

        // category_id can be empty -> NULL
        $category_id = ($form['category_id'] !== '') ? (int) $form['category_id'] : null;

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO plants
                (category_id, common_name, scientific_name, local_name, family, origin,
                 description, medicinal_uses, preparation, parts_used, active_compounds,
                 side_effects, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')"
        );

        mysqli_stmt_bind_param(
            $stmt,
            'isssssssssss',
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
            $form['side_effects']
        );

        if (mysqli_stmt_execute($stmt)) {
            $plant_id = mysqli_insert_id($conn);

            // ---------------------------------------------
            // Handle image uploads (optional, multiple files)
            // ---------------------------------------------
            $upload_dir = __DIR__ . '/uploads/plants/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

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

                    $ext          = pathinfo($filename, PATHINFO_EXTENSION);
                    $safe_name    = 'plant_' . $plant_id . '_' . uniqid() . '.' . $ext;
                    $destination  = $upload_dir . $safe_name;
                    $relative_path = 'uploads/plants/' . $safe_name;

                    if (move_uploaded_file($tmp_path, $destination)) {
                        $img_stmt = mysqli_prepare(
                            $conn,
                            "INSERT INTO images (plant_id, image_path) VALUES (?, ?)"
                        );
                        mysqli_stmt_bind_param($img_stmt, 'is', $plant_id, $relative_path);
                        mysqli_stmt_execute($img_stmt);
                        mysqli_stmt_close($img_stmt);
                    }
                }
            }

            $success_message = 'Plant "' . htmlspecialchars($form['common_name']) . '" was added successfully.';
            if (!empty($upload_errors)) {
                $success_message .= ' Note: ' . implode(' ', $upload_errors);
            }

            // Reset form after a successful insert
            foreach ($form as $key => $value) {
                $form[$key] = '';
            }

        } else {
            $error_message = 'Something went wrong while saving the plant: ' . mysqli_error($conn);
        }

        mysqli_stmt_close($stmt);
    }
}

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
<!-- <?php include 'includes/header.php'; ?> -->

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<title>Add New Plant | Medicinal Plants Database</title>
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
            <h1>Add New Plant</h1>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <form action="add.php" method="POST" enctype="multipart/form-data">
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
                                <?php foreach ($origin_options as $opt): ?>
                                    <option value="<?= htmlspecialchars($opt) ?>"
                                        <?= ($form['origin'] === $opt) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($opt) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4"><?= htmlspecialchars($form['description']) ?></textarea>
                        </div>
                    </div>

                    <div class="panel">
                        <h3>Images</h3>
                        <div class="upload-box">
                            <input type="file" id="images" name="images[]" accept="image/*" multiple>
                            <button type="button" class="upload-btn">⬆ Upload Images</button>
                            <div class="file-list" id="fileList">No files selected</div>
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
                    <button type="submit" class="btn btn-submit">+ Add Plant</button>
                </div>

            </div>
        </form>
    </div>

    <script>
        // Theme toggle (light/dark) — persists only for the current page view
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
    </script>

</body>
</html>
<?php include 'includes/footer.php'; ?>