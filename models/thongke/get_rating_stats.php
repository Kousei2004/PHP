<?php
include 'config.php';

$sql = "SELECT m.title AS name, ROUND(AVG(c.rating), 1) AS value 
        FROM comments c
        JOIN movies m ON c.movie_id = m.id
        GROUP BY c.movie_id
        ORDER BY value DESC
        LIMIT 10";
        
$result = mysqli_query($conn, $sql);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode($data);
?>
