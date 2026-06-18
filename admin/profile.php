<?php
// =========================================================================
// 1. SESSION, AUTHENTICATION & PROFILE CONTROLLER LOGIC
// =========================================================================
// session_start();
// Placeholder for Database Connection (e.g., include('config/db.php');)

// Mock static profile data (In production, load this directly from your DB users table)
$profile_data = [
    'name'  => 'Admin User',
    'email' => 'admin@peertutor.com'
];

$success_message = "";
$error_message = "";

// Handle Profile Update Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and read input fields safely
    $updated_name  = htmlspecialchars(trim($_POST['name']));
    $updated_email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

    // Basic Backend Field Validation Check
    if (empty($updated_name) || empty($updated_email)) {
        $error_message = "All profile fields are required.";
    } elseif (!filter_var($updated_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please provide a valid email address.";
    } else {
        // Database write operations connect here:
        // $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        // if ($stmt->execute([$updated_name, $updated_email, $_SESSION['user_id']])) {
            
        // Update local mock array context for immediate feedback display
        $profile_data['name'] = $updated_name;
        $profile_data['email'] = $updated_email;
        $success_message = "Your profile details have been successfully updated!";
        
        // }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

   <?php include('../includes/sidebar.php'); ?>

    <div class="main-content">

        <header>
            <h1>Admin Profile</h1>
        </header>

        <?php if (!empty($success_message)): ?>
            <div class="status-badge approved" style="padding: 12px; margin-bottom: 20px; display: block; border-radius: 4px;">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="status-badge rejected" style="padding: 12px; margin-bottom: 20px; display: block; border-radius: 4px;">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <section class="cards">

            <div class="card">
                <h3>Name</h3>
                <p><?php echo $profile_data['name']; ?></p>
            </div>

            <div class="card">
                <h3>Email Address</h3>
                <p><?php echo $profile_data['email']; ?></p>
            </div>

        </section>

        <div class="table-container">
            <h2 style="margin-bottom: 20px;">Update Profile Details</h2>
            
            <form method="POST" action="profile.php">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Full Name:</label>
                    <input 
                        type="text" 
                        name="name" 
                        placeholder="Enter full name" 
                        value="<?php echo $profile_data['name']; ?>" 
                        style="width: 100%; max-width: 400px; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"
                        required
                    >
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Email Address:</label>
                    <input 
                        type="email" 
                        name="email" 
                        placeholder="Enter email address" 
                        value="<?php echo $profile_data['email']; ?>" 
                        style="width: 100%; max-width: 400px; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"
                        required
                    >
                </div>

                <button type="submit" class="approve-btn">Update Profile</button>
            </form>
        </div>

    </div>

</body>
</html>