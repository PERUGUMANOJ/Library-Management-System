<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$res = $conn->query("SELECT * FROM books WHERE id=$book_id");

if ($res->num_rows == 0) {
    die("Book not found.");
}

$book = $res->fetch_assoc();
$title = htmlspecialchars($book['title']);
$author = htmlspecialchars($book['author']);
$cover_img = !empty($book['cover_url']) ? htmlspecialchars($book['cover_url']) : 'https://via.placeholder.com/300x450?text=No+Cover';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reading: <?php echo $title; ?></title>
    <link href="style.css" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <!-- We keep specific reader styles inline since they are highly specialized to the 3D book feature -->
    <style>
        body { background: #f0f4f8; overflow: hidden; height: 100vh; display: flex; flex-direction: column; transition: background 0.3s, color 0.3s; }
        body.dark-mode { background: #0a0a0a; color: #e0e0e0; }
        
        .reader-nav {
            padding: 15px 40px; display: flex; justify-content: space-between; align-items: center;
            background: rgba(255,255,255,0.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0,0,0,0.05); z-index: 50; flex-shrink: 0; box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        }
        body.dark-mode .reader-nav { background: rgba(20,20,20,0.85); border-bottom-color: rgba(255,255,255,0.05); }
        
        .book-meta { display: flex; align-items: center; gap: 20px; }
        .book-nav-title { font-family: 'Montserrat', sans-serif; font-weight: 800; font-size: 1.2rem; color: var(--text-main); letter-spacing: -0.3px; }
        body.dark-mode .book-nav-title { color: #fff; }
        .book-nav-author { color: var(--text-muted); font-size: 0.9rem; font-weight: 500; }
        
        .reader-actions { display: flex; gap: 15px; }
        
        .read-wrapper {
            display: flex; max-width: 1400px; width: 100%; margin: 0 auto; gap: 40px;
            align-items: stretch; justify-content: center; padding: 40px;
            flex: 1; min-height: 0;
        }

        /* LEFT SIDEBAR FRAME */
        .left-frame {
            width: 300px; border: none; padding: 30px;
            background: var(--bg-card); display: flex; flex-direction: column; align-items: center;
            border-radius: var(--radius-lg); box-shadow: 0 10px 40px rgba(0,0,0,0.06); height: 100%; overflow-y: auto;
        }
        .left-frame::-webkit-scrollbar { display: none; }
        body.dark-mode .left-frame { background: #151515; box-shadow: 0 10px 40px rgba(0,0,0,0.4); }

        .left-frame img { 
            width: 160px; height: 240px; border-radius: var(--radius-sm); 
            box-shadow: 0 15px 35px rgba(0,0,0,0.15), -5px 0 10px rgba(0,0,0,0.05); 
            margin-bottom: 30px; object-fit: cover; transition: transform 0.5s ease;
        }
        .left-frame img:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.2), -5px 0 10px rgba(0,0,0,0.05); }

        .rating-box {
            border: none; padding: 15px; width: 100%;
            text-align: center; border-radius: var(--radius-md); display: flex; flex-direction: column;
            align-items: center; justify-content: center; gap: 8px; margin-bottom: 25px; 
            background: linear-gradient(135deg, rgba(59,130,246,0.05) 0%, rgba(59,130,246,0.1) 100%);
        }
        body.dark-mode .rating-box { background: linear-gradient(135deg, rgba(59,130,246,0.1) 0%, rgba(59,130,246,0.2) 100%); }
        .stars { color: #f59e0b; font-size: 1.3rem; filter: drop-shadow(0 2px 4px rgba(245,158,11,0.2)); }

        .action-list { width: 100%; display: flex; flex-direction: column; gap: 10px; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 25px; }
        body.dark-mode .action-list { border-top-color: rgba(255,255,255,0.05); }
        
        .action-btn { 
            display: flex; align-items: center; gap: 15px; padding: 14px 18px; 
            border-radius: var(--radius-md); color: var(--text-main); font-weight: 600; 
            font-size: 0.95rem; cursor: pointer; transition: all 0.2s ease; border: 1px solid transparent; 
            background: rgba(0,0,0,0.02);
        }
        body.dark-mode .action-btn { color: #ddd; background: rgba(255,255,255,0.03); }
        .action-btn ion-icon { font-size: 1.4rem; color: var(--primary); transition: transform 0.3s; }
        .action-btn:hover { 
            background: rgba(59,130,246,0.08); color: var(--primary); 
            border-color: rgba(59,130,246,0.15); transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59,130,246,0.1);
        }
        .action-btn:hover ion-icon { transform: scale(1.1); }

        /* THE BOOK SPREAD */
        .book-spread {
            flex: 1; position: relative; height: calc(100% - 60px); perspective: 3000px;
        }

        .spread-pair {
            display: none; width: 100%; height: 100%; position: absolute;
            top: 0; left: 0; gap: 0; box-shadow: 0 40px 80px rgba(0,0,0,0.15);
            border-radius: 12px; transform-origin: center; transform-style: preserve-3d;
        }
        body.dark-mode .spread-pair { box-shadow: 0 40px 80px rgba(0,0,0,0.6); }
        .spread-pair.active { display: flex; }

        /* Real Page Turn Animations */
        .turn-next-out { animation: turnNextOut 0.7s cubic-bezier(0.645, 0.045, 0.355, 1) forwards; display: flex; z-index: 10; }
        @keyframes turnNextOut { 0% { transform: rotateY(0deg) scale(1); opacity: 1; filter: brightness(1) drop-shadow(0 0 0 rgba(0,0,0,0)); } 100% { transform: rotateY(-90deg) scale(0.92); opacity: 0; filter: brightness(0.4) drop-shadow(50px 0 50px rgba(0,0,0,0.3)); } }
        .turn-next-in { animation: turnNextIn 0.7s cubic-bezier(0.645, 0.045, 0.355, 1) forwards; display: flex; z-index: 5; }
        @keyframes turnNextIn { 0% { transform: rotateY(90deg) scale(0.92); opacity: 0; filter: brightness(0.4); } 100% { transform: rotateY(0deg) scale(1); opacity: 1; filter: brightness(1); } }
        .turn-prev-out { animation: turnPrevOut 0.7s cubic-bezier(0.645, 0.045, 0.355, 1) forwards; display: flex; z-index: 10; }
        @keyframes turnPrevOut { 0% { transform: rotateY(0deg) scale(1); opacity: 1; filter: brightness(1); } 100% { transform: rotateY(90deg) scale(0.92); opacity: 0; filter: brightness(0.4) drop-shadow(-50px 0 50px rgba(0,0,0,0.3)); } }
        .turn-prev-in { animation: turnPrevIn 0.7s cubic-bezier(0.645, 0.045, 0.355, 1) forwards; display: flex; z-index: 5; }
        @keyframes turnPrevIn { 0% { transform: rotateY(-90deg) scale(0.92); opacity: 0; filter: brightness(0.4); } 100% { transform: rotateY(0deg) scale(1); opacity: 1; filter: brightness(1); } }

        /* Page Shape logic */
        .page-left, .page-right {
            flex: 1; height: 100%; overflow-y: auto; background: #fff;
            position: relative; padding: 70px 60px;
        }
        .page-left::-webkit-scrollbar, .page-right::-webkit-scrollbar { width: 6px; }
        .page-left::-webkit-scrollbar-thumb, .page-right::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 10px; }
        
        body.dark-mode .page-left, body.dark-mode .page-right { background: #1a1a1a; }
        body.dark-mode .page-left::-webkit-scrollbar-thumb, body.dark-mode .page-right::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }

        .page-left {
            border-top-left-radius: 12px; border-bottom-left-radius: 12px;
            background: linear-gradient(to right, #ffffff 0%, #ffffff 85%, #e8edf2 100%);
            box-shadow: inset -40px 0 50px -20px rgba(0,0,0,0.08), inset -5px 0 10px rgba(0,0,0,0.03);
            border-right: 1px solid rgba(0,0,0,0.05);
        }
        body.dark-mode .page-left { 
            background: linear-gradient(to right, #1a1a1a 0%, #1a1a1a 85%, #0f0f0f 100%);
            box-shadow: inset -40px 0 50px -20px rgba(0,0,0,0.5), inset -5px 0 10px rgba(0,0,0,0.4); border-right: 1px solid rgba(0,0,0,0.3);
        }

        .page-right {
            border-top-right-radius: 12px; border-bottom-right-radius: 12px;
            background: linear-gradient(to left, #ffffff 0%, #ffffff 85%, #e8edf2 100%);
            box-shadow: inset 40px 0 50px -20px rgba(0,0,0,0.08), inset 5px 0 10px rgba(0,0,0,0.03);
            border-left: 1px solid rgba(255,255,255,0.8);
        }
        body.dark-mode .page-right { 
            background: linear-gradient(to left, #1a1a1a 0%, #1a1a1a 85%, #0f0f0f 100%);
            box-shadow: inset 40px 0 50px -20px rgba(0,0,0,0.5), inset 5px 0 10px rgba(0,0,0,0.4); border-left: 1px solid rgba(255,255,255,0.05);
        }

        /* Typography */
        .page-title { font-size: 3.5rem; font-weight: 800; font-family: 'Montserrat', sans-serif; color: #111; line-height: 1.1; margin-bottom: 25px; letter-spacing: -1.5px; }
        body.dark-mode .page-title { color: #f8f8f8; }

        .page-subtitle { font-size: 1.2rem; color: var(--primary); font-weight: 600; margin-bottom: 50px; text-transform: uppercase; letter-spacing: 2px; font-family: 'Poppins', sans-serif; }
        body.dark-mode .page-subtitle { color: var(--primary); }

        .page-text { font-size: 1.1rem; color: #333; line-height: 2.1; margin-bottom: 25px; text-align: justify; font-family: Georgia, 'Times New Roman', serif; }
        body.dark-mode .page-text { color: #ccc; }

        .page-subheading { font-family: 'Montserrat', sans-serif; font-weight: 800; font-size: 1.5rem; margin: 45px 0 20px; color: #222; }
        body.dark-mode .page-subheading { color: #eee; }

        .page-number { position: absolute; bottom: 30px; left: 0; width: 100%; text-align: center; color: #aaa; font-size: 0.9rem; font-weight: 600; font-family: 'Montserrat', sans-serif; }

        /* Spread Nav Controls */
        .spread-controls { display: flex; justify-content: center; gap: 40px; align-items: center; margin-top: 20px; z-index: 100; position: relative; }
        .spread-controls button {
            background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%); border: none; padding: 14px 35px; border-radius: var(--radius-full); cursor: pointer; font-weight: 600; color: #fff; font-family: 'Poppins', sans-serif; transition: all 0.3s; display: flex; align-items: center; gap: 12px; box-shadow: 0 10px 25px rgba(59,130,246,0.3); font-size: 1rem;
        }
        .spread-controls button ion-icon { font-size: 1.3rem; color: #fff; }
        .spread-controls button:hover:not(:disabled) { box-shadow: 0 15px 35px rgba(59,130,246,0.4); transform: translateY(-3px) scale(1.02); }
        .spread-controls button:disabled { opacity: 0.5; cursor: not-allowed; box-shadow: none; filter: grayscale(1); transform: none; }

        /* REVIEW MODAL */
        #reviewModal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(8px); align-items: center; justify-content: center; }
        .modal-box { background: var(--bg-card); width: 90%; max-width: 500px; padding: 40px; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        @keyframes popIn { from { opacity: 0; transform: scale(0.9) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        body.dark-mode .modal-box { background: #1e1e1e; }
        .modal-box h3 { font-family: 'Montserrat', sans-serif; color: var(--text-main); margin-bottom: 10px; font-size: 1.6rem; }
        body.dark-mode .modal-box h3 { color: #fff; }
        #review-input { width: 100%; padding: 15px; border: 2px solid var(--border-color); border-radius: var(--radius-md); font-size: 1rem; font-family: 'Poppins', sans-serif; resize: vertical; margin-bottom: 25px; outline: none; transition: var(--transition); background: var(--bg-body); color: var(--text-main); }
        body.dark-mode #review-input { background: #121212; border-color: #444; color: #fff; }

        /* Mobile Responsiveness for Reader */
        @media (max-width: 900px) {
            .read-wrapper { flex-direction: column; padding: 15px; overflow-y: auto; }
            .left-frame { width: 100%; height: auto; max-height: none; flex-direction: row; flex-wrap: wrap; justify-content: space-between; margin-bottom: 20px; }
            .left-frame img { width: 100px; height: 150px; margin-right: 20px; margin-bottom: 0; }
            .rating-box { width: 150px; padding: 5px; margin: 0; }
            .action-list { border-top: none; padding-top: 0; flex-direction: row; flex-wrap: wrap; justify-content: center; gap: 10px; width: 100%; margin-top: 20px; }
            .action-btn { flex: 1; min-width: 140px; justify-content: center; }
            
            .book-spread { height: 60vh; min-height: 400px; }
            .page-left { border-radius: 10px; border-right: none; }
            .page-right { display: none; } /* Hide right page entirely on small mobile to emulate single-page scroll */
            .spread-pair.active { justify-content: center; }
            
            .reader-nav { flex-direction: column; gap: 15px; text-align: center; padding: 15px 20px; }
            .book-meta { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body class="<?php echo isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark' ? 'dark-mode' : ''; ?>">

    <div class="reader-nav">
        <div class="book-meta">
            <div class="btn-icon" onclick="window.location.href='user_dashboard.php'" style="background:var(--bg-body); border:1px solid var(--border-color); color:var(--text-main); cursor:pointer;"><ion-icon name="arrow-back"></ion-icon></div>
            <div>
                <div class="book-nav-title"><?php echo $title; ?></div>
                <div class="book-nav-author">By <?php echo $author; ?></div>
            </div>
        </div>
        <div class="reader-actions">
            <button class="btn btn-icon" onclick="toggleDarkMode()" title="Toggle Dark/Light Mode" style="background:var(--bg-body); border:1px solid var(--border-color); color:var(--text-main);"><ion-icon name="moon-outline"></ion-icon></button>
            <button class="btn btn-icon" title="Reader Settings" style="background:var(--bg-body); border:1px solid var(--border-color); color:var(--text-main);"><ion-icon name="options-outline"></ion-icon></button>
        </div>
    </div>

    <div class="read-wrapper">
        
        <!-- LEFT SIDEBAR -->
        <div class="left-frame">
            <img src="<?php echo $cover_img; ?>" alt="Cover Art">
            
            <button class="btn btn-primary" style="width:100%; margin-bottom:20px; justify-content:space-between; padding:15px 20px;">
                <span>Reading Status</span> <ion-icon name="checkmark-circle"></ion-icon>
            </button>
            
            <div class="rating-box">
                <span style="font-size:0.8rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px;">Average Rating</span>
                <div style="display:flex; align-items:center; gap:10px;">
                    <span class="stars"><ion-icon name="star"></ion-icon><ion-icon name="star"></ion-icon><ion-icon name="star"></ion-icon><ion-icon name="star"></ion-icon><ion-icon name="star-half"></ion-icon></span>
                    <span style="font-weight:700; font-size:1.1rem; color:var(--text-main);">4.5</span>
                </div>
            </div>

            <div class="action-list">
                <div class="action-btn" onclick="writeReview()">
                    <ion-icon name="create-outline"></ion-icon> Write a Review
                </div>
                <div id="inline-review-display" style="font-size: 0.85rem; font-style: italic; color: var(--text-muted); padding: 0 10px 10px 42px; display:none;">
                    <!-- Appears when review is saved -->
                </div>
                
                <div class="action-btn" onclick="bookmarkPage()">
                    <ion-icon name="bookmark-outline"></ion-icon> Save Bookmark
                </div>
                
                <div class="action-btn" onclick="speakSelectedText()">
                    <ion-icon name="volume-high-outline"></ion-icon> Text to Speech
                </div>
            </div>
        </div>

        <!-- RIGHT AREA: BOOK SPREADS -->
        <div style="flex: 1; display: flex; flex-direction: column;">
            <div class="book-spread">
                <?php
                // Mock generator
                $totalSpreads = 15; 

                function generateContent($title, $page_num) {
                    $lorem = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";
                    
                    $paragraphs = [];
                    if ($page_num === 1) {
                        $paragraphs[] = "Welcome to the physical emulation of this critical text. The digital interface provides high-fidelity access directly mapped to traditional print formatting schemas.";
                        $paragraphs[] = "Exploring the depths of <strong>" . htmlspecialchars($title) . "</strong> introduces us to complex theoretical models and highly abstract systems orchestration. In the modern era, reading takes on a fluid, dynamic dimension, blending the tactile nostalgia of print with the boundless potential of the digital realm.";
                    } else if ($page_num === 2) {
                        $paragraphs[] = "At a fundamental level, the systems detailed herein dictate how logic processes traverse networked environments. Scale is achieved not merely by duplicating hardware, but intelligently routing information streams.";
                        $paragraphs[] = "Memory safety forms the bedrock of any secure mechanism. As observed in historical exploit patterns, unchecked buffer boundaries represent catastrophic failure points. Mitigation strategies must be deployed holistically.";
                    } else {
                        $intro = [
                            "As we dive deeper into chapter " . ceil($page_num / 2) . ", the foundational elements become clearer.",
                            "The subsequent chapters dive rigorously into distributed consensus mechanisms.",
                            "A highly optimized system does not emerge by accident. It is painstakingly sculpted.",
                            "The decoupling of services initiates a paradigm shift.",
                            "In an interconnected domain, zero-trust architectures define survival.",
                            "The diligent pursuit of " . htmlspecialchars($title) . " yields profound technological ramifications."
                        ];
                        $paragraphs[] = $intro[$page_num % count($intro)];
                        $paragraphs[] = $lorem;
                        $paragraphs[] = "Furthermore, applying these concepts in real-world scenarios demands a rigorous adherence to structural integrity protocols mapped out in previous deployments. Observation and telemetry remain critical.";
                    }
                    return implode("</p><p class='page-text'>", $paragraphs);
                }

                for ($i = 1; $i <= $totalSpreads; $i++) {
                    $activeClass = ($i === 1) ? 'active' : '';
                    $leftPageNum = ($i * 2) - 1;
                    $rightPageNum = $i * 2;

                    echo "<div class='spread-pair {$activeClass}' id='spread-{$i}'>
                            <div class='page-left'>";
                    
                    if ($i === 1) {
                        echo "<div class='page-title'>" . htmlspecialchars($title) . "</div>
                              <div class='page-subtitle'>Authored by " . htmlspecialchars($author) . "</div>";
                    } else {
                        echo "<div class='page-subheading'>Chapter " . ceil($leftPageNum / 2) . "</div>";
                    }

                    echo "      <div class='page-text'>" . generateContent($title, $leftPageNum) . "</div>
                                <div class='page-number'>- {$leftPageNum} -</div>
                            </div>
                            <div class='page-right'>
                                <div class='page-subheading'>Section " . $rightPageNum . ".0</div>
                                <div class='page-text'>" . generateContent($title, $rightPageNum) . "</div>
                                <div class='page-number'>- {$rightPageNum} -</div>
                            </div>
                          </div>";
                }
                ?>
            </div>

            <!-- Page Navigation Controls -->
            <div class="spread-controls">
                <button id="btn-prev" onclick="turnSpread(-1)" disabled><ion-icon name="arrow-back"></ion-icon> Previous Spread</button>
                <div style="font-weight: 700; color: var(--text-muted); font-size: 0.95rem; font-family:'Montserrat';">Spread <span id="current-spread" style="color:var(--text-main);">1</span> of <?php echo $totalSpreads; ?></div>
                <button id="btn-next" onclick="turnSpread(1)">Next Spread <ion-icon name="arrow-forward"></ion-icon></button>
            </div>
        </div>
    </div>


    <!-- Review Modal Overlay -->
    <div id="reviewModal">
        <div class="modal-box">
            <h3>Write a Review</h3>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 25px;">Share your thoughts about <strong><?php echo $title; ?></strong> with the community.</p>
            <textarea id="review-input" rows="5" placeholder="I genuinely enjoyed how this book..."></textarea>
            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button onclick="closeReviewModal()" class="btn" style="background:var(--bg-body); border:1px solid var(--border-color); color:var(--text-main); font-weight:600;">Cancel</button>
                <button onclick="submitReviewModal()" class="btn btn-primary" style="font-weight:600;">Save Review</button>
            </div>
        </div>
    </div>

    <script>
        const bookId = <?php echo $book_id; ?>;
        let currentSpread = 1;
        const totalSpreads = <?php echo $totalSpreads; ?>;

        // Turn Spread Logic with Real 3D Book Animation
        let isAnimating = false;
        function turnSpread(dir) {
            if (isAnimating) return;
            let next = currentSpread + dir;
            if (next < 1 || next > totalSpreads) return;
            isAnimating = true;

            let prevEl = document.getElementById('spread-' + currentSpread);
            let nextEl = document.getElementById('spread-' + next);

            // Apply Keyframe Classes
            prevEl.className = 'spread-pair ' + (dir === 1 ? 'turn-next-out' : 'turn-prev-out');
            nextEl.className = 'spread-pair ' + (dir === 1 ? 'turn-next-in' : 'turn-prev-in');

            setTimeout(() => {
                prevEl.className = 'spread-pair'; 
                nextEl.className = 'spread-pair active'; 
                currentSpread = next;
                
                document.getElementById('btn-prev').disabled = (currentSpread === 1);
                document.getElementById('btn-next').disabled = (currentSpread === totalSpreads);
                document.getElementById('current-spread').innerText = currentSpread;
                
                isAnimating = false;
            }, 550);
        }

        // Bookmark function
        function bookmarkPage() {
            localStorage.setItem('bookmark_book_' + bookId, currentSpread);
            // Replace generic alert with UI feedback if possible.
            alert('Bookmark securely saved for Spread ' + currentSpread + '!');
        }

        // Speech function
        function speakSelectedText() {
            let text = window.getSelection().toString();
            if (!text) {
                alert("Please highlight or select text on the page first!");
                return;
            }
            let utterance = new SpeechSynthesisUtterance(text);
            speechSynthesis.speak(utterance);
        }

        // Review function
        function writeReview() {
            let existing = localStorage.getItem('review_book_' + bookId);
            if(existing) document.getElementById('review-input').value = existing;
            
            document.getElementById('reviewModal').style.display = 'flex';
            setTimeout(() => { document.getElementById('review-input').focus(); }, 100);
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';
        }

        function submitReviewModal() {
            let val = document.getElementById('review-input').value;
            if (val.trim() !== "") {
                let sanitized = val.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                localStorage.setItem('review_book_' + bookId, sanitized.trim());
                
                let disp = document.getElementById('inline-review-display');
                disp.innerHTML = `"${sanitized.trim()}"`;
                disp.style.display = 'block';
                closeReviewModal();
            } else {
                document.getElementById('review-input').style.borderColor = "var(--danger)";
            }
        }

        // Init on Load
        window.onload = function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.body.classList.add('dark-mode');
            }

            // Restore Bookmark
            let savedSpread = localStorage.getItem('bookmark_book_' + bookId);
            if (savedSpread) {
                let s = parseInt(savedSpread);
                if (s > 1 && s <= totalSpreads) {
                    turnSpread(s - currentSpread);
                }
            }

            // Restore Review
            let savedReview = localStorage.getItem('review_book_' + bookId);
            if (savedReview) {
                let disp = document.getElementById('inline-review-display');
                disp.innerHTML = `"${savedReview}"`;
                disp.style.display = 'block';
            }
        };

        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            if (document.body.classList.contains('dark-mode')) {
                localStorage.setItem('theme', 'dark');
                document.cookie = "theme=dark; path=/; max-age=31536000;";
            } else {
                localStorage.setItem('theme', 'light');
                document.cookie = "theme=light; path=/; max-age=31536000;";
            }
        }
    </script>
</body>
</html>
