<?php
session_start();
require_once 'config.php';

// টিচার অথেনটিকেশন চেক
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['username'];
$message = "";
$msg_type = "";

// ----------------------------------------------------
// ১. রুম বুকিং প্রসেস হ্যান্ডেল করা (POST Request)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_room'])) {
    $room_number  = trim($_POST['room_number']);
    $course_id    = intval($_POST['course_id']);
    // তারিখ ঠিকমত YYYY-MM-DD ফরম্যাটে নিশ্চিত করা
    $raw_date     = $_POST['booking_date'];
    $booking_date = !empty($raw_date) ? date('Y-m-d', strtotime($raw_date)) : '';
    $start_time   = $_POST['start_time'];
    $end_time     = $_POST['end_time'];

    // ভ্যালিডেশন
    if (empty($room_number) || empty($course_id) || empty($booking_date) || empty($start_time) || empty($end_time) || $booking_date == '1970-01-01') {
        $message = "Please enter a valid date and fill in all fields.";
        $msg_type = "danger";
    } elseif ($start_time >= $end_time) {
        $message = "End time must be greater than start time!";
        $msg_type = "danger";
    } else {
        // 🔥 স্মার্ট কনফликт চেক: ওই নির্দিষ্ট দিনে ও রুমে একই টাইমে অলরেডি কোনো বুকিং আছে কি না
        $check_sql = "SELECT id FROM room_bookings 
                      WHERE room_number = ? 
                      AND booking_date = ? 
                      AND status = 'Booked' 
                      AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND end_time <= ?))";

        $check_stmt = $conn->prepare($check_sql);
        if ($check_stmt) {
            $check_stmt->bind_param("ssssssss", $room_number, $booking_date, $end_time, $start_time, $start_time, $start_time, $start_time, $end_time);
            $check_stmt->execute();
            $check_res = $check_stmt->get_result();

            if ($check_res && $check_res->num_rows > 0) {
                $message = "Room <b>{$room_number}</b> is already booked for this time slot!";
                $msg_type = "danger";
            } else {
                // বুকিং সেভ করা
                $ins_stmt = $conn->prepare("INSERT INTO room_bookings (room_number, teacher_id, course_id, booking_date, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, ?, 'Booked')");
                if ($ins_stmt) {
                    $ins_stmt->bind_param("ssisss", $room_number, $teacher_id, $course_id, $booking_date, $start_time, $end_time);
                    if ($ins_stmt->execute()) {
                        $message = "Room booked successfully!";
                        $msg_type = "success";
                    } else {
                        $message = "Database error: Unable to book room.";
                        $msg_type = "danger";
                    }
                    $ins_stmt->close();
                }
            }
            $check_stmt->close();
        }
    }
}

// ----------------------------------------------------
// ২. বুকিং ক্যানসেল করার লজিক
// ----------------------------------------------------
if (isset($_GET['cancel_id'])) {
    $cancel_id = intval($_GET['cancel_id']);
    $can_stmt = $conn->prepare("UPDATE room_bookings SET status = 'Cancelled' WHERE id = ? AND teacher_id = ?");
    if ($can_stmt) {
        $can_stmt->bind_param("is", $cancel_id, $teacher_id);
        $can_stmt->execute();
        $can_stmt->close();
        header("Location: room_booking.php");
        exit();
    }
}

// ----------------------------------------------------
// ৩. টিচারের অ্যাসাইন করা কোর্সগুলো আনা (কলামের নাম assigned_teacher দিয়ে আপডেট করা হয়েছে)
// ----------------------------------------------------
$courses_res = false;
$c_stmt = $conn->prepare("SELECT * FROM courses WHERE assigned_teacher = ? ORDER BY course_code ASC");
if ($c_stmt) {
    $c_stmt->bind_param("s", $teacher_id);
    $c_stmt->execute();
    $courses_res = $c_stmt->get_result();
}

// যদি এই নির্দিষ্ট টিচারের কোনো নির্দিষ্ট কোর্স অ্যাসাইন না থাকে, তবে ডাটাবেজের সব কোর্স দেখাবে
if (!$courses_res || $courses_res->num_rows === 0) {
    $courses_res = $conn->query("SELECT * FROM courses ORDER BY course_code ASC");
}

