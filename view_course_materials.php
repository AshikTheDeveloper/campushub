<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$course_id = $_GET['course_id'] ?? 0;

// কোর্স ও টিচার ইনফো
$course_stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course = $course_stmt->get_result()->fetch_assoc();

// আপলোড করা কনটেন্ট/ফাইল
$materials_stmt = $conn->prepare("SELECT * FROM course_materials WHERE course_id = ? ORDER BY uploaded_at DESC");
$materials_stmt->bind_param("i", $course_id);
$materials_stmt->execute();
$materials = $materials_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Materials - <?php echo htmlspecialchars($course['course_code'] ?? 'Course'); ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #eef2f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .material-item { background: #f8fafc; border-left: 4px solid #3182ce; padding: 15px; border-radius: 6px; margin-bottom: 12px; }
        .btn-download { display: inline-block; background: #319795; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; margin-top: 8px; }
        .btn-back { display: inline-block; background: #4a5568; color: white; padding: 8px 14px; text-decoration: none; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
    </style>
</head>
<body>

<div class="container">
    <a href="student_dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
    <h2>📚 <?php echo htmlspecialchars($course['course_name'] ?? ''); ?> (<?php echo htmlspecialchars($course['course_code'] ?? ''); ?>)</h2>
    <p>Assigned Teacher: <b><?php echo htmlspecialchars($course['assigned_teacher'] ?? 'N/A'); ?></b></p>
    <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">

    <h3>📥 Uploaded Class Materials & Slides</h3>

    <?php if ($materials && $materials->num_rows > 0): ?>
        <?php while ($m = $materials->fetch_assoc()): ?>
            <div class="material-item">
                <h4 style="margin:0 0 5px 0; color: #2d3748;"><?php echo htmlspecialchars($m['title']); ?></h4>
                <p style="margin:0 0 8px 0; font-size: 13px; color: #4a5568;"><?php echo htmlspecialchars($m['description']); ?></p>
                <a href="uploads/materials/<?php echo $m['file_name']; ?>" class="btn-download" download>📥 Download File</a>
                <span style="font-size: 11px; color: #a0aec0; margin-left: 10px;"><?php echo date('M d, Y', strtotime($m['uploaded_at'])); ?></span>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="color: #718096;">No study materials uploaded by the teacher yet.</p>
    <?php endif; ?>
</div>

</body>
</html>