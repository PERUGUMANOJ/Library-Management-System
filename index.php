<?php
session_start();
require 'db.php';

$is_search = isset($_GET['search']) && !empty(trim($_GET['search']));
$filter_avail = isset($_GET['availability']) && $_GET['availability'] == '1';
$search_results = null;

if ($is_search) {
    $search = $conn->real_escape_string(trim($_GET['search']));
    $avail_sql = $filter_avail ? " AND status='available'" : "";
    $search_results = $conn->query("SELECT * FROM books WHERE (title LIKE '%$search%' OR author LIKE '%$search%' OR category LIKE '%$search%') $avail_sql ORDER BY id DESC");
}

// Category rows (fallbacks to general books if specific keywords not found)
// Fetch External API for News
$api_news_enabled = false;
$api_news = [];
$ctx = stream_context_create(['http'=>['timeout'=>3]]);
$json = @file_get_contents('https://saurav.tech/NewsAPI/top-headlines/category/general/us.json', false, $ctx);
if ($json) {
    $data = json_decode($json, true);
    if (!empty($data['articles'])) {
        $api_news_enabled = true;
        foreach ($data['articles'] as $article) {
            if (!empty($article['title']) && !empty($article['urlToImage'])) {
                $api_news[] = [
                    'title' => $article['title'],
                    'author' => $article['source']['name'] ?? 'Global News',
                    'cover_url' => $article['urlToImage'],
                    'url' => $article['url']
                ];
                if (count($api_news) >= 6) break; 
            }
        }
    }
}

