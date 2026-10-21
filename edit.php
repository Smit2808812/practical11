
<?php
include 'db.php';

function safe($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit("Invalid student ID.");
}

$stmt = $conn->prepare("SELECT * FROM students WHERE id = ? AND status = 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    exit("Student record not found.");
}

$student = $result->fetch_assoc();
$error = '';

$courses = ['Computer Science', 'Web Development', 'Data Science'];
$genders = ['Male', 'Female'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $course = $_POST['course'] ?? '';
    $gender = $_POST['gender'] ?? '';

    if (
        $name === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !in_array($course, $courses, true) ||
        !in_array($gender, $genders, true)
    ) {
        $error = 'Please enter valid student details.';
        $student = compact('name', 'email', 'course', 'gender');
    } else {
        $stmt = $conn->prepare(
            "UPDATE students SET name=?, email=?, course=?, gender=? WHERE id=? AND status=1"
        );
        $stmt->bind_param("ssssi", $name, $email, $course, $gender, $id);

        if ($stmt->execute()) {
            header("Location: index.php?msg=updated");
            exit;
        }

        if ($stmt->errno === 1062) {
            $error = "This email address already exists.";
        } else {
            $error = "Unable to update student details.";
        }

        $student = compact('name', 'email', 'course', 'gender');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Student | StudentHub</title>
<link rel="stylesheet" href="style.css">
<style>
.edit-page {
    max-width: 700px;
    margin: 45px auto;
}
.edit-page .panel { padding: 32px; }
.edit-page .student-form { margin-top: 25px; }
.back-link {
    display: inline-block;
    margin-bottom: 22px;
    color: #7155e8;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
}
.error {
    padding: 13px;
    margin-bottom: 15px;
    background: #fff0f0;
    color: #b42335;
    border-radius: 9px;
    font-size: 13px;
}
</style>
</head>
<body>
<main class="edit-page">
    <a href="index.php" class="back-link">← Back to Dashboard</a>

    <section class="panel">
        <p class="eyebrow">STUDENT RECORD / EDIT</p>
        <h1>Update Student</h1>
        <p class="subtitle">Modify the information and save your changes.</p>

        <?php if ($error !== ''): ?>
            <div class="error"><?= safe($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="student-form">
            <div class="field">
                <label>Full Name</label>
                <input name="name" value="<?= safe($student['name']) ?>" required>
            </div>

            <div class="field">
                <label>Email Address</label>
                <input type="email" name="email" value="<?= safe($student['email']) ?>" required>
            </div>

            <div class="field">
                <label>Course</label>
                <select name="course" required>
                    <?php foreach ($courses as $c): ?>
                    <option value="<?= safe($c) ?>"
                        <?= $student['course'] === $c ? 'selected' : '' ?>>
                        <?= safe($c) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label>Gender</label>
                <select name="gender" required>
                    <?php foreach ($genders as $g): ?>
                    <option value="<?= safe($g) ?>"
                        <?= $student['gender'] === $g ? 'selected' : '' ?>>
                        <?= safe($g) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-actions">
                <button class="button button-primary" type="submit">Save Changes</button>
                <a href="index.php" class="button button-light">Cancel</a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
