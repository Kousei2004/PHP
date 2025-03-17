<?php
// Kết nối CSDL
include __DIR__ . '/../../config/config.php';

session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/main.php");
    exit();
}

// Check if user is admin or regular user
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$currentUserId = $_SESSION['user_id'];


// Truy vấn lấy thống kê theo payment_method
$sql_payment_methods = "SELECT payment_method, COUNT(*) as total_transactions, SUM(amount) as total_amount 
                        FROM subscriptions
                        GROUP BY payment_method 
                        ORDER BY total_amount DESC";

// Truy vấn lấy thống kê theo tháng/năm
$sql_monthly = "SELECT DATE_FORMAT(payment_date, '%Y-%m') as month_year, 
                COUNT(*) as total_transactions, 
                SUM(amount) as total_amount 
                FROM subscriptions
                GROUP BY month_year 
                ORDER BY month_year DESC 
                LIMIT 12";

// Truy vấn lấy thống kê theo gói dịch vụ
$sql_packages = "SELECT package_name, COUNT(*) as total_transactions, SUM(amount) as total_amount 
                FROM subscriptions
                GROUP BY package_name 
                ORDER BY total_amount DESC";

// Lấy dữ liệu thống kê tổng quan
$sql_overview = "SELECT 
                COUNT(*) as total_transactions,
                SUM(amount) as total_revenue,
                COUNT(DISTINCT user_id) as total_users,
                AVG(amount) as average_transaction,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_subscriptions
            FROM subscriptions";

// Truy vấn lấy danh sách 10 giao dịch gần nhất
$sql_recent = "SELECT user_id, package_name, transaction_id, amount, payment_method, 
              payment_date, expiry_date, status
              FROM subscriptions
              ORDER BY payment_date DESC
              LIMIT 10";

// Thực thi các truy vấn
// Thực thi các truy vấn
$result_payment_methods = $conn->query($sql_payment_methods);
$result_monthly = $conn->query($sql_monthly);
$result_packages = $conn->query($sql_packages);
$result_overview = $conn->query($sql_overview);
$result_recent = $conn->query($sql_recent);

// Chuẩn bị dữ liệu cho biểu đồ doanh thu theo tháng
$months = [];
$revenue = [];
while($row = $result_monthly->fetch_assoc()) {
    $months[] = date('m/Y', strtotime($row['month_year'] . '-01'));
    $revenue[] = $row['total_amount'];
}

// Reset con trỏ để có thể đọc lại dữ liệu
$result_monthly->data_seek(0);