// Fetch External API for Magazines (Space/Tech Journals)
$api_mag_enabled = false;
$api_mags = [];
$json_m = @file_get_contents('https://api.spaceflightnewsapi.net/v4/articles?limit=6', false, $ctx);
if ($json_m) {
    $data_m = json_decode($json_m, true);
    if (!empty($data_m['results'])) {
        $api_mag_enabled = true;
        foreach ($data_m['results'] as $article) {
            $api_mags[] = [
                'title' => $article['title'],
                'author' => $article['news_site'] ?? 'Tech Magazine',
                'cover_url' => $article['image_url'],
                'url' => $article['url']
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Digital Library - Discover, Read and Borrow books online.">
    <title>Digital Library | Welcome</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        /* Landing Page Specific Overrides */
        body { background-color: var(--bg-body); }
        .hero {
            position: relative;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
            padding: 120px 5% 100px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 40px;
        }
        .hero::before {
            content: ''; position: absolute; width: 400px; height: 400px;
            background: var(--primary); filter: blur(120px); border-radius: 50%;
            top: -100px; left: -100px; opacity: 0.3; pointer-events: none;
        }
        .hero::after {
            content: ''; position: absolute; width: 300px; height: 300px;
            background: var(--accent); filter: blur(100px); border-radius: 50%;
            bottom: -50px; right: -50px; opacity: 0.2; pointer-events: none;
        }
        .hero-content {
            max-width: 600px;
            position: relative;
            z-index: 2;
        }
        .hero-title {
            color: #fff; font-size: 4rem; font-weight: 800; line-height: 1.1; margin-bottom: 20px;
            font-family: 'Montserrat', sans-serif; letter-spacing: -1px;
        }
        .hero-title span { color: #60a5fa; }
        .hero-subtitle {
            color: rgba(255,255,255,0.8); font-size: 1.15rem; line-height: 1.6; margin-bottom: 40px;
        }
        .hero-visual {
            position: relative; z-index: 2; flex: 1; display: flex; justify-content: center;
        }
        .floating-book {
            font-size: 12rem;
            filter: drop-shadow(0 20px 40px rgba(0,0,0,0.4));
            animation: float 6s ease-in-out infinite;
        }
        
        /* Navbar */
        .landing-nav {
            position: absolute; top: 0; left: 0; width: 100%; padding: 25px 5%;
            display: flex; justify-content: space-between; align-items: center; z-index: 100;
        }
        .logo-text { font-family: 'Montserrat', sans-serif; font-size: 1.8rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px; }
        .logo-text ion-icon { color: var(--primary); }
        
        .nav-links { display: flex; gap: 30px; align-items: center; }
        .nav-link { color: rgba(255,255,255,0.8); font-weight: 600; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px; }
        .nav-link:hover { color: #fff; }
        
        .btn-glass {
            background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2); color: #fff;
            padding: 10px 24px; border-radius: 30px; font-weight: 600; transition: var(--transition);
        }
        .btn-glass:hover { background: rgba(255,255,255,0.2); }
        
        /* Categories */
        .section-container { padding: 60px 5%; max-width: 1400px; margin: 0 auto; }
        .section-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 25px; }
        .section-title { font-size: 1.8rem; font-weight: 800; color: var(--text-main); }
        .view-all { color: var(--primary); font-weight: 600; font-size: 0.95rem; display: flex; align-items: center; gap: 5px; }
        
        /* Gradient Rows container */
        .showcase-row {
            display: flex; gap: 20px; overflow-x: auto; padding: 20px 0 40px;
            scroll-behavior: smooth;
        }
        .showcase-row::-webkit-scrollbar { display: none; }
        
        .cat-card {
            min-width: 300px; background: var(--bg-card); border-radius: var(--radius-lg);
            padding: 20px; box-shadow: var(--shadow-md); border: 1px solid var(--border-color);
            display: flex; gap: 20px; transition: var(--transition);
            position: relative; overflow: hidden;
        }
        .cat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); border-color: rgba(59,130,246,0.3); }
        .cat-cover { width: 90px; height: 130px; border-radius: 8px; object-fit: cover; box-shadow: var(--shadow-sm); z-index: 2; }
        .cat-info { flex: 1; display: flex; flex-direction: column; z-index: 2; }
        .cat-title { font-size: 1.05rem; font-weight: 700; color: var(--text-main); margin-bottom: 5px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .cat-author { font-size: 0.85rem; color: var(--text-muted); margin-bottom: auto; }
        
        .accent-blob { position: absolute; width: 100px; height: 100px; background: var(--primary); opacity: 0.05; border-radius: 50%; filter: blur(20px); top: -20px; right: -20px; z-index: 1; }
        
        /* Footer */
        .premium-footer { background: #0f172a; padding: 80px 5% 40px; color: rgba(255,255,255,0.7); }
        .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 40px; margin-bottom: 60px; max-width: 1400px; margin-left: auto; margin-right: auto; }
        .footer-col h4 { color: #fff; font-size: 1.1rem; margin-bottom: 25px; }
        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 12px; }
        .footer-col ul li a { color: rgba(255,255,255,0.7); transition: var(--transition); }
        .footer-col ul li a:hover { color: #fff; padding-left: 5px; }
        
        .footer-bottom { border-top: 1px solid rgba(255,255,255,0.1); padding-top: 30px; text-align: center; font-size: 0.9rem; }

        /* Mobile Responsiveness for Landing Page */
        @media (max-width: 900px) {
            .hero { flex-direction: column-reverse; text-align: center; padding: 120px 5% 60px; gap: 20px; }
            .hero-title { font-size: 3rem; }
            .hero-content { align-items: center; display: flex; flex-direction: column; }
            .hero-visual { transform: scale(0.7); }
            
            .landing-nav { flex-wrap: wrap; background: rgba(15,23,42,0.9); padding: 15px 5%; position: fixed; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
            .logo-text { font-size: 1.5rem; }
            .nav-links { width: 100%; justify-content: center; margin-top: 10px; gap: 15px; }
            .btn-glass { font-size: 0.8rem; padding: 8px 16px; }
            
            .search-form > div { flex-wrap: wrap; border-radius: 20px; }
            .search-form input { padding: 15px; text-align: center; border-radius: 20px 20px 0 0 !important; }
            .search-form label { width: 100%; justify-content: center; padding: 10px 0 !important; border-left: none !important; border-top: 1px solid #eee; margin: 0 !important; }
            .search-form button { width: 100%; border-radius: 0 0 20px 20px !important; }
            
            .section-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .section-title { font-size: 1.5rem; }
            .showcase-row { padding-bottom: 20px; }
            .cat-card { min-width: 260px; }
            
            .footer-grid { grid-template-columns: 1fr; text-align: center; }
            .logo-text { justify-content: center; }
            .footer-col div { justify-content: center; }
        }
        
        @media (max-width: 480px) {
            .hero-title { font-size: 2.2rem; }
            .floating-book { font-size: 7rem; }
            .hero { padding-top: 140px; }
        }
    </style>
</head>
<body>

    <nav class="landing-nav">
        <a href="index.php" class="logo-text">
            <ion-icon name="library"></ion-icon> LibSys.
        </a>
        <div class="nav-links">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="<?php echo $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>" class="btn-glass">Dashboard</a>
            <?php else: ?>
                <a href="login.php" class="nav-link">Sign In</a>
                <a href="register.php" class="btn-glass">Create Free Account</a>
            <?php endif; ?>
        </div>
    </nav>

    <?php if ($is_search): ?>
    <div class="hero" style="padding: 150px 5% 60px; display:block;">
        <div class="section-container" style="position:relative; z-index:2;">
            <h1 style="color:#fff; font-size:2.5rem; margin-bottom:20px; font-family:'Montserrat';">Search Results</h1>
            <p style="color:rgba(255,255,255,0.8); font-size:1.1rem;">Showing matches for "<strong style="color:var(--primary);"><?php echo htmlspecialchars(trim($_GET['search'])); ?></strong>"</p>
        </div>
    </div>
    
    <div class="section-container">
        <div class="books-grid">
            <?php
            if ($search_results && $search_results->num_rows > 0) {
                while ($b = $search_results->fetch_assoc()) {
                    $c = !empty($b['cover_url']) ? $b['cover_url'] : 'https://via.placeholder.com/150x220?text=Cover';
                    $read_link = isset($_SESSION['user_id']) ? "read.php?id={$b['id']}" : "login.php?action=login_required";
                    echo "<div class='cat-card' style='flex-direction:column; align-items:center; text-align:center; min-width:260px;'>
                            <img src='{$c}' class='cat-cover' style='width:130px; height:190px; margin-bottom:15px;'>
                            <div class='cat-info' style='width:100%; align-items:center;'>
                                <h4 class='cat-title' title='".htmlspecialchars($b['title'])."'>".htmlspecialchars($b['title'])."</h4>
                                <p class='cat-author'>{$b['author']}</p>
                                <a href='{$read_link}' class='btn btn-primary' style='margin-top:15px; width:100%;'><ion-icon name='book-outline'></ion-icon> Details</a>
                            </div>
                          </div>";
                }
            } else {
                echo "<p style='color:var(--text-muted); font-size:1.1rem;'>No reading materials found matching your search.</p>";
            }
            ?>
        </div>
    </div>

    <?php else: ?>
    <!-- HERO -->
    <header class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Expand your mind,<br><span>one page</span> at a time.</h1>
            <p class="hero-subtitle">Access thousands of premium quality books, engaging tutorials, and modern magazines. Your ultimate digital reading companion.</p>
            
            <form method="GET" action="index.php" style="position:relative; width:100%; max-width:600px;">
                <div style="display:flex; background:#fff; padding:8px; border-radius:50px; box-shadow:0 10px 30px rgba(0,0,0,0.2); align-items:center;">
                    <input type="text" name="search" id="searchInput" placeholder="Search by title, author, or category..." style="flex:1; border:none; outline:none; padding:10px 20px; border-radius:30px 0 0 30px; font-size:1rem; font-family:'Poppins';" autocomplete="off">
                    
                    <label style="display:flex; align-items:center; gap:5px; font-size:0.85rem; color:#555; margin-right:15px; border-left:1px solid #eee; padding-left:15px; cursor:pointer;" title="Only show books that are currently available to borrow">
                        <input type="checkbox" name="availability" value="1" style="accent-color:var(--primary);"> Available Only
                    </label>

                    <button type="submit" class="btn btn-primary" style="border-radius:50px; padding:12px 24px;"><ion-icon name="search"></ion-icon> Search</button>
                </div>
                <!-- Autocomplete suggestions box -->
                <div id="suggestionsBox" style="display:none; position:absolute; top:100%; left:20px; right:20px; background:#fff; border-radius:10px; box-shadow:0 10px 25px rgba(0,0,0,0.15); margin-top:10px; z-index:100; text-align:left; overflow:hidden;"></div>
            </form>
            
            <div style="display:flex; gap:20px; align-items:center; margin-top:30px; color:rgba(255,255,255,0.7); font-size:0.9rem;">
                <span style="display:flex; align-items:center; gap:5px;"><ion-icon name="checkmark-circle" style="color:#10b981;"></ion-icon> Free forever</span>
                <span style="display:flex; align-items:center; gap:5px;"><ion-icon name="checkmark-circle" style="color:#10b981;"></ion-icon> 5,000+ Books</span>
                <span style="display:flex; align-items:center; gap:5px;"><ion-icon name="checkmark-circle" style="color:#10b981;"></ion-icon> Mobile ready</span>
            </div>
        </div>
        <div class="hero-visual">
            <div class="floating-book">📚</div>
        </div>
    </header>

    <!-- CONTENT SECTIONS -->
    <main>
        <?php
        function renderShowcase($result, $title, $icon, $is_api = false) {
            echo "<div class='section-container'>
                    <div class='section-header'>
                        <h2 class='section-title'><ion-icon name='{$icon}' style='color:var(--primary); margin-right:10px; vertical-align:middle;'></ion-icon>{$title}</h2>
                        <a href='#' class='view-all'>View entire collection <ion-icon name='arrow-forward'></ion-icon></a>
                    </div>
                    <div class='showcase-row'>";
            
            $items = [];
            if ($is_api && is_array($result)) {
                $items = $result;
            } else if ($result && $result->num_rows > 0) {
                while ($b = $result->fetch_assoc()) {
                    $items[] = [
                        'id' => $b['id'],
                        'title' => $b['title'],
                        'author' => $b['author'],
                        'cover_url' => $b['cover_url'] ?? ''
                    ];
                }
            }

            if (!empty($items)) {
                foreach ($items as $b) {
                    $c = !empty($b['cover_url']) ? $b['cover_url'] : 'https://via.placeholder.com/100x140?text=Book';
                    $read_link = isset($b['url']) ? $b['url'] : (isset($_SESSION['user_id']) ? "read.php?id={$b['id']}" : "login.php?action=login_required");
                    $target = isset($b['url']) ? "target='_blank'" : "";
                    $btn_text = isset($b['url']) ? "Read Online" : "Read Book";
                    
                    echo "<div class='cat-card'>
                            <div class='accent-blob'></div>
                            <img src='{$c}' class='cat-cover'>
                            <div class='cat-info'>
                                <div>
                                    <h4 class='cat-title' title='".htmlspecialchars($b['title'])."'>".htmlspecialchars($b['title'])."</h4>
                                    <p class='cat-author'>{$b['author']}</p>
                                </div>
                                <div style='color:#f59e0b; font-size:0.85rem; margin-top:5px;'>
                                    <ion-icon name='star'></ion-icon><ion-icon name='star'></ion-icon><ion-icon name='star'></ion-icon><ion-icon name='star'></ion-icon><ion-icon name='star-half'></ion-icon>
                                </div>
                                <a href='{$read_link}' {$target} style='color:var(--primary); font-weight:600; font-size:0.9rem; margin-top:auto; display:flex; align-items:center; gap:5px;'>
                                    {$btn_text} <ion-icon name='chevron-forward'></ion-icon>
                                </a>
                            </div>
                          </div>";
                }
            } else {
                echo "<p style='color:var(--text-muted);'>No items currently available in this category.</p>";
            }
            
            echo "</div></div>";
        }
        
        if ($api_news_enabled) { renderShowcase($api_news, 'Live Global News', 'earth', true); }
        if ($api_mag_enabled) { renderShowcase($api_mags, 'Space Journal Magazines', 'newspaper', true); }
        
        $langs = $conn->query("SELECT * FROM books WHERE title LIKE '%python%' OR title LIKE '%java%' OR title LIKE '%c++%' LIMIT 5");
        if (!$langs || $langs->num_rows == 0) $langs = $conn->query("SELECT * FROM books LIMIT 5 OFFSET 5");
        renderShowcase($langs, 'Core Programming', 'code-slash');
        
        $anime = $conn->query("SELECT * FROM books WHERE title LIKE '%art%' OR title LIKE '%game%' LIMIT 5");
        if (!$anime || $anime->num_rows == 0) $anime = $conn->query("SELECT * FROM books LIMIT 5 OFFSET 25");
        renderShowcase($anime, 'Anime & Artbooks', 'color-palette');
        ?>
    </main>
    <?php endif; ?>

    <!-- FOOTER -->
    <footer class="premium-footer">
        <div class="footer-grid">
            <div class="footer-col" style="max-width:300px;">
                <a href="#" class="logo-text" style="font-size:1.5rem; margin-bottom:20px; display:inline-flex;">
                    <ion-icon name="library"></ion-icon> LibSys.
                </a>
                <p style="font-size:0.9rem; line-height:1.6; margin-bottom:20px;">The world's most elegant digital reading platform designed for modern students, professionals, and avid readers everywhere.</p>
                <div style="display:flex; gap:15px; font-size:1.5rem;">
                    <a href="#" style="color:#fff;"><ion-icon name="logo-twitter"></ion-icon></a>
                    <a href="#" style="color:#fff;"><ion-icon name="logo-github"></ion-icon></a>
                    <a href="#" style="color:#fff;"><ion-icon name="logo-discord"></ion-icon></a>
                </div>
            </div>
            
            <div class="footer-col">
                <h4>Library</h4>
                <ul>
                    <li><a href="#">Explore Collection</a></li>
                    <li><a href="#">New Arrivals</a></li>
                    <li><a href="#">Bestselling Authors</a></li>
                    <li><a href="#">Topics & Genres</a></li>
                </ul>
            </div>
            
            <div class="footer-col">
                <h4>Platform</h4>
                <ul>
                    <li><a href="login.php">Member Login</a></li>
                    <li><a href="register.php">Create Account</a></li>
                    <li><a href="#">Reader App</a></li>
                    <li><a href="#">System Help</a></li>
                </ul>
            </div>
            
            <div class="footer-col">
                <h4>Legal</h4>
                <ul>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Cookie Guidelines</a></li>
                    <li><a href="#">Digital Rights</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            &copy; <?php echo date('Y'); ?> Digital Library Systems. All rights reserved. Built with ❤️ for readers.
        </div>
    </footer>

    <!-- AJAX Auto-suggest script -->
    <script>
        const input = document.getElementById('searchInput');
        const box = document.getElementById('suggestionsBox');
        let timeout = null;

        if (input) {
            input.addEventListener('input', function() {
                clearTimeout(timeout);
                const q = this.value.trim();
                if(q.length < 2) {
                    box.style.display = 'none';
                    return;
                }
                
                timeout = setTimeout(() => {
                    fetch(`ajax_search.php?q=${encodeURIComponent(q)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.length > 0) {
                            let html = '';
                            data.forEach(item => {
                                html += `<a href="index.php?search=${encodeURIComponent(item.title)}" style="display:block; padding:12px 20px; border-bottom:1px solid #f0f0f0; text-decoration:none; color:#333; transition:background 0.2s;">
                                    <div style="font-weight:600; font-size:0.95rem;">${item.title}</div>
                                    <div style="font-size:0.8rem; color:#777;">${item.author} &bull; ${item.category}</div>
                                </a>`;
                            });
                            box.innerHTML = html;
                            box.style.display = 'block';
                        } else {
                            box.style.display = 'none';
                        }
                    });
                }, 300);
            });

            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !box.contains(e.target)) {
                    box.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
