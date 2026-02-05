<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$ticket_id = $_GET['id'];

// Check permission (only admin or ticket creator can delete)
$ticket = getTicket($ticket_id);
$role = $auth->getUserRole();
$user_id = $auth->getCurrentUserId();

if (!($role === 'admin' || $ticket['created_by'] == $user_id)) {
    header("Location: list.php");
    exit();
}

// Delete ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    $sql = "DELETE FROM tickets WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ticket_id);
    
    if ($stmt->execute()) {
        header("Location: list.php?deleted=1");
        exit();
    } else {
        $error = "Failed to delete ticket";
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Ticket - Ticketing System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h2>Delete Ticket</h2>
            
            <p>Are you sure you want to delete ticket #<?php echo $ticket_id; ?>?</p>
            <p><strong><?php echo htmlspecialchars($ticket['title']); ?></strong></p>
            
            <form method="POST" action="">
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    <a href="view.php?id=<?php echo $ticket_id; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
