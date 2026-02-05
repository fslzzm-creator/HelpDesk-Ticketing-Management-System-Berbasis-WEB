<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole('admin'); // Hanya admin yang bisa export

// Get filters from URL or POST
$filters = [
    'status' => $_GET['status'] ?? $_POST['status'] ?? '',
    'priority' => $_GET['priority'] ?? $_POST['priority'] ?? '',
    'category' => $_GET['category'] ?? $_POST['category'] ?? '',
    'start_date' => $_GET['start_date'] ?? $_POST['start_date'] ?? '',
    'end_date' => $_GET['end_date'] ?? $_POST['end_date'] ?? ''
];

// Get all tickets for export
$tickets = getAllTicketsForExport($filters);

// Check export type
$export_type = $_GET['type'] ?? $_POST['export_type'] ?? '';

if ($export_type === 'excel') {
    exportToExcel($tickets, 'tickets_report');
} elseif ($export_type === 'pdf') {
    // For PDF, you need TCPDF library
    // exportToPDF($tickets, 'tickets_report');
    generatePDFForPrint($tickets, 'tickets_report');
} else {
    // Show export form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Export Tickets - Ticketing System</title>
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
                    <a href="export.php" class="nav-link active">Export</a>
                    <a href="../logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </nav>

            <div class="card">
                <div class="card-header">
                    <h2>Export Tickets to PDF/Excel</h2>
                    <p>Filter data sebelum export (optional)</p>
                </div>

                <form method="POST" action="" id="exportForm">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="open" <?php echo $filters['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                <option value="in_progress" <?php echo $filters['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="resolved" <?php echo $filters['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                <option value="closed" <?php echo $filters['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="priority">Priority</label>
                            <select id="priority" name="priority" class="form-control">
                                <option value="">All Priority</option>
                                <option value="low" <?php echo $filters['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                <option value="medium" <?php echo $filters['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="high" <?php echo $filters['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                                <option value="urgent" <?php echo $filters['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="category">Category</label>
                            <select id="category" name="category" class="form-control">
                                <option value="">All Categories</option>
                                <option value="Technical" <?php echo $filters['category'] === 'Technical' ? 'selected' : ''; ?>>Technical</option>
                                <option value="Billing" <?php echo $filters['category'] === 'Billing' ? 'selected' : ''; ?>>Billing</option>
                                <option value="General" <?php echo $filters['category'] === 'General' ? 'selected' : ''; ?>>General</option>
                                <option value="Feature Request" <?php echo $filters['category'] === 'Feature Request' ? 'selected' : ''; ?>>Feature Request</option>
                                <option value="Bug Report" <?php echo $filters['category'] === 'Bug Report' ? 'selected' : ''; ?>>Bug Report</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" class="form-control" 
                                   value="<?php echo htmlspecialchars($filters['start_date']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" class="form-control" 
                                   value="<?php echo htmlspecialchars($filters['end_date']); ?>">
                        </div>
                    </div>

                    <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                        <h3>Summary</h3>
                        <p>Total Tickets: <strong><?php echo count($tickets); ?></strong></p>
                        <p>Apply filters above to customize your export data.</p>
                    </div>

                    <div style="display: flex; gap: 15px; justify-content: center; padding-top: 20px;">
                        <button type="submit" name="export_type" value="excel" class="btn btn-success" style="min-width: 150px;">
                            📊 Export to Excel
                        </button>
                        <button type="submit" name="export_type" value="pdf" class="btn btn-danger" style="min-width: 150px;">
                            📄 Export to PDF
                        </button>
                        <a href="list.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>

                <div style="margin-top: 30px; padding: 15px; background: #f0f8ff; border-radius: 5px;">
                    <h4>📋 Preview (First 5 Records)</h4>
                    <?php if (empty($tickets)): ?>
                        <p>No tickets found with current filters.</p>
                    <?php else: ?>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                            <thead>
                                <tr style="background-color: #e9ecef;">
                                    <th style="padding: 8px; border: 1px solid #dee2e6;">ID</th>
                                    <th style="padding: 8px; border: 1px solid #dee2e6;">Title</th>
                                    <th style="padding: 8px; border: 1px solid #dee2e6;">Status</th>
                                    <th style="padding: 8px; border: 1px solid #dee2e6;">Priority</th>
                                    <th style="padding: 8px; border: 1px solid #dee2e6;">Created By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $preview_tickets = array_slice($tickets, 0, 5);
                                foreach ($preview_tickets as $ticket): 
                                ?>
                                    <tr>
                                        <td style="padding: 8px; border: 1px solid #dee2e6;"><?php echo $ticket['ticket_id']; ?></td>
                                        <td style="padding: 8px; border: 1px solid #dee2e6;"><?php echo htmlspecialchars(substr($ticket['title'], 0, 30)) . '...'; ?></td>
                                        <td style="padding: 8px; border: 1px solid #dee2e6;">
                                            <span class="badge status-<?php echo str_replace('_', '-', $ticket['status']); ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 8px; border: 1px solid #dee2e6;">
                                            <span class="badge priority-<?php echo $ticket['priority']; ?>">
                                                <?php echo ucfirst($ticket['priority']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 8px; border: 1px solid #dee2e6;"><?php echo htmlspecialchars($ticket['created_by']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (count($tickets) > 5): ?>
                            <p style="margin-top: 10px; font-style: italic;">... and <?php echo count($tickets) - 5; ?> more records</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
        // Form validation for dates
        document.getElementById('exportForm').addEventListener('submit', function(e) {
            var startDate = document.getElementById('start_date').value;
            var endDate = document.getElementById('end_date').value;
            
            if (startDate && endDate) {
                if (new Date(startDate) > new Date(endDate)) {
                    alert('Start date cannot be later than end date!');
                    e.preventDefault();
                    return false;
                }
            }
            
            // Show loading message
            var exportType = e.submitter.value;
            if (exportType === 'excel' || exportType === 'pdf') {
                alert('Preparing ' + exportType.toUpperCase() + ' export... Please wait.');
            }
        });
        </script>
    </body>
    </html>
    <?php
}
?>
