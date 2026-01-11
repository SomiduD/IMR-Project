<?php
session_start();
require 'includes/db.php';
require 'includes/functions.php'; 

$calculation_result = "";
$total_bill = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $units = intval($_POST['units']);
    $utility_type = $_POST['utility_type'];
    $customer_type = $_POST['customer_type'];
    
    $fixed_charge = 0;
    $energy_charge = 0;
    $rate_info = "";

    )
    if ($utility_type == 'Electricity') {
        
        if ($customer_type == 'Residential') {
            if ($units <= 60) {
                $energy_charge = $units * 30.00; 
                $fixed_charge = 400.00;
                $rate_info = "Residential Block 1 (0-60 Units)";
            } else {
                $first_block = 60 * 30.00;
                $remaining = $units - 60;
                $energy_charge = $first_block + ($remaining * 60.00);
                $fixed_charge = 1000.00;
                $rate_info = "Residential Block 2 (>60 Units)";
            }
        } 
        elseif ($customer_type == 'Business') {
            $energy_charge = $units * 75.00;
            $fixed_charge = 2000.00;
            $rate_info = "Business Flat Rate (Commercial)";
        } 
        elseif ($customer_type == 'Government') {
            $energy_charge = $units * 55.00;
            $fixed_charge = 1500.00;
            $rate_info = "Government General Purpose";
        }
    } 

    elseif ($utility_type == 'Water') {
        if ($customer_type == 'Residential') {
            $energy_charge = $units * 50.00;
            $fixed_charge = 300.00;
        } elseif ($customer_type == 'Business') {
            $energy_charge = $units * 110.00; 
            $fixed_charge = 2500.00;
        } else {
            $energy_charge = $units * 60.00;
            $fixed_charge = 1000.00;
        }
    }

    elseif ($utility_type == 'Gas') {
       
        $base_rate = 350.00;
        if ($customer_type == 'Business') $base_rate = 450.00;
        
        $energy_charge = $units * $base_rate;
        $fixed_charge = 500.00;
    }

    $total_bill = $energy_charge + $fixed_charge;
    
    $calculation_result = "
    <div class='bill-summary'>
        <h3>Bill Estimate ($customer_type)</h3>
        <p><strong>Utility:</strong> $utility_type ($rate_info)</p>
        <p><strong>Units Consumed:</strong> $units</p>
        <hr>
        <div style='display:flex; justify-content:space-between;'>
            <span>Energy Charge:</span>
            <span>" . formatCurrency($energy_charge) . "</span>
        </div>
        <div style='display:flex; justify-content:space-between;'>
            <span>Fixed Charge:</span>
            <span>" . formatCurrency($fixed_charge) . "</span>
        </div>
        <hr>
        <div style='display:flex; justify-content:space-between; font-size:1.4rem; font-weight:bold; color:#2e7d32;'>
            <span>Total Payble:</span>
            <span>" . formatCurrency($total_bill) . "</span>
        </div>
        <p style='font-size:0.8rem; color:#666; margin-top:10px;'>*This is an estimate based on current $customer_type tariff slabs.</p>
    </div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Advanced Tariff Calculator - UtilityOne SL</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .calc-wrapper {
            background: #f4f6f9;
            min-height: 100vh;
            padding: 40px 20px;
        }
        .calc-card {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .bill-summary { 
            background: #e8f5e9; 
            padding: 25px; 
            border-radius: 10px; 
            margin-top: 30px; 
            border-left: 5px solid #2e7d32; 
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; }
    </style>
</head>
<body>

<div style="background: #1a1a2e; padding: 15px 40px; color: white; display: flex; justify-content: space-between; align-items: center;">
    <span style="font-size: 1.2rem; font-weight: bold;">UtilityOne SL 🇱🇰</span>
    <div>
        <?php if(isset($_SESSION['role'])): ?>
            <a href="<?php echo ($_SESSION['role']=='Admin')?'admin/dashboard.php':'index.php'; ?>" style="color: white; text-decoration: none; margin-right: 20px;">Dashboard</a>
        <?php else: ?>
            <a href="index.php" style="color: white; text-decoration: none; margin-right: 20px;">Home</a>
        <?php endif; ?>
    </div>
</div>

<div class="calc-wrapper">
    <div class="calc-card">
        <h2 style="text-align: center; color: #007bff;">🇱🇰 Tariff Calculator</h2>
        <p style="text-align: center; color: #666; margin-bottom: 30px;">
            Calculate accurate utility costs for Residential, Business, and Government sectors.
        </p>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>1. Select Utility Service</label>
                <select name="utility_type" required>
                    <option value="Electricity">Electricity (CEB / LECO)</option>
                    <option value="Water">Water (NWSDB)</option>
                    <option value="Gas">Gas Supply</option>
                </select>
            </div>

            <div class="form-group">
                <label>2. Select Customer Category</label>
                <select name="customer_type" required>
                    <option value="Residential">Residential (Domestic)</option>
                    <option value="Business">Business (Commercial)</option>
                    <option value="Government">Government / Religious</option>
                </select>
            </div>

            <div class="form-group">
                <label>3. Units Consumed</label>
                <input type="number" name="units" placeholder="e.g. 90 kWh" required min="0" step="1">
            </div>

            <button type="submit" class="btn-main">Calculate Estimate</button>
        </form>

        <?php echo $calculation_result; ?>
    </div>
</div>

</body>
</html>