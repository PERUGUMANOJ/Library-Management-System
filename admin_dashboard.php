<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$page = $_GET['page'] ?? 'overview';
$msg = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_book'])) {
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        $cover_url = trim($_POST['cover_url']);
        $stmt = $conn->prepare("INSERT INTO books (title, author, cover_url) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $author, $cover_url);
        if ($stmt->execute()) {
            $msg = "<div class='flash flash-success'><ion-icon name='checkmark-circle'></ion-icon> Book added successfully!</div>";
        }
    } elseif (isset($_POST['issue_book'])) {
        $book_id = intval($_POST['book_id']);
        $user_id = intval($_POST['user_id']);
        $conn->query("UPDATE books SET status='issued' WHERE id=$book_id");
        $stmt = $conn->prepare("INSERT INTO issued_books (book_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $book_id, $user_id);
        $stmt->execute();
        $msg = "<div class='flash flash-success'><ion-icon name='checkmark-circle'></ion-icon> Book issued successfully!</div>";
    }
}

if (isset($_GET['delete_book'])) {
    $id = intval($_GET['delete_book']);
    $conn->query("DELETE FROM books WHERE id = $id");
    $msg = "<div class='flash flash-success'><ion-icon name='trash'></ion-icon> Book deleted completely.</div>";
}

if (isset($_GET['mark_returned'])) {
    $id = intval($_GET['mark_returned']);
    $res = $conn->query("SELECT book_id FROM issued_books WHERE id=$id");
    if ($res->num_rows > 0) {
        $book_id = $res->fetch_assoc()['book_id'];
        $conn->query("UPDATE books SET status='available' WHERE id=$book_id");
        $conn->query("UPDATE issued_books SET status='returned', return_date=CURRENT_TIMESTAMP WHERE id=$id");
        $msg = "<div class='flash flash-success'><ion-icon name='checkmark-circle'></ion-icon> Book marked as returned!</div>";
    }
}

