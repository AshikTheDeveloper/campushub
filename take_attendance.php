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

// টিচারের নিজস্ব কোর্সগুলোর লিস্ট নিয়ে আসা (Error-Safe Query)
$my_courses = false;
if ($_SESSION['role'] === 'admin') {
    $my_courses = $conn->query("SELECT * FROM courses ORDER BY course_code ASC");
} else {
    $stmt = $conn->prepare("SELECT * FROM courses WHERE teacher_id = ? OR instructor_id = ? ORDER BY course_code ASC");
    if ($stmt) {
        $stmt->bind_param("ss", $teacher_id, $teacher_id);
        $stmt->execute();
        $my_courses = $stmt->get_result();
        $stmt->close();
    }
    
    // যদি teacher_id / instructor_id কলাম না থাকে বা কোনো ড্রপডাউন কোর্স না আসে, তবে সব কোর্স লোড করার Fallback
    if (!$my_courses || $my_courses->num_rows === 0) {
        $my_courses = $conn->query("SELECT * FROM courses ORDER BY course_code ASC");
    }
}

// অ্যাটেনডেন্স সাবমিট প্রসেস
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_attendance'])) {
    $course_id = intval($_POST['course_id']);
    $date = $_POST['attendance_date'];
    $attendance_data = $_POST['status'] ?? [];

    if (!empty($attendance_data)) {
        foreach ($attendance_data as $student_id => $status) {
            // আগের কোনো রেকর্ড থাকলে তা আপডেট হবে, না থাকলে নতুন ইনসার্ট হবে (ON DUPLICATE KEY UPDATE)
            $stmt = $conn->prepare("INSERT INTO attendance (course_id, student_id, attendance_date, status, marked_by) 
                                    VALUES (?, ?, ?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by)");
            if ($stmt) {
                $stmt->bind_param("issss", $course_id, $student_id, $date, $status, $teacher_id);
                $stmt->execute();
                $stmt->close();
            }
        }
        $message = "✅ Attendance recorded successfully for date: " . htmlspecialchars($date);
    } else {
        $error = "❌ No student attendance status selected!";
    }
}

// সিলেক্ট করা কোর্সের এনরোল্ড স্টুডেন্টদের লিস্ট ও তাদের পূর্বের সেভ করা অ্যাটেনডেন্স ডাটা আনা
$students_res = null;
$selected_course_info = null;
$existing_attendance = [];

