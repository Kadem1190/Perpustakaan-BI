<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Set content type for JSON response
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first.']);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Get the action from POST
$action = $_POST['action'] ?? '';

try {
    $pdo->beginTransaction();

    switch ($action) {
        case 'borrow':
            // Check if user is a member
            if ($_SESSION['p_role'] !== 'member') {
                echo json_encode(['success' => false, 'message' => 'Only members can borrow books.']);
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

            break;

        case 'return_book':
            if (!isset($_POST['borrow_id'])) {
                throw new Exception("Missing borrowing ID");
            }

            $borrowId = intval($_POST['borrow_id']);
            $returnDate = date('Y-m-d');

            // Get borrowing details
            $stmt = $pdo->prepare("
                SELECT b.*, bk.title as book_title 
                FROM borrowings b
                JOIN books bk ON b.book_id = bk.book_id
                WHERE b.borrowing_id = ? AND b.return_date IS NULL
            ");
            $stmt->execute([$borrowId]);
            $borrowing = $stmt->fetch();

            if (!$borrowing) {
                throw new Exception("Borrowing record not found or already returned");
            }

            // Calculate if overdue
            $dueDate = new DateTime($borrowing['due_date']);
            $returnDateObj = new DateTime($returnDate);
            $daysOverdue = 0;
            $fineAmount = 0;

            if ($returnDateObj > $dueDate) {
                $daysOverdue = $returnDateObj->diff($dueDate)->days;
                // Get fine amount from settings
                $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'fine_per_day'");
                $finePerDay = $stmt->fetchColumn() ?: 5000; // Default to 5000 if not set
                $fineAmount = $daysOverdue * $finePerDay;
            }

            // Update borrowing record
            $stmt = $pdo->prepare("
                UPDATE borrowings 
                SET return_date = ?, 
                    status = 'returned',
                    updated_at = NOW()
                WHERE borrowing_id = ?
            ");
            $stmt->execute([$returnDate, $borrowId]);

            // Create fine record if overdue
            if ($fineAmount > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO fines (
                        borrowing_id, 
                        anggota_id, 
                        amount, 
                        days_overdue, 
                        status,
                        created_at
                    ) VALUES (?, ?, ?, ?, 'unpaid', NOW())
                ");
                $stmt->execute([
                    $borrowId,
                    $borrowing['anggota_id'],
                    $fineAmount,
                    $daysOverdue
                ]);

                $message = "Book '{$borrowing['book_title']}' returned successfully. Fine of Rp " .
                    number_format($fineAmount, 0, ',', '.') . " has been recorded.";
            } else {
                $message = "Book '{$borrowing['book_title']}' returned successfully with no fine.";
            }

            // Update book stock
            $stmt = $pdo->prepare("UPDATE books SET stock = stock + 1 WHERE book_id = ?");
            $stmt->execute([$borrowing['book_id']]);

            $response = ['success' => true, 'message' => $message];
            break;

        case 'pay_fine':
            // Check if user has permission
            if (!in_array($_SESSION['p_role'], [p_role_ADMIN, p_role_STAFF])) {
                throw new Exception("Unauthorized access");
            }

            if (!isset($_POST['fine_id'])) {
                throw new Exception("Missing fine ID");
            }

            $fineId = intval($_POST['fine_id']);

            // Check if fine exists and is unpaid
            $stmt = $pdo->prepare("
                SELECT f.*, b.book_id, m.name as member_name 
                FROM fines f
                JOIN borrowings b ON f.borrowing_id = b.borrowing_id
                JOIN anggota m ON f.anggota_id = m.anggota_id
                WHERE f.fine_id = ? AND f.status = 'unpaid'
            ");
            $stmt->execute([$fineId]);
            $fine = $stmt->fetch();

            if (!$fine) {
                throw new Exception("Fine not found or already paid");
            }

            // Update fine status
            $stmt = $pdo->prepare("
                UPDATE fines 
                SET status = 'paid',
                    paid_date = CURRENT_DATE(),
                    updated_by = ?,
                    updated_at = NOW()
                WHERE fine_id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $fineId]);

            $response = [
                'success' => true,
                'message' => "Fine for {$fine['member_name']} has been marked as paid.",
                'fine_amount' => $fine['amount']
            ];
            break;

        default:
            throw new Exception("Invalid action");
    }

    $pdo->commit();
    echo json_encode($response);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}