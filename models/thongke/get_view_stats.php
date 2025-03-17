<?php
include 'config.php';

$sql = "SELECT title AS name, views AS value FROM movies ORDER BY views DESC LIMIT 10";
$result = mysqli_query($conn, $sql);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode($data);
?>
