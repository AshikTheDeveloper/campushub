<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$message = '';
$error = '';

// teachers টেবিল থেকে টিচারের সকল প্রোফাইল ডাটা ফেচ করার লজিক
$teacher_info = [
    'teacher_id' => $username,
    'name'       => $username,
    'email'      => 'N/A',
    'department' => 'N/A',
    'designation'=> 'Faculty Member'
];

$t_stmt = $conn->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
$t_stmt->bind_param("s", $username);
$t_stmt->execute();
$t_res = $t_stmt->get_result();

if ($t_row = $t_res->fetch_assoc()) {
    $teacher_info['name']       = !empty($t_row['name']) ? $t_row['name'] : $username;
    $teacher_info['email']      = !empty($t_row['email']) ? $t_row['email'] : 'N/A';
    $teacher_info['department'] = !empty($t_row['department']) ? $t_row['department'] : 'N/A';
    
    // যদি ডাটাবেজে designation কলাম থাকে
    if (isset($t_row['designation']) && !empty($t_row['designation'])) {
        $teacher_info['designation'] = $t_row['designation'];
    }
}
$t_stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_notice'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $posted_by = "Teacher (" . $teacher_info['name'] . ")";
    $file_name = null;

    if (!empty($title) && !empty($description)) {
        
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_name = time() . "_" . basename($_FILES["attachment"]["name"]);
            $target_file = $target_dir . $file_name;
            move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file);
        }

        $stmt = $conn->prepare("INSERT INTO notices (title, description, posted_by, file_name) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $title, $description, $posted_by, $file_name);

        if ($stmt->execute()) {
            $message = "✅ Notice posted successfully!";
        } else {
            $error = "❌ Failed to post notice: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "❌ Title and Description are required!";
    }
}

if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM notices WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $message = "🗑️ Notice deleted successfully!";
    }
    $stmt->close();
}

$notices_result = $conn->query("SELECT * FROM notices ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Portal - CampusHub</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: auto; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #1a202c; color: white; }
        .header h2 { margin: 0; font-size: 22px; }
        .btn-logout { background: #dc3545; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .btn-logout:hover { background: #c82333; }

        /* Profile Details Styling */
        .profile-container { margin-top: 15px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 18px; border-radius: 8px; }
        .profile-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 10px; }
        .profile-item { background: white; padding: 12px; border-radius: 6px; border: 1px solid #edf2f7; }
        .profile-item span { display: block; font-size: 12px; color: #718096; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; }
        .profile-item p { margin: 0; font-size: 15px; color: #2d3748; font-weight: 600; }

        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .box { background: #eef2f5; padding: 20px; border-radius: 8px; border-left: 5px solid #28a745; transition: transform 0.2s ease; }
        .box:hover { transform: translateY(-2px); }
        .box h3 { margin-top: 0; font-size: 18px; }
        .box h3 a { color: #28a745; text-decoration: none; }
        .box h3 a:hover { text-decoration: underline; }
        .box p { margin-bottom: 0; font-size: 14px; color: #555; }

        .form-group { margin-bottom: 15px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; color: #333; }
        input[type="text"], textarea, input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 15px; }
        .btn-submit:hover { background: #218838; }

        .msg { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .err-msg { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; }

        .notice-item { background: #f8f9fa; border-left: 4px solid #007bff; padding: 15px; border-radius: 4px; margin-bottom: 10px; position: relative; }
        .notice-item h4 { margin: 0 0 5px 0; color: #333; }
        .notice-item p { margin: 0 0 8px 0; font-size: 14px; color: #555; }
        .notice-meta { font-size: 12px; color: #888; }
        .btn-delete { color: #dc3545; text-decoration: none; font-weight: bold; font-size: 13px; float: right; }
        .btn-delete:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="card header">
        <h2>👨‍🏫 Teacher Portal</h2>
        <div>
            <a href="change_password.php" style="background: #4a5568; color: white; padding: 8px 14px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 13px; margin-right: 8px;">🔑 Change Password</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <!-- Teacher Profile Card -->
    <div class="card">
        <h3 style="margin-bottom: 5px;">Welcome, <?php echo htmlspecialchars($teacher_info['name']); ?>! 👋</h3>
        <p style="margin-top: 0; color: #718096;">Teacher Profile & Information Details</p>

        <div class="profile-container">
            <div class="profile-grid">
                <div class="profile-item">
                    <span>Teacher ID</span>
                    <p><?php echo htmlspecialchars($teacher_info['teacher_id']); ?></p>
                </div>
                <div class="profile-item">
                    <span>Email Address</span>
                    <p><?php echo htmlspecialchars($teacher_info['email']); ?></p>
                </div>
                <div class="profile-item">
                    <span>Department</span>
                    <p><?php echo htmlspecialchars($teacher_info['department']); ?></p>
                </div>
                <div class="profile-item">
                    <span>Designation</span>
                    <p><?php echo htmlspecialchars($teacher_info['designation']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid">
        <div class="box">
            <h3>📋 <a href="manage_marks.php">Student Marks & Results</a></h3>
            <p>Upload exam marks, assign grades, and submit results.</p>
        </div>
        <div class="box">
            <h3>📅 <a href="take_attendance.php">Class Attendance</a></h3>
            <p>Take daily class attendance and view attendance reports.</p>
        </div>
        <div class="box">
            <h3>🗓️ <a href="manage_routine.php">Class Routine</a></h3>
            <p>View and manage weekly class schedules and room numbers.</p>
        </div>
        <div class="box">
            <h3>📂 <a href="teacher_materials.php">Course Materials</a></h3>
            <p>Upload lecture notes, assignments, and class notices.</p>
        </div>
    </div>

    <div class="card">
        <h3>📢 Post New Notice / Announcement</h3>

        <?php if(!empty($message)): ?>
            <div class="msg"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if(!empty($error)): ?>
            <div class="err-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Notice Title:</label>
                <input type="text" name="title" placeholder="e.g. Midterm Exam Schedule" required>
            </div>

            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" rows="4" placeholder="Write the details here..." required></textarea>
            </div>

            <div class="form-group">
                <label>Attachment (Optional - PDF, Image, Doc):</label>
                <input type="file" name="attachment">
            </div>

            <button type="submit" name="post_notice" class="btn-submit">Publish Notice 🚀</button>
        </form>
    </div>

    <div class="card">
        <h3>📋 Published Notices</h3>

        <?php if ($notices_result && $notices_result->num_rows > 0): ?>
            <?php while ($notice = $notices_result->fetch_assoc()): ?>
                <div class="notice-item">
                    <a href="teacher_dashboard.php?delete_id=<?php echo $notice['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this notice?');">Delete ❌</a>
                    <h4><?php echo htmlspecialchars($notice['title']); ?></h4>
                    <p><?php echo nl2br(htmlspecialchars($notice['description'])); ?></p>
                    <div class="notice-meta">
                        Posted by: <b><?php echo htmlspecialchars($notice['posted_by']); ?></b> | 
                        <?php echo date('M d, Y - h:i A', strtotime($notice['created_at'])); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color: #777;">No notices posted yet.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>