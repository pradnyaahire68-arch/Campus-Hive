<?php
session_start();
require_once '../config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];

    $db = new Database();
    $conn = $db->getConnection();

    if ($user_type == 'admin') {
        // Admin login
        if ($email == 'admin@campusconnect.com' && $password == 'admin123') {
            $_SESSION['user_id'] = 1;
            $_SESSION['user_type'] = 'admin';
            $_SESSION['email'] = $email;
            header('Location: ../admin/dashboard.php');
            exit();
        } else {
            $message = 'Invalid admin credentials.';
        }
    } else {
        // Student or College login
        $table = ($user_type == 'student') ? 'students' : 'colleges';
        $id_field = ($user_type == 'student') ? 'student_id' : 'college_id';

        $stmt = $conn->prepare("SELECT * FROM $table WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user[$id_field];
            $_SESSION['user_type'] = $user_type;
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['login_time'] = time();

            if ($user_type == 'student') {
                header('Location: ../student/dashboard.php');
            } else {
                header('Location: ../college/dashboard.php');
            }
            exit();
        } else {
            $message = 'Invalid email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CampusHive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <span class="fw-bold text-primary">Campus</span><span class="fw-bold text-accent">Hive</span>
            </a>
            <div class="ms-auto">
                <a href="../index.php" class="btn btn-outline-accent btn-sm">
                    <i class="fas fa-home me-1"></i>Back to Home
                </a>
            </div>
        </div>
    </nav>

    <!-- Login Section -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card shadow border-0">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <div class="bg-accent rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="fas fa-sign-in-alt fa-2x text-white"></i>
                                </div>
                                <h2 class="text-primary mb-2">Welcome Back</h2>
                                <p class="text-muted">Sign in to your CampusHive account</p>
                            </div>

                            <?php if ($message): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" data-validate>
                                <div class="mb-4">
                                    <label for="user_type" class="form-label fw-bold">Login as:</label>
                                    <select class="form-select form-select-lg" id="user_type" name="user_type" required>
                                        <option value="">Choose your role</option>
                                        <option value="student">🎓 Student</option>
                                        <option value="college">🏫 College Representative</option>
                                        <option value="admin">⚙️ Administrator</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label for="email" class="form-label fw-bold">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-envelope text-muted"></i>
                                        </span>
                                        <input type="email" class="form-control form-control-lg" id="email" name="email" placeholder="Enter your email" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="password" class="form-label fw-bold">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock text-muted"></i>
                                        </span>
                                        <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Enter your password" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="rememberMe">
                                        <label class="form-check-label text-muted" for="rememberMe">
                                            Remember me
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg w-100 mb-3" data-loading-text="Signing in..." style="background-color: #3182ce; color: white; border: none; padding: 0.75rem 1.5rem; font-weight: 500; border-radius: 6px; cursor: pointer;">
                                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                                </button>
                            </form>

                            <div class="text-center">
                                <p class="text-muted mb-3">Don't have an account?</p>
                                <div class="d-flex gap-2 justify-content-center">
                                    <a href="register_student.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-user-plus me-1"></i>Join as Student
                                    </a>
                                    <a href="register_college.php" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-university me-1"></i>Add Your College
                                    </a>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="text-center">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Your data is secure and encrypted
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Demo Credentials -->
                    <div class="card mt-4 border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Demo Credentials
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-4">
                                    <div class="text-center">
                                        <strong class="text-primary">Admin</strong><br>
                                        <small class="text-muted">admin@campusconnect.com<br>admin123</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="text-center">
                                        <strong class="text-success">College</strong><br>
                                        <small class="text-muted">Register new college<br>or use existing</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="text-center">
                                        <strong class="text-info">Student</strong><br>
                                        <small class="text-muted">Register new student<br>or use existing</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
