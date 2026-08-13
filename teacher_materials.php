<?php
session_start();
require_once 'config.php';

// শিক্ষক লগইন নিশ্চিত করা
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_username = $_SESSION['username'];
$message = '';
$error = '';

// ১. ফাইল আপলোড করার লজিক
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_material'])) {
    $course_id  = intval($_POST['course_id']);
    $department = trim($_POST['department']);
    $batch      = trim($_POST['batch']);
    $title      = trim($_POST['title']);
    $type       = trim($_POST['type']); // note, assignment, notice

    // ফাইল হ্যান্ডলিং
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $target_dir = "uploads/materials/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES["file"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
            // ডাটাবেজের কলাম সিকোয়েন্স: course_id, teacher_username, title, type, file_path, department, batch
            $stmt = $conn->prepare("INSERT INTO course_materials (course_id, teacher_username, title, type, file_path, department, batch) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("issssss", $course_id, $teacher_username, $title, $type, $target_file, $department, $batch);
                if ($stmt->execute()) {
                    $message = "✅ Material uploaded successfully!";
                } else {
                    $error = "❌ Database error: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $error = "❌ Failed to upload file!";
        }
    } else {
        $error = "❌ Please select a valid file!";
    }
}

// ২. কেবল এই শিক্ষকের অ্যাসাইন করা কোর্সসমূহ আনা
$my_courses = $conn->prepare("SELECT id, course_code, course_name FROM courses WHERE assigned_teacher = ?");
$my_courses->bind_param("s", $teacher_username);
$my_courses->execute();
$courses_res = $my_courses->get_result();

// ৩. এই শিক্ষকের আপলোড করা পূর্বের উপাদানসমূহ আনা
$materials_query = $conn->prepare("SELECT m.*, c.course_code FROM course_materials m JOIN courses c ON m.course_id = c.id WHERE m.teacher_username = ? ORDER BY m.id DESC");
$materials_query->bind_param("s", $teacher_username);
$materials_query->execute();
$materials_res = $materials_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Materials - Teacher Portal</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef2f5; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #1a202c; color: white; padding: 15px 25px; border-radius: 10px; margin-bottom: 20px; }
        .btn-back { background: #4a5568; color: white; padding: 8px 14px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13px; }
        .main-grid { display: grid; grid-template-columns: 320px 1fr; gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 12px; }
        label { display: block; font-size: 13px; font-weight: bold; color: #4a5568; margin-bottom: 4px; }
        input, select { width: 100%; padding: 9px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box; }
        .btn-submit { width: 100%; background: #38a169; color: white; border: none; padding: 10px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .msg { background: #c6f6d5; color: #22543d; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
        .err { background: #fed7d7; color: #742a2a; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #f7fafc; }
        .badge { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-note { background: #e2e8f0; color: #2d3748; }
        .badge-assignment { background: #feebc8; color: #742a2a; }
        .badge-notice { background: #fed7d7; color: #9b2c2c; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>📁 Upload Course Materials</h2>
        <a href="teacher_dashboard.php" class="btn-back">⬅️ Back to Dashboard</a>
    </div>

    <?php if(!empty($message)) echo "<div class='msg'>$message</div>"; ?>
    <?php if(!empty($error)) echo "<div class='err'>$error</div>"; ?>

    <div class="main-grid">
        <!-- Form -->
        <div class="card">
            <h3>➕ Upload File</h3>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Select Course:</label>
                    <select name="course_id" required>
                        <option value="">-- Choose Course --</option>
                        <?php while($c = $courses_res->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Department:</label>
                    <input type="text" name="department" value="CSE" required>
                </div>

                <div class="form-group">
                    <label>Target Batch:</label>
                    <input type="text" name="batch" placeholder="e.g. 61" required>
                </div>

                <div class="form-group">
                    <label>Title / Description:</label>
                    <input type="text" name="title" placeholder="e.g. Lecture 1 PDF" required>
                </div>

                <div class="form-group">
                    <label>Material Type:</label>
                    <select name="type" required>
                        <option value="note">Lecture Note</option>
                        <option value="assignment">Assignment</option>
                        <option value="notice">Class Notice</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Select File:</label>
                    <input type="file" name="file" required>
                </div>

                <button type="submit" name="upload_material" class="btn-submit">Upload Material 🚀</button>
            </form>
        </div>

        <!-- Material List -->
        <div class="card">
            <h3>📖 Uploaded Materials List</h3>
            <table>
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Batch</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($materials_res && $materials_res->num_rows > 0): ?>
                        <?php while($m = $materials_res->fetch_assoc()): ?>
                            <tr>
                                <td><b><?php echo htmlspecialchars($m['course_code']); ?></b></td>
                                <td><?php echo !empty($m['batch']) ? htmlspecialchars($m['batch']) : 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($m['title']); ?></td>
                                <td><span class="badge badge-<?php echo $m['type']; ?>"><?php echo $m['type']; ?></span></td>
                                <td><a href="<?php echo $m['file_path']; ?>" target="_blank">View / Download 📥</a></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; color:#777;">No materials uploaded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>