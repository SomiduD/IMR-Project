<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UtilityOne  - Sri Lanka's Unified Utility Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero-section {
            height: 100vh;
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('assets/img/02.jpeg');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
        }
        
        .hero-content h1 { font-size: 3.5rem; margin-bottom: 10px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
        .hero-content p { font-size: 1.2rem; margin-bottom: 30px; max-width: 600px; }
        
        .btn-hero {
            padding: 15px 40px;
            font-size: 1.1rem;
            background: #00d2ff;
            color: #000;
            text-decoration: none;
            border-radius: 30px;
            font-weight: bold;
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-hero:hover { background: #fff; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
        
        .features { display: flex; justify-content: center; gap: 30px; margin-top: 50px; }
        .feature-box { background: rgba(255,255,255,0.1); backdrop-filter: blur(5px); padding: 20px; border-radius: 10px; width: 200px; border: 1px solid rgba(255,255,255,0.2); }
    </style>
</head>
<body>

    <div class="hero-section">
        <div class="hero-content">
            <h1>UtilityOne SL</h1>
            <p>The centralized platform for managing Electricity, Water, and Gas services across Sri Lanka. Smart metering, instant billing, and seamless payments.</p>
            
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="login.php" class="btn-hero">Go to Dashboard</a>
            <?php else: ?>
                <a href="login.php" class="btn-hero">Login / Register</a>
            <?php endif; ?>
        </div>

        <div class="features">
            <div class="feature-box">
                <h3> CEB/LECO</h3>
                <p>Manage electricity tariffs & usage.</p>
            </div>
            <div class="feature-box">
                <h3> NWSDB</h3>
                <p>Track water consumption & bills.</p>
            </div>
            <div class="feature-box">
                <h3> Litro/Laugfs</h3>
                <p>Gas supply monitoring.</p>
            </div>
        </div>
    </div>

</body>
</html>