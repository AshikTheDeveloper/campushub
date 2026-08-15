<?php
session_start();
require_once 'config.php';

// Check if admin/teacher
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    header("Location: login.php");
    exit();
}

$msg = "";
$error = "";

// ১. নতুন ব্যাচ ফি যোগ করা
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_batch_fee'])) {
    $dept = trim($_POST['department']);
    $batch = trim($_POST['batch']);
    $title = trim($_POST['title']);
    $amount = floatval($_POST['total_amount']);

    if (!empty($dept) && !empty($batch) && !empty($title) && $amount > 0) {
        $stmt = $conn->prepare("INSERT INTO batch_fees (department, batch, title, total_amount) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssd", $dept, $batch, $title, $amount);
            if ($stmt->execute()) {
                $msg = "Batch Fee successfully added!";
            } else {
                $error = "Error adding batch fee.";
            }
            $stmt->close();
        }
    } else {
        $error = "Please fill all required fields correctly.";
    }
}

// ২. স্টুডেন্টের পেমেন্ট এন্ট্রি দেওয়া
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_payment'])) {
    $student_id = trim($_POST['student_id']);
    $batch_fee_id = intval($_POST['batch_fee_id']);
    $paid_amount = floatval($_POST['paid_amount']);
    $receipt_no = trim($_POST['receipt_no']);
    $payment_date = $_POST['payment_date'];
    $method = trim($_POST['payment_method']);

    if (!empty($student_id) && $batch_fee_id > 0 && $paid_amount > 0 && !empty($receipt_no)) {
        $stmt = $conn->prepare("INSERT INTO payments (student_id, batch_fee_id, paid_amount, receipt_no, payment_date, payment_method) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sidsss", $student_id, $batch_fee_id, $paid_amount, $receipt_no, $payment_date, $method);
            if ($stmt->execute()) {
                $msg = "Payment entry saved successfully!";
            } else {
                $error = "Error saving payment.";
            }
            $stmt->close();
        }
    } else {
        $error = "Please fill all payment fields correctly.";
    }
}

// ডাটা ফেচ করা
$batch_fees = $conn->query("SELECT * FROM batch_fees ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Fees & Payments - CampusHub</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef2f5; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        h2, h3 { color: #2d3748; margin-top: 0; }
        .form-group { margin-bottom: 12px; }
        label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 4px; color: #4a5568; }
        input, select { width: 100%; padding: 8px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box; }
        .btn { background: #3182ce; color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn:hover { background: #2b6cb0; }
        .alert-success { background: #c6f6d5; color: #22543d; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
        .alert-danger { background: #fed7d7; color: #742a2a; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
        th { background: #f7fafc; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    </style>
</head>
<body>

<div class="container">
    <h2>💳 Manage Student Fees & Payments</h2>
    <a href="student_dashboard.php" style="text-decoration: none; color: #3182ce; font-weight: bold;">← Back</a>
    <br><br>

    <?php if ($msg): ?><div class="alert-success"><?php echo $msg; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-danger"><?php echo $error; ?></div><?php endif; ?>

    <div class="grid-2">
        <!-- ১. ব্যাচ ভিত্তিক ফি সেট ফর্ম -->
        <div class="card">
            <h3>📌 Add Batch Fee Structure</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" placeholder="e.g. CSE" required>
                </div>
                <div class="form-group">
                    <label>Batch</label>
                    <input type="text" name="batch" placeholder="e.g. 60th" required>
                </div>
                <div class="form-group">
                    <label>Title / Particulars</label>
                    <input type="text" name="title" placeholder="e.g. Semester Fee / Admission Fee" required>
                </div>
                <div class="form-group">
                    <label>Total Fee Amount (BDT)</label>
                    <input type="number" step="0.01" name="total_amount" placeholder="50000" required>
                </div>
                <button type="submit" name="add_batch_fee" class="btn">Save Fee Structure</button>
            </form>
        </div>

        <!-- ২. স্টুডেন্ট পেমেন্ট এন্ট্রি ফর্ম -->
        <div class="card">
            <h3>📥 Record Student Payment</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Student ID / Username</label>
                    <input type="text" name="student_id" placeholder="e.g. 221-15-000" required>
                </div>
                <div class="form-group">
                    <label>Select Fee Structure</label>
                    <select name="batch_fee_id" required>
                        <option value="">-- Select Fee --</option>
                        <?php 
                        if ($batch_fees && $batch_fees->num_rows > 0) {
                            $batch_fees->data_seek(0);
                            while ($f = $batch_fees->fetch_assoc()) {
                                echo "<option value='".$f['id']."'>[".$f['department']." - Batch ".$f['batch']."] ".$f['title']." (BDT ".$f['total_amount'].")</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Paid Amount (BDT)</label>
                    <input type="number" step="0.01" name="paid_amount" placeholder="15000" required>
                </div>
                <div class="form-group">
                    <label>Receipt / Money Receipt No</label>
                    <input type="text" name="receipt_no" placeholder="MR-987654" required>
                </div>
                <div class="form-group">
                    <label>Payment Date</label>
                    <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method">
                        <option value="Cash">Cash</option>
                        <option value="bKash">bKash</option>
                        <option value="Bank">Bank Transfer</option>
                        <option value="Online">Online Gateway</option>
                    </select>
                </div>
                <button type="submit" name="add_payment" class="btn" style="background: #38a169;">Submit Payment Record</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>