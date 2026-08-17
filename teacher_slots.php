<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_username = $_SESSION['username'];
$msg = '';

// Slot Add Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_slot'])) {
    $day = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $max_students = intval($_POST['max_students']);

    $stmt = $conn->prepare("INSERT INTO teacher_slots (teacher_username, day_of_week, start_time, end_time, max_students) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $teacher_username, $day, $start_time, $end_time, $max_students);
    if ($stmt->execute()) {
        $msg = "✅ Office hour slot added!";
    }
    $stmt->close();
}

// Status Update Logic (Approve / Reject)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $booking_id = intval($_POST['booking_id']);
    $new_status = $_POST['status']; // 'approved' or 'rejected'

    $update_stmt = $conn->prepare("UPDATE slot_bookings SET status = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_status, $booking_id);
    if ($update_stmt->execute()) {
        $msg = "✅ Appointment status updated successfully!";
    }
    $update_stmt->close();
}

// Fetch Existing Slots
$stmt_slots = $conn->prepare("SELECT * FROM teacher_slots WHERE teacher_username = ? ORDER BY FIELD(day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')");
$stmt_slots->bind_param("s", $teacher_username);
$stmt_slots->execute();
$slots_res = $stmt_slots->get_result();

// Fetch Student Bookings
$booking_query = "
    SELECT b.id, b.student_id, s.name as student_name, b.booking_date, b.reason, b.status, ts.day_of_week, ts.start_time, ts.end_time
    FROM slot_bookings b
    JOIN teacher_slots ts ON b.slot_id = ts.id
    LEFT JOIN students s ON b.student_id = s.student_id
    WHERE ts.teacher_username = ?
    ORDER BY b.booking_date DESC
";
$stmt_b = $conn->prepare($booking_query);
$stmt_b->bind_param("s", $teacher_username);
$stmt_b->execute();
$bookings_res = $stmt_b->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Office Hours Slot Setup</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; padding: 20px; }
        .grid { display: grid; grid-template-columns: 320px 1fr; gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 12px; }
        label { font-weight: bold; font-size: 13px; display: block; margin-bottom: 4px; }
        input, select, textarea { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn { background: #3182ce; color: white; border: none; padding: 10px; width: 100%; font-weight: bold; cursor: pointer; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; border-bottom: 1px solid #ddd; text-align: left; font-size: 13px; }
        .btn-approve { background: #38a169; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-weight: bold; font-size: 11px; }
        .btn-reject { background: #e53e3e; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-weight: bold; font-size: 11px; }
    </style>
</head>
<body>

<h2>📅 Manage Office Hours & Appointments</h2>
<?php if($msg) echo "<p style='color:green;'>$msg</p>"; ?>

<div class="grid">
    <!-- Slot Add Form -->
    <div class="card">
        <h3>➕ Set Available Time Slot</h3>
        <form method="POST">
            <div class="form-group">
                <label>Day of Week:</label>
                <select name="day_of_week" required>
                    <option value="Sunday">Sunday</option>
                    <option value="Monday">Monday</option>
                    <option value="Tuesday">Tuesday</option>
                    <option value="Wednesday">Wednesday</option>
                    <option value="Thursday">Thursday</option>
                </select>
            </div>
            <div class="form-group">
                <label>Start Time:</label>
                <input type="time" name="start_time" required>
            </div>
            <div class="form-group">
                <label>End Time:</label>
                <input type="time" name="end_time" required>
            </div>
            <div class="form-group">
                <label>Max Capacity (Students):</label>
                <input type="number" name="max_students" value="1" min="1" required>
            </div>
            <button type="submit" name="add_slot" class="btn">Add Slot</button>
        </form>
    </div>

    <!-- Bookings View -->
    <div class="card">
        <h3>📋 Student Appointment Requests</h3>
        <table>
            <thead>
                <tr>
                    <th>Student ID & Name</th>
                    <th>Date</th>
                    <th>Time Slot</th>
                    <th>Reason</th>
                    <th>Status / Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($b = $bookings_res->fetch_assoc()): ?>
                <tr>
                    <td><b><?php echo htmlspecialchars($b['student_id']); ?></b> (<?php echo htmlspecialchars($b['student_name']); ?>)</td>
                    <td><?php echo $b['booking_date']; ?></td>
                    <td><?php echo $b['day_of_week'] . ' (' . date("g:i A", strtotime($b['start_time'])) . ' - ' . date("g:i A", strtotime($b['end_time'])) . ')'; ?></td>
                    <td><?php echo htmlspecialchars($b['reason']); ?></td>
                    <td>
                        <?php if ($b['status'] == 'pending'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                <button type="submit" name="update_status" value="update_status" onclick="this.form.status.value='approved';" class="btn-approve">Approve</button>
                                <button type="submit" name="update_status" value="update_status" onclick="this.form.status.value='rejected';" class="btn-reject">Reject</button>
                                <input type="hidden" name="status" value="">
                            </form>
                        <?php else: ?>
                            <b><?php echo ucfirst($b['status']); ?></b>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>