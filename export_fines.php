<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check authentication and authorization
session_start();
if (!isset($_SESSION['p_role']) || !in_array($_SESSION['p_role'], [p_role_ADMIN, p_role_STAFF])) {
    exit('Unauthorized access');
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="fines_report_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, [
    'Member Name',
    'NIM',
    'Book Title',
    'Due Date',
    'Days Late',
    'Fine Amount (Rp)',
    'Status',
    'Paid Date'
]);

// Get fines data
$query = "SELECT f.*, 
          a.name as member_name, a.nim,
          b.due_date, b.return_date,
          bk.title as book_title
          FROM fines f
          JOIN borrowings b ON f.borrowing_id = b.borrowing_id
          JOIN anggota a ON f.anggota_id = a.anggota_id
          JOIN books bk ON b.book_id = bk.book_id
          ORDER BY f.created_at DESC";

try {
    $fines = $pdo->query($query)->fetchAll();

    // Add data rows
    foreach ($fines as $fine) {
        fputcsv($output, [
            $fine['member_name'],
            $fine['nim'],
            $fine['book_title'],
            formatDate($fine['due_date']),
            $fine['days_overdue'],
            number_format($fine['amount'], 0, ',', '.'),
            ucfirst($fine['status']),
            $fine['paid_date'] ? formatDate($fine['paid_date']) : '-'
        ]);
    }
} catch (PDOException $e) {
    error_log("Error exporting fines: " . $e->getMessage());
    exit('Error generating report');
} finally {
    fclose($output);
}