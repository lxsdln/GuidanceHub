<?php
include '../config.php';
header('Content-Type: application/json');

$student_id = $_SESSION['user_id'] ?? 0;
if ($student_id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid student ID"]);
    exit;
}

if (isset($_POST['question_no'], $_POST['answer'])) {
    $question_no = intval($_POST['question_no']);
    $answer = ($_POST['answer'] === 'yes') ? 1 : 0;

    // --- Get student full info ---
    $stmt = $conn->prepare("
        SELECT s.first_name, s.m_name, s.last_name, s.course,
               c.college_code, c.college_name
        FROM student s
        LEFT JOIN college c ON s.college_id = c.college_id
        WHERE s.user_id = ?
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Student record not found"]);
        exit;
    }
    $student = $res->fetch_assoc();

    // --- Check if a concern row already exists for today ---
    $stmt = $conn->prepare("
        SELECT id FROM student_concerns
        WHERE f_name = ? AND l_name = ?
        ORDER BY submitted_at DESC LIMIT 1
    ");
    $stmt->bind_param("ss", $student['first_name'], $student['last_name']);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        // Update the existing question
        $id = $res->fetch_assoc()['id'];
        $stmt = $conn->prepare("UPDATE student_concerns SET q$question_no = ? WHERE id = ?");
        $stmt->bind_param("ii", $answer, $id);
        $stmt->execute();
    } else {
        // Insert new snapshot
        $stmt = $conn->prepare("
            INSERT INTO student_concerns (
                f_name, m_name, l_name, course, college_code, college_name, q$question_no, submitted_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param(
            "ssssssi",
            $student['first_name'],
            $student['m_name'],
            $student['last_name'],
            $student['course'],
            $student['college_code'],
            $student['college_name'],
            $answer
        );
        $stmt->execute();
    }

    echo json_encode(["success" => true, "message" => "Answer saved"]);
    exit;
}

echo json_encode(["success" => false, "message" => "No data provided"]);
exit;
?>