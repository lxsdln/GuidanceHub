<?php
include '../config.php';

if (!isset($_GET['id'])) die("Student not found.");

$student_id = intval($_GET['id']);

// Fetch student details
$query = "
    SELECT s.student_id, s.first_name, s.m_name, s.last_name,
           c.college_code, c.college_name
    FROM student s
    LEFT JOIN college c ON s.college_id = c.college_id
    WHERE s.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) die("Student not found.");
$student = $result->fetch_assoc();

// Fetch student concerns
$concernsQuery = "SELECT * FROM student_concerns WHERE student_id=?";
$stmt = $conn->prepare($concernsQuery);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$resConcerns = $stmt->get_result();
$concerns = $resConcerns->fetch_assoc();

// Questions array
$questions = [
  "Do you often experience academic-related stress?",
  "Do you sometimes feel anxious or overwhelmed because of school?",
  "Do personal or family issues affect your studies?",
  "Do you face financial difficulties that affect your academic performance?",
  "Do you find it hard to manage your time between school and personal life?",
  "Do you feel comfortable sharing your concerns with the guidance office?",
  "Do you prefer to keep your personal problems to yourself rather than seek help?",
  "Do you think having an online system like GuidanceHub will make it easier to raise your concerns?",
  "Do you believe your concerns will be kept confidential when shared with the guidance office?",
  "Do you think seeking help from the guidance office can improve your well-being and academic performance?",
  "Do you often struggle to understand your lessons?",
  "Do you find it difficult to complete assignments on time?",
  "Do you feel overwhelmed by your academic workload?",
  "Have you ever thought about dropping a subject because of poor performance?",
  "Do you find it difficult to focus or concentrate during class?",
  "Do you experience conflicts at home that affect your well-being?",
  "Do you feel unsupported by your family in your academic journey?",
  "Do you have difficulties balancing your personal responsibilities and school tasks?",
  "Do you feel isolated or disconnected from your family?",
  "Do you sometimes feel sad or hopeless without knowing why?",
  "Do you have difficulty managing your emotions?",
  "Do you experience low self-confidence or self-esteem?",
  "Do you sometimes feel left out or excluded by classmates or peers?",
  "Have you ever been bullied (online or in person) by other students?",
  "Do you feel uncomfortable participating in group activities or class discussions?"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Record</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .yes { color: green; font-weight: bold; }
    .no { color: red; font-weight: bold; }
    .line-question { margin-bottom: 8px; }
  </style>
</head>
<body class="bg-light">

<div class="container mt-5">

  <div class="card shadow">
    <div class="card-header bg-primary text-white">
      <h4>Student Information & Concerns</h4>
    </div>
    <div class="card-body">
      <!-- Student Info -->
      <p><strong>Student ID:</strong> <?= htmlspecialchars($student['student_id']) ?></p>
      <p><strong>Full Name:</strong> <?= htmlspecialchars($student['first_name'] . ' ' . $student['m_name'] . ' ' . $student['last_name']) ?></p>
      <p><strong>College:</strong> <?= htmlspecialchars($student['college_code'] . ' - ' . $student['college_name']) ?></p>

      <hr>

      <!-- Student Concerns -->
      <h5>Yes/No Questions</h5>
      <?php if ($concerns): ?>
        <?php for ($i = 1; $i <= 25; $i++): 
            $qKey = "q$i";
            $ansVal = isset($concerns[$qKey]) ? $concerns[$qKey] : null;
            $ansText = ($ansVal === null) ? "Not answered" : ($ansVal ? "Yes" : "No");
            $ansClass = ($ansVal === null) ? "" : ($ansVal ? "yes" : "no");
        ?>
          <div class="line-question">
            <strong><?= $i ?>. <?= $questions[$i - 1] ?></strong> - <span class="<?= $ansClass ?>"><?= $ansText ?></span>
          </div>
        <?php endfor; ?>
      <?php else: ?>
        <p>No concern answers recorded yet.</p>
      <?php endif; ?>
    </div>
    <div class="card-footer">
      <a href="record_session.php" class="btn btn-secondary">Back</a>
      <a href="edit_student.php?id=<?= $student_id ?>" class="btn btn-warning">Edit</a>
    </div>
  </div>

</div>

</body>
</html>
