<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['username']; // assuming student_id is username
$msg = '';

// Slot Booking Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_now'])) {
    $slot_id = intval($_POST['slot_id']);
    $booking_date = $_POST['booking_date'];
    $reason = trim($_POST['reason']);

    // Check duplicate booking
    $check_stmt = $conn->prepare("SELECT id FROM slot_bookings WHERE slot_id = ? AND student_id = ? AND booking_date = ?");
    $check_stmt->bind_param("iss", $slot_id, $student_id, $booking_date);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $msg = "❌ You already booked this slot for this date!";
    } else {
        $stmt = $conn->prepare("INSERT INTO slot_bookings (slot_id, student_id, booking_date, reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $slot_id, $student_id, $booking_date, $reason);
        if ($stmt->execute()) {
            $msg = "✅ Booking request sent successfully!";
        }
        $stmt->close();
    }
}

// Fetch all Available Teacher Slots
$slots_query = "
    SELECT ts.*, t.name as teacher_name 
    FROM teacher_slots ts
    LEFT JOIN teachers t ON ts.teacher_username = t.username
    ORDER BY ts.teacher_username, ts.id DESC
";
$all_slots = $conn->query($slots_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Office Hours Slot</title>
    <style>
        body { font-family: sans-serif; background: #eef2f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .slot-box { border: 1px solid #cbd5e0; padding: 15px; margin-bottom: 12px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; }
        .btn-book { background: #38a169; color: white; border: none; padding: 8px 14px; border-radius: 4px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h2>🤝 Book Teacher Counseling Slot</h2>
    <?php if($msg) echo "<p style='color:blue;'>$msg</p>"; ?>

    <?php while($slot = $all_slots->fetch_assoc()): ?>
        <div class="slot-box">
            <div>
                <h4>👨‍🏫 Teacher: <?php echo htmlspecialchars($slot['teacher_name'] ?? $slot['teacher_username']); ?></h4>
                <p><b>Day:</b> <?php echo $slot['day_of_week']; ?> | <b>Time:</b> <?php echo date("g:i A", strtotime($slot['start_time'])) . " - " . date("g:i A", strtotime($slot['end_time'])); ?></p>
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
</div>

</body>
</html>