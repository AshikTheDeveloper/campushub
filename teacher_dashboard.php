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

// ----------------------------------------------------
// 1. TEACHER PROFILE DATA FETCH
// ----------------------------------------------------
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
    
    if (isset($t_row['designation']) && !empty($t_row['designation'])) {
        $teacher_info['designation'] = $t_row['designation'];
    }
}
$t_stmt->close();

// ----------------------------------------------------
// 2. CHECK-IN / CHECK-OUT SYSTEM LOGIC
// ----------------------------------------------------
$today_date = date('Y-m-d');
$today_log = null;

// আজকের লগ ডাটা আনা
$chk_stmt = $conn->prepare("SELECT * FROM teacher_campus_logs WHERE teacher_id = ? AND log_date = ?");
$chk_stmt->bind_param("ss", $username, $today_date);
$chk_stmt->execute();
$chk_res = $chk_stmt->get_result();
if ($row = $chk_res->fetch_assoc()) {
    $today_log = $row;
}
$chk_stmt->close();

// Check-In Action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_check_in'])) {
    $current_time = date('H:i:s');
    if (!$today_log) {
        $in_stmt = $conn->prepare("INSERT INTO teacher_campus_logs (teacher_id, log_date, check_in_time) VALUES (?, ?, ?)");
        $in_stmt->bind_param("sss", $username, $today_date, $current_time);
        if ($in_stmt->execute()) {
            $message = "🟢 Checked In successfully at " . date('h:i A', strtotime($current_time));
        } else {
            $error = "❌ Failed to Check-In.";
        }
        $in_stmt->close();
    }
    header("Location: teacher_dashboard.php");
    exit();
}

// Check-Out Action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_check_out'])) {
    $current_time = date('H:i:s');
    if ($today_log && empty($today_log['check_out_time'])) {
        $out_stmt = $conn->prepare("UPDATE teacher_campus_logs SET check_out_time = ? WHERE teacher_id = ? AND log_date = ?");
        $out_stmt->bind_param("sss", $current_time, $username, $today_date);
        if ($out_stmt->execute()) {
            $message = "🔴 Checked Out successfully at " . date('h:i A', strtotime($current_time));
        } else {
            $error = "❌ Failed to Check-Out.";
        }
        $out_stmt->close();
    }
    header("Location: teacher_dashboard.php");
    exit();
}

// ----------------------------------------------------
// 3. POST NOTICE LOGIC
// ----------------------------------------------------
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

// ----------------------------------------------------
// 4. DELETE NOTICE LOGIC
// ----------------------------------------------------
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

// ----------------------------------------------------
// 5. STATS CALCULATIONS
// ----------------------------------------------------
// Total Assigned Courses
$stmt_c = $conn->prepare("SELECT COUNT(*) as total FROM courses WHERE assigned_teacher = ?");
$stmt_c->bind_param("s", $username);
$stmt_c->execute();
$total_courses = $stmt_c->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_c->close();

