<?php
session_start();
require_once 'config.php';

// শুধুমাত্র Admin এক্সেস করতে পারবে
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// ফিল্টার ভ্যারিয়েবল সেট করা (বাই-ডিফল্ট আজকের তারিখ)
$selected_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');
$selected_teacher = isset($_GET['teacher_id']) ? trim($_GET['teacher_id']) : '';

// সকল টিচারদের লিস্ট আনা (ড্রপডাউন ফিল্টারের জন্য)
$teachers_list = $conn->query("SELECT teacher_id, name, department FROM teachers ORDER BY name ASC");

// অ্যাটেনডেন্স ক্যোয়ারি বিল্ড করা
$sql = "SELECT l.*, t.name as teacher_name, t.department 
        FROM teacher_campus_logs l 
        LEFT JOIN teachers t ON l.teacher_id = t.teacher_id 
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($selected_date)) {
    $sql .= " AND l.log_date = ?";
    $params[] = $selected_date;
    $types .= "s";
}

if (!empty($selected_teacher)) {
    $sql .= " AND l.teacher_id = ?";
    $params[] = $selected_teacher;
    $types .= "s";
}

$sql .= " ORDER BY l.log_date DESC, l.check_in_time DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Attendance Report - Admin Panel</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: auto; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
        
        .header { display: flex; justify-content: space-between; align-items: center; background: #1a202c; color: white; border-radius: 8px 8px 0 0; padding: 20px 25px; }
        .header h2 { margin: 0; font-size: 22px; }
        .btn-back { background: #4a5568; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px; }
        .btn-back:hover { background: #2d3748; }

        .filter-box { background: #edf2f7; padding: 18px; border-radius: 8px; margin-bottom: 20px; }
        .filter-form { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
        .form-group { flex: 1; min-width: 180px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 13px; color: #4a5568; }
        .form-group input, .form-group select { width: 100%; padding: 9px; border: 1px solid #cbd5e0; border-radius: 4px; box-sizing: border-box; }
        .btn-filter { background: #3182ce; color: white; border: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .btn-filter:hover { background: #2b6cb0; }
        .btn-reset { background: #e2e8f0; color: #4a5568; text-decoration: none; padding: 10px 15px; border-radius: 4px; font-weight: bold; font-size: 13px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #f7fafc; color: #4a5568; font-weight: bold; text-transform: uppercase; font-size: 12px; }
        tr:hover { background: #f8fafc; }

        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-in { background: #c6f6d5; color: #22543d; }
        .badge-out { background: #edf2f7; color: #4a5568; }
        .badge-working { background: #feebc8; color: #744210; }

        .print-btn { background: #38a169; color: white; border: none; padding: 8px 16px; border-radius: 4px; font-weight: bold; cursor: pointer; float: right; margin-bottom: 15px; }
        .print-btn:hover { background: #2f855a; }

        @media print {
            .filter-box, .btn-back, .print-btn { display: none; }
            body { background: white; padding: 0; }
            .card { box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="card header">
        <h2>⏱️ Teacher Campus Attendance Report</h2>
        <a href="admin_dashboard.php" class="btn-back">⬅️ Back to Dashboard</a>
    </div>

    <!-- Main Content Card -->
    <div class="card">
        <!-- Filter Form -->
        <div class="filter-box">
            <form action="" method="GET" class="filter-form">
                <div class="form-group">
                    <label>Select Date:</label>
                    <input type="date" name="filter_date" value="<?php echo htmlspecialchars($selected_date); ?>">
                </div>

                <div class="form-group">
                    <label>Filter by Teacher:</label>
                    <select name="teacher_id">
                        <option value="">-- All Teachers --</option>
                        <?php if ($teachers_list && $teachers_list->num_rows > 0): ?>
                            <?php while ($t = $teachers_list->fetch_assoc()): ?>
                                <option value="<?php echo $t['teacher_id']; ?>" <?php if($selected_teacher == $t['teacher_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($t['name']) . " (" . $t['teacher_id'] . ")"; ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div>
                    <button type="submit" class="btn-filter">🔍 Filter</button>
                    <a href="teacher_attendance_report.php" class="btn-reset">Reset</a>
                </div>
            </form>
        </div>

        <button onclick="window.print()" class="print-btn">🖨️ Print Report</button>

        <!-- Attendance Logs Table -->
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Teacher ID</th>
                    <th>Teacher Name</th>
                    <th>Department</th>
                    <th>Check-In Time</th>
                    <th>Check-Out Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($logs_result && $logs_result->num_rows > 0): ?>
                    <?php while ($log = $logs_result->fetch_assoc()): ?>
                        <tr>
                            <td><b><?php echo date('d M, Y', strtotime($log['log_date'])); ?></b></td>
                            <td><?php echo htmlspecialchars($log['teacher_id']); ?></td>
                            <td><?php echo htmlspecialchars(!empty($log['teacher_name']) ? $log['teacher_name'] : $log['teacher_id']); ?></td>
                            <td><?php echo htmlspecialchars(!empty($log['department']) ? $log['department'] : 'N/A'); ?></td>
                            <td>
                                🟢 <?php echo date('h:i A', strtotime($log['check_in_time'])); ?>
                            </td>
                            <td>
                                <?php 
                                    if (!empty($log['check_out_time'])) {
                                        echo "🔴 " . date('h:i A', strtotime($log['check_out_time']));
                                    } else {
                                        echo '<span style="color: #a0aec0;">Not Checked Out</span>';
                                    }
                                ?>
                            </td>
                            <td>
                                <?php if (!empty($log['check_out_time'])): ?>
                                    <span class="badge badge-out">Completed</span>
                                <?php else: ?>
                                    <span class="badge badge-working">In Campus</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #718096; padding: 20px;">
                            ❌ No attendance records found for the selected filter.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>