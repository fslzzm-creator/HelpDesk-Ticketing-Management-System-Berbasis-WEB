<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

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
        $created_by = $auth->getCurrentUserId();
        
        $sql = "INSERT INTO tickets (title, description, priority, category, created_by, assigned_to) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        if ($assigned_to === '') {
            $assigned_to = null;
        }
        
        $stmt->bind_param("ssssii", $title, $description, $priority, $category, $created_by, $assigned_to);
        
        if ($stmt->execute()) {
            $ticket_id = $conn->insert_id;
            header("Location: view.php?id=$ticket_id");
            exit();
        } else {
            $error = "Failed to create ticket";
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
    <title>Create Ticket - Ticketing System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <a href="../dashboard.php" class="nav-brand">Ticketing System</a>
            <div class="nav-menu">
                <a href="../dashboard.php" class="nav-link">Dashboard</a>
                <a href="list.php" class="nav-link">Tickets</a>
                <a href="create.php" class="nav-link active">New Ticket</a>
                <a href="../users/profile.php" class="nav-link">Profile</a>
                <a href="../logout.php" class="btn btn-secondary btn-sm">Logout</a>
            </div>
        </nav>

        <div class="card">
            <div class="card-header">
                <h2>Create New Ticket</h2>
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
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" class="form-control">
                        <option value="">Select Category</option>
                        <option value="Technical">Technical</option>
                        <option value="Billing">Billing</option>
                        <option value="General">General</option>
                        <option value="Feature Request">Feature Request</option>
                        <option value="Bug Report">Bug Report</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority" class="form-control">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                
                <?php if ($auth->getUserRole() === 'admin' || $auth->getUserRole() === 'agent'): ?>
                <div class="form-group">
                    <label for="assigned_to">Assign To (Optional)</label>
                    <select id="assigned_to" name="assigned_to" class="form-control">
                        <option value="">Unassigned</option>
                        <?php foreach ($agents as $agent): ?>
                            <option value="<?php echo $agent['id']; ?>">
                                <?php echo htmlspecialchars($agent['full_name'] . ' (' . $agent['username'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" class="form-control" rows="6" required></textarea>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn">Create Ticket</button>
                    <a href="list.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
