<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// ১. স্টুডেন্ট ইনফরমেশন ফেচ
$student_info = null;
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $student_info = $res->fetch_assoc();
    }
    $stmt->close();
}

$student_id = $student_info['student_id'] ?? $username;
$dept = $student_info['department'] ?? 'CSE';
$batch = $student_info['batch'] ?? '';

// ২. ব্যাচ অনুযায়ী মোট ফি বের করা
$total_assigned_fee = 0;
$fee_details = [];

if (!empty($batch)) {
    $f_stmt = $conn->prepare("SELECT * FROM batch_fees WHERE (department = ? AND batch = ?) OR batch = ?");
    if ($f_stmt) {
        $f_stmt->bind_param("sss", $dept, $batch, $batch);
        $f_stmt->execute();
        $f_res = $f_stmt->get_result();
        if ($f_res) {
            while ($row = $f_res->fetch_assoc()) {
                $fee_details[] = $row;
                $total_assigned_fee += floatval($row['total_amount']);
            }
        }
        $f_stmt->close();
    }
}

// ৩. স্টুডেন্টের দেওয়া মোট পেমেন্ট হিসাব করা
$total_paid = 0;
$payments_history = [];

$p_stmt = $conn->prepare("
    SELECT p.*, bf.title 
    FROM payments p 
    JOIN batch_fees bf ON p.batch_fee_id = bf.id 
    WHERE p.student_id = ? OR p.student_id = ?
    ORDER BY p.payment_date DESC
");

if ($p_stmt) {
    $p_stmt->bind_param("ss", $username, $student_id);
    $p_stmt->execute();
    $p_res = $p_stmt->get_result();
    if ($p_res) {
        while ($row = $p_res->fetch_assoc()) {
            $payments_history[] = $row;
            $total_paid += floatval($row['paid_amount']);
        }
    }
    $p_stmt->close();
}

// ৪. বকেয়া হিসাব
$total_due = $total_assigned_fee - $total_paid;
if ($total_due < 0) $total_due = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Fees & Payments - CampusHub</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef2f5; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        h2, h3 { color: #2d3748; margin-top: 0; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 15px; border-radius: 10px; text-align: center; border-top: 4px solid #3182ce; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .stat-card h4 { margin: 0; font-size: 12px; color: #718096; text-transform: uppercase; }
        .stat-card .num { font-size: 22px; font-weight: bold; margin-top: 5px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
        th { background: #f7fafc; color: #4a5568; }
        .badge-receipt { background: #edf2f7; color: #2d3748; padding: 3px 8px; border-radius: 6px; font-family: monospace; font-weight: bold; }
        .btn-back { display: inline-block; background: #4a5568; color: white; padding: 8px 14px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container">
    <a href="student_dashboard.php" class="btn-back">← Back to Dashboard</a>
    <h2>💳 Student Fee & Payment History</h2>

    <!-- Summary Stats -->
    <div class="stats-grid">
        <div class="stat-card" style="border-color: #3182ce;">
            <h4>Total Assigned Fee</h4>
            <div class="num" style="color: #2b6cb0;">BDT <?php echo number_format($total_assigned_fee, 2); ?></div>
        </div>
        <div class="stat-card" style="border-color: #38a169;">
            <h4>Total Paid Amount</h4>
            <div class="num" style="color: #2f855a;">BDT <?php echo number_format($total_paid, 2); ?></div>
        </div>
        <div class="stat-card" style="border-color: #e53e3e;">
            <h4>Total Total Due</h4>
            <div class="num" style="color: #c53030;">BDT <?php echo number_format($total_due, 2); ?></div>
        </div>
    </div>

    <!-- 1. Fee Breakdown Structure -->
    <div class="card">
        <h3>📌 Assigned Fee Breakdown (Dept: <?php echo htmlspecialchars($dept); ?>, Batch: <?php echo htmlspecialchars($batch); ?>)</h3>
        <table>
            <thead>
                <tr>
                    <th>Fee Title / Particulars</th>
                    <th>Department</th>
                    <th>Batch</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($fee_details)): ?>
                    <?php foreach ($fee_details as $fee): ?>
                        <tr>
                            <td><b><?php echo htmlspecialchars($fee['title']); ?></b></td>
                            <td><?php echo htmlspecialchars($fee['department']); ?></td>
                            <td><?php echo htmlspecialchars($fee['batch']); ?></td>
                            <td><b>BDT <?php echo number_format($fee['total_amount'], 2); ?></b></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: #718096; padding: 15px;">
                            No fee structure assigned yet for Department: <b><?php echo htmlspecialchars($dept); ?></b> & Batch: <b><?php echo htmlspecialchars($batch); ?></b>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 2. Payment Receipts History -->
    <div class="card">
        <h3>🧾 Payment Receipts & Transaction History</h3>
        <table>
            <thead>
                <tr>
                    <th>Payment Date</th>
                    <th>Receipt No</th>
                    <th>Fee Head</th>
                    <th>Paid Method</th>
                    <th>Paid Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($payments_history)): ?>
                    <?php foreach ($payments_history as $pay): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($pay['payment_date'])); ?></td>
                            <td><span class="badge-receipt"><?php echo htmlspecialchars($pay['receipt_no']); ?></span></td>
                            <td><?php echo htmlspecialchars($pay['title']); ?></td>
                            <td><?php echo htmlspecialchars($pay['payment_method']); ?></td>
                            <td><b style="color: #2f855a;">BDT <?php echo number_format($pay['paid_amount'], 2); ?></b></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #718096; padding: 15px;">
                            No payment receipts found for Student ID: <b><?php echo htmlspecialchars($username); ?></b>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>