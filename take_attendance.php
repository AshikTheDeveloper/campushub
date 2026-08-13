<?php
session_start();
require_once 'config.php';

// শুধুমাত্র Teacher বা Admin এক্সেস করতে পারবে
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['username'];
$message = '';
$error = '';

$selected_course_id = $_GET['course_id'] ?? '';
$attendance_date = $_GET['date'] ?? date('Y-m-d');

// টিচারের নিজস্ব কোর্সগুলোর লিস্ট নিয়ে আসা
if ($_SESSION['role'] === 'admin') {
    $my_courses = $conn->query("SELECT * FROM courses ORDER BY course_code ASC");
} else {
    $stmt = $conn->prepare("SELECT * FROM courses WHERE teacher_id = ? ORDER BY course_code ASC");
    $stmt->bind_param("s", $teacher_id);
    $stmt->execute();
    $my_courses = $stmt->get_result();
}

// অ্যাটেনডেন্স সাবমিট প্রসেস
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_attendance'])) {
    $course_id = intval($_POST['course_id']);
    $date = $_POST['attendance_date'];
    $attendance_data = $_POST['status'] ?? [];

    if (!empty($attendance_data)) {
        foreach ($attendance_data as $student_id => $status) {
            // আগের কোনো রেকর্ড থাকলে তা আপডেট হবে, না থাকলে নতুন ইনসার্ট হবে (REPLACE / ON DUPLICATE)
            $stmt = $conn->prepare("INSERT INTO attendance (course_id, student_id, attendance_date, status, marked_by) 
                                    VALUES (?, ?, ?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by)");
            $stmt->bind_param("issss", $course_id, $student_id, $date, $status, $teacher_id);
            $stmt->execute();
            $stmt->close();
        }
        $message = "✅ Attendance recorded successfully for date: " . htmlspecialchars($date);
    } else {
        $error = "❌ No student attendance status selected!";
    }
}

// সিলেক্ট করা কোর্সের সব স্টুডেন্টদের লিস্ট আনা
$students_res = null;
if (!empty($selected_course_id)) {
    // সিস্টেমের সব স্টুডেন্ট অথবা নির্দিষ্ট কোর্সে এনরোল্ড স্টুডেন্টদের নিয়ে আসা
    $st_query = "SELECT s.student_id, s.name, s.department 
                 FROM students s 
                 ORDER BY s.student_id ASC";
    $students_res = $conn->query($st_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Take Attendance - CampusHub</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef2f5; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #1a202c; color: white; padding: 15px 25px; border-radius: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 20px; }
        .btn-back { background: #4a5568; color: white; padding: 8px 14px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13px; }
        .btn-back:hover { background: #2d3748; }

        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 13px; font-weight: bold; color: #4a5568; margin-bottom: 5px; }
        select, input[type="date"] { width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box; }

        .msg { background: #c6f6d5; color: #22543d; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
        .err-msg { background: #fed7d7; color: #742a2a; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #f7fafc; color: #4a5568; }

        .radio-group { display: flex; gap: 15px; align-items: center; }
        .radio-group label { margin: 0; font-size: 13px; cursor: pointer; font-weight: normal; }
        .radio-present { color: #2f855a; font-weight: bold !important; }
        .radio-absent { color: #c53030; font-weight: bold !important; }
        .radio-late { color: #d69e2e; font-weight: bold !important; }

        .btn-submit { background: #3182ce; color: white; border: none; padding: 12px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px; width: 100%; margin-top: 15px; }
        .btn-submit:hover { background: #2b6cb0; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>📋 Course-wise Attendance Sheet</h2>
        <a href="javascript:history.back()" class="btn-back">⬅️ Go Back</a>
    </div>

    <?php if(!empty($message)): ?>
        <div class="msg"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <div class="err-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Filter Form -->
    <div class="card">
        <form method="GET" action="">
            <div style="display: grid; grid-template-columns: 1fr 1fr 120px; gap: 15px; align-items: end;">
                <div>
                    <label>Select Course:</label>
                    <select name="course_id" required>
                        <option value="">-- Choose Course --</option>
                        <?php if($my_courses && $my_courses->num_rows > 0): ?>
                            <?php while($c = $my_courses->fetch_assoc()): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($selected_course_id == $c['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['course_code']) . " - " . htmlspecialchars($c['course_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div>
                    <label>Attendance Date:</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($attendance_date); ?>" required>
                </div>

                <div>
                    <button type="submit" style="background: #4a5568; color: white; border: none; padding: 10px; border-radius: 6px; font-weight: bold; cursor: pointer; width: 100%;">Load Sheet</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Attendance Form -->
    <?php if(!empty($selected_course_id)): ?>
        <div class="card">
            <form method="POST" action="">
                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($selected_course_id); ?>">
                <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($attendance_date); ?>">

                <h3>Student Roll Call (Date: <?php echo htmlspecialchars($attendance_date); ?>)</h3>
                
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Department</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($students_res && $students_res->num_rows > 0): ?>
                            <?php while($st = $students_res->fetch_assoc()): ?>
                                <tr>
                                    <td><b><?php echo htmlspecialchars($st['student_id']); ?></b></td>
                                    <td><?php echo htmlspecialchars($st['name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($st['department'] ?? 'CSE'); ?></td>
                                    <td>
                                        <div class="radio-group">
                                            <label class="radio-present">
                                                <input type="radio" name="status[<?php echo $st['student_id']; ?>]" value="Present" checked> Present
                                            </label>
                                            <label class="radio-absent">
                                                <input type="radio" name="status[<?php echo $st['student_id']; ?>]" value="Absent"> Absent
                                            </label>
                                            <label class="radio-late">
                                                <input type="radio" name="status[<?php echo $st['student_id']; ?>]" value="Late"> Late
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; color: #718096;">No students found in system.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <button type="submit" name="save_attendance" class="btn-submit">Save Attendance Record 💾</button>
            </form>
        </div>
    <?php endif; ?>
</div>

</body>
</html>