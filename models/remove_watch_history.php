<?php
session_start();
include "config.php";

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    // Trả về phản hồi lỗi
    echo json_encode(['success' => false, 'message' => 'Người dùng chưa đăng nhập']);
    exit();
}

// Kiểm tra xem có movie_id không
if (!isset($_POST['movie_id'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu tham số movie_id']);
    exit();
}

$user_id = $_SESSION['user_id'];
$movie_id = $_POST['movie_id'];

// Chuẩn bị truy vấn DELETE
$sql = "DELETE FROM watch_history WHERE user_id = ? AND movie_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu: ' . $conn->error]);
    exit();
}

$stmt->bind_param("ii", $user_id, $movie_id);
$result = $stmt->execute();

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Đã xóa mục khỏi lịch sử xem thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể xóa mục: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>