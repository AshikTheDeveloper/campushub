<?php
session_start();
require_once 'config.php';


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    
    $stmt = $conn->prepare("SELECT student_id FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $student_id = $row['student_id'];

        
        $del_student = $conn->prepare("DELETE FROM students WHERE id = ?");
        $del_student->bind_param("i", $id);
        $del_student->execute();
        $del_student->close();

        
        $del_user = $conn->prepare("DELETE FROM users WHERE username = ?");
        $del_user->bind_param("s", $student_id);
        $del_user->execute();
        $del_user->close();
    }
    $stmt->close();
}


header("Location: students.php");
exit();
?>