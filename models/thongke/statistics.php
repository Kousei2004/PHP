<?php
include 'config.php'; // Kết nối database
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="container mt-5">

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="myTabs">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#views">Lượt xem phim</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#ratings">Đánh giá phim</a>
        </li>
        
    </ul>

    <div class="tab-content mt-3">
        <!-- Thống kê lượt xem phim -->
        <div id="views" class="tab-pane fade show active">
            <h3>Thống kê lượt xem phim</h3>
            <canvas id="viewChart"></canvas>
        </div>

        <!-- Thống kê đánh giá phim -->
        <div id="ratings" class="tab-pane fade">
            <h3>Thống kê đánh giá phim</h3>
            <canvas id="ratingChart"></canvas>
        </div>

       
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script >
document.addEventListener("DOMContentLoaded", function () {
    fetchDataAndRenderChart('get_view_stats.php', 'viewChart', 'Lượt xem phim');
    fetchDataAndRenderChart('get_rating_stats.php', 'ratingChart', 'Đánh giá phim');
});

document.addEventListener("DOMContentLoaded", function () {
    // Gọi API và hiển thị dữ liệu lên biểu đồ
    fetchDataAndRenderChart('get_view_stats.php', 'viewChart', 'Lượt xem phim');
    fetchDataAndRenderChart('get_rating_stats.php', 'ratingChart', 'Đánh giá phim');
});

function fetchDataAndRenderChart(apiUrl, canvasId, label) {
    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            console.log("Dữ liệu từ " + apiUrl + ":", data); // Kiểm tra dữ liệu trên console

            if (!Array.isArray(data) || data.length === 0) {
                console.warn("Dữ liệu rỗng hoặc không đúng định dạng từ:", apiUrl);
                return;
            }

            let labels = data.map(item => item.name);
            let values = data.map(item => item.value);

            let ctx = document.getElementById(canvasId).getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: label,
                        data: values,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: { y: { beginAtZero: true } }
                }
            });
        })
        .catch(error => console.error("Lỗi khi tải dữ liệu từ " + apiUrl, error));
}

</script>

</body>
</html>
