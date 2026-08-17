<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['username'] ?? '';
$msg = '';

// Slot Booking Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_now'])) {
    $slot_id = intval($_POST['slot_id']);
    $booking_date = $_POST['booking_date'];
    $reason = trim($_POST['reason']);

    $check_stmt = $conn->prepare("SELECT id FROM slot_bookings WHERE slot_id = ? AND student_id = ? AND booking_date = ?");
    if ($check_stmt) {
        $check_stmt->bind_param("iss", $slot_id, $student_id, $booking_date);
        $check_stmt->execute();
        $chk_res = $check_stmt->get_result();
        
        if ($chk_res && $chk_res->num_rows > 0) {
            $msg = "❌ You already booked this slot for this date!";
        } else {
            $stmt = $conn->prepare("INSERT INTO slot_bookings (slot_id, student_id, booking_date, reason, status) VALUES (?, ?, ?, ?, 'pending')");
            if ($stmt) {
                $stmt->bind_param("isss", $slot_id, $student_id, $booking_date, $reason);
                if ($stmt->execute()) {
                    $msg = "✅ Booking request sent successfully!";
                }
                $stmt->close();
            }
        }
        $check_stmt->close();
    }
}

// Fetch all Available Teacher Slots safely
$slots_query = "
    SELECT ts.*, 
           COALESCE(t.name, ts.teacher_username) as teacher_name 
    FROM teacher_slots ts
    LEFT JOIN teachers t ON ts.teacher_username = t.username
    ORDER BY ts.teacher_username DESC
";
$all_slots = $conn->query($slots_query);

if (!$all_slots) {
    $all_slots = $conn->query("SELECT * FROM teacher_slots ORDER BY id DESC");
}

// Direct Fetch for Student Bookings (Matches current student_id)
$my_bookings_query = "SELECT * FROM slot_bookings WHERE student_id = ? ORDER BY id DESC";
$my_stmt = $conn->prepare($my_bookings_query);
$my_bookings = null;
if ($my_stmt) {
    $my_stmt->bind_param("s", $student_id);
    $my_stmt->execute();
    $my_bookings = $my_stmt->get_result();
    $my_stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Office Hours Slot</title>
    <style>
        body { font-family: sans-serif; background: #eef2f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .slot-box { border: 1px solid #cbd5e0; padding: 15px; margin-bottom: 12px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; }
        .btn-book { background: #38a169; color: white; border: none; padding: 8px 14px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; border-bottom: 1px solid #ddd; text-align: left; font-size: 13px; }
    </style>
</head>
<body>

<div class="container">
    <h2>🤝 Book Teacher Counseling Slot</h2>
    <?php if($msg) echo "<p style='color:blue;'>$msg</p>"; ?>

    <?php if ($all_slots && $all_slots->num_rows > 0): ?>
        <?php while($slot = $all_slots->fetch_assoc()): ?>
            <div class="slot-box">
                <div>
                    <h4>👨‍🏫 Teacher: <?php echo htmlspecialchars($slot['teacher_name'] ?? $slot['teacher_username']); ?></h4>
                    <p><b>Day:</b> <?php echo htmlspecialchars($slot['day_of_week']); ?> | <b>Time:</b> <?php echo date("g:i A", strtotime($slot['start_time'])) . " - " . date("g:i A", strtotime($slot['end_time'])); ?></p>
                </div>
                <div>
                    <form method="POST">
                        <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                        <input type="date" name="booking_date" required style="padding: 5px;">
                        <input type="text" name="reason" placeholder="Reason (e.g. Project Help)" required style="padding: 5px;">
                        <button type="submit" name="book_now" class="btn-book">Book Slot</button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="color: #4a5568; text-align: center; padding: 20px;">No teacher slots available at the moment.</p>
    <?php endif; ?>
</div>

<!-- My Booking Requests & Status Table -->
<div class="container">
    <h3>📌 My Booking Requests & Status</h3>
    <p style="font-size: 12px; color: #666;">Logged in as Student ID/Username: <b><?php echo htmlspecialchars($student_id); ?></b></p>
    <table>
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>Date</th>
                <th>Reason</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($my_bookings && $my_bookings->num_rows > 0): ?>
                <?php while($mb = $my_bookings->fetch_assoc()): ?>
                <tr>
                    <td><b>#<?php echo $mb['id']; ?></b> (Slot ID: <?php echo $mb['slot_id']; ?>)</td>
                    <td><?php echo $mb['booking_date']; ?></td>
                    <td><?php echo htmlspecialchars($mb['reason']); ?></td>
                    <td>
                        <b style="color: 
                            <?php 
                                $status = $mb['status'] ?? 'pending';
                                if($status == 'approved') echo 'green'; 
                                elseif($status == 'rejected') echo 'red'; 
                                else echo 'orange'; 
                            ?>;">
                            <?php echo ucfirst($status); ?>
                        </b>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: #718096;">You haven't booked any slots yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>