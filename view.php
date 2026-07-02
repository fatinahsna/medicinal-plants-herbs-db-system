<?php
/* ============================================================
   view.php — "View Plant (All)" page
   Displays every active plant as a card (image + name) in a
   responsive grid, with search + category/origin filtering
   and a link to add a new plant — per the wireframe.
   ============================================================ */

require 'includes/db.php'; // provides $conn (mysqli, procedural API)

/* ---------- Read filters from the query string ---------- */
$search        = isset($_GET['q']) ? trim($_GET['q']) : '';
$selCategories = isset($_GET['category']) && is_array($_GET['category']) ? $_GET['category'] : [];
$selOrigins    = isset($_GET['origin']) && is_array($_GET['origin']) ? $_GET['origin'] : [];

/* ---------- Load categories for the filter popup ---------- */
$categories = [];
$catResult = mysqli_query($conn, "SELECT category_id, name FROM categories ORDER BY name ASC");
if ($catResult) {
    while ($row = mysqli_fetch_assoc($catResult)) {
        $categories[] = $row;
    }
}

/* Static continent list used for the "Origin" filter.
   Matched against the free-text `origin` column with LIKE,
   since origins in the DB are things like "Southeast Asia". */
$originOptions = ['Africa', 'Southeast Asia', 'South Asia', 'East Asia',
    'Europe', 'Europe & Asia', 'North America', 'South America',
    'Australia & Oceania', 'Middle East'];

/* ---------- Build the plant query dynamically ---------- */
$sql = "
    SELECT
        p.plant_id,
        p.common_name,
        p.scientific_name,
        p.origin,
        c.name AS category_name,
        (SELECT i.image_path FROM images i WHERE i.plant_id = p.plant_id ORDER BY i.image_id ASC LIMIT 1) AS image_path
    FROM plants p
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.status = 'active'
";

$types  = '';
$params = [];

if ($search !== '') {
    $sql .= " AND (p.common_name LIKE ? OR p.scientific_name LIKE ? OR p.local_name LIKE ?) ";
    $like = "%{$search}%";
    $types .= 'sss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($selCategories)) {
    $placeholders = implode(',', array_fill(0, count($selCategories), '?'));
    $sql .= " AND c.name IN ($placeholders) ";
    foreach ($selCategories as $cat) {
        $types .= 's';
        $params[] = $cat;
    }
}

if (!empty($selOrigins)) {
    $originClauses = [];
    foreach ($selOrigins as $origin) {
        $originClauses[] = "p.origin LIKE ?";
        $types .= 's';
        $params[] = "%{$origin}%";
    }
    $sql .= " AND (" . implode(' OR ', $originClauses) . ") ";
}

$sql .= " ORDER BY p.common_name ASC ";

$stmt = mysqli_prepare($conn, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$plants = [];
while ($row = mysqli_fetch_assoc($result)) {
    $plants[] = $row;
}
mysqli_stmt_close($stmt);

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
        <form class="view-search-row" id="filterForm" action="view.php" method="GET">
        <div class="search-container">
            <input type="text" class="search-bar" name="q" placeholder="Search Plants..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="search-button">
                <i class="bi bi-search"></i>
            </button>
            <button type="button" class="filter-button" id="filterBtn">
                <i class="bi bi-filter"></i>
            </button>
        </div>

        <div class="filter-popup" id="filterPopup">
            <h2>Filter Plants</h2>
            <div class="filter-top">
                <button type="button" class="toggle-button" id="categoryBtn">Category</button>
                <button type="button" class="toggle-button" id="originBtn">Origin</button>
            </div>
            <div class="filter-content">
                <div class="filter-section" id="categorySection">
                    <h3>Category</h3>
                    <?php foreach ($categories as $cat): ?>
                        <label>
                            <input type="checkbox" name="category[]" value="<?php echo htmlspecialchars($cat['name']); ?>"
                                <?php echo in_array($cat['name'], $selCategories) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="filter-section" id="originSection">
                    <h3>Origin</h3>
                    <?php foreach ($originOptions as $origin): ?>
                        <label>
                            <input type="checkbox" name="origin[]" value="<?php echo htmlspecialchars($origin); ?>"
                                <?php echo in_array($origin, $selOrigins) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($origin); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="popup-buttons">
                <button type="button" class="reset-button" id="resetBtn">Reset</button>
                <button type="submit" class="apply-button" id="applyBtn">Apply</button>
            </div>
        </div>
    </form>
    
    <?php if (isset($_SESSION['admin'])): ?>
    <div class="view-actions">
        <a href="add.php" class="add-plant-btn">
            <i class="bi bi-plus-lg"></i> Add Plant
        </a>
    </div>
    <?php endif; ?>

    <p class="results-count"><?php echo count($plants); ?> plant<?php echo count($plants) === 1 ? '' : 's'; ?> found</p>

    <section class="plants-grid-section">
        <?php if (empty($plants)): ?>
            <div class="no-results">
                No plants match your search or filters. Try adjusting them.
            </div>
        <?php else: ?>
            <div class="plants-grid">
                <?php foreach ($plants as $plant): ?>
                    <a class="grid-card" href="view_details.php?id=<?php echo urlencode($plant['plant_id']); ?>">
                        <div class="grid-card-img">
                            <img
                                src="<?php echo htmlspecialchars($plant['image_path'] ?: 'assets/images/plant-placeholder.png'); ?>"
                                alt="<?php echo htmlspecialchars($plant['common_name']); ?>">
                        </div>
                        <div class="grid-card-title"><?php echo htmlspecialchars($plant['common_name']); ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

</body>
</html>

<?php
include 'includes/footer.php';
?>