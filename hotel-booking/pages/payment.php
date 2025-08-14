<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

// Lấy thông tin booking từ session hoặc GET
$bookingID = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$paymentMethod = $_GET['method'] ?? 'card';

if (!$bookingID) {
    header('Location: rooms.php');
    exit;
}

// Lấy thông tin booking
$stmt = $pdo->prepare('
    SELECT b.*, r.RoomNumber, r.PricePerNight, rt.TypeName, br.BranchName,
           DATEDIFF(b.CheckOutDate, b.CheckInDate) as Nights
    FROM bookings b 
    JOIN rooms r ON b.RoomID = r.RoomID 
    JOIN roomtypes rt ON r.RoomTypeID = rt.RoomTypeID 
    JOIN branches br ON r.BranchID = br.BranchID 
    WHERE b.BookingID = ? AND b.UserID = ?
');
$stmt->execute([$bookingID, $_SESSION['user']['UserID']]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: rooms.php');
    exit;
}

$totalAmount = $booking['PricePerNight'] * $booking['Nights'];

// Xử lý thanh toán
$paymentSuccess = $paymentError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = $_POST['payment_method'];
    
    // Validate thông tin thanh toán theo từng phương thức
    $isValid = true;
    
    switch ($paymentMethod) {
        case 'card':
            $cardNumber = trim($_POST['card_number'] ?? '');
            $cardName = trim($_POST['card_name'] ?? '');
            $expiry = trim($_POST['expiry'] ?? '');
            $cvv = trim($_POST['cvv'] ?? '');
            
            if (!$cardNumber || !$cardName || !$expiry || !$cvv) {
                $paymentError = 'Please fill in all card details.';
                $isValid = false;
            } elseif (!preg_match('/^\d{16}$/', str_replace(' ', '', $cardNumber))) {
                $paymentError = 'Invalid card number format.';
                $isValid = false;
            } elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
                $paymentError = 'Invalid CVV.';
                $isValid = false;
            }
            break;
            
        case 'bank_transfer':
            $accountName = trim($_POST['account_name'] ?? '');
            $accountNumber = trim($_POST['account_number'] ?? '');
            $bankName = trim($_POST['bank_name'] ?? '');
            
            if (!$accountName || !$accountNumber || !$bankName) {
                $paymentError = 'Please fill in all bank transfer details.';
                $isValid = false;
            }
            break;
            
        case 'ewallet':
            $phoneNumber = trim($_POST['phone_number'] ?? '');
            $otp = trim($_POST['otp'] ?? '');
            
            if (!$phoneNumber || !$otp) {
                $paymentError = 'Please fill in phone number and OTP.';
                $isValid = false;
            } elseif (!preg_match('/^\d{10,11}$/', $phoneNumber)) {
                $paymentError = 'Invalid phone number format.';
                $isValid = false;
            } elseif (!preg_match('/^\d{6}$/', $otp)) {
                $paymentError = 'Invalid OTP format.';
                $isValid = false;
            }
            break;
    }
    
    if ($isValid) {
        try {
            // Cập nhật trạng thái booking thành "paid"
            $stmt = $pdo->prepare('UPDATE bookings SET Status = "paid", PaymentMethod = ?, PaymentDate = NOW() WHERE BookingID = ?');
            if ($stmt->execute([$paymentMethod, $bookingID])) {
                $paymentSuccess = 'Payment successful! Your booking has been confirmed.';
                
                // Redirect sau 3 giây
                header("refresh:3;url=profile.php");
            } else {
                $paymentError = 'Payment processing failed. Please try again.';
            }
        } catch (Exception $e) {
            $paymentError = 'An error occurred: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<style>
.payment-container {
    max-width: 1000px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.payment-header {
    text-align: center;
    margin-bottom: 2rem;
}

.payment-header h1 {
    color: #1e293b;
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.payment-header p {
    color: #64748b;
    font-size: 1.1rem;
}

.payment-grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 2rem;
    align-items: start;
}

.payment-methods {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(30,41,59,0.07);
    padding: 2rem;
}

.payment-summary {
    background: #f8fafc;
    border-radius: 16px;
    padding: 2rem;
    position: sticky;
    top: 2rem;
}

.method-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 1rem;
}

.method-tab {
    padding: 0.75rem 1.5rem;
    border: none;
    background: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    color: #64748b;
    transition: all 0.2s;
}

.method-tab.active {
    background: #0ea5e9;
    color: white;
}

.method-tab:hover:not(.active) {
    background: #f1f5f9;
    color: #1e293b;
}

.payment-form {
    display: none;
}

.payment-form.active {
    display: block;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 1.5px solid #d1d5db;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
}

.card-input-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.pay-button {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(90deg, #ff9800 0%, #ff5722 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.pay-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(255,152,0,0.18);
}

.summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.summary-total {
    font-size: 1.2rem;
    font-weight: 700;
    color: #0ea5e9;
    border-top: 2px solid #e2e8f0;
    padding-top: 1rem;
    margin-top: 1rem;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    text-align: center;
    font-weight: 600;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

@media (max-width: 768px) {
    .payment-grid {
        grid-template-columns: 1fr;
    }
    
    .card-input-group {
        grid-template-columns: 1fr;
    }
    
    .method-tabs {
        flex-wrap: wrap;
    }
}

/* QR Payment Tooltip Styles */
.qr-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    backdrop-filter: blur(5px);
}

.qr-tooltip {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    text-align: center;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideIn 0.3s ease-out;
}

.qr-tooltip h3 {
    color: #1e293b;
    font-size: 1.5rem;
    margin-bottom: 1rem;
    font-weight: 700;
}

.qr-image {
    width: 200px;
    height: 200px;
    margin: 1rem auto;
    border-radius: 12px;
    background: url('../assets/images/qr.jpg') center/cover;
    border: 3px solid #e2e8f0;
}

.qr-instructions {
    color: #64748b;
    font-size: 0.95rem;
    margin: 1rem 0;
    line-height: 1.5;
}

.qr-buttons {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    justify-content: center;
}

.qr-btn {
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 1rem;
}

.qr-btn-cancel {
    background: #f1f5f9;
    color: #64748b;
}

.qr-btn-cancel:hover {
    background: #e2e8f0;
    color: #475569;
}

.qr-btn-confirm {
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
    color: white;
}

.qr-btn-confirm:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.qr-overlay.show {
    display: flex;
}
</style>

<div class="payment-container">
    <div class="payment-header">
        <h1>Complete Your Payment</h1>
        <p>Choose your preferred payment method to confirm your booking</p>
    </div>

    <?php if ($paymentSuccess): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($paymentSuccess) ?>
        </div>
    <?php endif; ?>

    <?php if ($paymentError): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($paymentError) ?>
        </div>
    <?php endif; ?>

    <div class="payment-grid">
        <div class="payment-methods">
            <div class="method-tabs">
                <button type="button" class="method-tab <?= $paymentMethod === 'card' ? 'active' : '' ?>" onclick="showMethod('card')">
                    <i class="fas fa-credit-card"></i> Credit Card
                </button>
                <button type="button" class="method-tab <?= $paymentMethod === 'bank_transfer' ? 'active' : '' ?>" onclick="showMethod('bank_transfer')">
                    <i class="fas fa-university"></i> Bank Transfer
                </button>
                <button type="button" class="method-tab <?= $paymentMethod === 'ewallet' ? 'active' : '' ?>" onclick="showMethod('ewallet')">
                    <i class="fas fa-mobile-alt"></i> E-Wallet
                </button>
            </div>

            <form method="post" id="payment-form">
                <input type="hidden" name="payment_method" id="payment_method" value="<?= $paymentMethod ?>">

                <!-- Credit Card Form -->
                <div class="payment-form <?= $paymentMethod === 'card' ? 'active' : '' ?>" id="card-form">
                    <div class="form-group">
                        <label for="card_number">Card Number</label>
                        <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                    </div>
                    <div class="form-group">
                        <label for="card_name">Cardholder Name</label>
                        <input type="text" id="card_name" name="card_name" placeholder="John Doe">
                    </div>
                    <div class="card-input-group">
                        <div class="form-group">
                            <label for="expiry">Expiry Date</label>
                            <input type="text" id="expiry" name="expiry" placeholder="MM/YY" maxlength="5">
                        </div>
                        <div class="form-group">
                            <label for="cvv">CVV</label>
                            <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="4">
                        </div>
                    </div>
                </div>

                <!-- Bank Transfer Form -->
                <div class="payment-form <?= $paymentMethod === 'bank_transfer' ? 'active' : '' ?>" id="bank_transfer-form">
                    <div class="form-group">
                        <label for="bank_name">Bank Name</label>
                        <select id="bank_name" name="bank_name" required>
                            <option value="">Select Bank</option>
                            <option value="Vietcombank">Vietcombank</option>
                            <option value="BIDV">BIDV</option>
                            <option value="Agribank">Agribank</option>
                            <option value="Techcombank">Techcombank</option>
                            <option value="MB Bank">MB Bank</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="account_name">Account Name</label>
                        <input type="text" id="account_name" name="account_name" placeholder="Account holder name">
                    </div>
                    <div class="form-group">
                        <label for="account_number">Account Number</label>
                        <input type="text" id="account_number" name="account_number" placeholder="1234567890">
                    </div>
                </div>

                <!-- E-Wallet Form -->
                <div class="payment-form <?= $paymentMethod === 'ewallet' ? 'active' : '' ?>" id="ewallet-form">
                    <div class="form-group">
                        <label for="ewallet_type">E-Wallet</label>
                        <select id="ewallet_type" name="ewallet_type" required>
                            <option value="">Select E-Wallet</option>
                            <option value="momo">MoMo</option>
                            <option value="zalo_pay">ZaloPay</option>
                            <option value="vnpay">VNPay</option>
                            <option value="shopee_pay">ShopeePay</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="text" id="phone_number" name="phone_number" placeholder="0123456789">
                    </div>
                    <div class="form-group">
                        <label for="otp">OTP Code</label>
                        <input type="text" id="otp" name="otp" placeholder="123456" maxlength="6">
                    </div>
                </div>

                <button type="button" class="pay-button" onclick="showQRPayment()">
                    <i class="fas fa-lock"></i> Pay Now - <?= number_format($totalAmount, 0, ',', '.') ?>đ
                </button>
            </form>
        </div>

        <div class="payment-summary">
            <h3>Booking Summary</h3>
            <div class="summary-item">
                <span>Room:</span>
                <span><?= htmlspecialchars($booking['RoomNumber']) ?> (<?= htmlspecialchars($booking['TypeName']) ?>)</span>
            </div>
            <div class="summary-item">
                <span>Branch:</span>
                <span><?= htmlspecialchars($booking['BranchName']) ?></span>
            </div>
            <div class="summary-item">
                <span>Check-in:</span>
                <span><?= date('M d, Y', strtotime($booking['CheckInDate'])) ?></span>
            </div>
            <div class="summary-item">
                <span>Check-out:</span>
                <span><?= date('M d, Y', strtotime($booking['CheckOutDate'])) ?></span>
            </div>
            <div class="summary-item">
                <span>Nights:</span>
                <span><?= $booking['Nights'] ?> night<?= $booking['Nights'] > 1 ? 's' : '' ?></span>
            </div>
            <div class="summary-item">
                <span>Guests:</span>
                <span><?= $booking['Guests'] ?> guest<?= $booking['Guests'] > 1 ? 's' : '' ?></span>
            </div>
            <div class="summary-item">
                <span>Price per night:</span>
                <span><?= number_format($booking['PricePerNight'], 0, ',', '.') ?>đ</span>
            </div>
            <div class="summary-total">
                <span>Total Amount:</span>
                <span><?= number_format($totalAmount, 0, ',', '.') ?>đ</span>
            </div>
        </div>
    </div>
</div>

<!-- QR Payment Tooltip -->
<div class="qr-overlay" id="qrOverlay">
    <div class="qr-tooltip">
        <h3>Complete Payment</h3>
        <div class="qr-image"></div>
        <div class="qr-instructions">
            Scan this QR code with your mobile banking app or e-wallet to complete the payment.<br>
            Amount: <strong><?= number_format($totalAmount, 0, ',', '.') ?>đ</strong>
        </div>
        <div class="qr-buttons">
            <button type="button" class="qr-btn qr-btn-cancel" onclick="hideQRPayment()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="button" class="qr-btn qr-btn-confirm" onclick="confirmPayment()">
                <i class="fas fa-check"></i> Confirm Payment
            </button>
        </div>
    </div>
</div>

<script>
function showMethod(method) {
    // Update active tab
    document.querySelectorAll('.method-tab').forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');
    
    // Update hidden input
    document.getElementById('payment_method').value = method;
    
    // Show/hide forms
    document.querySelectorAll('.payment-form').forEach(form => form.classList.remove('active'));
    document.getElementById(method + '-form').classList.add('active');
}

// Card number formatting
document.getElementById('card_number').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s/g, '');
    value = value.replace(/\D/g, '');
    value = value.replace(/(\d{4})/g, '$1 ').trim();
    e.target.value = value;
});

