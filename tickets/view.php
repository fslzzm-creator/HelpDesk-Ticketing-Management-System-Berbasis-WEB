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
$ticket = getTicket($ticket_id);
$replies = getTicketReplies($ticket_id);

if (!$ticket) {
    header("Location: list.php");
    exit();
}

// Check permission
$role = $auth->getUserRole();
$user_id = $auth->getCurrentUserId();

if ($role === 'user' && $ticket['created_by'] != $user_id) {
    header("Location: list.php");
    exit();
}

if ($role === 'agent' && $ticket['assigned_to'] != $user_id && $ticket['created_by'] != $user_id) {
    header("Location: list.php");
    exit();
}

$error = '';
$success = '';

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = $_POST['message'] ?? '';
    
    if (!empty($message)) {
        $conn = getDBConnection();
        $user_id = $auth->getCurrentUserId();
        
        $sql = "INSERT INTO ticket_replies (ticket_id, user_id, message) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $ticket_id, $user_id, $message);
        
        if ($stmt->execute()) {
            // Update ticket status if agent/admin replies
            if ($role === 'admin' || $role === 'agent') {
                $update_sql = "UPDATE tickets SET status = 'in_progress', updated_at = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $ticket_id);
                $update_stmt->execute();
                $ticket['status'] = 'in_progress';
            }
            
            $success = "Reply added successfully";
            header("Location: view.php?id=$ticket_id");
            exit();
        } else {
            $error = "Failed to add reply";
        }
        
        $conn->close();
    } else {
        $error = "Message cannot be empty";
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'] ?? '';
    
    if (in_array($new_status, ['open', 'in_progress', 'resolved', 'closed'])) {
        $conn = getDBConnection();
        $sql = "UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_status, $ticket_id);
        
        if ($stmt->execute()) {
            $ticket['status'] = $new_status;
            $success = "Status updated successfully";
            header("Location: view.php?id=$ticket_id");
            exit();
        }
        
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?php echo $ticket_id; ?> - Ticketing System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <a href="../dashboard.php" class="nav-brand">Ticketing System</a>
            <div class="nav-menu">
                <a href="../dashboard.php" class="nav-link">Dashboard</a>
                <a href="list.php" class="nav-link">Tickets</a>
                <a href="create.php" class="nav-link">New Ticket</a>
                <a href="../users/profile.php" class="nav-link">Profile</a>
                <a href="../logout.php" class="btn btn-secondary btn-sm">Logout</a>
            </div>
        </nav>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="card mb-3">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                <div>
                    <h1>Ticket #<?php echo $ticket['id']; ?>: <?php echo htmlspecialchars($ticket['title']); ?></h1>
                    <p style="color: #666; margin-top: 5px;">
                        Created by <?php echo htmlspecialchars($ticket['created_by_name']); ?> on 
                        <?php echo date('F j, Y \a\t g:i A', strtotime($ticket['created_at'])); ?>
                    </p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <?php if ($role === 'admin' || $role === 'agent'): ?>
                        <form method="POST" action="" style="display: inline;">
                            <select name="status" class="form-control" style="width: auto; display: inline-block;">
                                <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="resolved" <?php echo $ticket['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-sm">Update Status</button>
                        </form>
                    <?php endif; ?>
                    <a href="list.php" class="btn btn-secondary">Back to List</a>
                </div>
            </div>
            
            <div style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                <div>
                    <strong>Status:</strong>
                    <span class="badge status-<?php echo str_replace('_', '-', $ticket['status']); ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                    </span>
                </div>
                <div>
                    <strong>Priority:</strong>
                    <span class="badge priority-<?php echo $ticket['priority']; ?>">
                        <?php echo ucfirst($ticket['priority']); ?>
                    </span>
                </div>
                <div>
                    <strong>Category:</strong> <?php echo htmlspecialchars($ticket['category']); ?>
                </div>
                <div>
                    <strong>Assigned To:</strong> 
                    <?php echo $ticket['assigned_to_name'] ? htmlspecialchars($ticket['assigned_to_name']) : 'Unassigned'; ?>
                </div>
            </div>
            
            <div class="ticket-description" style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                <h3>Description</h3>
                <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($ticket['description']); ?></p>
            </div>
            
            <?php if ($role === 'admin' || $ticket['created_by'] == $user_id): ?>
                <div style="display: flex; gap: 10px;">
                    <a href="edit.php?id=<?php echo $ticket['id']; ?>" class="btn">Edit Ticket</a>
                    <a href="delete.php?id=<?php echo $ticket['id']; ?>" class="btn btn-danger" 
                       onclick="return confirm('Are you sure you want to delete this ticket?');">
                        Delete Ticket
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Replies Section -->
        <div class="card">
            <h2>Conversation</h2>
            
            <div style="margin-bottom: 30px;">
                <?php if (empty($replies)): ?>
                    <p>No replies yet. Be the first to respond.</p>
                <?php else: ?>
                    <?php foreach ($replies as $reply): ?>
                        <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <strong><?php echo htmlspecialchars($reply['full_name']); ?> (<?php echo htmlspecialchars($reply['username']); ?>)</strong>
                                <span style="color: #666;">
                                    <?php echo date('F j, Y \a\t g:i A', strtotime($reply['created_at'])); ?>
                                </span>
                            </div>
                            <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($reply['message']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Reply Form -->
            <form method="POST" action="">
                <div class="form-group">
                    <label for="message">Add Reply</label>
                    <textarea id="message" name="message" class="form-control" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn">Post Reply</button>
            </form>
        </div>
    </div>
</body>
</html>
