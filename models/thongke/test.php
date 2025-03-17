<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê Phim</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .dashboard-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: 30px;
            margin-bottom: 30px;
        }
        .nav-tabs {
            border-bottom: 2px solid #e9ecef;
        }
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom: 3px solid #0d6efd;
            background-color: transparent;
        }
        .tab-pane {
            padding: 20px 0;
        }
        h3 {
            color: #343a40;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        h3:before {
            content: "";
            display: inline-block;
            width: 6px;
            height: 24px;
            background-color: #0d6efd;
            margin-right: 12px;
            border-radius: 3px;
        }
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 30px;
        }
        .stat-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            flex: 1;
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            min-width: 150px;
            border-left: 4px solid #0d6efd;
        }
        .stat-card.views {
            border-left-color: #0d6efd;
        }
        .stat-card.ratings {
            border-left-color: #fd7e14;
        }
        .stat-card h4 {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 8px;
        }
        .stat-card p {
            font-size: 24px;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 0;
        }
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'config.php'; // Kết nối database ?>

    <div class="container dashboard-container">
        <h2 class="text-center mb-4">Thống kê phim</h2>
        
        <!-- Tabs -->
        <ul class="nav nav-tabs" id="myTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#views">
                    <i class="bi bi-eye-fill me-2"></i>Lượt xem phim
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#ratings">
                    <i class="bi bi-star-fill me-2"></i>Đánh giá phim
                </a>
            </li>
        </ul>
        
        <div class="tab-content mt-4">
            <!-- Thống kê lượt xem phim -->
            <div id="views" class="tab-pane fade show active">
                <h3>Thống kê lượt xem phim</h3>
                
                <div class="stat-summary">
                    <div class="stat-card views">
                        <h4>Tổng lượt xem</h4>
                        <p id="totalViews">-</p>
                    </div>
                    <div class="stat-card views">
                        <h4>Phim được xem nhiều nhất</h4>
                        <p id="mostViewedMovie">-</p>
                    </div>
                    <div class="stat-card views">
                        <h4>Trung bình lượt xem</h4>
                        <p id="avgViews">-</p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="chart-container">
                            <canvas id="viewChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-container">
                            <canvas id="viewPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Thống kê đánh giá phim -->
            <div id="ratings" class="tab-pane fade">
                <h3>Thống kê đánh giá phim</h3>
                
                <div class="stat-summary">
                    <div class="stat-card ratings">
                        <h4>Đánh giá trung bình</h4>
                        <p id="avgRating">-</p>
                    </div>
                    <div class="stat-card ratings">
                        <h4>Phim được đánh giá cao nhất</h4>
                        <p id="highestRatedMovie">-</p>
                    </div>
                    <div class="stat-card ratings">
                        <h4>Tổng số đánh giá</h4>
                        <p id="totalRatings">-</p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="chart-container">
                            <canvas id="ratingChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-container">
                            <canvas id="ratingDistChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Khởi tạo các biểu đồ và tải dữ liệu
        initViewStats();
        initRatingStats();
        
        // Xử lý sự kiện khi chuyển tab
        document.querySelectorAll('.nav-link').forEach(tab => {
            tab.addEventListener('click', function() {
                // Đảm bảo biểu đồ được hiển thị đúng khi chuyển tab
                setTimeout(() => {
                    window.dispatchEvent(new Event('resize'));
                }, 100);
            });
        });
    });

    function initViewStats() {
        fetch('get_view_stats.php')
            .then(response => response.json())
            .then(data => {
                console.log("Dữ liệu lượt xem:", data);
                
                if (!Array.isArray(data) || data.length === 0) {
                    console.warn("Dữ liệu lượt xem rỗng hoặc không đúng định dạng");
                    return;
                }
                
                // Tính toán các số liệu thống kê
                const totalViews = data.reduce((sum, item) => sum + item.value, 0);
                const mostViewed = data.reduce((prev, current) => 
                    prev.value > current.value ? prev : current, data[0]);
                const avgViews = totalViews / data.length;
                
                // Cập nhật các thẻ tóm tắt
                document.getElementById('totalViews').textContent = totalViews.toLocaleString();
                document.getElementById('mostViewedMovie').textContent = mostViewed.name;
                document.getElementById('avgViews').textContent = Math.round(avgViews).toLocaleString();
                
                // Dữ liệu và màu sắc cho biểu đồ
                const labels = data.map(item => item.name);
                const values = data.map(item => item.value);
                const backgroundColors = generateGradientColors(data.length, '#0d6efd', '#4dabf7');
                
                // Vẽ biểu đồ cột
                renderBarChart('viewChart', 'Lượt xem phim', labels, values, backgroundColors);
                
                // Vẽ biểu đồ tròn
                const topMovies = [...data].sort((a, b) => b.value - a.value).slice(0, 5);
                const otherViews = data.slice(5).reduce((sum, item) => sum + item.value, 0);
                
                let pieLabels = topMovies.map(item => item.name);
                let pieValues = topMovies.map(item => item.value);
                
                if (data.length > 5) {
                    pieLabels.push('Khác');
                    pieValues.push(otherViews);
                }
                
                renderPieChart('viewPieChart', 'Top 5 phim được xem nhiều', pieLabels, pieValues);
            })
            .catch(error => {
                console.error("Lỗi khi tải dữ liệu lượt xem:", error);
                showError('viewChart', 'Không thể tải dữ liệu lượt xem');
            });
    }

    function initRatingStats() {
        fetch('get_rating_stats.php')
            .then(response => response.json())
            .then(data => {
                console.log("Dữ liệu đánh giá:", data);
                
                if (!Array.isArray(data) || data.length === 0) {
                    console.warn("Dữ liệu đánh giá rỗng hoặc không đúng định dạng");
                    return;
                }
                
                // Tính toán các số liệu thống kê
                const totalRatings = data.reduce((sum, item) => sum + (item.count || 1), 0);
                const avgRating = data.reduce((sum, item) => sum + item.value, 0) / data.length;
                const highestRated = data.reduce((prev, current) => 
                    prev.value > current.value ? prev : current, data[0]);
                
                // Cập nhật các thẻ tóm tắt
                document.getElementById('avgRating').textContent = avgRating.toFixed(1) + '/5';
                document.getElementById('highestRatedMovie').textContent = highestRated.name;
                document.getElementById('totalRatings').textContent = totalRatings.toLocaleString();
                
                // Dữ liệu và màu sắc cho biểu đồ
                const labels = data.map(item => item.name);
                const values = data.map(item => item.value);
                const backgroundColors = generateGradientColors(data.length, '#fd7e14', '#ffcc80');
                
                // Vẽ biểu đồ cột
                renderBarChart('ratingChart', 'Đánh giá trung bình', labels, values, backgroundColors, 5);
                
                // Vẽ biểu đồ phân phối đánh giá (giả lập phân phối sao)
                const ratingDist = [5, 15, 30, 35, 15]; // Giả lập phân phối 1-5 sao
                renderDistributionChart('ratingDistChart', 'Phân phối đánh giá', ['1⭐', '2⭐', '3⭐', '4⭐', '5⭐'], ratingDist);
            })
            .catch(error => {
                console.error("Lỗi khi tải dữ liệu đánh giá:", error);
                showError('ratingChart', 'Không thể tải dữ liệu đánh giá');
            });
    }

    function renderBarChart(canvasId, label, labels, values, colors, maxY = null) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: label,
                    data: values,
                    backgroundColor: colors,
                    borderColor: colors.map(color => color.replace('0.6', '1')),
                    borderWidth: 1,
                    borderRadius: 6,
                    maxBarThickness: 50
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: 12,
                        displayColors: false,
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: maxY,
                        grid: {
                            display: true,
                            drawBorder: false,
                            color: 'rgba(0,0,0,0.05)'
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false'#0d6efd', '#36a2eb', '#4bc0c0', '#ffcd56', '#ff9f40', '#ff6384'
        ];
        
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderColor: colors.map(color => color.replace('0.6', '1')),
                    borderWidth: 1,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: {
                                size: 12
                            },
                            padding: 15
                        }
                    },
                    title: {
                        display: true,
                        text: label,
                        font: {
                            size: 14
                        },
                        padding: {
                            bottom: 15
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: 12,
                        displayColors: false,
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    function renderDistributionChart(canvasId, label, labels, values) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        const colors = generateGradientColors(values.length, '#ffcc80', '#fd7e14');
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Phân phối',
                    data: values,
                    backgroundColor: colors,
                    borderColor: colors.map(color => color.replace('0.6', '1')),
                    borderWidth: 1,
                    borderRadius: 6,
                    maxBarThickness: 50
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: label,
                        font: {
                            size: 14
                        },
                        padding: {
                            bottom: 15
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                return `${value}%`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    x: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            display: true,
                            drawBorder: false,
                            color: 'rgba(0,0,0,0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            },
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    }

    function generateGradientColors(count, startColor, endColor) {
        const start = hexToRgb(startColor);
        const end = hexToRgb(endColor);
        const colors = [];
        
        for (let i = 0; i < count; i++) {
            const ratio = i / (count - 1 || 1);
            const r = Math.round(start.r + ratio * (end.r - start.r));
            const g = Math.round(start.g + ratio * (end.g - start.g));
            const b = Math.round(start.b + ratio * (end.b - start.b));
            colors.push(`rgba(${r}, ${g}, ${b}, 0.6)`);
        }
        
        return colors;
    }

    function hexToRgb(hex) {
        const shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
        hex = hex.replace(shorthandRegex, (m, r, g, b) => r + r + g + g + b + b);
        
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    }

    function showError(canvasId, message) {
        const container = document.getElementById(canvasId).parentNode;
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        container.innerHTML = '';
        container.appendChild(errorDiv);
    }
</script>
</body>
</html>