// Expiry date formatting
document.getElementById('expiry').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    e.target.value = value;
});

// CVV only numbers
document.getElementById('cvv').addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/\D/g, '');
});

// Phone number formatting
document.getElementById('phone_number').addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/\D/g, '');
});

// QR Payment Functions
function showQRPayment() {
    // Validate form before showing QR
    const paymentMethod = document.getElementById('payment_method').value;
    let isValid = true;
    
    if (paymentMethod === 'card') {
        const cardNumber = document.getElementById('card_number').value;
        const cardName = document.getElementById('card_name').value;
        const expiry = document.getElementById('expiry').value;
        const cvv = document.getElementById('cvv').value;
        
        if (!cardNumber || !cardName || !expiry || !cvv) {
            alert('Please fill in all card details.');
            isValid = false;
        }
    } else if (paymentMethod === 'bank_transfer') {
        const bankName = document.getElementById('bank_name').value;
        const accountName = document.getElementById('account_name').value;
        const accountNumber = document.getElementById('account_number').value;
        
        if (!bankName || !accountName || !accountNumber) {
            alert('Please fill in all bank transfer details.');
            isValid = false;
        }
    } else if (paymentMethod === 'ewallet') {
        const phoneNumber = document.getElementById('phone_number').value;
        const otp = document.getElementById('otp').value;
        
        if (!phoneNumber || !otp) {
            alert('Please fill in phone number and OTP.');
            isValid = false;
        }
    }
    
    if (isValid) {
        document.getElementById('qrOverlay').classList.add('show');
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }
}

function hideQRPayment() {
    document.getElementById('qrOverlay').classList.remove('show');
    document.body.style.overflow = 'auto'; // Restore scrolling
}

function confirmPayment() {
    // Show loading state
    const confirmBtn = document.querySelector('.qr-btn-confirm');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    confirmBtn.disabled = true;
    
    // Simulate payment processing
    setTimeout(() => {
        // Submit the form
        document.getElementById('payment-form').submit();
    }, 2000);
}

// Close QR overlay when clicking outside
document.getElementById('qrOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        hideQRPayment();
    }
});
</script>

<?php include '../includes/footer.php'; ?> 