<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "config.php";

// Safety check: user must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* =========================
   Fetch User Basic Info
========================= */
$sql = "SELECT * FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($result);

$sql = "SELECT currency FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$current_currency = $row['currency'] ?? 'USD';
$currency_symbols = [
    "USD" => "$",
    "INR" => "₹",
    "EUR" => "€"
];

$currency_symbol = $currency_symbols[$current_currency] ?? "$";

/* =========================
   Fetch Income Profile
========================= */
$profile_sql = "SELECT * FROM income_details WHERE user_id = '$user_id'";
$profile_result = mysqli_query($conn, $profile_sql);
$profile_data = mysqli_fetch_assoc($profile_result);

/* =========================
   Fetch Recent Transactions
========================= */
$txn_sql = "SELECT * FROM transactions 
            WHERE user_id = '$user_id' 
            ORDER BY transaction_date DESC 
            LIMIT 5";

$txn_result = mysqli_query($conn, $txn_sql);

/* =========================
   Calculate Totals
========================= */
$income_total = mysqli_query($conn, 
    "SELECT SUM(amount) as total FROM transactions 
     WHERE user_id='$user_id' AND type='Income'");

$expense_total = mysqli_query($conn, 
    "SELECT SUM(amount) as total FROM transactions 
     WHERE user_id='$user_id' AND type='Expense'");

$income_row = mysqli_fetch_assoc($income_total);
$expense_row = mysqli_fetch_assoc($expense_total);

$income = $income_row['total'] ?? 0;
$expense = $expense_row['total'] ?? 0;

/* =========================
   Fetch Tracker Targets
========================= */
$target_query = mysqli_query($conn, 
    "SELECT * FROM tracker_targets WHERE user_id='$user_id'");

if(mysqli_num_rows($target_query) > 0){
    $targets = mysqli_fetch_assoc($target_query);
    $income_target = $targets['income_target'] ?? 0;
    $expense_target = $targets['expense_target'] ?? 0;
    $savings_target = $targets['savings_target'] ?? 0;
} else {
    $income_target = 0;
    $expense_target = 0;
    $savings_target = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Finance Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Light theme (default) */
body {
    background: #f4f6fb;
    color: #222;
}

/* Dark theme */
body.dark {
    background: #121212;
    color: #f4f4f4;
}

/* Button styling */
#themeToggle {
    padding: 8px 14px;
    margin: 10px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header Section */
        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 3.5em;
            background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            font-weight: 700;
            letter-spacing: -1px;
            animation: slideInDown 0.6s ease-out;
        }

        .header p {
            font-size: 1.1em;
            color: #6D28D9;
            font-weight: 500;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Currency Selector */
        .currency-selector {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            gap: 10px;
            align-items: center;
        }

        .currency-selector label {
            font-weight: 600;
            color: #6D28D9;
            font-size: 1.05em;
        }

        .currency-selector select {
            padding: 10px 15px;
            border: 2px solid #D8B4FE;
            border-radius: 8px;
            background-color: #FFFAF0;
            color: #4C1D95;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .currency-selector select:hover {
            border-color: #A78BFA;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2);
        }

        .currency-selector select:focus {
            outline: none;
            border-color: #8B5CF6;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }

        /* Main Layout - Three Columns */
       .dashboard-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

@media (max-width: 992px) {
    .dashboard-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

        /* Card Styling */
        .card {
            background-color: #FFFAF0;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.1);
            border: 1px solid #E9D5FF;
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease-out;
        }

        .card:hover {
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.2);
            transform: translateY(-5px);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card h2 {
            color: #6D28D9;
            font-size: 1.5em;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #E9D5FF;
        }

        /* Column 1: User Profile */
        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .profile-item {
            background: linear-gradient(135deg, #F3E8FF 0%, #FAF5FF 100%);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #A78BFA;
            transition: all 0.3s ease;
        }

        .profile-item:hover {
            background: linear-gradient(135deg, #EDE9FE 0%, #F5F3FF 100%);
            border-left-color: #8B5CF6;
            transform: translateX(5px);
        }

        .profile-item label {
            display: block;
            font-weight: 700;
            color: #6D28D9;
            font-size: 0.9em;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .profile-item span {
            font-size: 1.1em;
            color: #4C1D95;
            font-weight: 500;
        }

        .profile-avatar {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #E9D5FF;
            margin-bottom: 20px;
        }

        .avatar-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #A78BFA 0%, #8B5CF6 100%);
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2em;
            font-weight: bold;
        }

        .profile-name {
            font-size: 1.3em;
            font-weight: 700;
            color: #4C1D95;
        }

        /* Column 2: Transactions */
        .transaction-form {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 2px solid #E9D5FF;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-weight: 600;
            color: #6D28D9;
            font-size: 0.95em;
        }

        .form-group input,
        .form-group select {
            padding: 10px 12px;
            border: 2px solid #D8B4FE;
            border-radius: 8px;
            font-size: 0.95em;
            background-color: #FFFAF0;
            color: #4C1D95;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #8B5CF6;
            box-shadow: 0 0 8px rgba(139, 92, 246, 0.3);
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95em;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #A78BFA 0%, #8B5CF6 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 100%);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
            transform: translateY(-2px);
        }

        .btn-primary:active {
            background: #4C1D95;
            transform: translateY(0);
        }

        /* Transaction List */
        .transaction-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 500px;
            overflow-y: auto;
        }

        .transaction-item {
            background: linear-gradient(135deg, #F3E8FF 0%, #FAF5FF 100%);
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid #A78BFA;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95em;
            transition: all 0.3s ease;
        }

        .transaction-item:hover {
            background: linear-gradient(135deg, #EDE9FE 0%, #F5F3FF 100%);
            border-left-color: #8B5CF6;
            transform: translateX(5px);
        }

        .transaction-detail {
            flex: 1;
        }

        .transaction-name {
            font-weight: 600;
            color: #4C1D95;
            margin-bottom: 3px;
        }

        .transaction-date {
            font-size: 0.85em;
            color: #8B5CF6;
        }

        .transaction-amount {
            font-weight: 700;
            font-size: 1.05em;
        }

        .amount-income {
            color: #059669;
        }

        .amount-expense {
            color: #DC2626;
        }

        .amount-savings {
            color: #7C3AED;
        }

        .transactions-header {
            font-weight: 700;
            color: #6D28D9;
            margin-top: 20px;
            margin-bottom: 15px;
            font-size: 1.1em;
        }

        /* Column 3: Trackers */
        .trackers-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .tracker {
            background: linear-gradient(135deg, #F3E8FF 0%, #FAF5FF 100%);
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #E9D5FF;
            transition: all 0.3s ease;
        }

        .tracker:hover {
            border-color: #D8B4FE;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2);
        }

        .tracker-title {
            font-weight: 700;
            color: #6D28D9;
            margin-bottom: 15px;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tracker-icon {
            font-size: 1.3em;
        }

        .tracker-amount {
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: baseline;
            gap: 5px;
        }

        .tracker-amount-value {
            color: #4C1D95;
        }

        .currency {
            font-size: 0.6em;
            color: #8B5CF6;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: #E9D5FF;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .income-fill {
            background: linear-gradient(90deg, #059669 0%, #10B981 100%);
        }

        .expense-fill {
            background: linear-gradient(90deg, #DC2626 0%, #EF4444 100%);
        }

        .savings-fill {
            background: linear-gradient(90deg, #7C3AED 0%, #A78BFA 100%);
        }

        .tracker-info {
            font-size: 0.9em;
            color: #8B5CF6;
            text-align: right;
        }

        .empty-state {
            text-align: center;
            padding: 20px;
            color: #A78BFA;
            font-size: 0.95em;
            font-style: italic;
        }


            .header h1 {
                font-size: 2em;
            }

            .card {
                padding: 20px;
            }

            body {
                padding: 15px;
            }
        

        /* Scrollbar Styling */
        .transaction-list::-webkit-scrollbar {
            width: 6px;
        }

        .transaction-list::-webkit-scrollbar-track {
            background: #F3E8FF;
            border-radius: 4px;
        }

        .transaction-list::-webkit-scrollbar-thumb {
            background: #D8B4FE;
            border-radius: 4px;
        }

        .transaction-list::-webkit-scrollbar-thumb:hover {
            background: #A78BFA;
        }

        /* Edit Button */
        .edit-btn {
            background: linear-gradient(135deg, #A78BFA 0%, #8B5CF6 100%);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.85em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            width: 100%;
        }

        .edit-btn:hover {
            background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 100%);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
        }

        /* Edit Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeInModal 0.3s ease;
        }

        @keyframes fadeInModal {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .modal-content {
            background: linear-gradient(135deg, #FFFAF0 0%, #F0E6FF 100%);
            margin: 10% auto;
            padding: 30px;
            border-radius: 12px;
            border: 2px solid #D8B4FE;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(139, 92, 246, 0.3);
            animation: slideInModal 0.3s ease;
        }

        @keyframes slideInModal {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            font-size: 1.5em;
            font-weight: 700;
            color: #6D28D9;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #E9D5FF;
        }

        .modal-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .close {
            color: #8B5CF6;
            float: right;
            font-size: 1.5em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close:hover {
            color: #6D28D9;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-save {
            background: linear-gradient(135deg, #059669 0%, #10B981 100%);
            color: white;
        }

        .btn-save:hover {
            background: linear-gradient(135deg, #047857 0%, #059669 100%);
        }

        .btn-cancel {
            background: #E9D5FF;
            color: #6D28D9;
        }

        .btn-cancel:hover {
            background: #D8B4FE;
        }

        /* Logout Button */
        .logout-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 100;
        }

        .logout-btn {
            background: linear-gradient(135deg, #1F2937 0%, #111827 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .logout-btn:hover {
            background: linear-gradient(135deg, #111827 0%, #000000 100%);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.5);
            transform: translateY(-2px);
        }

        .logout-btn:active {
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .logout-container {
                bottom: 20px;
                right: 20px;
            }

            .logout-btn {
                padding: 10px 18px;
                font-size: 0.9em;
            }
        }
        /* Dashboard settings removed - styles for that block deleted per request */
    /* Additional dark-mode overrides to match dashboard setting */
    body.dark {
        background: #0f1724;
        color: #e6eef8;
    }

    body.dark .card,
    body.dark .tracker,
    body.dark .transaction-item,
    body.dark .profile-item,
    body.dark .modal-content {
        background: linear-gradient(135deg,#0b1220 0%, #111827 100%);
        border-color: #2b3440;
        box-shadow: 0 6px 18px rgba(0,0,0,0.6);
        color: #e6eef8;
    }

    body.dark .card:hover {
        box-shadow: 0 10px 30px rgba(0,0,0,0.7);
        transform: translateY(0);
    }

    body.dark .card h2,
    body.dark .tracker-title,
    body.dark .profile-item label,
    body.dark .modal-header,
    body.dark .dashboard-card h2 {
        color: #c7b3ff;
    }

    body.dark .profile-item span,
    body.dark .transaction-name,
    body.dark .transaction-date,
    body.dark .transaction-amount,
    body.dark .tracker-amount-value {
        color: #e6eef8;
    }

    body.dark .form-group input,
    body.dark .form-group select,
    body.dark textarea,
    body.dark select,
    body.dark .dashboard-card textarea,
    body.dark .dashboard-card select {
        background: #0b1220;
        color: #e6eef8;
        border: 1px solid #2b3440;
    }

    body.dark .currency-selector label,
    body.dark .currency,
    body.dark .tracker-info {
        color: #9aa4c2;
    }

    body.dark .transaction-list::-webkit-scrollbar-track { background: #0b1220; }
    body.dark .transaction-list::-webkit-scrollbar-thumb { background: #314155; }

    body.dark .logout-btn {
        background: linear-gradient(135deg,#111827 0%, #0b1220 100%);
        color: #fff;
        box-shadow: 0 6px 18px rgba(0,0,0,0.6);
    }

    body.dark .btn-primary {
        background: linear-gradient(135deg,#6d28d9 0%, #4c1d95 100%);
        color: #fff;
    }
    </style>
</head>
<body>
    <button id="themeToggle" style="position:fixed; top:15px; right:20px; padding:8px 12px; cursor:pointer;">
    <?php echo (($data['theme'] ?? 'light') === 'dark') ? '☀ Light Mode' : '🌙 Dark Mode';?>
</button>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>💰 Personal Finance Dashboard</h1>
            <p>Manage Your Money, Achieve Your Goals</p>
        </div>

        <!-- Currency Selector -->
        <div class="currency-selector">
            <label for="currency">Select Currency:</label>
            <select id="currency" onchange="changeCurrency()">
                <option value="$">USD ($) - US Dollar</option>
                <option value="€">EUR (€) - Euro</option>
                <option value="£">GBP (£) - British Pound</option>
                <option value="¥">JPY (¥) - Japanese Yen</option>
                <option value="₹">INR (₹) - Indian Rupee</option>
                <option value="C$">CAD (C$) - Canadian Dollar</option>
                <option value="A$">AUD (A$) - Australian Dollar</option>
                <option value="CHF">CHF (CHF) - Swiss Franc</option>
                <option value="SGD">SGD (SGD) - Singapore Dollar</option>
                <option value="HKD">HKD (HKD) - Hong Kong Dollar</option>
                <option value="NZD">NZD (NZD) - New Zealand Dollar</option>
                <option value="₽">RUB (₽) - Russian Ruble</option>
                <option value="R$">BRL (R$) - Brazilian Real</option>
                <option value="₩">KRW (₩) - South Korean Won</option>
                <option value="฿">THB (฿) - Thai Baht</option>
                <option value="₱">PHP (₱) - Philippine Peso</option>
                <option value="Rp">IDR (Rp) - Indonesian Rupiah</option>
                <option value="৳">BDT (৳) - Bangladeshi Taka</option>
                <option value="₪">ILS (₪) - Israeli Shekel</option>
                <option value="₦">NGN (₦) - Nigerian Naira</option>
            </select>
        </div>

        <!-- Main Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Column 1: User Profile -->
            <div class="card">
                <h2>👤 User Profile</h2>
                <div class="profile-avatar">
                    <div class="avatar-circle" id="avatarInitials">--</div>
                    <div class="profile-name" id="profileName">
    <?php
     echo $data['name'] ?? 'User'; 
     ?>
</div>
                </div>

                <div class="profile-info">
                    <div class="profile-item">
                        <label>Monthly Income</label>
                        <span id="monthlyIncome">
    <?php 
    echo $profile_data['monthly_income'] ?? 'Not Set';
     ?>
</span>
                    </div>

                    <div class="profile-item">
                        <label>Income Date</label>
                        <span id="incomeDate">
    <?php 
    echo $profile_data['income_date'] ?? 'Not Set';
     ?>
</span>
                    </div>

                    <div class="profile-item">
                        <label>Income Source</label>
                        <span id="incomeSource">
    <?php
     echo $profile_data['income_source'] ?? 'Not Set';
      ?>
</span>
                    </div>

                    <div class="profile-item">
                        <label>Email</label>
                        <span id="profileEmail">
    <?php
     echo $data['email'] ?? 'Not Set'; 
     ?>
</span>
                    </div>

                    <div class="profile-item">
                        <label>Account Status</label>
                        <span style="color: #059669; font-weight: 700;">🟢 Active</span>
                    </div>
                </div>

                <button class="edit-btn" onclick="openEditModal()">✏️ Edit Profile Information</button>
            </div>

           <!-- Column 2: Transactions -->
<div class="card">
    <h2>Transactions</h2>

    <form method="POST" action="add_transaction.php">
        <div class="transaction-form">

            <!-- Transaction Name -->
            <div class="form-group">
                <label for="transactionName">Transaction Name</label>
                <input type="text" 
                       name="transaction_name" 
                       id="transactionName" 
                       placeholder="e.g., Grocery Shopping" 
                       required>
            </div>

            <!-- Type -->
            <div class="form-group">
                <label for="transactionType">Type</label>
                <select name="type" id="transactionType" required>
                    <option value="Expense">Expense</option>
                    <option value="Income">Income</option>
                </select>
            </div>

            <!-- Amount -->
            <div class="form-group">
                <label for="transactionAmount">Amount</label>
                <input type="number" 
                       name="amount" 
                       id="transactionAmount" 
                       step="0.01" 
                       min="0" 
                       placeholder="0.00" 
                       required>
            </div>

            <!-- Date -->
            <div class="form-group">
                <label for="transactionDate">Date</label>
                <input type="date" 
                       name="date" 
                       id="transactionDate" 
                       required>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary">
                Add Transaction
            </button>

        </div>
    </form>

    <div class="transactions-header">
        Recent Transactions
    </div>

    <?php 
    if(mysqli_num_rows($txn_result) > 0){
        while($row = mysqli_fetch_assoc($txn_result)){
    ?>
        <div class="transaction-item">
            <strong><?php echo $row['transaction_name']; ?></strong>
            <span>
                <?php 
                    if($row['type'] == 'Income'){
                        echo "<span style='color:green;'>+ ₹".$row['amount']."</span>";
                    } else {
                        echo "<span style='color:red;'>- ₹".$row['amount']."</span>";
                    }
                ?>
            </span>
            <small><?php echo $row['transaction_date']; ?></small>
        </div>
    <?php 
        }
    } else {
        echo "No transactions yet.";
    }
    ?>
</div>
           <!-- Column 3: Trackers -->
<div class="card">
    <h2>📊 Financial Trackers</h2>

    <div class="trackers-container">

        <!-- Income Tracker -->
        <div class="tracker">
            <div class="tracker-title">
                <span class="tracker-icon">📈</span>
                Income
            </div>
            <div class="tracker-amount">
                <span class="tracker-amount-value" id="incomeAmount">0</span>
                <span class="currency" id="incomeCurrency"><?php echo $currency_symbol; ?></span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill income-fill" 
                     id="incomeProgressBar" 
                     style="width: 0%;">
                </div>
            </div>
            <div class="tracker-info" id="incomeTargetInfo">
                Target: 0
            </div>
            <button class="edit-btn" 
                    style="margin-top: 12px;" 
                    onclick="openTrackerModal('income')">
                ✏️ Set Target
            </button>
        </div>

        <!-- Expense Tracker -->
        <div class="tracker">
            <div class="tracker-title">
                <span class="tracker-icon">📉</span>
                Expenses
            </div>
            <div class="tracker-amount">
                <span class="tracker-amount-value" id="expenseAmount">0</span>
                <span class="currency" id="expenseCurrency"><?php echo $currency_symbol; ?></span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill expense-fill" 
                     id="expenseProgressBar" 
                     style="width: 0%;">
                </div>
            </div>
            <div class="tracker-info" id="expenseTargetInfo">
                Target: 0
            </div>
            <button class="edit-btn" 
                    style="margin-top: 12px;" 
                    onclick="openTrackerModal('expense')">
                ✏️ Set Target
            </button>
        </div>

        <!-- Savings Tracker -->
        <div class="tracker">
            <div class="tracker-title">
                <span class="tracker-icon">🎯</span>
                Savings
            </div>
            <div class="tracker-amount">
                <span class="tracker-amount-value" id="savingsAmount">0</span>
                <span class="currency" id="savingsCurrency"><?php echo $currency_symbol; ?></span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill savings-fill" 
                     id="savingsProgressBar" 
                     style="width: 0%;">
                </div>
            </div>
            <div class="tracker-info" id="savingsTargetInfo">
                Target: 0
            </div>
            <button class="edit-btn" 
                    style="margin-top: 12px;" 
                    onclick="openTrackerModal('savings')">
                ✏️ Set Target
            </button>
        </div>

    </div>

    <!-- ✅ Hidden Inputs (for passing DB values to JavaScript) -->
    <input type="hidden" 
           id="incomeTargetValue" 
           value="<?php echo $income_target ?? 0; ?>">

    <input type="hidden" 
           id="expenseTargetValue" 
           value="<?php echo $expense_target ?? 0; ?>">

    <input type="hidden" 
           id="savingsTargetValue" 
           value="<?php echo $savings_target ?? 0; ?>">

</div>
</div>
</div>

    <div class="logout-container">
    <a href="logout.php">
        <button class="logout-btn">🚪 Logout</button>
    </a>
</div>

    <!-- Edit Profile Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>

        <div class="modal-header">Edit Your Information</div>

        <form class="modal-form" method="POST" action="save_profile.php">

            <div class="form-group">
                <label for="editName">Full Name</label>
                <input type="text" id="editName" name="full_name" placeholder="Enter your name" required>
            </div>

            <div class="form-group">
                <label for="editEmail">Email</label>
                <input type="email" id="editEmail" name="email" placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label for="editIncome">Monthly Income Amount</label>
                <input type="number" id="editIncome" name="monthly_income" placeholder="0.00" step="0.01" min="0">
            </div>

            <div class="form-group">
                <label for="editIncomeDate">Income Date</label>
                <input type="date" id="editIncomeDate" name="income_date">
            </div>

            <div class="form-group">
                <label for="editIncomeSource">Income Source</label>
                <input type="text" id="editIncomeSource" name="income_source" placeholder="e.g., Full-time Employment">
            </div>

            <div class="modal-buttons">
                <button type="submit" class="btn-save">Save Changes</button>
            </div>

        </form>
    </div>
</div>

    <!-- Tracker Target Modal -->
    <div id="trackerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeTrackerModal()">&times;</span>
            <div class="modal-header" id="trackerModalHeader">Set Income Target</div>
            <div class="modal-form">
                <div class="form-group">
                    <label for="trackerTargetAmount">Target Amount</label>
                    <input type="number" id="trackerTargetAmount" placeholder="0.00" step="0.01" min="0">
                </div>

                <div class="modal-buttons">
                    <button class="btn-save" onclick="saveTrackerTarget()">Save Target</button>
                    <button class="btn-cancel" onclick="closeTrackerModal()">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    
</body>
<script>
document.addEventListener("DOMContentLoaded", function () {

    // ================= INITIAL SETUP =================

    // Set today's date
    const dateInput = document.getElementById('transactionDate');
    if (dateInput) dateInput.valueAsDate = new Date();

    // Load from localStorage
    let transactions = JSON.parse(localStorage.getItem("transactions")) || [];
    let trackerTargets = JSON.parse(localStorage.getItem("trackerTargets")) || {
        income: 0,
        expense: 0,
        savings: 0
    };
    let currentCurrency = localStorage.getItem("currency") || "<?php echo $current_currency ?? '₹'; ?>";
    let currentTrackerType = null;

    // Set currency dropdown value
    const currencySelect = document.getElementById("currency");
    if (currencySelect) currencySelect.value = currentCurrency;

    // ================= TRANSACTION LIST =================
    function updateTransactionList() {
        const list = document.getElementById('transactionList');
        if (!list) return;

        list.innerHTML = '';

        transactions.forEach(transaction => {
            const item = document.createElement('div');
            item.className = 'transaction-item';

            const sign = transaction.type === 'income' ? '+' : '-';
            const amountClass = `amount-${transaction.type}`;
            const formattedDate = new Date(transaction.date).toLocaleDateString('en-US');

            item.innerHTML = `
                <div class="transaction-detail">
                    <div class="transaction-name">${transaction.name}</div>
                    <div class="transaction-date">${formattedDate}</div>
                </div>
                <div class="transaction-amount ${amountClass}">
                    ${sign}${currentCurrency}${transaction.amount.toFixed(2)}
                </div>
            `;

            list.appendChild(item);
        });

        if (transactions.length === 0) {
            list.innerHTML = '<div class="empty-state">No transactions yet.</div>';
        }
    }

    // ================= TRACKERS =================
    function updateTrackers() {
        let income = 0, expense = 0, savings = 0;

        transactions.forEach(t => {
            if (t.type === 'income') income += t.amount;
            if (t.type === 'expense') expense += t.amount;
            if (t.type === 'savings') savings += t.amount;
        });

        document.getElementById('incomeAmount').textContent = `${currentCurrency}${income.toFixed(2)}`;
        document.getElementById('expenseAmount').textContent = `${currentCurrency}${expense.toFixed(2)}`;
        document.getElementById('savingsAmount').textContent = `${currentCurrency}${savings.toFixed(2)}`;

        updateTrackerDisplay('income', income);
        updateTrackerDisplay('expense', expense);
        updateTrackerDisplay('savings', savings);
    }

    function updateTrackerDisplay(type, currentAmount) {
        const target = trackerTargets[type] || 0;
        const targetEl = document.getElementById(`${type}TargetInfo`);
        const progressBarEl = document.getElementById(`${type}ProgressBar`);

        if (!targetEl || !progressBarEl) return;

        if (target === 0) {
            targetEl.textContent = 'Target: Not set';
            progressBarEl.style.width = '0%';
        } else {
            targetEl.textContent =
                `Target: ${currentCurrency}${currentAmount.toFixed(2)} / ${currentCurrency}${target.toFixed(2)}`;

            const percentage = Math.min((currentAmount / target) * 100, 100);
            progressBarEl.style.width = percentage + '%';
        }
    }

    // ================= ADD TRANSACTION =================
    window.addTransaction = function () {
        const name = document.getElementById('transactionName').value;
        const type = document.getElementById('transactionType').value;
        const amount = parseFloat(document.getElementById('transactionAmount').value);
        const date = document.getElementById('transactionDate').value;

        if (!name || isNaN(amount)) {
            alert("Please fill all fields");
            return;
        }

        transactions.push({ name, type, amount, date });

        // SAVE
        localStorage.setItem("transactions", JSON.stringify(transactions));

        updateTransactionList();
        updateTrackers();

        document.getElementById('transactionName').value = '';
        document.getElementById('transactionAmount').value = '';
        document.getElementById('transactionDate').valueAsDate = new Date();
    };

    // ================= TARGET MODAL =================
    window.openTrackerModal = function (type) {
        currentTrackerType = type;
        document.getElementById('trackerModal').style.display = 'block';
        document.getElementById('trackerModalHeader').textContent = `Set ${type} Target`;
        document.getElementById('trackerTargetAmount').value = trackerTargets[type] || '';
    };

    window.closeTrackerModal = function () {
        document.getElementById('trackerModal').style.display = 'none';
        currentTrackerType = null;
    };

   window.saveTrackerTarget = function () {
    const target = parseFloat(document.getElementById('trackerTargetAmount').value);

    if (isNaN(target) || target < 0) {
        alert("Enter valid amount");
        return;
    }

    // Save locally
    trackerTargets[currentTrackerType] = target;
    localStorage.setItem("trackerTargets", JSON.stringify(trackerTargets));

    // 🔥 SAVE TO DATABASE
    fetch("save_tracker_target.php", {
        method: "POST",
        credentials: "include",  // VERY IMPORTANT for session
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            type: currentTrackerType,
            value: target
        })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            console.error("DB Error:", data.error);
        }
    })
    .catch(err => console.error("Fetch error:", err));

    updateTrackers();
    closeTrackerModal();
};

    // ================= CURRENCY =================
    window.changeCurrency = function () {
        currentCurrency = currencySelect.value;
        localStorage.setItem("currency", currentCurrency);

        updateTransactionList();
        updateTrackers();
    };

    // ================= LOGOUT =================
    window.logout = function () {
        if (confirm("Are you sure you want to logout?")) {
            window.location.href = "index.php";
        }
    };

    // ================= INIT =================
    updateTransactionList();
    updateTrackers();

});
</script>
</html>