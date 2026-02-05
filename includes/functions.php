<?php
require_once __DIR__ . '/../config/database.php';
require_once 'auth.php';

$auth = new Auth();

function getTickets($filters = []) {
    $conn = getDBConnection();
    $user_id = $_SESSION['user_id'] ?? null;
    $role = $_SESSION['role'] ?? null;
    
    $sql = "SELECT t.*, 
                   u1.username as created_by_username, 
                   u2.username as assigned_to_username 
            FROM tickets t
            LEFT JOIN users u1 ON t.created_by = u1.id
            LEFT JOIN users u2 ON t.assigned_to = u2.id
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    // Filter by role
    if ($role === 'user') {
        $sql .= " AND t.created_by = ?";
        $params[] = $user_id;
        $types .= "i";
    } elseif ($role === 'agent') {
        $sql .= " AND (t.assigned_to = ? OR t.assigned_to IS NULL OR t.created_by = ?)";
        $params[] = $user_id;
        $params[] = $user_id;
        $types .= "ii";
    }
    
    // Additional filters
    if (!empty($filters['status'])) {
        $sql .= " AND t.status = ?";
        $params[] = $filters['status'];
        $types .= "s";
    }
    
    if (!empty($filters['priority'])) {
        $sql .= " AND t.priority = ?";
        $params[] = $filters['priority'];
        $types .= "s";
    }
    
    if (!empty($filters['category'])) {
        $sql .= " AND t.category = ?";
        $params[] = $filters['category'];
        $types .= "s";
    }
    
    $sql .= " ORDER BY t.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $tickets = $result->fetch_all(MYSQLI_ASSOC);
    
    $conn->close();
    return $tickets;
}

function getTicket($id) {
    $conn = getDBConnection();
    
    $sql = "SELECT t.*, 
                   u1.username as created_by_username, 
                   u1.full_name as created_by_name,
                   u2.username as assigned_to_username,
                   u2.full_name as assigned_to_name
            FROM tickets t
            LEFT JOIN users u1 ON t.created_by = u1.id
            LEFT JOIN users u2 ON t.assigned_to = u2.id
            WHERE t.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ticket = $result->fetch_assoc();
    
    $conn->close();
    return $ticket;
}

function getTicketReplies($ticket_id) {
    $conn = getDBConnection();
    
    $sql = "SELECT tr.*, u.username, u.full_name
            FROM ticket_replies tr
            JOIN users u ON tr.user_id = u.id
            WHERE tr.ticket_id = ?
            ORDER BY tr.created_at ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $replies = $result->fetch_all(MYSQLI_ASSOC);
    
    $conn->close();
    return $replies;
}

function getUsersByRole($role = null) {
    $conn = getDBConnection();
    
    $sql = "SELECT id, username, full_name, email, role FROM users";
    
    if ($role) {
        $sql .= " WHERE role = ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($role) {
        $stmt->bind_param("s", $role);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    
    $conn->close();
    return $users;
}

function getUser($id) {
    $conn = getDBConnection();
    
    $sql = "SELECT id, username, full_name, email, role, created_at FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $conn->close();
    return $user;
}

function updateProfile($user_id, $full_name, $email) {
    $conn = getDBConnection();
    
    $sql = "UPDATE users SET full_name = ?, email = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $full_name, $email, $user_id);
    
    $success = $stmt->execute();
    $conn->close();
    
    return $success;
}

function getTicketStats($user_id = null, $role = null) {
    $conn = getDBConnection();
    
    $stats = [
        'total' => 0,
        'open' => 0,
        'in_progress' => 0,
        'resolved' => 0,
        'closed' => 0
    ];
    
    $sql = "SELECT status, COUNT(*) as count FROM tickets";
    
    if ($role === 'user') {
        $sql .= " WHERE created_by = ? GROUP BY status";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
    } else {
        $sql .= " GROUP BY status";
        $stmt = $conn->prepare($sql);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $stats[$row['status']] = $row['count'];
        $stats['total'] += $row['count'];
    }
    
    $conn->close();
    return $stats;
}

function getAllTicketsForExport($filters = []) {
    $conn = getDBConnection();
    
    $sql = "SELECT 
                t.id as ticket_id,
                t.title,
                t.description,
                t.priority,
                t.status,
                t.category,
                t.created_at,
                t.updated_at,
                u1.username as created_by,
                u1.full_name as created_by_name,
                u2.username as assigned_to,
                u2.full_name as assigned_to_name
            FROM tickets t
            LEFT JOIN users u1 ON t.created_by = u1.id
            LEFT JOIN users u2 ON t.assigned_to = u2.id
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    // Apply filters
    if (!empty($filters['status'])) {
        $sql .= " AND t.status = ?";
        $params[] = $filters['status'];
        $types .= "s";
    }
    
    if (!empty($filters['priority'])) {
        $sql .= " AND t.priority = ?";
        $params[] = $filters['priority'];
        $types .= "s";
    }
    
    if (!empty($filters['category'])) {
        $sql .= " AND t.category = ?";
        $params[] = $filters['category'];
        $types .= "s";
    }
    
    if (!empty($filters['start_date'])) {
        $sql .= " AND DATE(t.created_at) >= ?";
        $params[] = $filters['start_date'];
        $types .= "s";
    }
    
    if (!empty($filters['end_date'])) {
        $sql .= " AND DATE(t.created_at) <= ?";
        $params[] = $filters['end_date'];
        $types .= "s";
    }
    
    $sql .= " ORDER BY t.created_at DESC";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }
    
    $tickets = $result->fetch_all(MYSQLI_ASSOC);
    $conn->close();
    
    return $tickets;
}

