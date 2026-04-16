<?php
require 'db.php';

header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query)) {
    echo json_encode([]);
    exit;
}

$search = $conn->real_escape_string($query);
// Search by title, author or category
$sql = "SELECT id, title, author, category FROM books WHERE title LIKE '%$search%' OR author LIKE '%$search%' OR category LIKE '%$search%' LIMIT 5";
$res = $conn->query($sql);

$results = [];
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $results[] = [
            'id' => $row['id'],
            'title' => htmlspecialchars($row['title']),
            'author' => htmlspecialchars($row['author']),
            'category' => htmlspecialchars($row['category'] ?? 'General')
        ];
    }
}

echo json_encode($results);