// Today's Avg Attendance (%)
$stmt_att = $conn->prepare("
    SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count
    FROM attendance 
    WHERE marked_by = ? AND attendance_date = ?
");
$stmt_att->bind_param("ss", $username, $today_date);
$stmt_att->execute();
$att_data = $stmt_att->get_result()->fetch_assoc();
$stmt_att->close();

$avg_attendance = "N/A";
if ($att_data['total_records'] > 0) {
    $percentage = ($att_data['present_count'] / $att_data['total_records']) * 100;
    $avg_attendance = round($percentage, 1) . '%';
}

// ----------------------------------------------------
// 6. TODAY'S ROUTINE FETCH
// ----------------------------------------------------
$today_day = date('l');
$stmt_routine = $conn->prepare("SELECT * FROM routines WHERE day = ? ORDER BY time_slot ASC");
$stmt_routine->bind_param("s", $today_day);
$stmt_routine->execute();
$today_classes = $stmt_routine->get_result();

// ----------------------------------------------------
// 7. PENDING COUNSELING APPOINTMENTS COUNT
// ----------------------------------------------------
$total_pending_appointments = 0;
$stmt_p = $conn->prepare("
    SELECT COUNT(b.id) as total_pending 
    FROM slot_bookings b
    JOIN teacher_slots ts ON b.slot_id = ts.id
    WHERE ts.teacher_username = ? AND b.status = 'pending'
");
if ($stmt_p) {
    $stmt_p->bind_param("s", $username);
    $stmt_p->execute();
    $p_res = $stmt_p->get_result()->fetch_assoc();
    $total_pending_appointments = $p_res['total_pending'] ?? 0;
    $stmt_p->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Portal - CampusHub</title>
    <style>
        /* Light/Dark Mode CSS Variables */
        :root {
            --bg-color: #f4f6f9;
            --card-bg: #ffffff;
            --text-color: #2d3748;
            --sub-text: #718096;
            --header-bg: #1a202c;
            --border-color: #e2e8f0;
            --profile-bg: #f8fafc;
            --item-bg: #f8f9fa;
            --box-bg: #eef2f5;
            --input-bg: #ffffff;
            --input-border: #ccc;
            --table-th: #f7fafc;
        }

        body.dark-mode {
            --bg-color: #1a202c;
            --card-bg: #2d3748;
            --text-color: #f7fafc;
            --sub-text: #cbd5e0;
            --header-bg: #0f172a;
            --border-color: #4a5568;
            --profile-bg: #374151;
            --item-bg: #374151;
            --box-bg: #374151;
            --input-bg: #1a202c;
            --input-border: #4a5568;
            --table-th: #374151;
        }

        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: var(--bg-color); 
            color: var(--text-color);
            margin: 0; 
            padding: 20px; 
            transition: background 0.3s ease, color 0.3s ease;
        }

        .container { max-width: 1000px; margin: auto; }
        .card { background: var(--card-bg); color: var(--text-color); padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; border: 1px solid var(--border-color); }
        
        .header { display: flex; justify-content: space-between; align-items: center; background: var(--header-bg); color: white; border: none; }
        .header h2 { margin: 0; font-size: 22px; }
        .btn-logout { background: #dc3545; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .btn-logout:hover { background: #c82333; }

        .profile-container { margin-top: 15px; background: var(--profile-bg); border: 1px solid var(--border-color); padding: 18px; border-radius: 8px; }
        .profile-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 10px; }
        .profile-item { background: var(--card-bg); padding: 12px; border-radius: 6px; border: 1px solid var(--border-color); }
        .profile-item span { display: block; font-size: 12px; color: var(--sub-text); font-weight: bold; text-transform: uppercase; margin-bottom: 3px; }
        .profile-item p { margin: 0; font-size: 15px; color: var(--text-color); font-weight: 600; }

        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: var(--card-bg); padding: 18px; border-radius: 8px; border-left: 5px solid #3182ce; border-top: 1px solid var(--border-color); border-right: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .stat-card.green { border-left-color: #38a169; }
        .stat-card.purple { border-left-color: #805ad5; }
        .stat-number { font-size: 24px; font-weight: bold; margin-top: 5px; }
        .stat-label { font-size: 12px; color: var(--sub-text); font-weight: 600; text-transform: uppercase; }

        /* Check-In Widget Style */
        .checkin-card { background: var(--box-bg); border-left: 5px solid #3182ce; display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; border-top: 1px solid var(--border-color); border-right: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); }
        .checkin-info p { margin: 0; font-size: 14px; color: var(--sub-text); }
        .checkin-info b { color: var(--text-color); }
        .btn-checkin { background: #38a169; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; }
        .btn-checkin:hover { background: #2f855a; }
        .btn-checkout { background: #e53e3e; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; }
        .btn-checkout:hover { background: #c53030; }

        /* Routine Table Style */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        th { background: var(--table-th); }
        .btn-action { background: #38a169; color: white; padding: 6px 10px; text-decoration: none; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .btn-action:hover { background: #2f855a; }

        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .box { background: var(--box-bg); padding: 20px; border-radius: 8px; border-left: 5px solid #28a745; transition: transform 0.2s ease; border-top: 1px solid var(--border-color); border-right: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); }
        .box:hover { transform: translateY(-2px); }
        .box h3 { margin-top: 0; font-size: 18px; }
        .box h3 a { color: #28a745; text-decoration: none; }
        .box h3 a:hover { text-decoration: underline; }
        .box p { margin-bottom: 0; font-size: 14px; color: var(--sub-text); }

        .form-group { margin-bottom: 15px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; color: var(--text-color); }
        input[type="text"], textarea, input[type="file"] { width: 100%; padding: 10px; background: var(--input-bg); color: var(--text-color); border: 1px solid var(--input-border); border-radius: 4px; box-sizing: border-box; }
        .btn-submit { background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 15px; }
        .btn-submit:hover { background: #218838; }

        .msg { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .err-msg { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; }

        .notice-item { background: var(--item-bg); border-left: 4px solid #007bff; padding: 15px; border-radius: 4px; margin-bottom: 10px; position: relative; border-top: 1px solid var(--border-color); border-right: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); }
        .notice-item h4 { margin: 0 0 5px 0; color: var(--text-color); }
        .notice-item p { margin: 0 0 8px 0; font-size: 14px; color: var(--sub-text); }
        .notice-meta { font-size: 12px; color: var(--sub-text); }
        .btn-delete { color: #dc3545; text-decoration: none; font-weight: bold; font-size: 13px; float: right; }
        .btn-delete:hover { text-decoration: underline; }

        /* Theme Toggle Button Style */
        .theme-toggle-btn {
            background: #4a5568;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 4px;
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
    <!-- Header -->
    <div class="card header">
        <h2>👨‍🏫 Teacher Portal</h2>
        <div>
            <!-- 🌓 Dark/Light Mode Toggle Button -->
            <button id="themeToggle" class="theme-toggle-btn">
                <span id="themeIcon">🌙</span> <span id="themeText">Dark</span>
            </button>
            <a href="change_password.php" style="background: #4a5568; color: white; padding: 8px 14px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 13px; margin-right: 8px;">🔑 Change Password</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <!-- Teacher Profile Card -->
    <div class="card">
        <h3 style="margin-bottom: 5px;">Welcome, <?php echo htmlspecialchars($teacher_info['name']); ?>! 👋</h3>
        <p style="margin-top: 0; color: var(--sub-text);">Teacher Profile & Information Details</p>

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

    <!-- Quick Stats Overview -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Assigned Courses</div>
            <div class="stat-number" style="color: #3182ce;"><?php echo $total_courses; ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-label">Today's Avg Attendance</div>
            <div class="stat-number" style="color: #38a169;"><?php echo $avg_attendance; ?></div>
        </div>
        <div class="stat-card purple">
            <div class="stat-label">Pending Counseling Requests</div>
            <div class="stat-number" style="color: #805ad5;"><?php echo $total_pending_appointments; ?></div>
        </div>
    </div>

    <!-- Campus Attendance Check-In / Check-Out Widget -->
    <div class="checkin-card">
        <div class="checkin-info">
            <h4 style="margin:0 0 5px 0; color: var(--text-color);">⏱️ Campus Attendance Log (Today: <?php echo date('d M, Y'); ?>)</h4>
            <p>
                <b>Check-In:</b> <?php echo (!empty($today_log['check_in_time'])) ? date('h:i A', strtotime($today_log['check_in_time'])) : '<span style="color:#e53e3e;">Not Logged Yet</span>'; ?> | 
                <b>Check-Out:</b> <?php echo (!empty($today_log['check_out_time'])) ? date('h:i A', strtotime($today_log['check_out_time'])) : '<span style="color:var(--sub-text);">N/A</span>'; ?>
            </p>
        </div>
        <div>
            <form action="" method="POST" style="margin:0;">
                <?php if (!$today_log): ?>
                    <button type="submit" name="action_check_in" class="btn-checkin">🟢 Check-In</button>
                <?php elseif (empty($today_log['check_out_time'])): ?>
                    <button type="submit" name="action_check_out" class="btn-checkout">🔴 Check-Out</button>
                <?php else: ?>
                    <span style="background: #38a169; color: white; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 13px;">✅ Logged for Today</span>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Today's Class Schedule -->
    <div class="card">
        <h3 style="margin-top: 0;">📅 Today's Class Schedule (<?php echo date('l, d M Y'); ?>)</h3>
        <?php if ($today_classes && $today_classes->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Time Slot</th>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Room No</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($class = $today_classes->fetch_assoc()): ?>
                        <tr>
                            <td><b><?php echo htmlspecialchars($class['time_slot']); ?></b></td>
                            <td><span style="background: #ebf8ff; color: #2b6cb0; padding: 3px 8px; border-radius: 4px; font-weight: bold;"><?php echo htmlspecialchars($class['course_code']); ?></span></td>
                            <td><?php echo htmlspecialchars($class['course_name']); ?></td>
                            <td><span style="background: #feebc8; color: #9c4221; padding: 3px 8px; border-radius: 4px; font-weight: bold;"><?php echo htmlspecialchars($class['room_no']); ?></span></td>
                            <td>
                                <a href="take_attendance.php?course=<?php echo urlencode($class['course_code']); ?>" class="btn-action">Take Attendance</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: var(--sub-text); margin: 10px 0 0 0;">🎉 No classes scheduled for today (<?php echo $today_day; ?>)!</p>
        <?php endif; ?>
    </div>

    <!-- Navigation Cards Grid -->
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
        <div class="box" style="border-left-color: #3182ce;">
            <h3>🏫 <a href="room_booking.php" style="color: #3182ce;">Room Booking</a></h3>
            <p>Book empty classrooms/labs and view schedule slots.</p>
        </div>
        <div class="box" style="border-left-color: #805ad5;">
            <h3>🤝 <a href="teacher_slots.php" style="color: #805ad5;">Office Hours & Counseling</a></h3>
            <p>Set weekly available slots and review student appointments.</p>
        </div>
    </div>

    <!-- Notice Section -->
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
            <p style="color: var(--sub-text);">No notices posted yet.</p>
        <?php endif; ?>
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