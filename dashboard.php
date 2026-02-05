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

function exportToPDF($tickets, $filename = 'tickets_export') {
    require_once __DIR__ . '/../vendor/autoload.php'; // Untuk TCPDF
    
    // Create new PDF document
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Ticketing System');
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle('Tickets Report');
    $pdf->SetSubject('Tickets Export');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Add a page
    $pdf->AddPage();
    
    // Title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Tickets Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
    $pdf->Ln(10);
    
    // Table header
    $pdf->SetFont('helvetica', 'B', 9);
    $header = array('ID', 'Title', 'Priority', 'Status', 'Category', 'Created By', 'Created Date');
    $widths = array(10, 60, 20, 20, 25, 40, 30);
    
    // Header row
    for($i = 0; $i < count($header); $i++) {
        $pdf->Cell($widths[$i], 7, $header[$i], 1, 0, 'C');
    }
    $pdf->Ln();
    
    // Table data
    $pdf->SetFont('helvetica', '', 8);
    foreach ($tickets as $ticket) {
        $pdf->Cell($widths[0], 6, $ticket['ticket_id'], 'LR', 0, 'C');
        $pdf->Cell($widths[1], 6, substr($ticket['title'], 0, 40), 'LR', 0, 'L');
        $pdf->Cell($widths[2], 6, ucfirst($ticket['priority']), 'LR', 0, 'C');
        $pdf->Cell($widths[3], 6, ucfirst(str_replace('_', ' ', $ticket['status'])), 'LR', 0, 'C');
        $pdf->Cell($widths[4], 6, $ticket['category'], 'LR', 0, 'C');
        $pdf->Cell($widths[5], 6, $ticket['created_by'], 'LR', 0, 'L');
        $pdf->Cell($widths[6], 6, date('Y-m-d', strtotime($ticket['created_at'])), 'LR', 0, 'C');
        $pdf->Ln();
    }
    
    // Closing line
    $pdf->Cell(array_sum($widths), 0, '', 'T');
    
    // Output PDF
    $pdf->Output($filename . '_' . date('Y-m-d') . '.pdf', 'D');
    exit();
}

// Simple PDF Export (tanpa TCPDF)
function exportToPDFSimple($tickets, $filename = 'tickets_export') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Y-m-d') . '.pdf"');
    
    $html = '<html>';
    $html .= '<head>';
    $html .= '<style>';
    $html .= 'body { font-family: Arial, sans-serif; }';
    $html .= 'h1 { text-align: center; color: #333; }';
    $html .= 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
    $html .= 'th { background-color: #f2f2f2; padding: 8px; text-align: left; border: 1px solid #ddd; }';
    $html .= 'td { padding: 8px; border: 1px solid #ddd; }';
    $html .= '.header { text-align: center; margin-bottom: 20px; }';
    $html .= '.date { color: #666; font-size: 12px; }';
    $html .= '</style>';
    $html .= '</head>';
    $html .= '<body>';
    
    $html .= '<div class="header">';
    $html .= '<h1>Tickets Report</h1>';
    $html .= '<div class="date">Generated on: ' . date('Y-m-d H:i:s') . '</div>';
    $html .= '</div>';
    
    $html .= '<table>';
    $html .= '<tr>';
    $html .= '<th>ID</th>';
    $html .= '<th>Title</th>';
    $html .= '<th>Priority</th>';
    $html .= '<th>Status</th>';
    $html .= '<th>Category</th>';
    $html .= '<th>Created By</th>';
    $html .= '<th>Created Date</th>';
    $html .= '</tr>';
    
    foreach ($tickets as $ticket) {
        $html .= '<tr>';
        $html .= '<td>' . $ticket['ticket_id'] . '</td>';
        $html .= '<td>' . htmlspecialchars($ticket['title']) . '</td>';
        $html .= '<td>' . ucfirst($ticket['priority']) . '</td>';
        $html .= '<td>' . ucfirst(str_replace('_', ' ', $ticket['status'])) . '</td>';
        $html .= '<td>' . htmlspecialchars($ticket['category']) . '</td>';
        $html .= '<td>' . htmlspecialchars($ticket['created_by']) . '</td>';
        $html .= '<td>' . date('Y-m-d', strtotime($ticket['created_at'])) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    $html .= '</body>';
    $html .= '</html>';
    
    // For simple PDF generation (requires mPDF or similar library)
    // For now, just output HTML that can be printed as PDF
    echo $html;
    exit();
}
?>