// Lấy dữ liệu tổng quan
if ($result_overview) {
    $overview = $result_overview->fetch_assoc();
} else {
    die("Lỗi truy vấn: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê hóa đơn thanh toán</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-header {
            background-color: var(--dark-color);
            color: white;
            padding: 20px 0;
            margin-bottom: 25px;
        }
        
        .stats-card {
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-card .card-header {
            border-radius: 10px 10px 0 0;
            font-weight: 600;
        }
        
        .stats-card .card-body {
            padding: 20px;
        }
        
        .stats-icon {
            background-color: rgba(52, 152, 219, 0.1);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .stats-icon i {
            font-size: 24px;
            color: var(--primary-color);
        }
        
        .stats-info h3 {
            font-size: 24px;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .stats-info p {
            margin: 0;
            color: #7f8c8d;
        }
        
        .table-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .date-filter {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .date-filter .form-control {
            max-width: 200px;
            margin-right: 10px;
        }
        
        .chart-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 25px;
            height: 100%;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background-color: rgba(46, 204, 113, 0.15);
            color: #27ae60;
        }
        
        .status-expired {
            background-color: rgba(231, 76, 60, 0.15);
            color: #c0392b;
        }
        
        .status-pending {
            background-color: rgba(243, 156, 18, 0.15);
            color: #d35400;
        }
        
        .payment-method-icon {
            width: 40px;
            height: 40px;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 18px;
        }
        
        .payment-zalopay {
            background-color: #0068ff;
            color: white;
        }
        
        .payment-momo {
            background-color: #d82d8b;
            color: white;
        }
        
        .payment-bank {
            background-color: #2ecc71;
            color: white;
        }
        
        .payment-paypal {
            background-color: #003087;
            color: white;
        }
        
        .recent-transaction-table th {
            font-weight: 600;
            color: #34495e;
        }
        
        .recent-transaction-table td {
            vertical-align: middle;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../includes/nav.php'; ?>

    <div class="dashboard-header">
        <div class="container" style="margin-top: 60px;">
            <h1><i class="fas fa-chart-line me-2"></i> Thống kê hóa đơn thanh toán</h1>
        </div>
    </div>
    
    <div class="container">
        <!-- Bộ lọc ngày tháng -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="table-container">
                    <h4>Bộ lọc</h4>
                    <div class="date-filter">
                        <div class="input-group me-3" style="max-width: 200px;">
                            <span class="input-group-text">Từ</span>
                            <input type="date" class="form-control" id="startDate">
                        </div>
                        <div class="input-group me-3" style="max-width: 200px;">
                            <span class="input-group-text">Đến</span>
                            <input type="date" class="form-control" id="endDate">
                        </div>
                        <select class="form-select me-3" style="max-width: 200px;">
                            <option value="">Tất cả phương thức</option>
                            <option value="zalopay">ZaloPay</option>
                            <option value="momo">MomoPay</option>
                            <option value="bank">Ngân hàng</option>
                            <option value="paypal">PayPal</option>
                        </select>
                        <select class="form-select me-3" style="max-width: 200px;">
                            <option value="">Tất cả gói</option>
                            <option value="Gói 1 tháng">Gói 1 tháng</option>
                            <option value="Gói 3 tháng">Gói 3 tháng</option>
                            <option value="Gói 6 tháng">Gói 6 tháng</option>
                            <option value="Gói 12 tháng">Gói 12 tháng</option>
                        </select>
                        <button class="btn btn-primary">Lọc dữ liệu</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Thống kê tổng quan -->
        <div class="row mb-4">
            <div class="col-md-4 mb-4">
                <div class="stats-card card border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stats-info">
                            <h3><?php echo number_format($overview['total_revenue']); ?> VNĐ</h3>
                            <p>Tổng doanh thu</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stats-card card border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <div class="stats-info">
                            <h3><?php echo number_format($overview['total_transactions']); ?></h3>
                            <p>Tổng số giao dịch</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stats-card card border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stats-info">
                            <h3><?php echo number_format($overview['total_users']); ?></h3>
                            <p>Tổng số người dùng</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stats-card card border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stats-info">
                            <h3><?php echo number_format($overview['average_transaction']); ?> VNĐ</h3>
                            <p>Giá trị giao dịch trung bình</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stats-card card border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stats-info">
                            <h3><?php echo number_format($overview['active_subscriptions']); ?></h3>
                            <p>Gói đang hoạt động</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stats-card card border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stats-info">
                            <h3><?php echo number_format($overview['total_transactions'] / 12, 1); ?></h3>
                            <p>Giao dịch trung bình/tháng</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bảng giao dịch gần đây -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="table-container">
                    <h4>Giao dịch gần đây</h4>
                    <div class="table-responsive">
                        <table class="table table-hover recent-transaction-table">
                            <thead>
                                <tr>
                                    <th>ID người dùng</th>
                                    <th>Gói dịch vụ</th>
                                    <th>Mã giao dịch</th>
                                    <th>Số tiền</th>
                                    <th>Phương thức</th>
                                    <th>Ngày thanh toán</th>
                                    <th>Ngày hết hạn</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                while($row = $result_recent->fetch_assoc()): 
                                    $statusClass = '';
                                    if($row['status'] == 'active') {
                                        $statusClass = 'status-active';
                                    } else if($row['status'] == 'expired') {
                                        $statusClass = 'status-expired';
                                    } else {
                                        $statusClass = 'status-pending';
                                    }
                                    
                                    $paymentIcon = '';
                                    if($row['payment_method'] == 'zalopay') {
                                        $paymentIcon = '<span class="payment-method-icon payment-zalopay"><i class="fas fa-wallet"></i></span>';
                                    } else if($row['payment_method'] == 'momo') {
                                        $paymentIcon = '<span class="payment-method-icon payment-momo"><i class="fas fa-money-bill"></i></span>';
                                    } else if($row['payment_method'] == 'bank') {
                                        $paymentIcon = '<span class="payment-method-icon payment-bank"><i class="fas fa-university"></i></span>';
                                    } else {
                                        $paymentIcon = '<span class="payment-method-icon payment-paypal"><i class="fab fa-paypal"></i></span>';
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $row['user_id']; ?></td>
                                    <td><?php echo $row['package_name']; ?></td>
                                    <td><span class="text-primary"><?php echo $row['transaction_id']; ?></span></td>
                                    <td><strong><?php echo number_format($row['amount']); ?> VNĐ</strong></td>
                                    <td><?php echo $paymentIcon . $row['payment_method']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['payment_date'])); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['expiry_date'])); ?></td>
                                    <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $row['status']; ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-3">
                        <button class="btn btn-outline-primary">Xem tất cả giao dịch</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Biểu đồ thống kê -->
        <div class="row mb-4">
            <div class="col-lg-8 mb-4">
                <div class="chart-container">
                    <h4>Doanh thu theo tháng</h4>
                    <canvas id="monthlyRevenueChart"></canvas>
                </div>
            </div>
            <!-- <div class="col-lg-4 mb-4">
                <div class="chart-container">
                    <h4>Phân bổ phương thức thanh toán</h4>
                    <canvas id="paymentMethodChart"></canvas>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="chart-container">
                    <h4>Doanh thu theo gói dịch vụ</h4>
                    <canvas id="packageRevenueChart"></canvas>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="chart-container">
                    <h4>Số lượng giao dịch theo tháng</h4>
                    <canvas id="transactionCountChart"></canvas>
                </div>
            </div> -->
        </div>
        
      
        <!-- Thống kê chi tiết -->
        <!-- <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <div class="table-container">
                    <h4>Thống kê theo phương thức thanh toán</h4>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Phương thức</th>
                                    <th>Số giao dịch</th>
                                    <th>Tổng tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result_payment_methods->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php
                                        if($row['payment_method'] == 'zalopay') {
                                            echo '<span class="payment-method-icon payment-zalopay"><i class="fas fa-wallet"></i></span>ZaloPay';
                                        } else if($row['payment_method'] == 'momo') {
                                            echo '<span class="payment-method-icon payment-momo"><i class="fas fa-money-bill"></i></span>MomoPay';
                                        } else if($row['payment_method'] == 'bank') {
                                            echo '<span class="payment-method-icon payment-bank"><i class="fas fa-university"></i></span>Ngân hàng';
                                        } else {
                                            echo '<span class="payment-method-icon payment-paypal"><i class="fab fa-paypal"></i></span>PayPal';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo number_format($row['total_transactions']); ?></td>
                                    <td><strong><?php echo number_format($row['total_amount']); ?> VNĐ</strong></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="table-container">
                    <h4>Thống kê theo gói dịch vụ</h4>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Gói dịch vụ</th>
                                    <th>Số giao dịch</th>
                                    <th>Tổng tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result_packages->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo $row['package_name']; ?></strong></td>
                                    <td><?php echo number_format($row['total_transactions']); ?></td>
                                    <td><strong><?php echo number_format($row['total_amount']); ?> VNĐ</strong></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> -->
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        const monthlyRevenueChart = new Chart(monthlyRevenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: <?php echo json_encode($revenue); ?>,
                    // Rest of your chart configuration
                }]
            },
            // Rest of your chart options
        });
        // Dữ liệu mẫu cho biểu đồ
        // Trong thực tế, bạn sẽ lấy dữ liệu này từ PHP
        
        // Biểu đồ doanh thu theo tháng
        const monthlyRevenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
        const monthlyRevenueChart = new Chart(monthlyRevenueCtx, {
            type: 'line',
            data: {
                labels: ['T3/2024', 'T4/2024', 'T5/2024', 'T6/2024', 'T7/2024', 'T8/2024', 
                         'T9/2024', 'T10/2024', 'T11/2024', 'T12/2024', 'T1/2025', 'T2/2025', 'T3/2025'],
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: [42000000, 45000000, 47500000, 46000000, 50000000, 51500000, 
                           55000000, 57500000, 60000000, 63000000, 62000000, 64500000, 67000000],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#3498db',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString() + ' VNĐ';
                            }
                        }
                    }
                }
            }
        });
        
        // Biểu đồ phân bổ phương thức thanh toán
        const paymentMethodCtx = document.getElementById('paymentMethodChart').getContext('2d');
        const paymentMethodChart = new Chart(paymentMethodCtx, {
            type: 'doughnut',
            data: {
                labels: ['ZaloPay', 'MomoPay', 'Ngân hàng', 'PayPal'],
                datasets: [{
                    data: [45, 30, 20, 5],
                    backgroundColor: ['#0068ff', '#d82d8b', '#2ecc71', '#003087'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '70%'
            }
        });
        
        // Biểu đồ doanh thu theo gói dịch vụ
        const packageRevenueCtx = document.getElementById('packageRevenueChart').getContext('2d');
        const packageRevenueChart = new Chart(packageRevenueCtx, {
            type: 'bar',
            data: {
                labels: ['Gói 1 tháng', 'Gói 3 tháng', 'Gói 6 tháng', 'Gói 12 tháng'],
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: [35000000, 120000000, 255000000, 340000000],
                    backgroundColor: [
                        'rgba(52, 152, 219, 0.7)',
                        'rgba(46, 204, 113, 0.7)',
                        'rgba(155, 89, 182, 0.7)',
                        'rgba(243, 156, 18, 0.7)'
                    ],
                    borderColor: [
                        'rgba(52, 152, 219, 1)',
                        'rgba(46, 204, 113, 1)',
                        'rgba(155, 89, 182, 1)',
                        'rgba(243, 156, 18, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString() + ' VNĐ';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Biểu đồ số lượng giao dịch theo tháng
        const transactionCountCtx = document.getElementById('transactionCountChart').getContext('2d');
        const transactionCountChart = new Chart(transactionCountCtx, {
            type: 'bar',
            data: {
                labels: ['T3/2024', 'T4/2024', 'T5/2024', 'T6/2024', 'T7/2024', 'T8/2024', 
                         'T9/2024', 'T10/2024', 'T11/2024', 'T12/2024', 'T1/2025', 'T2/2025', 'T3/2025'],
                datasets: [{
                    label: 'Số giao dịch',
                    data: [95, 110, 125, 120, 135, 140, 155, 165, 175, 190, 185, 195, 210],
                    backgroundColor: 'rgba(52, 152, 219, 0.7)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>