
<?php
include 'db.php';

function safe($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (isset($_POST['add_student'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $course = $_POST['course'] ?? '';
    $gender = $_POST['gender'] ?? '';

    $courses = ['Computer Science', 'Web Development', 'Data Science'];
    $genders = ['Male', 'Female'];

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || !in_array($course, $courses, true)
        || !in_array($gender, $genders, true)) {
        header('Location: index.php?msg=invalid');
        exit;
    }

    $stmt = $conn->prepare(
        "INSERT INTO students (name, email, course, gender) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("ssss", $name, $email, $course, $gender);

    if ($stmt->execute()) {
        header('Location: index.php?msg=added');
        exit;
    }

    if ($stmt->errno === 1062) {
        header('Location: index.php?msg=duplicate');
        exit;
    }

    die("Unable to add student.");
}

if (isset($_GET['delete'])) {
    $id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);

    if ($id) {
        $stmt = $conn->prepare("UPDATE students SET status = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    header('Location: index.php?msg=deleted');
    exit;
}

$search = trim($_GET['search'] ?? '');
$courseFilter = $_GET['course_filter'] ?? '';

$sql = "SELECT * FROM students WHERE status = 1";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $keyword = "%$search%";
    $params[] = $keyword;
    $params[] = $keyword;
    $types .= 'ss';
}

$courses = ['Computer Science', 'Web Development', 'Data Science'];

if (in_array($courseFilter, $courses, true)) {
    $sql .= " AND course = ?";
    $params[] = $courseFilter;
    $types .= 's';
} else {
    $courseFilter = '';
}

$sql .= " ORDER BY id DESC";
$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$countResult = $conn->query(
    "SELECT COUNT(*) AS total FROM students WHERE status = 1"
);
$totalStudents = $countResult->fetch_assoc()['total'];

$countCourses = $conn->query(
    "SELECT COUNT(DISTINCT course) AS total FROM students WHERE status = 1"
)->fetch_assoc()['total'];

$countFiltered = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StudentHub | Management Dashboard</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<aside class="sidebar">
    <div class="brand"><span class="brand-icon">S</span> StudentHub</div>

    <p class="nav-label">WORKSPACE</p>
    <a class="nav-link active" href="index.php">▦ &nbsp; Dashboard</a>
    <a class="nav-link" href="#register">＋ &nbsp; Add Student</a>
    <a class="nav-link" href="#directory">☷ &nbsp; Student Directory</a>
    <a class="nav-link" href="export.php">⇩ &nbsp; Export CSV</a>

    <div class="sidebar-bottom">
        <div class="profile-circle">AD</div>
        <div><strong>Administrator</strong><small>Student Management</small></div>
    </div>
</aside>

<main class="main-content">
    <header class="topbar">
        <div>
            <p class="eyebrow">OVERVIEW / STUDENTS</p>
            <h1>Student Dashboard</h1>
            <p class="subtitle">Manage your student records in one place.</p>
        </div>
        <a class="button button-primary" href="#register">＋ Add Student</a>
    </header>

    <?php if (isset($_GET['msg'])): ?>
    <div class="notice">
        <?php
        $messages = [
            'added' => 'Student added successfully.',
            'updated' => 'Student updated successfully.',
            'deleted' => 'Student record moved to inactive records.',
            'invalid' => 'Please enter valid student details.',
            'duplicate' => 'This email address already exists.'
        ];
        echo safe($messages[$_GET['msg']] ?? 'Operation completed.');
        ?>
    </div>
    <?php endif; ?>

    <section class="stats">
        <article class="stat-card">
            <div class="stat-icon purple">♙</div>
            <p>Active Students</p>
            <h2><?= (int)$totalStudents ?></h2>
            <small>Currently registered</small>
        </article>
        <article class="stat-card">
            <div class="stat-icon green">▤</div>
            <p>Available Courses</p>
            <h2><?= (int)$countCourses ?></h2>
            <small>Course categories</small>
        </article>
        <article class="stat-card">
            <div class="stat-icon orange">⌕</div>
            <p>Matching Records</p>
            <h2><?= (int)$countFiltered ?></h2>
            <small>Based on current filter</small>
        </article>
    </section>

    <section class="panel" id="register">
        <div class="section-heading">
            <div>
                <p class="eyebrow">NEW RECORD</p>
                <h2>Register Student</h2>
            </div>
            <span class="pill">STUDENT FORM</span>
        </div>

        <form method="POST" action="index.php" class="student-form">
            <div class="field">
                <label>Full Name</label>
                <input type="text" name="name" placeholder="Enter student name" required>
            </div>
            <div class="field">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="student@example.com" required>
            </div>
            <div class="field">
                <label>Course</label>
                <select name="course" required>
                    <option value="">Select course</option>
                    <option>Computer Science</option>
                    <option>Web Development</option>
                    <option>Data Science</option>
                </select>
            </div>
            <div class="field">
                <label>Gender</label>
                <select name="gender" required>
                    <option value="">Select gender</option>
                    <option>Male</option>
                    <option>Female</option>
                </select>
            </div>
            <div class="form-actions">
                <button class="button button-primary" type="submit" name="add_student">
                    Save Student
                </button>
                <button class="button button-light" type="reset">Clear Form</button>
            </div>
        </form>
    </section>

    <section class="panel" id="directory">
        <div class="section-heading">
            <div>
                <p class="eyebrow">RECORDS DATABASE</p>
                <h2>Student Directory</h2>
            </div>
            <span class="pill"><?= (int)$countFiltered ?> RECORDS</span>
        </div>

        <form method="GET" class="filter-form">
            <input type="search" name="search"
                   placeholder="Search by name or email..."
                   value="<?= safe($search) ?>">

            <select name="course_filter">
                <option value="">All Courses</option>
                <?php foreach ($courses as $course): ?>
                <option value="<?= safe($course) ?>"
                    <?= $courseFilter === $course ? 'selected' : '' ?>>
                    <?= safe($course) ?>
                </option>
                <?php endforeach; ?>
            </select>

            <button class="button button-primary" type="submit">Search</button>
            <a class="button button-light" href="index.php">Reset</a>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>STUDENT</th>
                        <th>COURSE</th>
                        <th>GENDER</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= (int)$row['id'] ?></td>
                        <td>
                            <div class="student-cell">
                                <span class="avatar"><?= strtoupper(substr($row['name'], 0, 1)) ?></span>
                                <div>
                                    <strong><?= safe($row['name']) ?></strong>
                                    <small><?= safe($row['email']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td><span class="course-tag"><?= safe($row['course']) ?></span></td>
                        <td><?= safe($row['gender']) ?></td>
                        <td class="actions">
                            <a class="edit-link" href="edit.php?id=<?= (int)$row['id'] ?>">Edit</a>
                            <a class="delete-link"
                               href="index.php?delete=<?= (int)$row['id'] ?>"
                               onclick="return confirm('Move this student to inactive records?')">
                               Delete
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="empty">No matching students found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <footer>StudentHub · Student Information Management</footer>
</main>
</body>
</html>
