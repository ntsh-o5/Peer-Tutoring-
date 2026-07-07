<?php
// learner/bookings.php
session_start();
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'learner') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$learner_id = $_SESSION['user_id'];
$message = '';

// Handle actions (Create, Cancel, Reschedule)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    if ($_GET['action'] === 'create') {
    $tutor_id = (int)$_POST['tutor_id'];
    $unit_code = strtoupper(trim($_POST['unit_code']));
    $booking_date = $_POST['booking_date'];

    // Validate against tutor's declared availability
    $day_name = date('l', strtotime($booking_date)); // e.g. "Monday"
    $time_only = date('H:i:s', strtotime($booking_date));

    $availCheck = $pdo->prepare("
        SELECT COUNT(*) FROM tutor_availability 
        WHERE tutor_id = ? AND day_of_week = ? 
        AND ? >= start_time AND ? <= end_time
    ");
    $availCheck->execute([$tutor_id, $day_name, $time_only, $time_only]);

    if ((int)$availCheck->fetchColumn() === 0) {
        $message = "Selected time falls outside this tutor's declared availability. Please choose a time within their listed hours.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO bookings (learner_id, tutor_id, unit_code, booking_date, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$learner_id, $tutor_id, $unit_code, $booking_date]);
            $message = "Session pipeline provisioned successfully! Awaiting tutor approval.";
        } catch (PDOException $e) {
            $message = "Booking transaction error: " . $e->getMessage();
        }
    }

    }
    
    if ($_GET['action'] === 'cancel') {
        $booking_id = (int)$_POST['booking_id'];
        try {
            // Fixed column references from 'id' to 'booking_id'
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND learner_id = ?");
            $stmt->execute([$booking_id, $learner_id]);
            $message = "Session successfully cancelled.";
        } catch (PDOException $e) {
            $message = "Cancellation fault: " . $e->getMessage();
        }
    }

    if ($_GET['action'] === 'reschedule') {
        $booking_id = (int)$_POST['booking_id'];
        $new_date = $_POST['new_booking_date'];
        try {
            // Reset to pending so the tutor re-verifies the newly shifted date matrix
            $stmt = $pdo->prepare("UPDATE bookings SET booking_date = ?, status = 'pending' WHERE booking_id = ? AND learner_id = ?");
            $stmt->execute([$new_date, $booking_id, $learner_id]);
            $message = "Session date metrics shifted flawlessly! Re-sent for approval.";
        } catch (PDOException $e) {
            $message = "Rescheduling fault: " . $e->getMessage();
        }
    }
}

