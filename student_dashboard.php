<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// ১. স্টুডেন্ট ইনফরমেশন (Error Safe)
$student_info = null;
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $student_info = $res->fetch_assoc();
    }
    $stmt->close();
}

$student_id = $student_info['student_id'] ?? $username;
$dept = $student_info['department'] ?? 'CSE';
$batch = $student_info['batch'] ?? '';

// ১.১. এনরোল্ড কোর্সের সংখ্যা ডাইনামিকালি বের করা (Multi-fallback Query)
$enrolled_courses_count = 0;

// প্রথম চেষ্টা: ব্যাচ অনুযায়ী কোর্স গণনা করা (courses টেবিলে batch কলাম রয়েছে)
if (!empty($batch)) {
    $e_stmt = $conn->prepare("SELECT COUNT(*) as total FROM courses WHERE batch = ?");
    if ($e_stmt) {
        $e_stmt->bind_param("s", $batch);
        $e_stmt->execute();
        $e_res = $e_stmt->get_result();
        if ($e_res && $e_row = $e_res->fetch_assoc()) {
            $enrolled_courses_count = $e_row['total'];
        }
        $e_stmt->close();
    }
}

// দ্বিতীয় চেষ্টা: যদি ব্যাচ খালি বা না মিলে তবে course_enrollments টেবিল চেক করবে
if ($enrolled_courses_count == 0) {
    $e_stmt2 = $conn->prepare("SELECT COUNT(*) as total FROM course_enrollments WHERE student_id = ? OR student_id = ?");
    if ($e_stmt2) {
        $e_stmt2->bind_param("ss", $username, $student_id);
        $e_stmt2->execute();
        $e_res2 = $e_stmt2->get_result();
        if ($e_res2 && $e_row2 = $e_res2->fetch_assoc()) {
            $enrolled_courses_count = $e_row2['total'];
        }
        $e_stmt2->close();
    }
}

// তৃতীয় চেষ্টা (Fallback): নির্দিষ্ট ফিল্টার কাজ না করলে সব কোর্স গণনা করা
if ($enrolled_courses_count == 0) {
    $all_c = $conn->query("SELECT COUNT(*) as total FROM courses");
    if ($all_c && $all_row = $all_c->fetch_assoc()) {
        $enrolled_courses_count = $all_row['total'];
    }
}

// ১.২. স্টুডেন্টের রুম বুকিং / এক্সট্রা ক্লাস শিডিউল ফেচ করা (UPDATED QUERY FOR BATCH & DEPT MATCHING WITH FALLBACK)
$today = date('Y-m-d');
$room_schedule = false;