// ----------------------------------------------------
// ৪. আজকের ও ভবিষ্যতের সব অ্যাক্টিভ রুম বুকিংয়ের লিস্ট আনা
// ----------------------------------------------------
$today = date('Y-m-d');
$bookings_sql = "SELECT rb.*, c.course_code, c.course_name 
                FROM room_bookings rb 
                JOIN courses c ON rb.course_id = c.id 
                WHERE rb.booking_date >= '$today' AND rb.status = 'Booked' 
                ORDER BY rb.booking_date ASC, rb.start_time ASC";
$bookings_res = $conn->query($bookings_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Room Booking - CampusHub</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef2f5; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #1a202c; color: white; padding: 15px 25px; border-radius: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 20px; }
        .btn-back { background: #4a5568; color: white; padding: 8px 14px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13px; }
        
        .grid { display: grid; grid-template-columns: 350px 1fr; gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .card h3 { margin-top: 0; color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; font-size: 16px; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; font-weight: bold; color: #4a5568; margin-bottom: 5px; }
        .form-control { width: 100%; padding: 8px 10px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box; font-size: 13px; }
        
        .btn-submit { width: 100%; background: #3182ce; color: white; padding: 10px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-submit:hover { background: #2b6cb0; }

        .alert { padding: 10px 12px; border-radius: 6px; font-size: 13px; margin-bottom: 15px; }
        .alert-danger { background: #fed7d7; color: #9b2c2c; }
        .alert-success { background: #c6f6d5; color: #22543d; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
        th { background: #f7fafc; color: #4a5568; }
        
        .badge-room { background: #3182ce; color: white; padding: 3px 8px; border-radius: 6px; font-weight: bold; }
        .btn-cancel { background: #e53e3e; color: white; padding: 4px 8px; text-decoration: none; border-radius: 4px; font-size: 11px; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>🏫 Classroom / Room Booking System</h2>
        <a href="teacher_dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $msg_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="grid">
        <!-- Booking Form -->
        <div class="card">
            <h3>📌 Book a Class Room</h3>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Select Course</label>
                    <select name="course_id" class="form-control" required>
                        <option value="">-- Choose Course --</option>
                        <?php if ($courses_res && $courses_res->num_rows > 0): ?>
                            <?php while ($c = $courses_res->fetch_assoc()): ?>
                                <option value="<?php echo $c['id']; ?>">
                                    <?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Room Number / Lab Name</label>
                    <input type="text" name="room_number" class="form-control" placeholder="e.g. Room 602, Lab 4" required>
                </div>

                <div class="form-group">
                    <label>Booking Date</label>
                    <input type="date" name="booking_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" name="start_time" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>End Time</label>
                    <input type="time" name="end_time" class="form-control" required>
                </div>

                <button type="submit" name="book_room" class="btn-submit">🚀 Confirm Booking</button>
            </form>
        </div>

        <!-- Current Schedule Table -->
        <div class="card">
            <h3>📅 Upcoming Room Schedule</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Room</th>
                        <th>Course</th>
                        <th>Booked By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($bookings_res && $bookings_res->num_rows > 0): ?>
                        <?php while ($b = $bookings_res->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <b><?php echo date('M d, Y', strtotime($b['booking_date'])); ?></b><br>
                                    <small style="color: #718096;">
                                        <?php echo date('h:i A', strtotime($b['start_time'])) . ' - ' . date('h:i A', strtotime($b['end_time'])); ?>
                                    </small>
                                </td>
                                <td><span class="badge-room"><?php echo htmlspecialchars($b['room_number']); ?></span></td>
                                <td><b><?php echo htmlspecialchars($b['course_code']); ?></b></td>
                                <td>
                                    <?php echo ($b['teacher_id'] === $teacher_id) ? '<b style="color:#3182ce;">You</b>' : htmlspecialchars($b['teacher_id']); ?>
                                </td>
                                <td>
                                    <?php if ($b['teacher_id'] === $teacher_id): ?>
                                        <a href="room_booking.php?cancel_id=<?php echo $b['id']; ?>" class="btn-cancel" onclick="return confirm('Cancel this room booking?');">Cancel</a>
                                    <?php else: ?>
                                        <span style="color:#a0aec0; font-size:11px;">Restricted</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #718096; padding: 15px;">
                                No active room bookings for upcoming dates.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>