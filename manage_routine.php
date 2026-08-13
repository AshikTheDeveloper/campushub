<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['role'] ?? '';
$message = '';
$error = '';

// Add Routine (Admin or Teacher)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_routine'])) {
    if ($user_role === 'student') {
        $error = "❌ Students cannot add routine!";
    } else {
        $day = $_POST['day'];
        $time_slot = trim($_POST['time_slot']);
        $course_code = trim($_POST['course_code']);
        $course_name = trim($_POST['course_name']);
        $room_no = trim($_POST['room_no']);
        $teacher_name = trim($_POST['teacher_name']);

        $stmt = $conn->prepare("INSERT INTO routines (day, time_slot, course_code, course_name, room_no, teacher_name) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $day, $time_slot, $course_code, $course_name, $room_no, $teacher_name);

        if ($stmt->execute()) {
            $message = "✅ Class Routine entry added successfully!";
        } else {
            $error = "❌ Failed to add routine!";
        }
        $stmt->close();
    }
}

// Delete Routine Entry
if (isset($_GET['delete_routine_id']) && $user_role !== 'student') {
    $r_id = intval($_GET['delete_routine_id']);
    $conn->query("DELETE FROM routines WHERE id = $r_id");
    $message = "🗑️ Routine deleted!";
}

// Dynamic Back Link
$back_link = ($user_role === 'admin') ? 'admin_dashboard.php' : (($user_role === 'teacher') ? 'teacher_dashboard.php' : 'student_dashboard.php');

// Fetch Routine List
$routines = $conn->query("SELECT * FROM routines ORDER BY FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), time_slot ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Routine - CampusHub</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef2f5; margin: 0; padding: 20px; }
        .container { max-width: 1050px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #1a202c; color: white; padding: 15px 25px; border-radius: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 20px; }
        .btn-back { background: #4a5568; color: white; padding: 8px 14px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13px; }
        .btn-back:hover { background: #2d3748; }
        
        .main-grid { display: grid; grid-template-columns: <?php echo ($user_role !== 'student') ? '320px 1fr' : '1fr'; ?>; gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .card h3 { margin-top: 0; color: #2d3748; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; font-size: 16px; }

        .form-group { margin-bottom: 12px; }
        label { display: block; font-size: 13px; font-weight: bold; color: #4a5568; margin-bottom: 4px; }
        input, select { width: 100%; padding: 9px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box; font-size: 13px; }
        .btn-submit { width: 100%; background: #3182ce; color: white; border: none; padding: 10px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; }
        .btn-submit:hover { background: #2b6cb0; }

        .msg { background: #c6f6d5; color: #22543d; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
        .err-msg { background: #fed7d7; color: #742a2a; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
        th { background: #f7fafc; color: #4a5568; }
        .day-badge { background: #ebf8ff; color: #2b6cb0; padding: 3px 8px; border-radius: 12px; font-weight: bold; font-size: 11px; }
        .btn-del { color: #e53e3e; text-decoration: none; font-weight: bold; }
        .btn-del:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>📅 Class Routine & Timetable</h2>
        <a href="<?php echo $back_link; ?>" class="btn-back">⬅ Back to Dashboard</a>
    </div>

    <?php if(!empty($message)): ?>
        <div class="msg"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if(!empty($error)): ?>
        <div class="err-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="main-grid">
        <?php if ($user_role !== 'student'): ?>
        <!-- Add Routine Form (Teachers/Admins only) -->
        <div class="card">
            <h3>➕ Add Routine Slot</h3>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Day:</label>
                    <select name="day" required>
                        <option value="Sunday">Sunday</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Time Slot:</label>
                    <input type="text" name="time_slot" placeholder="e.g. 09:00 AM - 10:30 AM" required>
                </div>

                <div class="form-group">
                    <label>Course Code:</label>
                    <input type="text" name="course_code" placeholder="e.g. CSE-213" required>
                </div>

                <div class="form-group">
                    <label>Course Name:</label>
                    <input type="text" name="course_name" placeholder="e.g. Algorithms" required>
                </div>

                <div class="form-group">
                    <label>Room No:</label>
                    <input type="text" name="room_no" placeholder="e.g. AB4 - 502" required>
                </div>

                <div class="form-group">
                    <label>Teacher Name:</label>
                    <input type="text" name="teacher_name" placeholder="e.g. Mr. Rahim" required>
                </div>

                <button type="submit" name="add_routine" class="btn-submit">Add Slot 🚀</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Routine Table View -->
        <div class="card">
            <h3>📋 Class Schedule</h3>
            <table>
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Course</th>
                        <th>Room</th>
                        <th>Teacher</th>
                        <?php if ($user_role !== 'student'): ?><th>Action</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($routines && $routines->num_rows > 0): ?>
                        <?php while ($r = $routines->fetch_assoc()): ?>
                            <tr>
                                <td><span class="day-badge"><?php echo $r['day']; ?></span></td>
                                <td><b><?php echo htmlspecialchars($r['time_slot']); ?></b></td>
                                <td><?php echo htmlspecialchars($r['course_code'] . " - " . $r['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($r['room_no']); ?></td>
                                <td><?php echo htmlspecialchars($r['teacher_name']); ?></td>
                                <?php if ($user_role !== 'student'): ?>
                                <td>
                                    <a href="manage_routine.php?delete_routine_id=<?php echo $r['id']; ?>" class="btn-del" onclick="return confirm('Delete this routine entry?');">Delete ❌</a>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; color:#a0aec0; padding:15px;">No routine added yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>