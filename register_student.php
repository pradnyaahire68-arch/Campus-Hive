<?php
session_start();
require_once '../config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $college = trim($_POST['college']);
    $course = trim($_POST['course']);
    $semester = trim($_POST['semester']);
    $address = trim($_POST['address']);

    if ($password !== $confirm_password) {
        $message = 'Passwords do not match.';
    } else {
        $db = new Database();
        $conn = $db->getConnection();

        // Check if email already exists
        $stmt = $conn->prepare("SELECT student_id FROM students WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $message = 'Email already registered.';
        } else {
            // Geocode the address to get latitude and longitude
            $latitude = null;
            $longitude = null;

            if (!empty($address)) {
                // Use Google Maps Geocoding API (you'll need to replace YOUR_API_KEY with actual key)
                $api_key = 'YOUR_API_KEY'; // Replace with your actual Google Maps API key
                $geocode_url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key=" . $api_key;

                $geocode_response = file_get_contents($geocode_url);
                $geocode_data = json_decode($geocode_response, true);

                if ($geocode_data['status'] == 'OK') {
                    $latitude = $geocode_data['results'][0]['geometry']['location']['lat'];
                    $longitude = $geocode_data['results'][0]['geometry']['location']['lng'];
                }
            }

            // Insert new student
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO students (name, email, password, phone, college, course, semester, address, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $hashed_password, $phone, $college, $course, $semester, $address, $latitude, $longitude])) {
                $student_id = $conn->lastInsertId();
                $_SESSION['user_id'] = $student_id;
                $_SESSION['user_type'] = 'student';
                $_SESSION['email'] = $email;
                $_SESSION['name'] = $name;
                $_SESSION['login_time'] = time();
                header('Location: ../student/dashboard.php');
                exit();
                $message = 'Registration successful! You can now <a href="login.php">login</a>.';
            } else {
                $message = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - CampusHive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">CampusHive</a>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Student Registration</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-info"><?php echo $message; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="college" class="form-label">College Name</label>
                                    <input type="text" class="form-control" id="college" name="college" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="course" class="form-label">Course/Program</label>
                                    <input type="text" class="form-control" id="course" name="course" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="semester" class="form-label">Semester/Year</label>
                                    <input type="text" class="form-control" id="semester" name="semester" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3" placeholder="Enter your full address" required></textarea>
                                <small class="form-text text-muted">This will be used to show your location on maps for event proximity.</small>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Register</button>
                        </form>

                        <div class="text-center mt-3">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
