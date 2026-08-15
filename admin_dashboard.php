<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// ১. ইউজার অ্যাড করার লজিক (Add User)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $password  = md5(trim($_POST['password'])); // MD5 Hash
    $role      = $_POST['role'];
    $full_name = trim($_POST['full_name']);
    $dept      = trim($_POST['department']);
    $batch     = trim($_POST['batch'] ?? '');

    $check_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_user->bind_param("s", $username);
    $check_user->execute();
    $res = $check_user->get_result();

    if ($res->num_rows > 0) {
        $error = "❌ Username/ID already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $password, $role);

        if ($stmt->execute()) {
            // Student হলে students টেবিলে ডাটা সেভ (Batch সহ)
            if ($role === 'student') {
                $st_stmt = $conn->prepare("INSERT INTO students (student_id, name, email, department, batch) VALUES (?, ?, ?, ?, ?)");
                $st_stmt->bind_param("sssss", $username, $full_name, $email, $dept, $batch);
                $st_stmt->execute();
                $st_stmt->close();
            } 
            // Teacher হলে teachers টেবিলে ডাটা সেভ
            else if ($role === 'teacher') {
                $tch_stmt = $conn->prepare("INSERT INTO teachers (teacher_id, name, email, department) VALUES (?, ?, ?, ?)");
                $tch_stmt->bind_param("ssss", $username, $full_name, $email, $dept);
                $tch_stmt->execute();
                $tch_stmt->close();
            }

            $message = "✅ New " . ucfirst($role) . " added successfully!";
        } else {
            $error = "❌ Failed to create user!";
        }
        $stmt->close();
    }
    $check_user->close();
}

// ২. ইউজার ডিলিট করার লজিক (Delete User)
if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    
    $u_stmt = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
    $u_stmt->bind_param("i", $del_id);
    $u_stmt->execute();
    $u_data = $u_stmt->get_result()->fetch_assoc();
    $u_stmt->close();

    if ($u_data) {
        if ($u_data['role'] === 'student') {
            $del_st = $conn->prepare("DELETE FROM students WHERE student_id = ?");
            $del_st->bind_param("s", $u_data['username']);
            $del_st->execute();
            $del_st->close();
        } else if ($u_data['role'] === 'teacher') {
            $del_tch = $conn->prepare("DELETE FROM teachers WHERE teacher_id = ?");
            $del_tch->bind_param("s", $u_data['username']);
            $del_tch->execute();
            $del_tch->close();
        }

        $del_usr = $conn->prepare("DELETE FROM users WHERE id = ?");
        $del_usr->bind_param("i", $del_id);
        $del_usr->execute();
        $del_usr->close();

        $message = "🗑️ User deleted successfully!";
    }
}

// স্ট্যাট কাউন্ট
$total_students = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='student'")->fetch_assoc()['count'] ?? 0;
$total_teachers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='teacher'")->fetch_assoc()['count'] ?? 0;
$total_courses  = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'] ?? 0;
$total_enrolls  = $conn->query("SELECT COUNT(*) as count FROM course_enrollments")->fetch_assoc()['count'] ?? 0;

