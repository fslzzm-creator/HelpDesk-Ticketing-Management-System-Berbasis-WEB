<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$filters = [
    'status' => $_GET['status'] ?? '',
    'priority' => $_GET['priority'] ?? '',
    'category' => $_GET['category'] ?? ''
];

$tickets = getTickets($filters);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Tickets - Ticketing System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <a href="../dashboard.php" class="nav-brand">Ticketing System</a>
            <div class="nav-menu">
                <a href="../dashboard.php" class="nav-link">Dashboard</a>
                <a href="list.php" class="nav-link active">Tickets</a>
                <a href="create.php" class="nav-link">New Ticket</a>
                <a href="../users/profile.php" class="nav-link">Profile</a>
                <a href="../logout.php" class="btn btn-secondary btn-sm">Logout</a>
            </div>
        </nav>

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2>All Tickets</h2>
                <div style="display: flex; gap: 10px;">
                 <a href="create.php" class="btn">+ New Ticket</a>
        
                <!-- Export button hanya untuk Admin -->
                 <?php if ($auth->getUserRole() === 'admin'): ?>
                <a href="export.php" class="btn btn-success">📊 Export</a>
                <?php endif; ?>
            </div>
            </div>
            
            <!-- Filter Form -->
            <form method="GET" action="" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                <select name="status" class="form-control" style="flex: 1; min-width: 150px;">
                    <option value="">All Status</option>
                    <option value="open" <?php echo $filters['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                    <option value="in_progress" <?php echo $filters['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="resolved" <?php echo $filters['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                    <option value="closed" <?php echo $filters['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
                
                <select name="priority" class="form-control" style="flex: 1; min-width: 150px;">
                    <option value="">All Priority</option>
                    <option value="low" <?php echo $filters['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo $filters['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo $filters['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="urgent" <?php echo $filters['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                </select>
                
                <select name="category" class="form-control" style="flex: 1; min-width: 150px;">
                    <option value="">All Categories</option>
                    <option value="Technical" <?php echo $filters['category'] === 'Technical' ? 'selected' : ''; ?>>Technical</option>
                    <option value="Billing" <?php echo $filters['category'] === 'Billing' ? 'selected' : ''; ?>>Billing</option>
                    <option value="General" <?php echo $filters['category'] === 'General' ? 'selected' : ''; ?>>General</option>
                    <option value="Feature Request" <?php echo $filters['category'] === 'Feature Request' ? 'selected' : ''; ?>>Feature Request</option>
                    <option value="Bug Report" <?php echo $filters['category'] === 'Bug Report' ? 'selected' : ''; ?>>Bug Report</option>
                </select>
                
                <button type="submit" class="btn btn-secondary">Filter</button>
                <a href="list.php" class="btn">Clear</a>
            </form>
            
            <?php if (empty($tickets)): ?>
                <p>No tickets found.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Category</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td>#<?php echo $ticket['id']; ?></td>
                                <td>
                                    <a href="view.php?id=<?php echo $ticket['id']; ?>">
                                        <?php echo htmlspecialchars($ticket['title']); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge status-<?php echo str_replace('_', '-', $ticket['status']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge priority-<?php echo $ticket['priority']; ?>">
                                        <?php echo ucfirst($ticket['priority']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($ticket['category']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['created_by_username']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></td>
                                <td>
                                    <a href="view.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm">View</a>
                                    <?php if ($auth->getUserRole() === 'admin' || ($auth->getCurrentUserId() == $ticket['created_by'])): ?>
                                        <a href="edit.php?id=<?php echo $ticket['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