if (!empty($batch)) {
    $room_stmt = $conn->prepare("
        SELECT rb.booking_date, rb.start_time, rb.end_time, rb.room_number, c.course_code, c.course_name 
        FROM room_bookings rb
        JOIN courses c ON rb.course_id = c.id
        WHERE rb.booking_date >= ? 
          AND LOWER(c.department) = LOWER(?) 
          AND (c.batch = '' OR LOWER(c.batch) = LOWER(?))
        ORDER BY rb.booking_date ASC, rb.start_time ASC
    ");
    if ($room_stmt) {
        $room_stmt->bind_param("sss", $today, $dept, $batch);
        $room_stmt->execute();
        $room_schedule = $room_stmt->get_result();
    }
} else {
    $room_stmt = $conn->prepare("
        SELECT rb.booking_date, rb.start_time, rb.end_time, rb.room_number, c.course_code, c.course_name 
        FROM room_bookings rb
        JOIN courses c ON rb.course_id = c.id
        WHERE rb.booking_date >= ? 
          AND LOWER(c.department) = LOWER(?)
        ORDER BY rb.booking_date ASC, rb.start_time ASC
    ");
    if ($room_stmt) {
        $room_stmt->bind_param("ss", $today, $dept);
        $room_stmt->execute();
        $room_schedule = $room_stmt->get_result();
    }
}

// ১.৩. স্টুডেন্টের ফি সংক্রান্ত ডিউ (Due) হিসাব করা (UPDATED LOGIC FOR BATCH_FEES & PAYMENTS TABLES)
$total_due_amount = 0;

if (!empty($dept) && !empty($batch)) {
    // ১. স্টুডেন্টের ডিপার্টমেন্ট এবং ব্যাচের মোট কত ফি ধার্য করা হয়েছে
    $total_assigned_fee = 0;
    $b_stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM batch_fees WHERE LOWER(department) = LOWER(?) AND LOWER(batch) = LOWER(?)");
    if ($b_stmt) {
        $b_stmt->bind_param("ss", $dept, $batch);
        $b_stmt->execute();
        $b_res = $b_stmt->get_result();
        if ($b_res && $b_row = $b_res->fetch_assoc()) {
            $total_assigned_fee = $b_row['total'] ?? 0;
        }
        $b_stmt->close();
    }

    // ২. এই স্টুডেন্ট এ পর্যন্ত মোট কত পেমেন্ট করেছে
    $total_paid_amount = 0;
    $p_stmt = $conn->prepare("SELECT SUM(paid_amount) as total_paid FROM payments WHERE student_id = ? OR student_id = ?");
    if ($p_stmt) {
        $p_stmt->bind_param("ss", $username, $student_id);
        $p_stmt->execute();
        $p_res = $p_stmt->get_result();
        if ($p_res && $p_row = $p_res->fetch_assoc()) {
            $total_paid_amount = $p_row['total_paid'] ?? 0;
        }
        $p_stmt->close();
    }

    // ৩. মোট ডিউ হিসাব
    $total_due_amount = max(0, $total_assigned_fee - $total_paid_amount);
}

// ২. অফিসিয়াল নোটিশ বোর্ড
$notices_res = $conn->query("SELECT * FROM notices ORDER BY created_at DESC");

// ৩. একাডেমিক মার্কস (Error Safe)
$marks_res = false;
$marks_stmt = $conn->prepare("SELECT * FROM marks WHERE student_id = ? OR student_id = ? ORDER BY id DESC");
if ($marks_stmt) {
    $marks_stmt->bind_param("ss", $username, $student_id);
    $marks_stmt->execute();
    $marks_res = $marks_stmt->get_result();
}

// ৩.১. স্টাডি মেটেরিয়ালস (Error Safe Fetch)
$materials_res = false;
if (!empty($dept) && !empty($batch)) {
    $mat_stmt = $conn->prepare("SELECT * FROM course_materials WHERE (department = ? AND batch = ?) OR batch = ? ORDER BY id DESC");
    if ($mat_stmt) {
        $mat_stmt->bind_param("sss", $dept, $batch, $batch);
        $mat_stmt->execute();
        $materials_res = $mat_stmt->get_result();
    }
} else {
    $materials_res = $conn->query("SELECT * FROM course_materials ORDER BY id DESC");
}

// ৪. কোর্সভিত্তিক অ্যাটেনডেন্স ক্যালকুলেশন
$att_summary = [];
$total_classes_all = 0;
$total_present_all = 0;

// স্টুডেন্টের ব্যাচ অনুযায়ী কোর্স ফেচ করা
$courses_res = false;
if (!empty($batch)) {
    $courses_stmt = $conn->prepare("SELECT * FROM courses WHERE batch = ? ORDER BY course_code ASC");
    if ($courses_stmt) {
        $courses_stmt->bind_param("s", $batch);
        $courses_stmt->execute();
        $courses_res = $courses_stmt->get_result();
    }
}

// যদি ব্যাচ অনুযায়ী না পাওয়া যায়, সব কোর্স লোড করবে
if (!$courses_res || $courses_res->num_rows == 0) {
    $courses_res = $conn->query("SELECT * FROM courses ORDER BY course_code ASC");
}

if ($courses_res && $courses_res->num_rows > 0) {
    while ($course = $courses_res->fetch_assoc()) {
        $c_id = $course['id'];
        
        // ১. ওই কোর্সে টিচার মোট কতটি ক্লাস (ইউনিক ডেট) নিয়েছেন
        $total_classes = 0;
        $c_total_stmt = $conn->prepare("SELECT COUNT(DISTINCT attendance_date) as cnt FROM attendance WHERE course_id = ?");
        if ($c_total_stmt) {
            $c_total_stmt->bind_param("i", $c_id);
            $c_total_stmt->execute();
            $res_tot = $c_total_stmt->get_result();
            if ($res_tot) {
                $total_classes = $res_tot->fetch_assoc()['cnt'] ?? 0;
            }
            $c_total_stmt->close();
        }

        // ২. এই স্টুডেন্ট ওই কোর্সে কয়টিতে Present/Late ছিল
        $present_classes = 0;
        $c_pres_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM attendance WHERE course_id = ? AND (student_id = ? OR student_id = ?) AND (status = 'Present' OR status = 'Late')");
        if ($c_pres_stmt) {
            $c_pres_stmt->bind_param("iss", $c_id, $username, $student_id);
            $c_pres_stmt->execute();
            $res_pres = $c_pres_stmt->get_result();
            if ($res_pres) {
                $present_classes = $res_pres->fetch_assoc()['cnt'] ?? 0;
            }
            $c_pres_stmt->close();
        }

        // ৩. পার্সেন্টেজ হিসাব
        $percentage = ($total_classes > 0) ? round(($present_classes / $total_classes) * 100) : 0;
        
        $att_summary[] = [
            'course_code' => $course['course_code'],
            'course_name' => $course['course_name'],
            'credit'      => $course['credits'] ?? $course['credit'] ?? 0,
            'total'       => $total_classes,
            'present'     => $present_classes,
            'percentage'  => $percentage
        ];

        $total_classes_all += $total_classes;
        $total_present_all += $present_classes;
    }
}

// ওভারঅল অ্যাটেনডেন্স পার্সেন্টেজ
$overall_attendance = ($total_classes_all > 0) ? round(($total_present_all / $total_classes_all) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Portal - CampusHub</title>
    <style>
        /* Light/Dark Mode CSS Variables */
        :root {
            --bg-color: #eef2f5;
            --card-bg: #ffffff;
            --text-color: #2d3748;
            --sub-text: #4a5568;
            --header-bg: #1a202c;
            --border-color: #e2e8f0;
            --table-th-bg: #f7fafc;
            --item-bg: #f7fafc;
        }

        body.dark-mode {
            --bg-color: #1a202c;
            --card-bg: #2d3748;
            --text-color: #f7fafc;
            --sub-text: #cbd5e0;
            --header-bg: #0f172a;
            --border-color: #4a5568;
            --table-th-bg: #374151;
            --item-bg: #374151;
        }

        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: var(--bg-color); 
            color: var(--text-color);
            margin: 0; 
            padding: 20px; 
            transition: background 0.3s ease, color 0.3s ease;
        }

        .container { max-width: 1100px; margin: 0 auto; }
        
        .header { display: flex; justify-content: space-between; align-items: center; background: var(--header-bg); color: white; padding: 15px 25px; border-radius: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 22px; }
        .btn-logout { background: #e53e3e; color: white; padding: 8px 16px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13px; }
        .btn-logout:hover { background: #c53030; }

        .main-grid { display: grid; grid-template-columns: 300px 1fr; gap: 20px; }
        .card { background: var(--card-bg); color: var(--text-color); padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; border: 1px solid var(--border-color); }
        .card h3 { margin-top: 0; color: var(--text-color); border-bottom: 2px solid var(--border-color); padding-bottom: 8px; font-size: 16px; }

        .profile-info p { margin: 8px 0; font-size: 13px; color: var(--sub-text); }
        .profile-info b { color: var(--text-color); display: block; font-size: 14px; }

        .btn-routine { display: block; text-align: center; background: #3182ce; color: white; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 14px; margin-top: 15px; transition: background 0.2s ease; }
        .btn-routine:hover { background: #2b6cb0; }
        
        .btn-fee { display: block; text-align: center; background: #38a169; color: white; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 14px; margin-top: 10px; transition: background 0.2s ease; }
        .btn-fee:hover { background: #2f855a; }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: var(--card-bg); padding: 15px; border-radius: 10px; text-align: center; border-top: 4px solid #3182ce; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 1px solid var(--border-color); border-right: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); }
        .stat-card h4 { margin: 0; font-size: 11px; color: var(--sub-text); text-transform: uppercase; }
        .stat-card .num { font-size: 20px; font-weight: bold; color: #2b6cb0; margin-top: 5px; }

        /* Notice & Material Item */
        .notice-item { background: var(--item-bg); border-left: 4px solid #3182ce; padding: 12px; border-radius: 6px; margin-bottom: 10px; }
        .notice-item h4 { margin: 0 0 5px 0; color: var(--text-color); font-size: 15px; }
        .notice-item p { margin: 0 0 8px 0; font-size: 13px; color: var(--sub-text); }
        .notice-meta { font-size: 11px; color: #a0aec0; }
        .btn-attach { display: inline-block; background: #319795; color: white; padding: 4px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; margin-top: 5px; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 13px; color: var(--text-color); }
        th { background: var(--table-th-bg); color: var(--sub-text); }
        .badge-grade { background: #38a169; color: white; padding: 3px 8px; border-radius: 12px; font-weight: bold; font-size: 11px; }
        .progress-bar-bg { background: var(--border-color); border-radius: 10px; height: 10px; width: 100%; overflow: hidden; margin-top: 4px; }
        .progress-bar-fill { background: #38a169; height: 100%; border-radius: 10px; }

        /* Theme Toggle Button Style */
        .theme-toggle-btn {
            background: #4a5568;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-right: 8px;
            transition: background 0.2s ease;
        }
        .theme-toggle-btn:hover { background: #718096; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>CampusHub | Student Portal 🎓</h2>
        <div>
            <!-- 🌓 Dark/Light Mode Toggle Button -->
            <button id="themeToggle" class="theme-toggle-btn">
                <span id="themeIcon">🌙</span> <span id="themeText">Dark</span>
            </button>
            <a href="change_password.php" style="background: #4a5568; color: white; padding: 8px 14px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13px; margin-right: 8px;">🔑 Change Password</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="main-grid">
        <!-- Sidebar: Profile Info -->
        <div>
            <div class="card profile-info">
                <h3>👤 My Profile</h3>
                <p>FULL NAME
                    <b><?php echo htmlspecialchars($student_info['name'] ?? $username); ?></b>
                </p>
                <p>USERNAME / STUDENT ID
                    <b><?php echo htmlspecialchars($username); ?></b>
                </p>
                <p>EMAIL ADDRESS
                    <b><?php echo htmlspecialchars($student_info['email'] ?? 'N/A'); ?></b>
                </p>
                <p>ROLE
                    <b style="color: #3182ce;">Student</b>
                </p>
                <p>DEPARTMENT
                    <b><?php echo htmlspecialchars($student_info['department'] ?? 'CSE'); ?></b>
                </p>
                <a href="manage_routine.php" class="btn-routine">📅 View Class Routine</a>
                
                <!-- 💳 My Fees & Payments Button -->
                <a href="view_fees.php" class="btn-fee">💳 My Fees & Payments</a>
            </div>
        </div>

        <div>
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card" style="border-top-color: #3182ce;">
                    <h4>Enrolled Courses</h4>
                    <div class="num"><?php echo $enrolled_courses_count; ?></div>
                </div>
                <div class="stat-card" style="border-top-color: #38a169;">
                    <h4>Attendance Rate</h4>
                    <div class="num" style="color: #2f855a;"><?php echo $overall_attendance; ?>%</div>
                </div>
                <div class="stat-card" style="border-top-color: #d69e2e;">
                    <h4>Total Classes Attended</h4>
                    <div class="num" style="color: #b7791f;"><?php echo $total_present_all; ?> / <?php echo $total_classes_all; ?></div>
                </div>
                <!-- Total Due Fee Stat Card -->
                <div class="stat-card" style="border-top-color: #e53e3e;">
                    <h4>Total Due Fee</h4>
                    <div class="num" style="color: <?php echo ($total_due_amount > 0) ? '#e53e3e' : '#38a169'; ?>;">
                        ৳ <?php echo number_format($total_due_amount, 2); ?>
                    </div>
                </div>
            </div>

            <!-- Upcoming Extra Class / Room Schedule -->
            <div class="card">
                <h3>📢 Upcoming Extra Class / Room Schedule</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Course</th>
                            <th>Room / Lab</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($room_schedule && $room_schedule->num_rows > 0): ?>
                            <?php while ($row = $room_schedule->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <b><?php echo date('M d, Y', strtotime($row['booking_date'])); ?></b><br>
                                        <small style="color: var(--sub-text);">
                                            <?php echo date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <b><?php echo htmlspecialchars($row['course_code']); ?></b><br>
                                        <small style="color: var(--sub-text);"><?php echo htmlspecialchars($row['course_name']); ?></small>
                                    </td>
                                    <td>
                                        <span style="background: #3182ce; color: white; padding: 4px 10px; border-radius: 6px; font-weight: bold; font-size: 12px;">
                                            <?php echo htmlspecialchars($row['room_number']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: var(--sub-text); padding: 15px;">
                                    No upcoming room schedule found for your enrolled courses.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Attendance Summary Card -->
            <div class="card">
                <h3>📊 Course-wise Attendance Report</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Title</th>
                            <th>Classes Attended</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($att_summary)): ?>
                            <?php foreach ($att_summary as $att): ?>
                                <tr>
                                    <td><b><?php echo htmlspecialchars($att['course_code']); ?></b></td>
                                    <td><?php echo htmlspecialchars($att['course_name']); ?></td>
                                    <td><?php echo $att['present']; ?> / <?php echo $att['total']; ?></td>
                                    <td style="width: 150px;">
                                        <b><?php echo $att['percentage']; ?>%</b>
                                        <div class="progress-bar-bg">
                                            <div class="progress-bar-fill" style="width: <?php echo $att['percentage']; ?>%; background: <?php echo ($att['percentage'] < 75) ? '#e53e3e' : '#38a169'; ?>;"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--sub-text); padding: 15px;">
                                    No attendance record available yet for your account.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Academic Marks -->
            <div class="card">
                <h3>📈 Academic Marks & Grades</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Obtained Marks</th>
                            <th>Grade</th>
                            <th>Uploaded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($marks_res && $marks_res->num_rows > 0): ?>
                            <?php while ($mark = $marks_res->fetch_assoc()): ?>
                                <tr>
                                    <td><b><?php echo htmlspecialchars($mark['course_code']); ?></b></td>
                                    <td><?php echo htmlspecialchars($mark['course_name']); ?></td>
                                    <td><?php echo $mark['marks']; ?> / 100</td>
                                    <td><span class="badge-grade"><?php echo $mark['grade']; ?></span></td>
                                    <td><?php echo htmlspecialchars($mark['posted_by']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--sub-text); padding: 15px;">
                                    No marks or grades published yet for your ID (<b><?php echo htmlspecialchars($username); ?></b>).
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- My Enrolled Courses & Study Materials -->
            <div class="card">
                <h3>📚 My Enrolled Courses & Study Materials</h3>
                <?php if ($materials_res && $materials_res->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Title / Topic</th>
                                <th>Type</th>
                                <th>Uploaded Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($material = $materials_res->fetch_assoc()): ?>
                                <tr>
                                    <td><b><?php echo htmlspecialchars($material['title']); ?></b></td>
                                    <td><?php echo htmlspecialchars(ucfirst($material['type'] ?? 'Note')); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($material['uploaded_at'] ?? 'now')); ?></td>
                                    <td>
                                        <?php if (!empty($material['file_path'])): ?>
                                            <a href="uploads/<?php echo htmlspecialchars($material['file_path']); ?>" class="btn-attach" download>📥 Download</a>
                                        <?php else: ?>
                                            <span style="color: #a0aec0;">No File</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: var(--sub-text); margin-top: 10px;">No study materials or course resources uploaded yet for your department and batch.</p>
                <?php endif; ?>
            </div>

            <!-- Notice Board -->
            <div class="card">
                <h3>📢 Official Notice Board</h3>
                <?php if ($notices_res && $notices_res->num_rows > 0): ?>
                    <?php while ($notice = $notices_res->fetch_assoc()): ?>
                        <div class="notice-item">
                            <h4><?php echo htmlspecialchars($notice['title']); ?></h4>
                            <p><?php echo nl2br(htmlspecialchars($notice['description'])); ?></p>
                            <?php if(!empty($notice['file_name'])): ?>
                                <a href="uploads/<?php echo $notice['file_name']; ?>" class="btn-attach" download>📥 Download Attachment</a>
                            <?php endif; ?>
                            <div class="notice-meta" style="margin-top: 5px;">
                                Posted by: <b><?php echo htmlspecialchars($notice['posted_by']); ?></b> | 
                                <?php echo date('M d, Y - h:i A', strtotime($notice['created_at'])); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: var(--sub-text);">No notices posted yet.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- JavaScript for Dark/Light Mode Persistence -->
<script>
    const themeToggleBtn = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const themeText = document.getElementById('themeText');

    // ১. LocalStorage চেক করে পূর্বের থিম সিলেক্ট রাখা
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
        if(themeIcon) themeIcon.textContent = '☀️';
        if(themeText) themeText.textContent = 'Light';
    }

    // ২. বাটনে ক্লিকে ডার্ক/লাইট টগল লজিক
    if(themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            let theme = 'light';
            
            if (document.body.classList.contains('dark-mode')) {
                theme = 'dark';
                themeIcon.textContent = '☀️';
                themeText.textContent = 'Light';
            } else {
                themeIcon.textContent = '🌙';
                themeText.textContent = 'Dark';
            }
            // LocalStorage-এ পছন্দ সংরক্ষণ
            localStorage.setItem('theme', theme);
        });
    }
</script>

</body>
</html>