function exportToExcel($tickets, $filename = 'tickets_export') {
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Start output
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '</head>';
    echo '<body>';
    
    echo '<table border="1">';
    
    // Header row
    echo '<tr style="background-color: #f2f2f2;">';
    echo '<th>ID</th>';
    echo '<th>Title</th>';
    echo '<th>Description</th>';
    echo '<th>Priority</th>';
    echo '<th>Status</th>';
    echo '<th>Category</th>';
    echo '<th>Created By</th>';
    echo '<th>Assigned To</th>';
    echo '<th>Created Date</th>';
    echo '<th>Last Updated</th>';
    echo '</tr>';
    
    // Data rows
    foreach ($tickets as $ticket) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($ticket['ticket_id']) . '</td>';
        echo '<td>' . htmlspecialchars($ticket['title']) . '</td>';
        echo '<td>' . htmlspecialchars(substr($ticket['description'], 0, 100)) . '...</td>';
        echo '<td>' . htmlspecialchars(ucfirst($ticket['priority'])) . '</td>';
        echo '<td>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $ticket['status']))) . '</td>';
        echo '<td>' . htmlspecialchars($ticket['category']) . '</td>';
        echo '<td>' . htmlspecialchars($ticket['created_by_name'] . ' (' . $ticket['created_by'] . ')') . '</td>';
        echo '<td>' . htmlspecialchars($ticket['assigned_to_name'] ?? 'Unassigned') . '</td>';
        echo '<td>' . date('Y-m-d H:i', strtotime($ticket['created_at'])) . '</td>';
        echo '<td>' . date('Y-m-d H:i', strtotime($ticket['updated_at'])) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body>';
    echo '</html>';
    
    exit();
}


function generatePDFForPrint($tickets, $filename = 'tickets_report') {
    // JANGAN set header PDF di sini!
    // Biarkan browser menampilkan HTML dulu, user bisa print sebagai PDF
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo $filename; ?></title>
        <style>
            @media print {
                @page {
                    size: A4 landscape;
                    margin: 0.5cm;
                }
                body {
                    font-family: Arial, sans-serif;
                    font-size: 10px;
                    margin: 0;
                    padding: 0;
                }
                .no-print {
                    display: none !important;
                }
            }
            
            body {
                font-family: Arial, sans-serif;
                font-size: 10px;
                margin: 20px;
            }
            
            .header {
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #000;
                padding-bottom: 10px;
            }
            
            h1 {
                margin: 0;
                font-size: 20px;
            }
            
            .report-info {
                font-size: 11px;
                color: #666;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
            }
            
            th {
                background-color: #333;
                color: white;
                padding: 8px;
                text-align: left;
                border: 1px solid #ddd;
            }
            
            td {
                padding: 6px;
                border: 1px solid #ddd;
            }
            
            .print-btn {
                background: #4CAF50;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                margin: 20px auto;
                display: block;
            }
        </style>
    </head>
    <body>
        <button class="close-button no-print" onclick="window.location.href='export.php'">
            ← Back
        </button>
        <button class="print-btn no-print" onclick="window.print()">
            🖨️ Click to Print / Save as PDF
        </button> 
        
        
        <div class="header">
            <h1>TICKETS REPORT</h1>
            <div class="report-info">
                Generated: <?php echo date('Y-m-d H:i:s'); ?> | 
                Total: <?php echo count($tickets); ?> tickets
            </div>
        </div>
        
        <table>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Category</th>
                <th>Created By</th>
                <th>Date</th>
            </tr>
            <?php foreach ($tickets as $ticket): ?>
            <tr>
                <td>#<?php echo $ticket['ticket_id']; ?></td>
                <td><?php echo htmlspecialchars(substr($ticket['title'], 0, 40)); ?></td>
                <td><?php echo strtoupper($ticket['priority']); ?></td>
                <td><?php echo strtoupper(str_replace('_', ' ', $ticket['status'])); ?></td>
                <td><?php echo htmlspecialchars($ticket['category']); ?></td>
                <td><?php echo htmlspecialchars($ticket['created_by']); ?></td>
                <td><?php echo date('Y-m-d', strtotime($ticket['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <script>
            // Auto print after 1 second
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 1000);
            };
        </script>
    </body>
    </html>
    <?php
    exit();
}
?>
