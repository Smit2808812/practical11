
<?php
include 'db.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="student_list.csv"');

$output = fopen('php://output', 'w');

fputcsv($output, ['ID', 'Name', 'Email', 'Course', 'Gender']);

$result = $conn->query(
    "SELECT id, name, email, course, gender
     FROM students
     WHERE status = 1
     ORDER BY id ASC"
);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['name'],
        $row['email'],
        $row['course'],
        $row['gender']
    ]);
}

fclose($output);
exit;
?>
