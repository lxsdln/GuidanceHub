<?php
session_start();
include '../config.php';
header('Content-Type: application/json');

$student_id = $_SESSION['user_id'] ?? 0; // make sure it's set in session
if ($student_id <= 0) {
    echo json_encode(["success"=>false,"message"=>"Invalid student ID"]);
    exit;
}

// ------------------ Personal Info ------------------
$personal_fields = ['first_name','m_name','last_name','course','college_name'];
$isPersonal = false;
foreach($personal_fields as $f){
    if(isset($_POST[$f])){ $isPersonal = true; break; }
}

if($isPersonal){
    $first_name = $_POST['first_name'] ?? '';
    $m_name = $_POST['m_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $course = $_POST['course'] ?? '';
    $college_code = $_POST['college_code'] ?? '';

    $college_id = null;
    if($college_code){
        $stmt = $conn->prepare("SELECT college_id FROM college WHERE college_code=?");
        $stmt->bind_param("s",$college_code);
        $stmt->execute();
        $res = $stmt->get_result();
        if($res->num_rows>0){
            $college_id = $res->fetch_assoc()['college_id'];
        } else {
            $college_id = 'COL'.rand(1000,9999);
            $stmt = $conn->prepare("INSERT INTO college (college_id, college_name, college_code) VALUES (?,?,?)");
            $stmt->bind_param("sss",$college_id,$college_name,$college_id);
            $stmt->execute();
        }
    }

    // Check if student exists
    $stmt = $conn->prepare("SELECT id FROM student WHERE student_id=?");
    $stmt->bind_param("s",$student_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res->num_rows>0){
        $stmt = $conn->prepare("UPDATE student SET first_name=?, m_name=?, last_name=?, course=?, college_id=? WHERE student_id=?");
        $stmt->bind_param("ssssss",$first_name,$m_name,$last_name,$course,$college_id,$student_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO student (student_id, first_name, m_name, last_name, course, college_id) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("ssssss",$student_id,$first_name,$m_name,$last_name,$course,$college_id);
        $stmt->execute();
    }

    echo json_encode(["success"=>true,"message"=>"Personal info saved"]);
    exit;
}

// ------------------ Yes/No Questions ------------------
if(isset($_POST['question_no'],$_POST['answer'])){
    $question_no = intval($_POST['question_no']);
    $answer = ($_POST['answer']==='yes')?1:0;

    // Make sure row exists
    $stmt = $conn->prepare("SELECT id FROM student_concerns WHERE student_id=(SELECT id FROM student WHERE student_id=?)");
    $stmt->bind_param("s",$student_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res->num_rows>0){
        $id = $res->fetch_assoc()['id'];
        $stmt = $conn->prepare("UPDATE student_concerns SET q$question_no=? WHERE id=?");
        $stmt->bind_param("ii",$answer,$id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO student_concerns (student_id, q$question_no) VALUES ((SELECT id FROM student WHERE student_id=?), ?)");
        $stmt->bind_param("si",$student_id,$answer);
        $stmt->execute();
    }

    echo json_encode(["success"=>true,"message"=>"Answer saved"]);
    exit;
}

echo json_encode(["success"=>false,"message"=>"No data provided"]);
?>