// সকল ইউজারের লিস্ট
$all_users = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - CampusHub</title>
    <style>
        /* CSS Variables for Dark / Light Theme */
        :root {
            --bg-color: #eef2f5;
            --card-bg: #ffffff;
            --text-color: #2d3748;
            --sub-text: #4a5568;
            --header-bg: #1a202c;
            --border-color: #e2e8f0;
            --table-th-bg: #f7fafc;
            --input-bg: #ffffff;
            --input-border: #cbd5e0;
        }

        body.dark-mode {
            --bg-color: #1a202c;
            --card-bg: #2d3748;
            --text-color: #f7fafc;
            --sub-text: #cbd5e0;
            --header-bg: #0f172a;
            --border-color: #4a5568;
            --table-th-bg: #374151;
            --input-bg: #1a202c;
            --input-border: #4a5568;
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
        
        .nav-btns { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .btn-nav { background: #3182ce; color: white; padding: 8px 12px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13px; }
        .btn-nav:hover { background: #2b6cb0; }
        .btn-nav-green { background: #38a169; }
        .btn-nav-green:hover { background: #2f855a; }
        .btn-nav-purple { background: #805ad5; }
        .btn-nav-purple:hover { background: #6b46c1; }
        .btn-nav-orange { background: #dd6b20; }
        .btn-nav-orange:hover { background: #c05621; }
        .btn-pass { background: #4a5568; color: white; padding: 8px 12px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13px; }
        .btn-pass:hover { background: #2d3748; }
        .logout-btn { background: #e53e3e; color: white; padding: 8px 14px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13px; }
        .logout-btn:hover { background: #c53030; }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: var(--card-bg); padding: 18px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); text-align: center; border-top: 4px solid #3182ce; border-left: 1px solid var(--border-color); border-right: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); }
        .stat-number { font-size: 26px; font-weight: bold; color: #2b6cb0; margin-top: 5px; }
        .stat-label { font-size: 13px; color: var(--sub-text); font-weight: 600; }

        .main-grid { display: grid; grid-template-columns: 320px 1fr; gap: 20px; }
        .card { background: var(--card-bg); color: var(--text-color); padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid var(--border-color); }
        .card h3 { margin-top: 0; color: var(--text-color); border-bottom: 2px solid var(--border-color); padding-bottom: 10px; }

        .form-group { margin-bottom: 12px; }
        label { display: block; font-size: 13px; font-weight: bold; color: var(--sub-text); margin-bottom: 4px; }
        input, select { width: 100%; padding: 9px; background: var(--input-bg); color: var(--text-color); border: 1px solid var(--input-border); border-radius: 6px; box-sizing: border-box; }
        .btn-add { width: 100%; background: #38a169; color: white; border: none; padding: 10px; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 5px; }
        .btn-add:hover { background: #2f855a; }

        .msg { background: #c6f6d5; color: #22543d; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
        .err-msg { background: #fed7d7; color: #742a2a; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        th { background: var(--table-th-bg); color: var(--text-color); font-weight: bold; }
        .role-badge { padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .role-student { background: #ebf8ff; color: #2b6cb0; }
        .role-teacher { background: #f0fff4; color: #276749; }
        .role-admin { background: #feebc8; color: #9c4221; }
        .btn-del { color: #e53e3e; text-decoration: none; font-weight: bold; }
        .btn-del:hover { text-decoration: underline; }

        /* Theme Toggle Button Style */
        .theme-toggle-btn {
            background: #4a5568;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.2s ease;
        }
        .theme-toggle-btn:hover { background: #718096; }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <h2>CampusHub | Admin Control Panel ⚙️</h2>
        <div class="nav-btns">
            <!-- 🌓 Dark/Light Mode Toggle Button -->
            <button id="themeToggle" class="theme-toggle-btn">
                <span id="themeIcon">🌙</span> <span id="themeText">Dark</span>
            </button>
            <a href="courses.php" class="btn-nav btn-nav-green">📚 Courses & Credit</a>
            <a href="enroll_student.php" class="btn-nav btn-nav-purple">📝 Student Enrollment</a>
            <a href="students.php" class="btn-nav">🎓 Students</a>
            <a href="manage_fees.php" class="btn-nav btn-nav-orange">💰 Manage Fees</a>
            <a href="view_fees.php" class="btn-nav" style="background: #319795;">💳 View Fees</a>
            <a href="manage_routine.php" class="btn-nav" style="background: #2b6cb0;">🗓️ Routine</a>
            <a href="teacher_attendance_report.php" class="btn-nav" style="background: #d69e2e;">⏱️ Teacher Attendance</a>
            <a href="change_password.php" class="btn-pass">🔑 Password</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <?php if(!empty($message)): ?>
        <div class="msg"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <div class="err-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Top Summary Cards -->
    <div class="stats-grid">
        <div class="stat-card" style="border-top-color: #3182ce;">
            <div class="stat-label">Total Students</div>
            <div class="stat-number"><?php echo $total_students; ?></div>
        </div>
        <div class="stat-card" style="border-top-color: #38a169;">
            <div class="stat-label">Total Teachers</div>
            <div class="stat-number" style="color: #2f855a;"><?php echo $total_teachers; ?></div>
        </div>
        <div class="stat-card" style="border-top-color: #805ad5;">
            <div class="stat-label">Active Courses</div>
            <div class="stat-number" style="color: #6b46c1;"><?php echo $total_courses; ?></div>
        </div>
        <div class="stat-card" style="border-top-color: #d69e2e;">
            <div class="stat-label">Course Registrations</div>
            <div class="stat-number" style="color: #b7791f;"><?php echo $total_enrolls; ?></div>
        </div>
    </div>

    <div class="main-grid">
        <!-- Add User Form -->
        <div class="card">
            <h3>➕ Add New User</h3>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Role:</label>
                    <select name="role" required>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Username / ID:</label>
                    <input type="text" name="username" placeholder="e.g. teacher283 or 211-15-101" required>
                </div>

                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="full_name" placeholder="e.g. John Doe">
                </div>

                <div class="form-group">
                    <label>Department:</label>
                    <input type="text" name="department" value="CSE">
                </div>

                <div class="form-group">
                    <label>Batch / Semester (Only for Student):</label>
                    <input type="text" name="batch" placeholder="e.g. 60">
                </div>

                <div class="form-group">
                    <label>Email Address:</label>
                    <input type="email" name="email" placeholder="user@campushub.com" required>
                </div>

                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>

                <button type="submit" name="add_user" class="btn-add">Create Account 🚀</button>
            </form>
        </div>

        <!-- System Users Table -->
        <div class="card">
            <h3>👥 System Users List</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($all_users && $all_users->num_rows > 0): ?>
                        <?php while ($user = $all_users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><b><?php echo htmlspecialchars($user['username']); ?></b></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo $user['role']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['role'] !== 'admin'): ?>
                                        <a href="admin_dashboard.php?delete_id=<?php echo $user['id']; ?>" class="btn-del" onclick="return confirm('Are you sure you want to delete this user?');">Delete ❌</a>
                                    <?php else: ?>
                                        <span style="color: #aaa;">Protected</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- JavaScript for Dark/Light Mode Persistence -->
<script>
    const themeToggleBtn = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const themeText = document.getElementById('themeText');

    // ১. LocalStorage চেক করে থিম সেট করা
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
        if(themeIcon) themeIcon.textContent = '☀️';
        if(themeText) themeText.textContent = 'Light';
    }

    // ২. বাটনে ক্লিকে ডার্ক/লাইট সুইচ লজিক
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
            // LocalStorage-এ স্টেট সংরক্ষণ
            localStorage.setItem('theme', theme);
        });
    }
</script>

</body>
</html>