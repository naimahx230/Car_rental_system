<?php
session_name('CUSTOMER_SESSION');
session_start();

$conn = mysqli_connect('127.0.0.1', 'urbanwheels', 'StrongPass123!', 'car_rental_system');
if (!$conn) die("Connection failed: " . mysqli_connect_error());

$error = '';

// Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['login_email']);
    $password = $_POST['login_password'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password']) && $user['status'] == 'active') {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = 'customer';
            $_SESSION['last_activity'] = time();
            header("Location: " . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'vehicles.php'));
            exit();
        } else { $error = "Invalid credentials"; }
    } else { $error = "Account not found"; }
}

// Register
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    if ($password !== $confirm) $error = "Passwords do not match";
    elseif (strlen($password) < 6) $error = "Password must be at least 6 characters";
    else {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
        if (mysqli_num_rows($check) > 0) $error = "Email already registered";
        else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            if (mysqli_query($conn, "INSERT INTO users (name,email,phone,password,role,status) VALUES ('$name','$email','$phone','$hashed','customer','active')")) {
                $uid = mysqli_insert_id($conn);
                $_SESSION['user_id'] = $uid;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'customer';
                $_SESSION['last_activity'] = time();
                header("Location: vehicles.php");
                exit();
            } else $error = "Registration failed.";
        }
    }
}

// Featured cars
$cars = [];
$res = mysqli_query($conn, "SELECT id, brand, model, year, transmission, seats, daily_rate, image FROM vehicles WHERE status='available' ORDER BY featured DESC, RAND() LIMIT 8");
while ($c = mysqli_fetch_assoc($res)) $cars[] = $c;

