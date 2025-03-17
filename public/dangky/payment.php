<?php
ob_start();
session_start();

// Kiểm tra xem người dùng đã đăng ký chưa
if(!isset($_SESSION['user_info'])) {
    header("Location: index.php");
    exit();
}

// Thông tin các gói
$packages = [
    'monthly' => ['name' => 'Gói 1 tháng', 'price' => 79000],
    'sixmonths' => ['name' => 'Gói 6 tháng', 'price' => 450000],
    'yearly' => ['name' => 'Gói 1 năm', 'price' => 790000]
];

// Lấy thông tin gói đã chọn
$selectedPackage = $_SESSION['user_info']['package'];
$packageInfo = $packages[$selectedPackage];

// Tạo ID giao dịch ngẫu nhiên
$transactionId = 'PF' . date('YmdHis') . rand(1000, 9999);
$_SESSION['transaction_id'] = $transactionId;

// Xử lý thanh toán
if(isset($_POST['pay'])) {
    $paymentMethod = $_POST['payment_method'] ?? '';
    
    if(empty($paymentMethod)) {
        $error = "Vui lòng chọn phương thức thanh toán!";
    } else {
        // Hiển thị mã QR để quét
        $_SESSION['show_qr'] = true;
        $_SESSION['payment_method'] = $paymentMethod;
    }
}

// Xử lý xác nhận thanh toán
if(isset($_POST['confirm_payment'])) {
    // Giả lập xử lý thanh toán thành công
    $_SESSION['payment_success'] = true;
    
    // Chuyển hướng đến trang xác nhận
    header("Location: confirmation.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Phimflix</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #000;
            color: #fff;
            font-family: 'Arial', sans-serif;
        }
        .logo {
            color: #e50914;
            font-size: 2.5rem;
            font-weight: bold;
        }
        .payment-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: rgba(0, 0, 0, 0.75);
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        .payment-method-card {
            background-color: #333;
            border: 1px solid #444;
            transition: all 0.3s;
            cursor: pointer;
            border-radius: 5px;
            margin-bottom: 15px;
            padding: 15px;
        }
        .payment-method-card:hover, .payment-method-card.selected {
            background-color: #444;
            border-color: #555;
        }
        .payment-method-card img {
            max-height: 40px;
            max-width: 120px;
            margin-right: 15px;
        }
        .payment-method-card.selected {
            border: 2px solid #e50914;
        }
        .btn-primary {
            background-color: #e50914;
            border-color: #e50914;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #b2070f;
            border-color: #b2070f;
        }
        .order-summary {
            background-color: #222;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 25px;
        }
        .qr-container {
            text-align: center;
            margin: 25px 0;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
        }
        .qr-code {
            width: 220px;
            height: 220px;
            margin: 0 auto;
            background-color: white;
            padding: 10px;
            border-radius: 5px;
        }
        .qr-info {
            margin-top: 15px;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="text-center mb-4">
            <div class="logo">PHIMFLIX</div>
            <p>Hoàn tất thanh toán để kích hoạt tài khoản</p>
        </div>
        
        <div class="payment-container">
            <h2 class="text-center mb-4">Thanh toán</h2>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="order-summary">
                <h4 class="mb-3">Thông tin đơn hàng</h4>
                <div class="row mb-2">
                    <div class="col-6">Họ và tên:</div>
                    <div class="col-6 text-end"><?php echo $_SESSION['user_info']['fullname']; ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6">Email:</div>
                    <div class="col-6 text-end"><?php echo $_SESSION['user_info']['email']; ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6">Gói dịch vụ:</div>
                    <div class="col-6 text-end"><?php echo $packageInfo['name']; ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6">Mã giao dịch:</div>
                    <div class="col-6 text-end"><?php echo $transactionId; ?></div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6"><strong>Tổng tiền:</strong></div>
                    <div class="col-6 text-end"><strong><?php echo number_format($packageInfo['price']); ?> VNĐ</strong></div>
                </div>
            </div>
            
            <?php if(isset($_SESSION['show_qr'])): ?>
                <!-- Hiển thị mã QR để thanh toán -->
                <div class="qr-container">
                    <h4 class="text-dark mb-3">Quét mã QR để thanh toán</h4>
                    <div class="qr-code">
                        <!-- Dùng placeholder cho mã QR -->
                        <img src="/webfilm/assets/img/QR.jpg" alt="Mã QR Thanh toán" class="img-fluid">
                    </div>
                    <div class="qr-info">
                        <p>Sử dụng ứng dụng <?php echo $_SESSION['payment_method'] == 'momo' ? 'MoMo' : 'ZaloPay'; ?> để quét mã</p>
                        <p>Số tiền: <strong><?php echo number_format($packageInfo['price']); ?> VNĐ</strong></p>
                        <p>Nội dung chuyển khoản: <strong><?php echo $transactionId; ?></strong></p>
                    </div>
                </div>
                
                <form method="post" action="">
                    <div class="d-grid mt-4">
                        <button type="submit" name="confirm_payment" class="btn btn-primary btn-lg">Tôi đã thanh toán</button>
                    </div>
                </form>
            <?php else: ?>
                <form method="post" action="">
                    <h4 class="mb-3">Chọn phương thức thanh toán</h4>
                    
                    <div class="payment-methods">
                        <div class="payment-method-card d-flex align-items-center" data-method="momo">
                            <input class="form-check-input me-2" type="radio" name="payment_method" id="momo" value="momo" required>
                            <img src="/api/placeholder/120/40" alt="MoMo" class="payment-logo">
                            <label class="form-check-label flex-grow-1" for="momo">
                                Ví điện tử MoMo
                            </label>
                        </div>
                        
                        <div class="payment-method-card d-flex align-items-center" data-method="zalopay">
                            <input class="form-check-input me-2" type="radio" name="payment_method" id="zalopay" value="zalopay" required>
                            <img src="/api/placeholder/120/40" alt="ZaloPay" class="payment-logo">
                            <label class="form-check-label flex-grow-1" for="zalopay">
                                Ví điện tử ZaloPay
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-grid mt-4">
                        <button type="submit" name="pay" class="btn btn-primary btn-lg">Thanh toán ngay</button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="index.php" class="text-light">Quay lại</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Thêm hiệu ứng chọn phương thức thanh toán
        document.querySelectorAll('.payment-method-card').forEach(card => {
            card.addEventListener('click', function() {
                // Bỏ chọn tất cả
                document.querySelectorAll('.payment-method-card').forEach(c => c.classList.remove('selected'));
                // Chọn phương thức hiện tại
                this.classList.add('selected');
                // Chọn radio button
                const method = this.getAttribute('data-method');
                document.getElementById(method).checked = true;
            });
        });
    </script>
</body>
</html>