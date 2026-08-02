<?php
session_start();
require_once 'config.php';


if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    header("Location: login.php");
    exit();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $posted_by = ucfirst($_SESSION['role']);
    $file_name = null;

    
    if (isset($_FILES['notice_file']) && $_FILES['notice_file']['error'] == 0) {
        $target_dir = "uploads/";
        
        
        $original_name = basename($_FILES["notice_file"]["name"]);
        $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $new_file_name = time() . '_' . rand(1000, 9999) . '.' . $file_extension;
        $target_file = $target_dir . $new_file_name;

        
        if (move_uploaded_file($_FILES["notice_file"]["tmp_name"], $target_file)) {
            $file_name = $new_file_name;
        } else {
            $message = "Error uploading the file.";
        }
    }

    if (empty($message)) {
        $stmt = $conn->prepare("INSERT INTO notices (title, description, file_name, posted_by) VALUES (?, ?, ?, ?)");
        
        
        if (!$stmt) {
            die("❌ SQL Error: " . $conn->error);
        }

        $stmt->bind_param("ssss", $title, $description, $file_name, $posted_by);

        if ($stmt->execute()) {
            $message = "✅ Notice published successfully!";
        } else {
            $message = "❌ Error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Notice - CampusHub</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 600px; background: white; margin: 0 auto; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; }
        input[type="text"], textarea, input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        textarea { height: 120px; resize: vertical; }
        .btn { background: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; }
        .btn:hover { background: #218838; }
        .msg { margin-bottom: 15px; padding: 10px; background: #e2e3e5; border-radius: 4px; font-weight: bold; }
        .back-link { display: inline-block; margin-top: 15px; text-decoration: none; color: #007bff; }
    </style>
</head>
<body>

<div class="container">
    <h2>📢 Publish New Notice</h2>
    
    <?php if(!empty($message)): ?>
        <div class="msg"><?php echo $message; ?></div>
    <?php endif; ?>

    
    <form action="add_notice.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Notice Title:</label>
            <input type="text" name="title" placeholder="Enter notice title" required>
        </div>

        <div class="form-group">
            <label>Description:</label>
            <textarea name="description" placeholder="Enter notice details..." required></textarea>
        </div>

        <div class="form-group">
            <label>Attach File (Optional - PDF/Image):</label>
            <input type="file" name="notice_file">
        </div>

        <button type="submit" class="btn">Publish Notice</button>
    </form>

    <a href="dashboard.php" class="back-link">⬅ Back to Dashboard</a>
</div>

</body>
</html>