if (!empty($selected_course_id)) {
    // ১. আগে সিলেক্টেড কোর্সের Department এবং Batch জেনে নেওয়া (Error-Safe)
    $c_stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    if ($c_stmt) {
        $c_stmt->bind_param("i", $selected_course_id);
        $c_stmt->execute();
        $c_res = $c_stmt->get_result();
        
        if ($c_res && $c_res->num_rows > 0) {
            $selected_course_info = $c_res->fetch_assoc();
            $course_dept = $selected_course_info['department'] ?? $selected_course_info['department_id'] ?? '';
            $course_batch = $selected_course_info['batch'] ?? '';

            // ২. স্টুডেন্ট ফেচ করার ক্যোয়ারি (Multi-fallback Query)
            if (!empty($course_dept) && !empty($course_batch)) {
                $st_stmt = $conn->prepare("SELECT s.student_id, s.name, s.department, s.batch 
                                           FROM students s 
                                           WHERE s.department = ? AND s.batch = ? 
                                           ORDER BY s.student_id ASC");
                if ($st_stmt) {
                    $st_stmt->bind_param("ss", $course_dept, $course_batch);
                    $st_stmt->execute();
                    $students_res = $st_stmt->get_result();
                }
            } elseif (!empty($course_batch)) {
                // ডিপার্টমেন্ট কলাম কোর্সে না থাকলে শুধু ব্যাচ দিয়ে ফিল্টার
                $st_stmt = $conn->prepare("SELECT s.student_id, s.name, s.department, s.batch 
                                           FROM students s 
                                           WHERE s.batch = ? 
                                           ORDER BY s.student_id ASC");
                if ($st_stmt) {
                    $st_stmt->bind_param("s", $course_batch);
                    $st_stmt->execute();
                    $students_res = $st_stmt->get_result();
                }
            } elseif (!empty($course_dept)) {
                // শুধু ডিপার্টমেন্ট দিয়ে ফিল্টার
                $st_stmt = $conn->prepare("SELECT s.student_id, s.name, s.department, s.batch 
                                           FROM students s 
                                           WHERE s.department = ? 
                                           ORDER BY s.student_id ASC");
                if ($st_stmt) {
                    $st_stmt->bind_param("s", $course_dept);
                    $st_stmt->execute();
                    $students_res = $st_stmt->get_result();
                }
            }

            // Fallback: যদি উপরের কোনো শর্তে স্টুডেন্ট না পাওয়া যায়, তবে ডাটাবেজের সব স্টুডেন্ট লোড করবে
            if (!$students_res || $students_res->num_rows === 0) {
                $students_res = $conn->query("SELECT student_id, name, department, batch FROM students ORDER BY student_id ASC");
            }
        }
        $c_stmt->close();
    }

    // ৩. উক্ত তারিখ ও কোর্সের জন্য আগে থেকে জমা হওয়া অ্যাটেনডেন্স ফেচ করা
    $att_fetch_stmt = $conn->prepare("SELECT student_id, status FROM attendance WHERE course_id = ? AND attendance_date = ?");
    if ($att_fetch_stmt) {
        $att_fetch_stmt->bind_param("is", $selected_course_id, $attendance_date);
        $att_fetch_stmt->execute();
        $att_fetch_res = $att_fetch_stmt->get_result();
        while ($row = $att_fetch_res->fetch_assoc()) {
            $existing_attendance[$row['student_id']] = $row['status'];
        }
        $att_fetch_stmt->close();
    }
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
                <?php if($selected_course_info): ?>
                    <p style="font-size: 13px; color: #718096; margin-top: -8px;">
                        Course: <b><?php echo htmlspecialchars($selected_course_info['course_code']); ?></b> | 
                        Dept: <b><?php echo htmlspecialchars($selected_course_info['department'] ?? $selected_course_info['department_id'] ?? 'CSE'); ?></b> | 
                        Batch: <b><?php echo htmlspecialchars($selected_course_info['batch'] ?? 'All'); ?></b>
                    </p>
                <?php endif; ?>
                
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
                            <?php while($st = $students_res->fetch_assoc()): 
                                $st_id = $st['student_id'];
                                // ডিফল্ট 'Present', যদি ডাটাবেজে আগে রেকর্ড থাকে তবে সেটি বসবে
                                $current_status = $existing_attendance[$st_id] ?? 'Present';
                            ?>
                                <tr>
                                    <td><b><?php echo htmlspecialchars($st_id); ?></b></td>
                                    <td><?php echo htmlspecialchars($st['name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($st['department'] ?? 'CSE'); ?></td>
                                    <td>
                                        <div class="radio-group">
                                            <label class="radio-present">
                                                <input type="radio" name="status[<?php echo $st_id; ?>]" value="Present" <?php echo ($current_status === 'Present') ? 'checked' : ''; ?>> Present
                                            </label>
                                            <label class="radio-absent">
                                                <input type="radio" name="status[<?php echo $st_id; ?>]" value="Absent" <?php echo ($current_status === 'Absent') ? 'checked' : ''; ?>> Absent
                                            </label>
                                            <label class="radio-late">
                                                <input type="radio" name="status[<?php echo $st_id; ?>]" value="Late" <?php echo ($current_status === 'Late') ? 'checked' : ''; ?>> Late
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; color: #718096;">No enrolled students found for this course's department/batch.</td></tr>
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