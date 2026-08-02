<?php
session_start();
require_once 'config.php';

// সিকিউরিটি চেক: স্টুডেন্ট হিসেবে লগইন আছে কিনা
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// ১. users টেবিল থেকে ইউজারের ইমেইল ও রোল আনা
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// ২. students টেবিল থেকে স্টুডেন্টের নাম ও ডিপার্টমেন্ট ফেচ করা
$st_stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$st_stmt->bind_param("s", $username);
$st_stmt->execute();
$st_data = $st_stmt->get_result()->fetch_assoc();
$st_stmt->close();

// ডাটাবেজ থেকে সব নোটিশ নিয়ে আসা
$notices_result = $conn->query("SELECT * FROM notices ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard - CampusHub</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef2f5; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        
        /* Header */
        .header { display: flex; justify-content: space-between; align-items: center; background: #1a202c; color: white; padding: 15px 25px; border-radius: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 22px; }
        .logout-btn { background: #e53e3e; color: white; padding: 8px 16px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px; }
        .logout-btn:hover { background: #c53030; }

        /* Dashboard Layout Grid */
        .dashboard-grid { display: grid; grid-template-columns: 300px 1fr; gap: 20px; }

        /* Left Sidebar - Profile Card */
        .profile-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); height: fit-content; }
        .profile-card h3 { margin-top: 0; color: #2d3748; border-bottom: 2px solid #3182ce; padding-bottom: 8px; }
        .info-group { margin-bottom: 12px; }
        .info-label { font-size: 12px; color: #718096; font-weight: bold; text-transform: uppercase; }
        .info-value { font-size: 15px; color: #1a202c; font-weight: 600; margin-top: 2px; }

        /* Right Content - Stats & Notices */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 15px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-top: 4px solid #3182ce; text-align: center; }
        .stat-number { font-size: 24px; font-weight: bold; color: #2b6cb0; margin-top: 5px; }
        .stat-label { font-size: 13px; color: #4a5568; }

        /* Notice Section */
        .section-title { font-size: 18px; color: #2d3748; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        .notice-card { background: white; border-left: 5px solid #3182ce; padding: 18px; margin-bottom: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .notice-title { font-size: 16px; font-weight: bold; color: #1a202c; margin-bottom: 6px; }
        .notice-meta { font-size: 12px; color: #718096; margin-bottom: 10px; }
        .notice-desc { color: #4a5568; font-size: 14px; line-height: 1.5; }
        .download-link { display: inline-block; margin-top: 10px; background: #38a169; color: white; padding: 6px 12px; text-decoration: none; border-radius: 5px; font-size: 12px; font-weight: bold; }
        .download-link:hover { background: #2f855a; }
        .no-notice { background: white; padding: 20px; text-align: center; color: #718096; border-radius: 8px; }
    </style>
</head>
<body>

<div class="container">
    <!-- Navbar Header -->
    <div class="header">
        <h2>CampusHub | Student Portal 🎓</h2>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <!-- Main Grid Layout -->
    <div class="dashboard-grid">
        
        <!-- Left Column: Student Profile Details -->
        <div class="profile-card">
            <h3>👤 My Profile</h3>
            
            <div class="info-group">
                <div class="info-label">Full Name</div>
                <div class="info-value"><?php echo htmlspecialchars($st_data['name'] ?? 'N/A'); ?></div>
            </div>

            <div class="info-group">
                <div class="info-label">Username / Student ID</div>
                <div class="info-value"><?php echo htmlspecialchars($user_data['username'] ?? 'N/A'); ?></div>
            </div>

            <div class="info-group">
                <div class="info-label">Email Address</div>
                <div class="info-value"><?php echo htmlspecialchars($user_data['email'] ?? ($st_data['email'] ?? 'N/A')); ?></div>
            </div>

            <div class="info-group">
                <div class="info-label">Role</div>
                <div class="info-value" style="color: #3182ce;"><?php echo ucfirst(htmlspecialchars($user_data['role'] ?? 'Student')); ?></div>
            </div>

            <div class="info-group">
                <div class="info-label">Department</div>
                <div class="info-value"><?php echo htmlspecialchars($st_data['department'] ?? 'N/A'); ?></div>
            </div>
        </div>

        <!-- Right Column: Stats & Notice Board -->
        <div>
            <!-- Quick Overview Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Enrolled Courses</div>
                    <div class="stat-number">5</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Attendance Rate</div>
                    <div class="stat-number" style="color: #38a169;">88%</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Completed Credits</div>
                    <div class="stat-number" style="color: #d69e2e;">45</div>
                </div>
            </div>

            <!-- Dynamic Notice Board -->
            <div class="section-title">📢 Official Notice Board</div>

            <?php if ($notices_result && $notices_result->num_rows > 0): ?>
                <?php while ($notice = $notices_result->fetch_assoc()): ?>
                    <div class="notice-card">
                        <div class="notice-title"><?php echo htmlspecialchars($notice['title']); ?></div>
                        <div class="notice-meta">
                            Posted by <b><?php echo htmlspecialchars($notice['posted_by']); ?></b> 
                            | <?php echo date('M d, Y - h:i A', strtotime($notice['created_at'])); ?>
                        </div>
                        <div class="notice-desc"><?php echo nl2br(htmlspecialchars($notice['description'])); ?></div>

                        <?php if (!empty($notice['file_name'])): ?>
                            <a href="uploads/<?php echo urlencode($notice['file_name']); ?>" class="download-link" target="_blank" download>
                                📥 Download Attachment
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-notice">No notices published yet!</div>
            <?php endif; ?>

        </div>

    </div>
</div>

</body>
</html>