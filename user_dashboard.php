<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$page = $_GET['page'] ?? 'discover';
$msg = '';

// Handle borrowing logic
if (isset($_GET['borrow'])) {
    $book_id = intval($_GET['borrow']);
    $check = $conn->query("SELECT status FROM books WHERE id=$book_id");
    if ($check->num_rows > 0 && $check->fetch_assoc()['status'] == 'available') {
        $conn->query("UPDATE books SET status='issued' WHERE id=$book_id");
        $stmt = $conn->prepare("INSERT INTO issued_books (book_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $book_id, $user_id);
        if($stmt->execute()){
            $msg = "<div class='flash flash-success'><ion-icon name='checkmark-circle'></ion-icon> Success! The book has been added to your library.</div>";
        }
    }
}

// Search Logic
$is_search = isset($_GET['search']) && !empty(trim($_GET['search']));
$search_results = null;

if ($is_search) {
    // Override page to discover
    $page = 'discover';
    $search = $conn->real_escape_string(trim($_GET['search']));
    $filter_avail = isset($_GET['availability']) && $_GET['availability'] == '1';
    $avail_sql = $filter_avail ? " AND status='available'" : "";
    $search_results = $conn->query("SELECT * FROM books WHERE (title LIKE '%$search%' OR author LIKE '%$search%' OR category LIKE '%$search%') $avail_sql ORDER BY id DESC");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal | Digital Library</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar overlay for mobile -->
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <!-- Sidebar -->
        <aside class="sidebar sidebar-light" id="mainSidebar">
            <div class="sidebar-header" style="border-bottom:1px solid var(--border-color); margin-bottom:20px; padding:25px 30px; display:flex; justify-content:space-between; align-items:center;">
                <a href="index.php" style="text-decoration:none;" class="sidebar-logo">
                    <ion-icon name="library" style="color:var(--primary);"></ion-icon>
                    <span style="color:var(--primary);">LibSys<span style="color:var(--primary);">.</span></span>
                </a>
                <ion-icon name="close-outline" class="close-sidebar-btn" onclick="toggleSidebar()"></ion-icon>
            </div>
            <div class="sidebar-nav">
                <p style="font-size:0.75rem; font-weight:700; color:var(--text-muted); padding:0 20px; margin-bottom:10px; letter-spacing:1px; text-transform:uppercase;">Explore Collection</p>
                
                <a href="?page=discover" class="nav-item <?php echo $page=='discover'?'active':''; ?>">
                    <ion-icon name="compass-outline"></ion-icon> <span>Discover Books</span>
                </a>
                
                <a href="?page=news" class="nav-item <?php echo $page=='news'?'active':''; ?>">
                    <ion-icon name="earth-outline"></ion-icon> <span>Global News</span>
                </a>
                
                <a href="?page=magazines" class="nav-item <?php echo $page=='magazines'?'active':''; ?>">
                    <ion-icon name="newspaper-outline"></ion-icon> <span>Digital Magazines</span>
                </a>
                
                <a href="?page=mylibrary" class="nav-item <?php echo $page=='mylibrary'?'active':''; ?>">
                    <ion-icon name="bookmarks-outline"></ion-icon> <span>My Library</span>
                </a>

                <p style="font-size:0.75rem; font-weight:700; color:var(--text-muted); padding:0 20px; margin-top:30px; margin-bottom:10px; letter-spacing:1px; text-transform:uppercase;">Settings</p>
                <a href="logout.php" class="nav-item" style="color:var(--danger);">
                    <ion-icon name="log-out-outline"></ion-icon> <span>Sign Out Portal</span>
                </a>
            </div>
            
            <!-- Quick help banner -->
            <div style="padding:20px; margin:20px; background:linear-gradient(135deg,rgba(59,130,246,0.1) 0%,rgba(59,130,246,0.05) 100%); border-radius:var(--radius-md); text-align:center;">
                <ion-icon name="help-buoy-outline" style="font-size:2rem; color:var(--primary); margin-bottom:10px;"></ion-icon>
                <h4 style="font-size:0.9rem; margin-bottom:5px;">Need Help?</h4>
                <p style="font-size:0.75rem; color:var(--text-muted);">Contact the librarian for support regarding your account.</p>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content" style="background:var(--bg-body);">
            <header class="topbar">
                <ion-icon name="menu-outline" class="mobile-menu-btn" onclick="toggleSidebar()"></ion-icon>
                
                <form method="GET" action="user_dashboard.php" class="search-wrapper" style="position:relative; flex:1; max-width:650px; display:flex; align-items:center;">
                    <ion-icon name="search-outline" style="color:var(--text-muted); font-size:1.4rem;"></ion-icon>
                    <input type="text" name="search" id="dashSearchInput" placeholder="Search by title, author, or category..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" autocomplete="off" style="flex:1;">
                    
                    <label style="display:flex; align-items:center; gap:5px; font-size:0.85rem; color:var(--text-muted); cursor:pointer; margin-right:15px; border-left:1px solid var(--border-color); padding-left:15px;">
                        <input type="checkbox" name="availability" value="1" style="accent-color:var(--primary);" <?php echo (isset($_GET['availability']) && $_GET['availability'] == '1') ? 'checked' : ''; ?>> Available Only
                    </label>

                    <?php if($is_search): ?>
                        <a href="user_dashboard.php" style="color:var(--danger); font-size:1.4rem; display:flex; padding:5px; border-radius:50%; background:rgba(239,68,68,0.1); text-decoration:none;" title="Clear Search"><ion-icon name="close"></ion-icon></a>
                    <?php endif; ?>
                    
                    <button type="submit" style="display:none;"></button>
                    <!-- Suggestions Box -->
                    <div id="dashSuggestionsBox" style="display:none; position:absolute; top:calc(100% + 5px); left:0; width:100%; background:var(--bg-card); border-radius:var(--radius-md); box-shadow:var(--shadow-lg); z-index:100; border:1px solid var(--border-color); overflow:hidden;"></div>
                </form>
                
                <div class="user-profile">
                    <div class="user-info" style="text-align:right;">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <span class="user-role">Enrolled Student</span>
                    </div>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name']); ?>&background=3b82f6&color=fff" class="user-avatar" alt="User">
                </div>
            </header>

            <div class="content-wrapper">
                <?php echo $msg; ?>

                <?php if ($page === 'discover'): ?>
                    
                    <?php if ($is_search): ?>
                        <div class="page-header">
                            <div>
                                <h1 class="page-title">Search Results</h1>
                                <p class="page-subtitle">Showing results for "<strong style="color:var(--primary);"><?php echo htmlspecialchars($_GET['search']); ?></strong>"</p>
                            </div>
                        </div>
                        <div class="books-grid">
                            <?php
                            if ($search_results && $search_results->num_rows > 0) {
                                while ($b = $search_results->fetch_assoc()) {
                                    $c_img = !empty($b['cover_url']) ? $b['cover_url'] : 'https://via.placeholder.com/150x220?text=Cover';
                                    $status_badge = $b['status'] == 'available' ? "<span class='badge badge-available' style='font-size:0.65rem; padding:6px 10px; position:absolute; top:15px; right:15px; z-index:10; box-shadow:var(--shadow-sm);'>Available</span>" : "<span class='badge badge-issued' style='font-size:0.65rem; padding:6px 10px; position:absolute; top:15px; right:15px; z-index:10; background:rgba(0,0,0,0.7); color:#fff; backdrop-filter:blur(4px);'>Issued</span>";
                                    echo "<div class='book-card' style='flex-direction:column; align-items:center; text-align:center;'>
                                        {$status_badge}
                                        <div style='position:relative; width:100%; display:flex; justify-content:center; margin-bottom:15px; padding-top:10px;'>
                                            <img src='{$c_img}' class='book-cover' style='width:130px; height:200px;'>
                                        </div>
                                        <div class='book-details' style='width:100%;align-items:center;'>
                                            <h4 class='book-title' title='".htmlspecialchars($b['title'])."'>".htmlspecialchars($b['title'])."</h4>
                                            <p class='book-author'>{$b['author']}</p>
                                            <div class='book-actions' style='width:100%; justify-content:center; gap:10px; margin-top:20px;'>
                                                <a href='read.php?id={$b['id']}' class='btn btn-icon' style='background:rgba(59,130,246,0.1); color:var(--primary);' title='View Details'><ion-icon name='book-outline'></ion-icon> Read</a>";
                                                
                                                if($b['status'] == 'available'){
                                                    echo "<a href='?borrow={$b['id']}' class='btn btn-primary' style='flex:1; padding:10px;'><ion-icon name='bookmark'></ion-icon> Borrow Now</a>";
                                                } else {
                                                    echo "<span class='btn' style='flex:1; padding:10px; background:var(--border-color); color:var(--text-muted); cursor:not-allowed;'><ion-icon name='time-outline'></ion-icon> Unavailable</span>";
                                                }
                                                
                                            echo "</div>
                                        </div>
                                    </div>";
                                }
                            } else {
                                echo "<div style='grid-column: 1 / -1; text-align:center; padding:60px; background:var(--bg-card); border-radius:var(--radius-lg);'>
                                    <ion-icon name='search' style='font-size:4rem; color:var(--border-color); margin-bottom:20px;'></ion-icon>
                                    <h3 style='font-size:1.5rem; margin-bottom:10px;'>No results found</h3>
                                    <p style='color:var(--text-muted);'>Try adjusting your search terms or keywords to find what you're looking for.</p>
                                </div>";
                            }
                            ?>
                        </div>
                    <?php else: ?>
                        <!-- Explore Hero -->
                        <div class="panel" style="background:linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); color:#fff; border:none; display:flex; justify-content:space-between; align-items:center; padding:60px; overflow:hidden; position:relative;">
                            <!-- background glowing orbs for effect -->
                            <div style="position:absolute; width:300px; height:300px; background:var(--primary); filter:blur(100px); border-radius:50%; top:-100px; right:-50px; opacity:0.3; z-index:0;"></div>
                            
                            <div style="max-width:550px; position:relative; z-index:2;">
                                <span class="badge" style="background:rgba(255,255,255,0.15); color:#fff; margin-bottom:20px; font-size:0.8rem; padding:8px 16px; border:1px solid rgba(255,255,255,0.2); backdrop-filter:blur(4px);">Welcome to the Digital Library</span>
                                <h1 style="font-size:3rem; color:#FFFFFF; font-weight:800; line-height:1.15; margin-bottom:20px;">Expand your mind,<br><span style="color:#3F63D1;">one page</span> at a time.</h1>
                                <p style="font-size:1.1rem; opacity:0.85; margin-bottom:35px; line-height:1.6;">Access thousands of modern titles and classic literature. Borrow instantly and read everywhere, all in one beautifully crafted platform.</p>
                                <a href="#latest" class="btn btn-primary" style="padding:16px 32px; font-size:1.05rem; border-radius:30px; box-shadow:0 10px 25px rgba(59,130,246,0.5);"><ion-icon name="book"></ion-icon> Start Reading Collection</a>
                            </div>
                            <div style="font-size:10rem; filter:drop-shadow(0 20px 40px rgba(0,0,0,0.5)); animation: float 6s ease-in-out infinite; position:relative; z-index:2; margin-right:40px;">
                                📚
                            </div>
                        </div>

                        <!-- General catalog row -->
                        <div class="page-header" id="latest" style="margin-top:50px;">
                            <div>
                                <h2 class="page-title" style="font-size:1.8rem;">Trending & Latest Arrivals</h2>
                                <p class="page-subtitle">Handpicked selection of the most popular books this week.</p>
                            </div>
                            <button class="btn btn-icon" style="background:var(--bg-card); border:1px solid var(--border-color);"><ion-icon name="options"></ion-icon> Filter</button>
                        </div>
                        
                        <div class="books-grid">
                            <?php
                            $latests = $conn->query("SELECT * FROM books ORDER BY id DESC LIMIT 12");
                            while ($b = $latests->fetch_assoc()) {
                                $c_img = !empty($b['cover_url']) ? $b['cover_url'] : 'https://via.placeholder.com/150x220?text=Cover';
                                $status_badge = $b['status'] == 'available' ? "<span class='badge badge-available' style='font-size:0.65rem; padding:6px 10px; position:absolute; top:15px; right:15px; z-index:10; box-shadow:var(--shadow-sm);'>Available</span>" : "<span class='badge badge-issued' style='font-size:0.65rem; padding:6px 10px; position:absolute; top:15px; right:15px; z-index:10; background:rgba(0,0,0,0.7); color:#fff; backdrop-filter:blur(4px);'>Issued</span>";
                                
                                echo "<div class='book-card' style='flex-direction:column; align-items:center; text-align:center;'>
                                    {$status_badge}
                                    <div style='position:relative; width:100%; display:flex; justify-content:center; margin-bottom:15px; padding-top:10px;'>
                                        <img src='{$c_img}' class='book-cover' style='width:130px; height:200px;'>
                                    </div>
                                    <div class='book-details' style='width:100%;align-items:center;'>
                                        <h4 class='book-title' title='".htmlspecialchars($b['title'])."'>".htmlspecialchars($b['title'])."</h4>
                                        <p class='book-author'>{$b['author']}</p>
                                        <div class='book-actions' style='width:100%; justify-content:center; gap:10px; margin-top:20px;'>
                                            <a href='read.php?id={$b['id']}' class='btn btn-icon' style='background:rgba(59,130,246,0.1); color:var(--primary); padding:10px 14px;' title='Read Inside'><ion-icon name='book-outline'></ion-icon></a>";
                                            
                                            if($b['status'] == 'available'){
                                                echo "<a href='?borrow={$b['id']}' class='btn btn-primary' style='flex:1; padding:10px;'><ion-icon name='bookmark'></ion-icon> Borrow Now</a>";
                                            } else {
                                                echo "<span class='btn' style='flex:1; padding:10px; background:var(--border-color); color:var(--text-muted); cursor:not-allowed;'><ion-icon name='time-outline'></ion-icon> Unavailable</span>";
                                            }
                                            
                                        echo "</div>
                                    </div>
                                </div>";
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                    
                <?php elseif ($page === 'news' || $page === 'magazines'): ?>
                    <div class="page-header" style="margin-top:20px;">
                        <div>
                            <h2 class="page-title" style="font-size:1.8rem;"><?php echo $page === 'news' ? 'Global News & Journals' : 'Digital Magazines'; ?></h2>
                            <p class="page-subtitle"><?php echo $page === 'news' ? 'Stay updated with the latest happenings worldwide.' : 'The latest issues from top publishers.'; ?></p>
                        </div>
                        <button class="btn btn-icon" style="background:var(--bg-card); border:1px solid var(--border-color);"><ion-icon name="options"></ion-icon> Filter</button>
                    </div>
                    
                    <?php if ($page === 'news'): 
                        $source = isset($_GET['source']) ? $_GET['source'] : 'general';
                    ?>
                    <div style="display:flex; gap:10px; margin-bottom:25px; overflow-x:auto; padding-bottom:10px;">
                        <a href="?page=news&source=general" class="btn" style="<?php echo $source==='general'?'background:var(--primary);color:#fff;':'background:var(--bg-card);border:1px solid var(--border-color);color:var(--text-main);'; ?> border-radius:20px; font-size:0.85rem; padding:8px 16px;">Top Headlines</a>
                        <a href="?page=news&source=bbc-news" class="btn" style="<?php echo $source==='bbc-news'?'background:var(--primary);color:#fff;':'background:var(--bg-card);border:1px solid var(--border-color);color:var(--text-main);'; ?> border-radius:20px; font-size:0.85rem; padding:8px 16px;">BBC News</a>
                        <a href="?page=news&source=cnn" class="btn" style="<?php echo $source==='cnn'?'background:var(--primary);color:#fff;':'background:var(--bg-card);border:1px solid var(--border-color);color:var(--text-main);'; ?> border-radius:20px; font-size:0.85rem; padding:8px 16px;">CNN</a>
                        <a href="?page=news&source=fox-news" class="btn" style="<?php echo $source==='fox-news'?'background:var(--primary);color:#fff;':'background:var(--bg-card);border:1px solid var(--border-color);color:var(--text-main);'; ?> border-radius:20px; font-size:0.85rem; padding:8px 16px;">Fox News</a>
                        <a href="?page=news&source=google-news" class="btn" style="<?php echo $source==='google-news'?'background:var(--primary);color:#fff;':'background:var(--bg-card);border:1px solid var(--border-color);color:var(--text-main);'; ?> border-radius:20px; font-size:0.85rem; padding:8px 16px;">Google News</a>
                    </div>
                    <?php endif; ?>
                    
                    <div class="books-grid">
                        <?php
                        // Fetch from APIs
                        $ctx = stream_context_create(['http'=>['timeout'=>3]]);
                        $api_items = [];
                        
                        if ($page === 'news') {
                            $ep = ($source === 'general') ? 'top-headlines/category/general/us.json' : "everything/{$source}.json";
                            $json = @file_get_contents('https://saurav.tech/NewsAPI/' . $ep, false, $ctx);
                            if ($json) {
                                $data = json_decode($json, true);
                                if (!empty($data['articles'])) {
                                    foreach ($data['articles'] as $article) {
                                        if (!empty($article['title']) && !empty($article['urlToImage'])) {
                                            $api_items[] = [
                                                'title' => $article['title'],
                                                'author' => $article['source']['name'] ?? 'Global News',
                                                'cover_url' => $article['urlToImage'],
                                                'url' => $article['url']
                                            ];
                                            if (count($api_items) >= 12) break; 
                                        }
                                    }
                                }
                            }
                        } else {
                            $json_m = @file_get_contents('https://api.spaceflightnewsapi.net/v4/articles?limit=12', false, $ctx);
                            if ($json_m) {
                                $data_m = json_decode($json_m, true);
                                if (!empty($data_m['results'])) {
                                    foreach ($data_m['results'] as $article) {
                                        $api_items[] = [
                                            'title' => $article['title'],
                                            'author' => $article['news_site'] ?? 'Tech Magazine',
                                            'cover_url' => $article['image_url'],
                                            'url' => $article['url']
                                        ];
                                    }
                                }
                            }
                        }
                        
                        if (!empty($api_items)) {
                            foreach ($api_items as $b) {
                                $c_img = !empty($b['cover_url']) ? $b['cover_url'] : 'https://via.placeholder.com/150x220?text=Cover';
                                
                                echo "<div class='book-card' style='flex-direction:column; align-items:center; text-align:center;'>
                                    <span class='badge badge-available' style='font-size:0.65rem; padding:6px 10px; position:absolute; top:15px; right:15px; z-index:10; box-shadow:var(--shadow-sm);'>API Feed</span>
                                    <div style='position:relative; width:100%; display:flex; justify-content:center; margin-bottom:15px; padding-top:10px;'>
                                        <img src='{$c_img}' class='book-cover' style='width:130px; height:200px; object-fit:cover;'>
                                    </div>
                                    <div class='book-details' style='width:100%;align-items:center;'>
                                        <h4 class='book-title' title='".htmlspecialchars($b['title'])."'>".htmlspecialchars($b['title'])."</h4>
                                        <p class='book-author'>{$b['author']}</p>
                                        <div class='book-actions' style='width:100%; justify-content:center; gap:10px; margin-top:20px;'>
                                            <a href='{$b['url']}' target='_blank' class='btn btn-primary' style='flex:1; padding:10px;'><ion-icon name='eye-outline'></ion-icon> Read Full Article</a>
                                        </div>
                                    </div>
                                </div>";
                            }
                        } else {
                            echo "<div style='grid-column: 1 / -1; text-align:center; padding:60px; background:var(--bg-card); border-radius:var(--radius-lg);'>
                                <ion-icon name='cloud-offline-outline' style='font-size:4rem; color:var(--danger); margin-bottom:20px;'></ion-icon>
                                <h3 style='font-size:1.5rem; margin-bottom:10px;'>API Feed Unavailable</h3>
                                <p style='color:var(--text-muted);'>We couldn't connect to the external news/magazine server. Please try again later.</p>
                            </div>";
                        }
                        ?>
                    </div>

                <?php elseif ($page === 'mylibrary'): ?>
                    <div class="page-header">
                        <div>
                            <h1 class="page-title">My Reading Library</h1>
                            <p class="page-subtitle">Track the books you are currently reading or have borrowed in the past.</p>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="table-responsive">
                            <table class="data-table">
                                <tr><th>Cover Art</th><th>Book Title</th><th>Author</th><th>Date Borrowed</th><th>Status</th><th>Actions</th></tr>
                                <?php
                                $my_books = $conn->query("SELECT i.id, i.issue_date, i.status as issue_status, b.title, b.author, b.cover_url, b.id as book_id FROM issued_books i JOIN books b ON i.book_id = b.id WHERE i.user_id = $user_id ORDER BY i.id DESC");
                                
                                if($my_books->num_rows > 0) {
                                    while ($mb = $my_books->fetch_assoc()) {
                                        $c_img = !empty($mb['cover_url']) ? $mb['cover_url'] : 'https://via.placeholder.com/40x60?text=Cover';
                                        $st_class = $mb['issue_status'] == 'returned' ? 'badge-returned' : 'badge-available';
                                        $st_text = $mb['issue_status'] == 'returned' ? 'Returned' : 'Currently Reading';
                                        
                                        echo "<tr>
                                            <td><img src='{$c_img}' style='width:50px; height:75px; object-fit:cover; border-radius:6px; box-shadow:var(--shadow-sm);'></td>
                                            <td><strong><a href='read.php?id={$mb['book_id']}' style='color:var(--text-main); font-size:1.05rem; display:block; margin-bottom:4px;'>{$mb['title']}</a></strong>
                                            <span style='color:var(--primary); font-size:0.8rem; font-weight:600;'>ID: {$mb['book_id']}</span></td>
                                            <td>{$mb['author']}</td>
                                            <td><span style='color:var(--text-muted);'>".date("M j, Y", strtotime($mb['issue_date']))."</span></td>
                                            <td><span class='badge {$st_class}'>{$st_text}</span></td>
                                            <td><a href='read.php?id={$mb['book_id']}' class='btn' style='background:rgba(59,130,246,0.1); color:var(--primary); padding:8px 14px; font-size:0.85rem;'><ion-icon name='book-outline'></ion-icon> Read</a></td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' style='text-align:center; padding:60px; color:var(--text-muted);'>
                                    <ion-icon name='bookmarks' style='font-size:3rem; color:var(--border-color); margin-bottom:15px;'></ion-icon>
                                    <h4 style='font-size:1.2rem; color:var(--text-main); margin-bottom:10px;'>Your library is empty</h4>
                                    <p style='margin-bottom:20px;'>You haven't borrowed any books yet. Explore the collection to get started.</p>
                                    <a href='?page=discover' class='btn btn-primary'><ion-icon name='compass'></ion-icon> Discover Books</a></td></tr>";
                                }
                                ?>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
    <!-- Theme / Interactive Scripts -->
    <script>
        // Sidebar Toggle for Mobile
        function toggleSidebar() {
            document.getElementById('mainSidebar').classList.toggle('show');
            document.querySelector('.sidebar-overlay').classList.toggle('show');
        }

        // Theme Toggle Logic
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            if (document.body.classList.contains('dark-mode')) {
                document.cookie = "theme=dark; path=/; max-age=31536000";
            } else {
                document.cookie = "theme=light; path=/; max-age=31536000";
            }
        }

        // AJAX Auto-suggest Search
        const inputDash = document.getElementById('dashSearchInput');
        const boxDash = document.getElementById('dashSuggestionsBox');
        let timeoutDash = null;

        if (inputDash) {
            inputDash.addEventListener('input', function() {
                clearTimeout(timeoutDash);
                const q = this.value.trim();
                const availEl = document.querySelector('input[name="availability"]');
                const v_avail = (availEl && availEl.checked) ? 1 : 0;
                
                if(q.length < 2) {
                    boxDash.style.display = 'none';
                    return;
                }
                
                timeoutDash = setTimeout(() => {
                    fetch(`ajax_search.php?q=${encodeURIComponent(q)}&avail=${v_avail}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.length > 0) {
                            let html = '';
                            data.forEach(item => {
                                html += `<a href="read.php?id=${item.id}" style="display:flex; flex-direction:column; padding:12px 20px; border-bottom:1px solid var(--border-color); text-decoration:none; color:var(--text-main); transition:var(--transition);">
                                    <span style="font-weight:600; font-size:0.95rem;">${item.title}</span>
                                    <span style="font-size:0.8rem; color:var(--text-muted);">${item.author} &bull; ${item.category || 'Book'}</span>
                                </a>`;
                            });
                            boxDash.innerHTML = html;
                            boxDash.style.display = 'block';
                            
                            // add hover style via JS since we can't easily inline pseudoclasses
                            boxDash.querySelectorAll('a').forEach(aEl => {
                                aEl.addEventListener('mouseenter', () => aEl.style.background = 'rgba(59,130,246,0.05)');
                                aEl.addEventListener('mouseleave', () => aEl.style.background = 'transparent');
                            });
                        } else {
                            boxDash.style.display = 'none';
                        }
                    }).catch(()=> { boxDash.style.display = 'none'; });
                }, 300);
            });

            document.addEventListener('click', function(e) {
                if (!inputDash.contains(e.target) && !boxDash.contains(e.target)) {
                    boxDash.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