// Fetch general history stack
$history = [];
try {
    // Fixed column reference from b.id to b.booking_id
    $stmt = $pdo->prepare("
    SELECT b.id as booking_id, b.unit_code, b.booking_date, b.status, u.name as tutor_name,
    EXISTS(SELECT 1 FROM payments p WHERE p.booking_id = b.id AND p.status = 'completed') as is_paid
    FROM bookings b 
    JOIN users u ON b.tutor_id = u.id 
    WHERE b.learner_id = ? 
    ORDER BY b.booking_date DESC
");
    $stmt->execute([$learner_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("History logging fetch errors: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Bookings - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { display: flex; min-height: 100vh; background: var(--light); margin: 0; font-family: 'Segoe UI', sans-serif; }
        .main-stage { flex: 1; padding: 40px; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; border: 1px solid var(--border); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #edf2f7; color: var(--navy); }
        .alert { background: #e0f2fe; color: #0369a1; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; }
        .btn-sm { padding: 6px 12px; font-size: 12px; border-radius: 4px; border: none; cursor: pointer; color: white; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="main-stage">
        <div style="margin-bottom: 20px;"><a href="dashboard.php" style="color: var(--slate); text-decoration: none;">← Back to Dashboard</a></div>
        <h2>Your Booking Log Framework</h2>

        <?php if (!empty($message)): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Tutor Name</th>
                    <th>Unit Code</th>
                    <th>Date Matrix Allocation</th>
                    <th>Current Status</th>
                    <th>Runtime Controls</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--slate); font-style: italic;">You haven't requested any tutor sessions yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['tutor_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['unit_code']); ?></td>
                            <td><?php echo date('M d, Y - H:i', strtotime($row['booking_date'])); ?></td>
                            <td>
                                <?php 
                                $status = strtolower($row['status']);
                                $color = 'var(--slate)';
                                if ($status === 'approved' || $status === 'completed') $color = '#10b981';
                                if ($status === 'pending') $color = '#f59e0b';
                                if ($status === 'cancelled' || $status === 'rejected') $color = '#ef4444';
                                ?>
                                <span style="font-weight: 600; text-transform: uppercase; font-size: 11px; color: <?php echo $color; ?>;">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                            </td>
                            <td>
    <?php if ($status === 'pending' || $status === 'approved'): ?>
        <form method="POST" action="bookings.php?action=reschedule" style="display:inline-block;">
            <input type="hidden" name="booking_id" value="<?php echo $row['booking_id']; ?>">
            <input type="datetime-local" name="new_booking_date" required style="padding:4px; font-size:11px;">
            <button type="submit" class="btn-sm" style="background: #64748b;">Shift</button>
        </form>

        <?php if ($status === 'approved' && !$row['is_paid']): ?>
            <button class="btn-sm" style="background: #1D9E75; color: white;"
                onclick="openPayModal(<?php echo $row['booking_id']; ?>, <?php echo $learner_id; ?>)">
                Pay KES 500
            </button>
        <?php elseif ($status === 'approved' && $row['is_paid']): ?>
<span style="color:#10b981; font-size:12px; font-weight:600;">✓ Paid</span>
<a href="receipt.php?booking_id=<?php echo $row['booking_id']; ?>" target="_blank" class="btn-sm" style="background:#0f2038; margin-left:6px;">Receipt</a>
<?php endif; ?>

        <form method="POST" action="bookings.php?action=cancel" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to cancel this peer session?');">
            <input type="hidden" name="booking_id" value="<?php echo $row['booking_id']; ?>">
            <button type="submit" class="btn-sm" style="background: #ef4444;">Cancel</button>
        </form>

    <?php elseif ($status === 'completed'): ?>
        <a href="rating.php?booking_id=<?php echo $row['booking_id']; ?>" class="btn-sm" style="background: #f59e0b;">Leave Review</a>
    <?php else: ?>
        <span style="color: var(--slate); font-size: 12px;">No Actions Available</span>
    <?php endif; ?>
</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<!-- M-Pesa Payment Modal -->
<div id="pay-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center;">
  <div style="background:white; border-radius:8px; padding:30px; width:350px;">
    <h3 style="margin:0 0 10px;">Pay with M-Pesa</h3>
    <p style="color:#666; margin:0 0 20px;">Session fee: <strong>KES 500</strong></p>
    
    <input type="hidden" id="modal-booking-id">
    <input type="hidden" id="modal-user-id">
    
    <label style="font-size:13px; color:#666;">M-Pesa phone number</label>
    <div style="display:flex; margin:6px 0 4px;">
      <span style="background:#f5f5f5; border:1px solid #ddd; border-right:none; padding:8px 10px; border-radius:4px 0 0 4px; font-size:13px;">+254</span>
      <input type="tel" id="modal-phone" placeholder="712 345 678" maxlength="9"
        style="flex:1; border:1px solid #ddd; padding:8px; border-radius:0 4px 4px 0; font-size:13px;">
    </div>
    <p style="font-size:11px; color:#999; margin:0 0 16px;">You will receive a PIN prompt on this number.</p>
    
    <div style="display:flex; gap:8px;">
      <button onclick="submitPayment()" 
        style="flex:1; background:#1D9E75; color:white; border:none; padding:10px; border-radius:4px; cursor:pointer; font-weight:bold;">
        Send Payment Request
      </button>
      <button onclick="closePayModal()" 
        style="background:#f5f5f5; border:1px solid #ddd; padding:10px 16px; border-radius:4px; cursor:pointer;">
        Cancel
      </button>
    </div>
    <p id="modal-status" style="margin:12px 0 0; font-size:13px; text-align:center;"></p>
  </div>
</div>

<script>
function openPayModal(bookingId, userId) {
  document.getElementById('modal-booking-id').value = bookingId;
  document.getElementById('modal-user-id').value = userId;
  document.getElementById('modal-phone').value = '';
  document.getElementById('modal-status').textContent = '';
  document.getElementById('pay-modal').style.display = 'flex';
}

function closePayModal() {
  document.getElementById('pay-modal').style.display = 'none';
}

async function submitPayment() {
  const phone     = document.getElementById('modal-phone').value.trim();
  const bookingId = document.getElementById('modal-booking-id').value;
  const userId    = document.getElementById('modal-user-id').value;

  if (phone.length < 9) {
    document.getElementById('modal-status').textContent = 'Please enter a valid phone number.';
    return;
  }

  document.getElementById('modal-status').textContent = 'Sending request...';

  const formData = new FormData();
  formData.append('phone', phone);
  formData.append('booking_id', bookingId);
  formData.append('user_id', userId);

  const res  = await fetch('../mpesa/pay.php', { method: 'POST', body: formData });
  const data = await res.json();

  document.getElementById('modal-status').textContent = data.message;
  
  if (data.success) {
    document.getElementById('modal-status').style.color = '#1D9E75';
  } else {
    document.getElementById('modal-status').style.color = '#ef4444';
  }
}
</script>
</body>
</html>