// Stats
$total_books = $conn->query("SELECT COUNT(*) as c FROM books")->fetch_assoc()['c'] ?? 0;
$total_users = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'] ?? 0;
$total_issues = $conn->query("SELECT COUNT(*) as c FROM issued_books WHERE status='issued'")->fetch_assoc()['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | Digital Library</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar overlay for mobile -->
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <!-- Sidebar -->
        <aside class="sidebar" id="mainSidebar">
            <div class="sidebar-header" style="display:flex; justify-content:space-between; align-items:center;">
                <a href="index.php" style="text-decoration:none;" class="sidebar-logo">
                    <ion-icon name="library"></ion-icon>
                    <span>LibSys<span>.</span></span>
                </a>
                <ion-icon name="close-outline" class="close-sidebar-btn" onclick="toggleSidebar()"></ion-icon>
            </div>
            <div class="sidebar-nav">
                <p style="font-size:0.75rem; font-weight:700; color:var(--text-muted); padding:0 20px; margin-bottom:10px; letter-spacing:1px; text-transform:uppercase;">Administration</p>
                <a href="?page=overview" class="nav-item <?php echo ($page=='overview' || $page=='')?'active':''; ?>">
                    <ion-icon name="grid"></ion-icon> <span>Overview</span>
                </a>
                <a href="?page=books" class="nav-item <?php echo $page=='books'?'active':''; ?>">
                    <ion-icon name="book"></ion-icon> <span>Manage Books</span>
                </a>
                <a href="?page=users" class="nav-item <?php echo $page=='users'?'active':''; ?>">
                    <ion-icon name="people"></ion-icon> <span>Students Directory</span>
                </a>
                <a href="?page=issues" class="nav-item <?php echo $page=='issues'?'active':''; ?>">
                    <ion-icon name="repeat"></ion-icon> <span>Issues & Returns</span>
                </a>
            </div>
            <div class="sidebar-footer">
                <a href="logout.php" class="nav-item" style="color:var(--danger); margin-bottom:0;">
                    <ion-icon name="log-out"></ion-icon> <span>Administrator Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="topbar">
                <ion-icon name="menu-outline" class="mobile-menu-btn" onclick="toggleSidebar()"></ion-icon>
                <div class="search-wrapper">
                    <ion-icon name="search" style="color:var(--text-muted); font-size:1.2rem;"></ion-icon>
                    <input type="text" placeholder="Search library records, books, or users...">
                </div>
                <div class="user-profile">
                    <div class="user-info" style="text-align:right;">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <span class="user-role">Super Admin</span>
                    </div>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name']); ?>&background=0f172a&color=fff" class="user-avatar" alt="Admin">
                </div>
            </header>

            <div class="content-wrapper">
                <?php echo $msg; ?>

                <?php if ($page === 'overview' || $page === ''): ?>
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">Admin Overview</h1>
                            <p class="page-subtitle">Welcome back, monitor your library's activity from here.</p>
                        </div>
                    </div>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-info">
                                <span class="stat-label">Total Books</span>
                                <span class="stat-value"><?php echo $total_books; ?></span>
                            </div>
                            <div class="stat-icon" style="color:#3b82f6; background:rgba(59,130,246,0.1);">
                                <ion-icon name="book"></ion-icon>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-info">
                                <span class="stat-label">Active Users</span>
                                <span class="stat-value"><?php echo $total_users; ?></span>
                            </div>
                            <div class="stat-icon" style="color:#10b981; background:rgba(16,185,129,0.1);">
                                <ion-icon name="people"></ion-icon>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-info">
                                <span class="stat-label">Currently Issued</span>
                                <span class="stat-value"><?php echo $total_issues; ?></span>
                            </div>
                            <div class="stat-icon" style="color:#f59e0b; background:rgba(245,158,11,0.1);">
                                <ion-icon name="swap-horizontal"></ion-icon>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-header">
                            <h2 class="panel-title">Recent Books Added</h2>
                            <a href="?page=books" class="btn btn-primary" style="padding:8px 16px; font-size:0.85rem;">View All Catalog <ion-icon name="arrow-forward"></ion-icon></a>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <tr><th>Book Title</th><th>Author</th><th>Status</th></tr>
                                <?php
                                $recent = $conn->query("SELECT * FROM books ORDER BY id DESC LIMIT 5");
                                while ($r = $recent->fetch_assoc()) {
                                    $sc = $r['status'] == 'available' ? 'badge-available' : 'badge-issued';
                                    echo "<tr>
                                        <td><strong>{$r['title']}</strong></td>
                                        <td>{$r['author']}</td>
                                        <td><span class='badge {$sc}'>{$r['status']}</span></td>
                                    </tr>";
                                }
                                ?>
                            </table>
                        </div>
                    </div>

                <?php elseif ($page === 'books'): ?>
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">Manage Books</h1>
                            <p class="page-subtitle">Add new books or remove existing ones from the catalog.</p>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-header">
                            <h2 class="panel-title">Add New Book to Inventory</h2>
                        </div>
                        <form method="POST" class="form-row">
                            <div class="form-group">
                                <label class="form-label">Book Title</label>
                                <input type="text" name="title" class="form-control" placeholder="E.g., The Great Gatsby" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Author Name</label>
                                <input type="text" name="author" class="form-control" placeholder="E.g., F. Scott Fitzgerald" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Cover Image URL (Optional)</label>
                                <input type="text" name="cover_url" class="form-control" placeholder="https://...">
                            </div>
                            <div class="form-group" style="display:flex; align-items:flex-end;">
                                <button type="submit" name="add_book" class="btn btn-primary" style="width:100%;"><ion-icon name="add-circle"></ion-icon> Add Book</button>
                            </div>
                        </form>
                    </div>

                    <div class="panel">
                        <div class="panel-header">
                            <h2 class="panel-title">Master Catalog</h2>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <tr><th>ID</th><th>Cover</th><th>Title</th><th>Author</th><th>Status</th><th>Actions</th></tr>
                                <?php
                                $books = $conn->query("SELECT * FROM books ORDER BY id DESC");
                                while ($b = $books->fetch_assoc()) {
                                    $sc = $b['status'] == 'available' ? 'badge-available' : 'badge-issued';
                                    $img = $b['cover_url'] ?: 'https://via.placeholder.com/40x60?text=No+Img';
                                    echo "<tr>
                                        <td><span style='color:var(--text-muted);font-weight:600;'>#{$b['id']}</span></td>
                                        <td><img src='{$img}' style='width:40px; height:60px; object-fit:cover; border-radius:4px; box-shadow:var(--shadow-sm);'></td>
                                        <td><strong>{$b['title']}</strong></td>
                                        <td>{$b['author']}</td>
                                        <td><span class='badge {$sc}'>{$b['status']}</span></td>
                                        <td><a href='?page=books&delete_book={$b['id']}' onclick='return confirm(\"Are you sure you want to permanently delete this book?\")' class='btn btn-danger' style='padding:8px 14px; font-size:0.8rem;'><ion-icon name='trash'></ion-icon> Delete</a></td>
                                    </tr>";
                                }
                                ?>
                            </table>
                        </div>
                    </div>

                <?php elseif ($page === 'users'): ?>
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">Registered Students</h1>
                            <p class="page-subtitle">View all enrolled library members and their access levels.</p>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="table-responsive">
                            <table class="data-table">
                                <tr><th>ID</th><th>User Profile</th><th>Email Address</th><th>Role Level</th><th>Registration Date</th></tr>
                                <?php
                                $users = $conn->query("SELECT * FROM users ORDER BY id DESC");
                                while ($u = $users->fetch_assoc()) {
                                    $rc = $u['role'] == 'admin' ? 'badge-issued' : 'badge-available';
                                    echo "<tr>
                                        <td><span style='color:var(--text-muted);font-weight:600;'>#{$u['id']}</span></td>
                                        <td><div style='display:flex;align-items:center;gap:12px;'><img src='https://ui-avatars.com/api/?name=".urlencode($u['name'])."&background=random' style='width:36px;height:36px;border-radius:50%;'> <strong>{$u['name']}</strong></div></td>
                                        <td><a href='mailto:{$u['email']}' style='color:var(--text-muted);'>{$u['email']}</a></td>
                                        <td><span class='badge {$rc}'>{$u['role']}</span></td>
                                        <td><span style='color:var(--text-muted);'>".date("M j, Y", strtotime($u['created_at']))."</span></td>
                                    </tr>";
                                }
                                ?>
                            </table>
                        </div>
                    </div>

                <?php elseif ($page === 'issues'): ?>
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">Issues & Returns</h1>
                            <p class="page-subtitle">Manage daily circulation, approve borrowing, and handle returns.</p>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-header">
                            <h2 class="panel-title">Manually Issue Book</h2>
                        </div>
                        <form method="POST" class="form-row">
                            <div class="form-group">
                                <label class="form-label">Active Student</label>
                                <select name="user_id" class="form-control" required style="cursor:pointer;">
                                    <option value="">-- Select Student --</option>
                                    <?php
                                    $usrs = $conn->query("SELECT id, name FROM users WHERE role='user'");
                                    while ($u = $usrs->fetch_assoc()) echo "<option value='{$u['id']}'>{$u['name']} (UID: {$u['id']})</option>";
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Available Title</label>
                                <select name="book_id" class="form-control" required style="cursor:pointer;">
                                    <option value="">-- Choose Book --</option>
                                    <?php
                                    $bks = $conn->query("SELECT id, title FROM books WHERE status='available'");
                                    while ($b = $bks->fetch_assoc()) echo "<option value='{$b['id']}'>{$b['title']} (BID: {$b['id']})</option>";
                                    ?>
                                </select>
                            </div>
                            <div class="form-group" style="display:flex; align-items:flex-end;">
                                <button type="submit" name="issue_book" class="btn btn-primary" style="width:100%;"><ion-icon name="send"></ion-icon> Confirm Issuance</button>
                            </div>
                        </form>
                    </div>

                    <div class="panel">
                        <div class="panel-header">
                            <h2 class="panel-title">Circulation Logs</h2>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <tr><th>Log ID</th><th>Book Title</th><th>Assigned Student</th><th>Issued Timestamp</th><th>Current Status</th><th>Actions</th></tr>
                                <?php
                                $q = "SELECT i.id, b.title, u.name, i.issue_date, i.status 
                                      FROM issued_books i 
                                      JOIN books b ON i.book_id = b.id 
                                      JOIN users u ON i.user_id = u.id 
                                      ORDER BY i.id DESC";
                                $issues = $conn->query($q);
                                if ($issues->num_rows > 0) {
                                    while ($i = $issues->fetch_assoc()) {
                                        $st_class = $i['status'] == 'returned' ? 'badge-returned' : 'badge-issued';
                                        $action = $i['status'] == 'issued' ? "<a href='?page=issues&mark_returned={$i['id']}' class='btn btn-primary' style='padding:8px 14px; font-size:0.8rem;'><ion-icon name='arrow-undo'></ion-icon> Mark Returned</a>" : "<span style='color:var(--text-muted); font-size:0.85rem; display:flex; align-items:center; gap:5px;'><ion-icon name='checkmark-done-circle' style='font-size:1.2rem; color:var(--success);'></ion-icon> Cleared</span>";
                                        echo "<tr>
                                            <td><span style='color:var(--text-muted);font-weight:600;'>#{$i['id']}</span></td>
                                            <td><strong>{$i['title']}</strong></td>
                                            <td><div style='display:flex;align-items:center;gap:8px;'><img src='https://ui-avatars.com/api/?name=".urlencode($i['name'])."&background=random' style='width:24px;height:24px;border-radius:50%;'> {$i['name']}</div></td>
                                            <td><span style='color:var(--text-muted);'>".date("M j, Y H:i", strtotime($i['issue_date']))."</span></td>
                                            <td><span class='badge {$st_class}'>{$i['status']}</span></td>
                                            <td>{$action}</td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' style='text-align:center; padding:40px; color:var(--text-muted);'>No circulation logs available yet.</td></tr>";
                                }
                                ?>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <!-- Interactive Scripts -->
    <script>
        // Sidebar Toggle for Mobile
        function toggleSidebar() {
            document.getElementById('mainSidebar').classList.toggle('show');
            document.querySelector('.sidebar-overlay').classList.toggle('show');
        }
    </script>
</body>
</html>
