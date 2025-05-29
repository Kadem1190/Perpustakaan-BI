<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Set content type for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to borrow books.']);
    exit;
}

// Check if user is a member
if ($_SESSION['p_role'] !== 'member') {
    echo json_encode(['success' => false, 'message' => 'Only members can borrow books.']);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Get POST data
$bookId = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
$anggotaId = isset($_SESSION['anggota_id']) ? $_SESSION['anggota_id'] : $_SESSION['user_id'];

// Validate input
if ($bookId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID.']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Check if book exists and is available
    $stmt = $pdo->prepare("SELECT book_id, title, author, stock FROM books WHERE book_id = ?");
    $stmt->execute([$bookId]);
    $book = $stmt->fetch();
    
    if (!$book) {
        throw new Exception('Book not found.');
    }
    
    if ($book['stock'] <= 0) {
        throw new Exception('Book is out of stock.');
    }
    
    // Check if user has reached maximum borrowing limit
    $maxBorrowBooks = defined('MAX_BORROW_BOOKS') ? MAX_BORROW_BOOKS : 5;
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_borrowings FROM borrowings 
                          WHERE anggota_id = ? AND status = 'borrowed'");
    $stmt->execute([$anggotaId]);
    $activeBorrowings = $stmt->fetch()['active_borrowings'];
    
    if ($activeBorrowings >= $maxBorrowBooks) {
        throw new Exception("You have reached the maximum number of books you can borrow ($maxBorrowBooks).");
    }
    
    // Check if user already borrowed this book
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM borrowings 
                          WHERE anggota_id = ? AND book_id = ? AND status = 'borrowed'");
    $stmt->execute([$anggotaId, $bookId]);
    $alreadyBorrowed = $stmt->fetch()['count'];
    
    if ($alreadyBorrowed > 0) {
        throw new Exception('You have already borrowed this book.');
    }
    
    // Calculate dates
    $borrowDate = date('Y-m-d');
    $defaultBorrowDays = defined('DEFAULT_BORROW_DAYS') ? DEFAULT_BORROW_DAYS : 14;
    $dueDate = date('Y-m-d', strtotime('+' . $defaultBorrowDays . ' days'));
    
    // Insert borrowing record
    $stmt = $pdo->prepare("INSERT INTO borrowings (anggota_id, book_id, borrow_date, due_date, status) 
                          VALUES (?, ?, ?, ?, 'borrowed')");
    $stmt->execute([$anggotaId, $bookId, $borrowDate, $dueDate]);
    
    $borrowingId = $pdo->lastInsertId();
    
    // Update book stock
    $stmt = $pdo->prepare("UPDATE books SET stock = stock - 1 WHERE book_id = ?");
    $stmt->execute([$bookId]);
    
    // Log activity
    if (function_exists('logActivity')) {
        logActivity('Book borrowed', $_SESSION['user_id'], 'borrowings', 'INSERT', $borrowingId);
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => "Book '{$book['title']}' has been borrowed successfully. Please return it by " . date('F j, Y', strtotime($dueDate)),
        'borrowing_id' => $borrowingId,
        'due_date' => $dueDate
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $pdo->rollback();
    
    // Return error response
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    // Rollback transaction
    $pdo->rollback();
    
    // Return error response
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>