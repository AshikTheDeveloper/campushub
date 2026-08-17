<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// ১. কোর্স অ্যাড করার লজিক (অটো-এনরোলমেন্টসহ)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_course'])) {
    $course_code = trim($_POST['course_code']);
    $course_name = trim($_POST['course_name']);
    $credits     = trim($_POST['credits']);
    $dept_input  = trim($_POST['department']);
    $batch       = trim($_POST['batch']); // Batch field
    $teacher     = trim($_POST['teacher']); // Selected teacher username

    // ডিপার্টমেন্ট আইডি খোঁজা
    $dept_id = null;
    $d_stmt = $conn->prepare("SELECT id FROM departments WHERE name = ? OR short_name = ?");
    if ($d_stmt) {
        $d_stmt->bind_param("ss", $dept_input, $dept_input);
        $d_stmt->execute();
        $d_res = $d_stmt->get_result();
        if ($d_row = $d_res->fetch_assoc()) {
            $dept_id = $d_row['id'];
        }
        $d_stmt->close();
    }

    // কোর্স কোড আগেই আছে কিনা দেখা
    $check_stmt = $conn->prepare("SELECT id FROM courses WHERE course_code = ?");
    if ($check_stmt) {
        $check_stmt->bind_param("s", $course_code);
        $check_stmt->execute();
        $res = $check_stmt->get_result();

        if ($res->num_rows > 0) {
            $error = "❌ Course Code already exists!";
        } else {
            // INSERT Query (assigned_teacher ও batch সহ)
            $stmt = $conn->prepare("INSERT INTO courses (course_code, course_name, credits, department_id, assigned_teacher, batch) VALUES (?, ?, ?, ?, ?, ?)");
            
            if ($stmt) {
                $stmt->bind_param("ssdiss", $course_code, $course_name, $credits, $dept_id, $teacher, $batch);
                if ($stmt->execute()) {
                    // নতুন তৈরি হওয়া কোর্সটির ID নিয়ে আসা
                    $new_course_id = $stmt->insert_id; 

                    // ওই ব্যাচের সব স্টুডেন্টকে খুঁজে বের করে অটো-এনরোল করানো
                    $get_students = $conn->prepare("SELECT student_id FROM students WHERE batch = ?");
                    if ($get_students) {
                        $get_students->bind_param("s", $batch);
                        $get_students->execute();
                        $students_result = $get_students->get_result();

                        if ($students_result->num_rows > 0) {
                            $enroll_stmt = $conn->prepare("INSERT INTO course_enrollments (student_id, course_id) VALUES (?, ?)");
                            if ($enroll_stmt) {
                                while ($row = $students_result->fetch_assoc()) {
                                    $student_id = $row['student_id'];
                                    // ডুপ্লিকেট এন্ট্রি এড়াতে IGNORE ব্যবহার করা যেতে পারে অথবা সরাসরি ইনসার্ট
                                    $enroll_stmt->bind_param("si", $student_id, $new_course_id);
                                    $enroll_stmt->execute();
                                }
                                $enroll_stmt->close();
                            }
                        }
                        $get_students->close();
                    }

                    $message = "✅ Course added and all students of batch $batch auto-enrolled successfully!";
                } else {
                    $error = "❌ Failed to add course: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "❌ SQL Prepare Error: " . $conn->error;
            }
        }
        $check_stmt->close();
    }
}

// ২. কোর্স ডিলিট করার লজিক
if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    $del_stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
    if ($del_stmt) {
        $del_stmt->bind_param("i", $del_id);
        if ($del_stmt->execute()) {
            $message = "🗑️ Course deleted successfully!";
        }
        $del_stmt->close();
    }
}

// টিচারদের লিস্ট fetch করা
$teachers_list = $conn->query("SELECT username FROM users WHERE role = 'teacher'");

// কোর্স টেবিলের সকল ডাটা fetch করা
$courses_list = $conn->query("SELECT * FROM courses ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Management - CampusHub</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef2f5; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #1a202c; color: white; padding: 15px 25px; border-radius: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 20px; }
        .btn-back { background: #4a5568; color: white; padding: 8px 14px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13px; }
        .btn-back:hover { background: #2d3748; }

        .main-grid { display: grid; grid-template-columns: 320px 1fr; gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .card h3 { margin-top: 0; color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; font-size: 18px; }

        .form-group { margin-bottom: 12px; }
        label { display: block; font-size: 13px; font-weight: bold; color: #4a5568; margin-bottom: 4px; }
        input, select { width: 100%; padding: 9px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box; }
        .btn-submit { width: 100%; background: #38a169; color: white; border: none; padding: 10px; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        .btn-submit:hover { background: #2f855a; }

        .msg { background: #c6f6d5; color: #22543d; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
        .err-msg { background: #fed7d7; color: #742a2a; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #f7fafc; color: #4a5568; font-weight: bold; }
        .btn-del { color: #e53e3e; text-decoration: none; font-weight: bold; }
        .btn-del:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>📚 Course Management Control Panel</h2>
        <a href="admin_dashboard.php" class="btn-back">⬅️ Back to Admin Dashboard</a>
    </div>

    <?php if(!empty($message)): ?>
        <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <div class="err-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="main-grid">
        <!-- Add Course Form -->
        <div class="card">
            <h3>➕ Add New Course</h3>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Course Code:</label>
                    <input type="text" name="course_code" placeholder="e.g. CSE321" required>
                </div>

                <div class="form-group">
                    <label>Course Title:</label>
                    <input type="text" name="course_name" placeholder="e.g. Database Management System" required>
                </div>

                <div class="form-group">
                    <label>Credit Hours:</label>
                    <input type="number" step="0.5" name="credits" placeholder="e.g. 3.0" required>
                </div>

                <div class="form-group">
                    <label>Department:</label>
                    <input type="text" name="department" value="CSE" required>
                </div>

                <div class="form-group">
                    <label>Batch / Semester:</label>
                    <input type="text" name="batch" placeholder="e.g. 61" required>
                </div>

                <div class="form-group">
                    <label>Assign Teacher (Optional):</label>
                    <select name="teacher">
                        <option value="">-- Select Teacher --</option>
                        <?php if ($teachers_list && $teachers_list->num_rows > 0): ?>
                            <?php while ($tch = $teachers_list->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($tch['username']); ?>">
                                    <?php echo htmlspecialchars($tch['username']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <button type="submit" name="add_course" class="btn-submit">Save Course 🚀</button>
            </form>
        </div>

        <!-- Course List Table -->
        <div class="card">
            <h3>📖 Available Courses List</h3>
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Course Title</th>
                        <th>Credit</th>
                        <th>Batch</th>
                        <th>Assigned Teacher</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($courses_list && $courses_list->num_rows > 0): ?>
                        <?php while ($course = $courses_list->fetch_assoc()): ?>
                            <tr>
                                <td><b><?php echo htmlspecialchars($course['course_code']); ?></b></td>
                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($course['credits']); ?></td>
                                <td><?php echo !empty($course['batch']) ? htmlspecialchars($course['batch']) : 'N/A'; ?></td>
                                <td>
                                    <?php 
                                        echo !empty($course['assigned_teacher']) 
                                            ? htmlspecialchars($course['assigned_teacher']) 
                                            : '<span style="color:#a0aec0; font-style:italic;">Not Assigned</span>'; 
                                    ?>
                                </td>
                                <td>
                                    <a href="courses.php?delete_id=<?php echo $course['id']; ?>" class="btn-del" onclick="return confirm('Delete this course?');">Delete ❌</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; color:#777;">No courses added yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>