// Stats
$total_vehicles = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM vehicles WHERE status='available'"))['c'] ?? 0;
$total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='customer'"))['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Urban Wheels | Premium Car Rental in Kenya</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  :root{
    --gold:#FFD700; --gold2:#FFA500;
    --dark:#0f0c29; --dark2:#1a1a2e; --dark3:#302b63;
    --text:#e8e8f0; --muted:#a0a0b8;
  }
  body{font-family:'Inter',sans-serif;background:var(--dark);color:var(--text);overflow-x:hidden}
  a{text-decoration:none;color:inherit}

  /* ---------- NAV ---------- */
  .nav{position:fixed;top:0;left:0;right:0;z-index:1000;background:rgba(15,12,41,0.85);backdrop-filter:blur(14px);border-bottom:1px solid rgba(255,215,0,0.12);padding:16px 5%}
  .nav-wrap{max-width:1400px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;gap:20px}
  .logo{display:flex;flex-direction:column;line-height:1}
  .logo b{font-size:22px;font-weight:900;background:linear-gradient(135deg,var(--gold),var(--gold2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;letter-spacing:1px}
  .logo span{font-size:9px;color:var(--gold);letter-spacing:4px;margin-top:3px}
  .nav-links{display:flex;gap:30px;align-items:center;flex-wrap:wrap}
  .nav-links a{font-size:14px;font-weight:500;color:var(--text);opacity:0.85;transition:opacity 0.2s,color 0.2s}
  .nav-links a:hover{opacity:1;color:var(--gold)}
  .btn-auth{background:linear-gradient(135deg,var(--gold),var(--gold2));color:#1a1a2e!important;padding:10px 24px;border-radius:40px;font-weight:700;font-size:13px;letter-spacing:0.5px;box-shadow:0 4px 18px rgba(255,215,0,0.25);transition:transform 0.2s}
  .btn-auth:hover{transform:translateY(-2px);color:#1a1a2e!important}

  /* ---------- HERO ---------- */
  .hero{position:relative;min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:140px 5% 80px;background:
    radial-gradient(circle at 20% 20%, rgba(255,215,0,0.08) 0%, transparent 50%),
    radial-gradient(circle at 80% 80%, rgba(118,75,162,0.15) 0%, transparent 50%),
    linear-gradient(135deg,#0f0c29 0%,#302b63 50%,#24243e 100%);
    overflow:hidden}
  .hero::before{content:'';position:absolute;inset:0;background-image:radial-gradient(rgba(255,215,0,0.08) 1px,transparent 1px);background-size:32px 32px;pointer-events:none;opacity:0.6}
  .hero-inner{position:relative;z-index:2;max-width:900px}
  .hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(255,215,0,0.1);border:1px solid rgba(255,215,0,0.3);padding:8px 20px;border-radius:50px;color:var(--gold);font-size:13px;font-weight:600;margin-bottom:28px}
  .hero h1{font-size:clamp(38px,6vw,72px);font-weight:900;line-height:1.05;margin-bottom:22px;letter-spacing:-1.5px}
  .hero h1 .accent{background:linear-gradient(135deg,var(--gold) 0%,var(--gold2) 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
  .hero p{font-size:clamp(15px,1.6vw,19px);color:var(--muted);line-height:1.7;max-width:640px;margin:0 auto 40px}
  .hero-cta{display:flex;gap:16px;justify-content:center;flex-wrap:wrap}
  .btn-primary{background:linear-gradient(135deg,var(--gold),var(--gold2));color:#1a1a2e;padding:16px 38px;border-radius:50px;font-weight:700;font-size:15px;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:10px;box-shadow:0 10px 30px rgba(255,215,0,0.3);transition:all 0.3s}
  .btn-primary:hover{transform:translateY(-3px);box-shadow:0 15px 40px rgba(255,215,0,0.4)}
  .btn-ghost{background:transparent;color:var(--text);padding:16px 34px;border-radius:50px;font-weight:600;font-size:15px;border:1px solid rgba(255,255,255,0.25);cursor:pointer;display:inline-flex;align-items:center;gap:10px;transition:all 0.3s}
  .btn-ghost:hover{border-color:var(--gold);color:var(--gold)}
  .scroll-hint{margin-top:60px;color:var(--muted);font-size:12px;letter-spacing:2px}
  .scroll-hint i{display:block;margin-top:10px;animation:bounce 2s infinite;font-size:18px;color:var(--gold)}
  @keyframes bounce{0%,100%{transform:translateY(0)}50%{transform:translateY(8px)}}

  /* ---------- TRUST BAR ---------- */
  .trust{background:#15102e;border-top:1px solid rgba(255,215,0,0.1);border-bottom:1px solid rgba(255,215,0,0.1);padding:40px 5%}
  .trust-wrap{max-width:1400px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:30px;text-align:center}
  .trust-item .num{font-size:34px;font-weight:800;color:var(--gold);letter-spacing:-1px}
  .trust-item .lbl{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:1.5px;margin-top:6px}

  /* ---------- SECTIONS ---------- */
  section.block{padding:90px 5%}
  .section-head{text-align:center;max-width:640px;margin:0 auto 55px}
  .section-head .eyebrow{color:var(--gold);font-size:12px;font-weight:700;letter-spacing:3px;text-transform:uppercase;margin-bottom:14px;display:block}
  .section-head h2{font-size:clamp(28px,4vw,44px);font-weight:800;letter-spacing:-1px;margin-bottom:14px;line-height:1.15}
  .section-head p{color:var(--muted);font-size:15px;line-height:1.6}

  /* ---------- FLEET ---------- */
  .fleet-grid{max-width:1400px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:26px}
  .car{background:#1a1535;border:1px solid rgba(255,215,0,0.08);border-radius:20px;overflow:hidden;transition:all 0.35s cubic-bezier(0.4,0,0.2,1);position:relative;display:flex;flex-direction:column}
  .car:hover{transform:translateY(-8px);border-color:rgba(255,215,0,0.4);box-shadow:0 25px 50px rgba(255,215,0,0.1)}
  .car-img{height:200px;background:linear-gradient(135deg,#221b47,#3a2e6b);overflow:hidden;position:relative}
  .car-img img{width:100%;height:100%;object-fit:cover;transition:transform 0.5s}
  .car:hover .car-img img{transform:scale(1.08)}
  .car-img .no-img{display:flex;align-items:center;justify-content:center;height:100%;font-size:64px;color:rgba(255,215,0,0.3)}
  .badge{position:absolute;top:14px;left:14px;background:linear-gradient(135deg,var(--gold),var(--gold2));color:#1a1a2e;font-size:10px;font-weight:800;letter-spacing:1px;text-transform:uppercase;padding:5px 12px;border-radius:20px;z-index:2}
  .badge.new{background:linear-gradient(135deg,#28a745,#20c997);color:#fff}
  .car-body{padding:22px;display:flex;flex-direction:column;flex:1}
  .car-title{font-size:17px;font-weight:700;margin-bottom:6px;line-height:1.3}
  .car-meta{font-size:12px;color:var(--muted);display:flex;gap:14px;flex-wrap:wrap;margin-bottom:16px}
  .car-meta span{display:flex;align-items:center;gap:5px}
  .car-meta i{color:var(--gold);font-size:11px}
  .car-foot{margin-top:auto;display:flex;justify-content:space-between;align-items:center;padding-top:16px;border-top:1px solid rgba(255,255,255,0.06)}
  .price{font-size:22px;font-weight:800;color:var(--gold);letter-spacing:-0.5px}
  .price small{font-size:11px;color:var(--muted);font-weight:400;letter-spacing:0}
  .btn-book{background:linear-gradient(135deg,var(--gold),var(--gold2));color:#1a1a2e;padding:10px 18px;border-radius:30px;font-weight:700;font-size:12px;letter-spacing:0.5px;transition:transform 0.2s}
  .btn-book:hover{transform:translateY(-2px)}

  /* ---------- WHY US ---------- */
  .why{background:#15102e}
  .why-grid{max-width:1400px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px}
  .why-card{background:#1a1535;border:1px solid rgba(255,215,0,0.08);border-radius:20px;padding:32px 26px;text-align:center;transition:all 0.3s}
  .why-card:hover{border-color:rgba(255,215,0,0.35);transform:translateY(-4px)}
  .why-icon{width:64px;height:64px;background:linear-gradient(135deg,var(--gold),var(--gold2));color:#1a1a2e;border-radius:16px;display:inline-flex;align-items:center;justify-content:center;font-size:26px;margin-bottom:20px;box-shadow:0 10px 24px rgba(255,215,0,0.25)}
  .why-card h3{font-size:17px;font-weight:700;margin-bottom:10px}
  .why-card p{font-size:13px;color:var(--muted);line-height:1.6}

  /* ---------- TESTIMONIALS ---------- */
  .testi-grid{max-width:1400px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px}
  .testi{background:#1a1535;border:1px solid rgba(255,215,0,0.08);border-radius:20px;padding:30px;position:relative}
  .testi .quote{font-size:38px;color:rgba(255,215,0,0.2);font-family:Georgia,serif;line-height:0.5;margin-bottom:10px}
  .testi p{font-size:14px;color:var(--text);line-height:1.7;margin-bottom:20px;font-style:italic}
  .testi .stars{color:var(--gold);margin-bottom:16px;font-size:13px;letter-spacing:2px}
  .testi .who{display:flex;align-items:center;gap:12px}
  .testi .avatar{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold2));color:#1a1a2e;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:16px}
  .testi .who b{display:block;font-size:13px;font-weight:700}
  .testi .who small{color:var(--muted);font-size:11px}

  /* ---------- CTA ---------- */
  .cta{padding:90px 5%;text-align:center;background:
    radial-gradient(circle at center, rgba(255,215,0,0.12) 0%, transparent 60%),
    linear-gradient(135deg,#1a1535 0%,#2a2055 100%);
    border-top:1px solid rgba(255,215,0,0.15);border-bottom:1px solid rgba(255,215,0,0.15)}
  .cta h2{font-size:clamp(28px,4vw,44px);font-weight:900;margin-bottom:16px;letter-spacing:-1px}
  .cta h2 span{background:linear-gradient(135deg,var(--gold),var(--gold2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
  .cta p{color:var(--muted);max-width:520px;margin:0 auto 32px;font-size:15px;line-height:1.7}

  /* ---------- FOOTER ---------- */
  .footer{background:#0a0820;padding:60px 5% 30px;border-top:1px solid rgba(255,215,0,0.08)}
  .footer-wrap{max-width:1400px;margin:0 auto;display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px}
  .footer-brand b{font-size:22px;font-weight:900;background:linear-gradient(135deg,var(--gold),var(--gold2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;letter-spacing:1px}
  .footer-brand p{color:var(--muted);font-size:13px;line-height:1.7;margin-top:14px;max-width:320px}
  .footer-col h4{color:var(--gold);font-size:13px;font-weight:700;letter-spacing:2px;text-transform:uppercase;margin-bottom:16px}
  .footer-col a{display:block;color:var(--muted);font-size:13px;margin-bottom:10px;transition:color 0.2s}
  .footer-col a:hover{color:var(--gold)}
  .footer-bottom{margin-top:50px;padding-top:24px;border-top:1px solid rgba(255,255,255,0.06);text-align:center;color:var(--muted);font-size:12px}

  /* ---------- MODAL ---------- */
  .modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.85);z-index:2000;backdrop-filter:blur(6px)}
  .modal.on{display:block}
  .modal-panel{position:fixed;top:0;right:0;bottom:0;width:100%;max-width:460px;background:linear-gradient(180deg,#15102e,#0f0c29);overflow-y:auto;transform:translateX(100%);transition:transform 0.4s cubic-bezier(0.4,0,0.2,1);border-left:1px solid rgba(255,215,0,0.2)}
  .modal.on .modal-panel{transform:translateX(0)}
  .modal-head{padding:24px 28px;border-bottom:1px solid rgba(255,215,0,0.15);display:flex;justify-content:space-between;align-items:center}
  .modal-head h3{color:var(--gold);font-size:19px;font-weight:800}
  .modal-close{background:none;border:none;color:var(--text);font-size:26px;cursor:pointer;transition:transform 0.2s}
  .modal-close:hover{transform:rotate(90deg);color:var(--gold)}
  .modal-body{padding:28px}
  .tabs{display:flex;background:rgba(255,255,255,0.05);border-radius:50px;padding:5px;margin-bottom:28px}
  .tab{flex:1;padding:11px;border:none;background:transparent;color:var(--text);font-weight:700;font-size:13px;cursor:pointer;border-radius:50px;transition:all 0.25s}
  .tab.on{background:linear-gradient(135deg,var(--gold),var(--gold2));color:#1a1a2e}
  .panel{display:none}
  .panel.on{display:block}
  .field{margin-bottom:18px;position:relative}
  .field i{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:var(--gold);font-size:14px}
  .field input{width:100%;padding:14px 16px 14px 46px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,215,0,0.2);border-radius:12px;color:var(--text);font-size:14px;font-family:inherit;transition:all 0.25s}
  .field input:focus{outline:none;border-color:var(--gold);background:rgba(255,255,255,0.1)}
  .field input::placeholder{color:rgba(255,255,255,0.35)}
  .btn-modal{width:100%;padding:15px;background:linear-gradient(135deg,var(--gold),var(--gold2));color:#1a1a2e;border:none;border-radius:12px;font-weight:800;font-size:15px;cursor:pointer;transition:all 0.25s;letter-spacing:0.5px}
  .btn-modal:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(255,215,0,0.3)}
  .alert{padding:12px;border-radius:10px;margin-bottom:18px;font-size:13px;text-align:center}
  .alert-err{background:rgba(220,53,69,0.18);border:1px solid rgba(220,53,69,0.4);color:#ff8080}

  @media (max-width:900px){
    .nav-links{display:none}
    .footer-wrap{grid-template-columns:1fr;gap:30px}
  }
</style>
<link rel="stylesheet" href="assets/css/chatbot.css">
</head>
<body>

<!-- NAV -->
<nav class="nav">
  <div class="nav-wrap">
    <a href="index.php" class="logo"><b>URBAN WHEELS</b><span>PREMIUM CAR RENTAL</span></a>
    <div class="nav-links">
      <a href="index.php" style="color:var(--gold)">Home</a>
      <a href="vehicles.php">Vehicles</a>
      <a href="services.php">Services</a>
      <a href="about.php">About</a>
      <a href="contact.php">Contact</a>
      <?php if(isset($_SESSION['user_id']) && $_SESSION['user_role']=='customer'): ?>
        <a href="my_bookings.php">My Bookings</a>
        <a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a>
        <span style="font-size:13px;color:var(--gold);font-weight:600">Hi, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <a href="logout.php" class="btn-auth" style="background:#dc3545;color:#fff!important">Logout</a>
      <?php else: ?>
        <a href="#" class="btn-auth" onclick="openModal();return false">Sign Up / Login</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-inner">
    <div class="hero-badge"><i class="fas fa-star"></i> Kenya's #1 Rated Car Rental</div>
    <h1>Drive Your Dreams<br>With <span class="accent">Urban Wheels</span></h1>
    <p>Experience Kenya like never before. From executive sedans to rugged 4x4s, our premium fleet is ready when you are — with transparent pricing and 24/7 support.</p>
    <div class="hero-cta">
      <a href="vehicles.php" class="btn-primary"><i class="fas fa-car"></i> Browse Fleet</a>
      <a href="#how" class="btn-ghost"><i class="fas fa-play-circle"></i> How It Works</a>
    </div>
    <div class="scroll-hint">SCROLL<i class="fas fa-chevron-down"></i></div>
  </div>
</section>

<!-- TRUST BAR -->
<div class="trust">
  <div class="trust-wrap">
    <div class="trust-item"><div class="num"><?= $total_vehicles ?>+</div><div class="lbl">Vehicles Ready</div></div>
    <div class="trust-item"><div class="num"><?= $total_customers ?>+</div><div class="lbl">Happy Customers</div></div>
    <div class="trust-item"><div class="num">24/7</div><div class="lbl">Support</div></div>
    <div class="trust-item"><div class="num">5★</div><div class="lbl">Average Rating</div></div>
  </div>
</div>

<!-- FLEET -->
<section class="block">
  <div class="section-head">
    <span class="eyebrow">Our Fleet</span>
    <h2>Available Right Now</h2>
    <p>Handpicked vehicles maintained to the highest standard. Book in minutes.</p>
  </div>
  <div class="fleet-grid">
    <?php foreach($cars as $c): ?>
    <div class="car">
      <div class="car-img">
        <span class="badge">Available</span>
        <?php if(!empty($c['image']) && file_exists($c['image'])): ?>
          <img src="<?= htmlspecialchars($c['image']) ?>" alt="<?= htmlspecialchars($c['brand'].' '.$c['model']) ?>">
        <?php else: ?>
          <div class="no-img"><i class="fas fa-car"></i></div>
        <?php endif; ?>
      </div>
      <div class="car-body">
        <div class="car-title"><?= htmlspecialchars($c['brand'].' '.$c['model']) ?></div>
        <div class="car-meta">
          <span><i class="fas fa-calendar"></i> <?= (int)$c['year'] ?></span>
          <span><i class="fas fa-cog"></i> <?= htmlspecialchars($c['transmission']) ?></span>
          <span><i class="fas fa-users"></i> <?= (int)$c['seats'] ?> seats</span>
        </div>
        <div class="car-foot">
          <div class="price">KES <?= number_format($c['daily_rate'],0) ?><small>/day</small></div>
          <a href="<?= isset($_SESSION['user_id']) ? 'book.php?id='.$c['id'] : 'javascript:openModal()' ?>" class="btn-book">Book Now</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div style="text-align:center;margin-top:50px">
    <a href="vehicles.php" class="btn-ghost">View All Vehicles <i class="fas fa-arrow-right"></i></a>
  </div>
</section>

<!-- WHY US -->
<section class="block why" id="how">
  <div class="section-head">
    <span class="eyebrow">Why Urban Wheels</span>
    <h2>Rental Done Right</h2>
    <p>No hidden fees, no surprises. Just premium cars and service that respects your time.</p>
  </div>
  <div class="why-grid">
    <div class="why-card">
      <div class="why-icon"><i class="fas fa-bolt"></i></div>
      <h3>Instant Booking</h3>
      <p>Reserve your car in under 2 minutes. Confirmation lands in your inbox immediately.</p>
    </div>
    <div class="why-card">
      <div class="why-icon"><i class="fas fa-shield-alt"></i></div>
      <h3>Fully Insured</h3>
      <p>Every rental includes comprehensive insurance. Drive with total peace of mind.</p>
    </div>
    <div class="why-card">
      <div class="why-icon"><i class="fas fa-tags"></i></div>
      <h3>Transparent Pricing</h3>
      <p>What you see is what you pay. No hidden charges, no surprise fees at pickup.</p>
    </div>
    <div class="why-card">
      <div class="why-icon"><i class="fas fa-headset"></i></div>
      <h3>24/7 Support</h3>
      <p>Our team is one call away — day or night, wherever you are in Kenya.</p>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="block">
  <div class="section-head">
    <span class="eyebrow">Testimonials</span>
    <h2>What Our Customers Say</h2>
  </div>
  <div class="testi-grid">
    <div class="testi">
      <div class="quote">&ldquo;</div>
      <div class="stars">★★★★★</div>
      <p>Booked a Prado for a weekend trip to Naivasha. Car was spotless, process was seamless. Will definitely use again.</p>
      <div class="who">
        <div class="avatar">JM</div>
        <div><b>James Mwangi</b><small>Nairobi</small></div>
      </div>
    </div>
    <div class="testi">
      <div class="quote">&ldquo;</div>
      <div class="stars">★★★★★</div>
      <p>The best rental experience I've had in Kenya. Transparent pricing and the customer service was incredible.</p>
      <div class="who">
        <div class="avatar">AW</div>
        <div><b>Aisha Wanjiru</b><small>Mombasa</small></div>
      </div>
    </div>
    <div class="testi">
      <div class="quote">&ldquo;</div>
      <div class="stars">★★★★★</div>
      <p>Rented an SUV for a corporate event. Car arrived on time, driver was professional. Highly recommended.</p>
      <div class="who">
        <div class="avatar">DK</div>
        <div><b>David Kipchoge</b><small>Eldoret</small></div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta">
  <h2>Ready to <span>Hit the Road?</span></h2>
  <p>Book your vehicle today and experience the Urban Wheels difference. Instant confirmation, no hidden fees.</p>
  <a href="vehicles.php" class="btn-primary"><i class="fas fa-key"></i> Book Your Ride Now</a>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div class="footer-wrap">
    <div class="footer-brand">
      <b>URBAN WHEELS</b>
      <p>Kenya's premier car rental service. Premium fleet, transparent pricing, and 24/7 support for every journey.</p>
    </div>
    <div class="footer-col">
      <h4>Explore</h4>
      <a href="vehicles.php">Our Fleet</a>
      <a href="services.php">Services</a>
      <a href="about.php">About Us</a>
      <a href="contact.php">Contact</a>
    </div>
    <div class="footer-col">
      <h4>Legal</h4>
      <a href="terms_conditions.php">Terms & Conditions</a>
      <a href="privacy_policy.php">Privacy Policy</a>
      <a href="refund_policy.php">Refund Policy</a>
    </div>
    <div class="footer-col">
      <h4>Contact</h4>
      <a href="tel:+254700000000"><i class="fas fa-phone"></i> +254 700 000 000</a>
      <a href="mailto:info@urbanwheels.com"><i class="fas fa-envelope"></i> info@urbanwheels.com</a>
      <a href="#"><i class="fas fa-map-marker-alt"></i> Nairobi CBD, Kenya</a>
    </div>
  </div>
  <div class="footer-bottom">© <?= date('Y') ?> Urban Wheels Car Rental. All rights reserved. | Designed in Nairobi</div>
</footer>

<!-- AUTH MODAL -->
<div id="authModal" class="modal" onclick="if(event.target===this)closeModal()">
  <div class="modal-panel" onclick="event.stopPropagation()">
    <div class="modal-head">
      <h3>Welcome to Urban Wheels</h3>
      <button class="modal-close" onclick="closeModal()">&times;</button>
    </div>
    <div class="modal-body">
      <div class="tabs">
        <button class="tab on" onclick="switchTab('login')">Login</button>
        <button class="tab" onclick="switchTab('register')">Sign Up</button>
      </div>
      <?php if($error): ?><div class="alert alert-err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <div id="panel-login" class="panel on">
        <form method="POST">
          <input type="hidden" name="login" value="1">
          <div class="field"><i class="fas fa-envelope"></i><input type="email" name="login_email" placeholder="Email address" required></div>
          <div class="field"><i class="fas fa-lock"></i><input type="password" name="login_password" placeholder="Password" required></div>
          <button type="submit" class="btn-modal"><i class="fas fa-sign-in-alt"></i> Sign In</button>
        </form>
      </div>

      <div id="panel-register" class="panel">
        <form method="POST">
          <input type="hidden" name="register" value="1">
          <div class="field"><i class="fas fa-user"></i><input type="text" name="name" placeholder="Full name" required></div>
          <div class="field"><i class="fas fa-envelope"></i><input type="email" name="email" placeholder="Email address" required></div>
          <div class="field"><i class="fas fa-phone"></i><input type="tel" name="phone" placeholder="Phone number"></div>
          <div class="field"><i class="fas fa-lock"></i><input type="password" name="password" placeholder="Password (min 6 chars)" required></div>
          <div class="field"><i class="fas fa-check-circle"></i><input type="password" name="confirm_password" placeholder="Confirm password" required></div>
          <button type="submit" class="btn-modal"><i class="fas fa-user-plus"></i> Create Account</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  function openModal(){document.getElementById('authModal').classList.add('on');document.body.style.overflow='hidden'}
  function closeModal(){document.getElementById('authModal').classList.remove('on');document.body.style.overflow='auto'}
  function switchTab(t){
    document.querySelectorAll('.tab').forEach(x=>x.classList.remove('on'));
    document.querySelectorAll('.panel').forEach(x=>x.classList.remove('on'));
    if(t==='login'){document.querySelectorAll('.tab')[0].classList.add('on');document.getElementById('panel-login').classList.add('on')}
    else{document.querySelectorAll('.tab')[1].classList.add('on');document.getElementById('panel-register').classList.add('on')}
  }
  document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal()});

  // Smooth scroll
  document.querySelectorAll('a[href^="#"]').forEach(a=>a.addEventListener('click',e=>{
    const t=document.querySelector(a.getAttribute('href'));
    if(t){e.preventDefault();t.scrollIntoView({behavior:'smooth'})}
  }));
</script>
<script src="assets/js/chatbot.js"></script>
</body>
</html>
