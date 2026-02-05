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

if (!$ticket) {
    header("Location: list.php");
    exit();
}

// Check permission
$role = $auth->getUserRole();
$user_id = $auth->getCurrentUserId();

if (!($role === 'admin' || $ticket['created_by'] == $user_id || $role === 'agent')) {
    header("Location: list.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    $category = $_POST['category'] ?? '';
    $assigned_to = $_POST['assigned_to'] ?? null;
    
    if (empty($title) || empty($description)) {
        $error = "Title and description are required";
    } else {
        $conn = getDBConnection();
        
        $sql = "UPDATE tickets SET title = ?, description = ?, priority = ?, category = ?, assigned_to = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($assigned_to === '') {
            $assigned_to = null;
        }
        
        $stmt->bind_param("ssssii", $title, $description, $priority, $category, $assigned_to, $ticket_id);
        
        if ($stmt->execute()) {
            $success = "Ticket updated successfully";
            $ticket = getTicket($ticket_id); // Refresh ticket data
        } else {
            $error = "Failed to update ticket";
        }
        
        $conn->close();
    }
}

// Get agents for assignment
$agents = getUsersByRole('agent');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Ticket #<?php echo $ticket_id; ?> - Ticketing System</title>
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

        <div class="card">
            <div class="card-header">
                <h2>Edit Ticket #<?php echo $ticket_id; ?></h2>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" class="form-control" 
                           value="<?php echo htmlspecialchars($ticket['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" class="form-control">
                        <option value="">Select Category</option>
                        <option value="Technical" <?php echo $ticket['category'] === 'Technical' ? 'selected' : ''; ?>>Technical</option>
                        <option value="Billing" <?php echo $ticket['category'] === 'Billing' ? 'selected' : ''; ?>>Billing</option>
                        <option value="General" <?php echo $ticket['category'] === 'General' ? 'selected' : ''; ?>>General</option>
                        <option value="Feature Request" <?php echo $ticket['category'] === 'Feature Request' ? 'selected' : ''; ?>>Feature Request</option>
                        <option value="Bug Report" <?php echo $ticket['category'] === 'Bug Report' ? 'selected' : ''; ?>>Bug Report</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority" class="form-control">
                        <option value="low" <?php echo $ticket['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo $ticket['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo $ticket['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="urgent" <?php echo $ticket['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                    </select>
                </div>
                
                <?php if ($role === 'admin' || $role === 'agent'): ?>
                <div class="form-group">
                    <label for="assigned_to">Assign To (Optional)</label>
                    <select id="assigned_to" name="assigned_to" class="form-control">
                        <option value="">Unassigned</option>
                        <?php foreach ($agents as $agent): ?>
                            <option value="<?php echo $agent['id']; ?>" 
                                <?php echo $ticket['assigned_to'] == $agent['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($agent['full_name'] . ' (' . $agent['username'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" class="form-control" rows="6" required><?php echo htmlspecialchars($ticket['description']); ?></textarea>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn">Update Ticket</button>
                    <a href="view.php?id=<?php echo $ticket_id; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
