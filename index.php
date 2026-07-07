<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config/database.php';

$featured_tutors = [];
try {
    $stmt = $pdo->query("
        SELECT u.id, u.name, tp.hourly_rate,
               STRING_AGG(DISTINCT tc.unit_code, ', ') as units,
               AVG(r.rating) as avg_rating,
               COUNT(DISTINCT r.rating_id) as review_count
        FROM users u
        JOIN tutor_credentials tc ON u.id = tc.tutor_id AND tc.submission_status = 'approved'
        LEFT JOIN tutor_profiles tp ON u.id = tp.user_id
        LEFT JOIN bookings b ON b.tutor_id = u.id
        LEFT JOIN ratings r ON r.tutor_id = u.id
        WHERE LOWER(u.role) = 'tutor'
        GROUP BY u.id, u.name, tp.hourly_rate
        ORDER BY avg_rating DESC NULLS LAST
        LIMIT 4
    ");
    $featured_tutors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Featured tutors fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PeerTutor - Peer Tutors</title>
<style>
    :root {
        --navy: #0f2038;
        --slate: #475569;
        --light: #f8fafc;
        --border: #e2e8f0;
        --success: #10b981;
        --amber: #d97706;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: var(--light); color: var(--navy); }

    nav { display: flex; justify-content: space-between; align-items: center; padding: 20px 40px; background: white; border-bottom: 1px solid var(--border); }
    .brand { font-size: 20px; font-weight: bold; color: var(--navy); text-decoration: none; }
    .nav-links a { color: var(--slate); text-decoration: none; font-size: 14px; font-weight: 600; margin-right: 20px; }
    .nav-actions a { text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; font-size: 14px; }
    .btn-ghost { color: var(--navy); border: 1px solid var(--border); margin-right: 10px; }
    .btn-primary { background: var(--navy); color: white; }

    .hero { text-align: center; padding: 80px 20px 60px; background: linear-gradient(160deg, #eef2f7 0%, var(--light) 60%); }
    .hero-eyebrow { display: inline-block; font-size: 12px; font-weight: 700; color: var(--navy); background: #e0f2fe; padding: 6px 14px; border-radius: 20px; margin-bottom: 20px; }
    .hero h1 { font-size: 42px; margin-bottom: 15px; }
    .hero h1 span { color: var(--amber); }
    .hero p { color: var(--slate); font-size: 16px; max-width: 480px; margin: 0 auto 30px; }
    .hero-actions a { text-decoration: none; padding: 14px 28px; border-radius: 6px; font-weight: 600; font-size: 15px; display: inline-block; margin: 0 8px; }
    .hero-primary { background: var(--navy); color: white; }
    .hero-ghost { background: white; color: var(--navy); border: 1px solid var(--border); }

    .tutor-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; }
    .tutor-card { background: white; border: 1px solid var(--border); border-radius: 8px; padding: 22px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .tutor-avatar { width: 44px; height: 44px; border-radius: 50%; background: #e0f2fe; color: var(--navy); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 15px; margin-bottom: 12px; }
    .tutor-card h3 { font-size: 15px; margin-bottom: 4px; }
    .tutor-card .unit { font-size: 12px; color: var(--slate); margin-bottom: 8px; }
   .tutor-card .rating { font-size: 13px; color: var(--amber); margin-bottom: 8px; }
   .tutor-card .rate { font-size: 14px; font-weight: 700; color: var(--navy); } 

    .section { padding: 60px 40px; max-width: 1100px; margin: 0 auto; }
    .section-title { text-align: center; font-size: 26px; margin-bottom: 8px; }
    .section-sub { text-align: center; color: var(--slate); font-size: 14px; margin-bottom: 35px; }

    .steps-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; }
    .step-card { background: white; border: 1px solid var(--border); border-radius: 8px; padding: 20px 15px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .step-num { width: 36px; height: 36px; background: var(--navy); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin: 0 auto 12px; }
    .step-card h3 { font-size: 16px; margin-bottom: 6px; }
    .step-card p { color: var(--slate); font-size: 13px; }

    footer { text-align: center; padding: 25px; color: var(--slate); font-size: 13px; border-top: 1px solid var(--border); background: white; }
</style>
</head>
<body>

<nav>
    <a href="index.php" class="brand">PeerTutors</a>

    <div class="nav-actions">
        <a href="auth/login.php" class="btn-ghost">Log in</a>
        <a href="auth/register.php" class="btn-primary">Sign up</a>
    </div>
</nav>

<div class="hero">
    <div class="hero-eyebrow">Strathmore University Peer Tutoring Platform</div>
    <h1>Find peer tutors at <span>Strathmore!</span></h1>
    <p>Connect with verified student tutors for 1-on-1 sessions across all your courses.</p>
    <div class="hero-actions">
        <a href="auth/register.php" class="hero-primary">Find a tutor</a>
        <a href="auth/register.php" class="hero-ghost">Become a tutor</a>
    </div>
</div>

<div class="section" id="how-it-works">
    <p class="section-title">How it works</p>
    <p class="section-sub">Get started in five easy steps</p>
    <div class="steps-grid">
        <div class="step-card"><div class="step-num">1</div><h3>Search</h3><p>Browse tutors by unit code</p></div>
        <div class="step-card"><div class="step-num">2</div><h3>Book</h3><p>Pick a slot within their hours</p></div>
        <div class="step-card"><div class="step-num">3</div><h3>Get Approved</h3><p>Tutor accepts your request</p></div>
        <div class="step-card"><div class="step-num">4</div><h3>Pay</h3><p>Settle the fee via M-Pesa</p></div>
        <div class="step-card"><div class="step-num">5</div><h3>Learn & Review</h3><p>Attend, then leave a review</p></div>
    </div>
</div>

<div class="section">
    <p class="section-title">Featured Tutors</p>
    <p class="section-sub">Top-rated peer instructors ready to help</p>
    <?php if (empty($featured_tutors)): ?>
        <p style="text-align:center; color: var(--slate); font-size: 14px;">No verified tutors available yet.</p>
    <?php else: ?>
        <div class="tutor-grid">
            <?php foreach ($featured_tutors as $t): ?>
                <div class="tutor-card">
                    <div class="tutor-avatar"><?php 
                        $initials = '';
                        foreach (explode(' ', $t['name']) as $part) { $initials .= strtoupper($part[0] ?? ''); }
                        echo substr($initials, 0, 2);
                    ?></div>
                    <h3><?php echo htmlspecialchars($t['name']); ?></h3>
                    <div class="unit"><?php echo htmlspecialchars($t['units'] ?: 'No units listed'); ?></div>
                    <div class="rating">
                        <?php if ($t['avg_rating']): ?>
                            ★ <?php echo number_format($t['avg_rating'], 1); ?> (<?php echo $t['review_count']; ?>)
                        <?php else: ?>
                            No reviews yet
                        <?php endif; ?>
                    </div>
                    <div class="rate"><?php echo !empty($t['hourly_rate']) ? "KES " . number_format($t['hourly_rate'], 2) . " / hr" : "Rate not set"; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<footer>© 2026 PeerTutors · Strathmore University Peer Tutoring Platform</footer>

</body>
</html>