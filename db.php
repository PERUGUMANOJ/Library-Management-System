<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // Leave blank for default local setup like XAMPP or assume correct local credentials
$dbname = 'library_system';

// Enable error reporting for mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass);
    
    // Create database if not exists
    $conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
    $conn->select_db($dbname);

    // Create tables if they do not exist
    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Check if admin exists
    $result = $conn->query("SELECT * FROM users WHERE email='admin@library.com'");
    if ($result->num_rows == 0) {
        $admin_pass = password_hash('password', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (name, email, password, role) VALUES ('Admin', 'admin@library.com', '$admin_pass', 'admin')");
    }

    $conn->query("CREATE TABLE IF NOT EXISTS books (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        author VARCHAR(255) NOT NULL,
        status ENUM('available', 'issued') DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $check_col = $conn->query("SHOW COLUMNS FROM books LIKE 'cover_url'");
    if ($check_col->num_rows == 0) {
        $conn->query("ALTER TABLE books ADD COLUMN cover_url VARCHAR(255) NULL AFTER author");
    }

    // Seed books from Open Library API
    $result = $conn->query("SELECT count(*) as count FROM books");
    if ($result && $result->fetch_assoc()['count'] < 1000) {
        $conn->query("DELETE FROM books");
        $api_url = "https://openlibrary.org/subjects/programming.json?limit=1000";
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: LibraryManagementSystem/1.0\r\n"
            ]
        ];
        $context = stream_context_create($opts);
        $json_data = @file_get_contents($api_url, false, $context);
        if ($json_data) {
            $data = json_decode($json_data, true);
            if (isset($data['works'])) {
                $stmt = $conn->prepare("INSERT INTO books (title, author, cover_url) VALUES (?, ?, ?)");
                foreach ($data['works'] as $work) {
                    $title = substr($work['title'], 0, 255);
                    $author = isset($work['authors'][0]['name']) ? substr($work['authors'][0]['name'], 0, 255) : 'Unknown';
                    // Fetch high-quality cover
                    $cover_url = isset($work['cover_id']) ? "https://covers.openlibrary.org/b/id/{$work['cover_id']}-L.jpg" : null;
                    $stmt->bind_param("sss", $title, $author, $cover_url);
                    $stmt->execute();
                }
            }
        }
    }

    $conn->query("CREATE TABLE IF NOT EXISTS issued_books (
        id INT AUTO_INCREMENT PRIMARY KEY,
        book_id INT NOT NULL,
        user_id INT NOT NULL,
        issue_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        return_date TIMESTAMP NULL DEFAULT NULL,
        status ENUM('issued', 'returned') DEFAULT 'issued',
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
