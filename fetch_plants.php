<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "medicinal_plants_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "
SELECT
    p.plant_id,
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
    i.image_path
FROM plants p
LEFT JOIN images i
ON p.plant_id = i.plant_id
WHERE p.status = 'active'
ORDER BY p.common_name ASC
";

$result = $conn->query($sql);

$plants = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $plants[] = $row;
    }
}

header("Content-Type: application/json");
echo json_encode($plants, JSON_PRETTY_PRINT);

$conn->close();

?>