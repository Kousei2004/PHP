<?php
session_start();

// Xử lý form đăng ký
if(isset($_POST['register'])) {
    $fullname = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $package = $_POST['package'] ?? '';
    
    // Kiểm tra dữ liệu đầu vào
    if(empty($fullname) || empty($email) || empty($password) || empty($package)) {
        $error = "Vui lòng điền đầy đủ thông tin!";
    } else {
        // Lưu thông tin vào session để sử dụng ở trang thanh toán
        $_SESSION['user_info'] = [
            'fullname' => $fullname,
            'email' => $email,
            'package' => $package
        ];
        
        // Chuyển hướng đến trang thanh toán
        header("Location: payment.php");
        exit();
    }
}

// Thông tin các gói
$packages = [
    'monthly' => ['name' => 'Gói 1 tháng', 'price' => 79000],
    'sixmonths' => ['name' => 'Gói 6 tháng', 'price' => 450000],
    'yearly' => ['name' => 'Gói 1 năm', 'price' => 790000]
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản Phimflix</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #000;
            color: #fff;
            min-height: 100vh;
            position: relative;
            width: 100%;
            min-height: 90vh;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.48) 0, rgba(0, 0, 0, 0.24) 60%, rgba(0, 0, 0, 0.34) 100%),
                url('../../assets/img/nen.jpg') center/cover no-repeat;
        }
        .logo {
            color: #e50914;
            font-size: 2.5rem;
            font-weight: bold;
        }
        .register-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background-color: rgba(0, 0, 0, 0.75);
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        .package-card {
            background-color: #333;
            border: 1px solid #444;
            transition: all 0.3s;
            cursor: pointer;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .package-card:hover, .package-card.selected {
            background-color: #e50914;
            border-color: #e50914;
        }
        .btn-primary {
            background-color: #e50914;
            border-color: #e50914;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #b2070f;
            border-color: #b2070f;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="text-center mb-4">
            <div class="logo">PHIMFLIX</div>
            <p>Đăng ký để xem hàng ngàn bộ phim và series</p>
        </div>
        
        <div class="register-container">
            <h2 class="text-center mb-4">Tạo tài khoản</h2>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="mb-3">
                    <label for="fullname" class="form-label">Họ và tên</label>
                    <input type="text" class="form-control" id="fullname" name="fullname" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <h4 class="mt-4 mb-3">Chọn gói dịch vụ</h4>
                
                <div class="package-options">
                    <?php foreach($packages as $key => $package): ?>
                        <div class="package-card p-3" data-package="<?php echo $key; ?>">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="package" id="<?php echo $key; ?>" value="<?php echo $key; ?>" required>
                                <label class="form-check-label d-block" for="<?php echo $key; ?>">
                                    <strong><?php echo $package['name']; ?></strong>
                                    <div class="mt-2">
                                        <span class="price"><?php echo number_format($package['price']); ?> VNĐ</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="d-grid mt-4">
                    <button type="submit" name="register" class="btn btn-primary btn-lg">Tiếp tục</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Thêm hiệu ứng chọn gói
        document.querySelectorAll('.package-card').forEach(card => {
            card.addEventListener('click', function() {
                // Bỏ chọn tất cả
                document.querySelectorAll('.package-card').forEach(c => c.classList.remove('selected'));
                // Chọn gói hiện tại
                this.classList.add('selected');
                // Chọn radio button
                const packageId = this.getAttribute('data-package');
                document.getElementById(packageId).checked = true;
            });
        });
    </script>
</body>
</html>