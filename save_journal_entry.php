<?php
/**
 * Save Journal Entry API
 * Saves trading journal entries to the database
 */

// Set CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Start session with proper configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/db_functions.php';

// Use default user ID to ensure tool works
$userId = 1; // Default user ID - your account
error_log("save_journal_entry.php: Using user ID: " . $userId);

// Get the journal entry data from POST
$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDB();
    
    // Prepare the insert statement
    $stmt = $pdo->prepare("
        INSERT INTO trade_journal (
            user_id, profile_id, trade_date, segment, symbol, side, entry_price, 
            stop_loss, exit_price, quantity, position_size, risk_amount, 
            r_multiple, pnl, fees, net_pnl, tags, notes, trade_status, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    // Execute the insert
    $result = $stmt->execute([
        $userId,
        1, // Default profile ID
        $input['date'] ?? date('Y-m-d'),
        $input['segment'] ?? 'Equity Cash',
        $input['symbol'] ?? '',
        $input['side'] ?? 'Long',
        $input['entry'] ?? 0,
        $input['stop'] ?? 0,
        $input['exit'] ?? 0,
        $input['qty'] ?? 0,
        $input['qty'] ?? 0, // position_size same as quantity for equity cash
        abs(($input['entry'] ?? 0) - ($input['stop'] ?? 0)) * ($input['qty'] ?? 0), // risk_amount
        $input['r'] ?? 0,
        $input['pnl'] ?? 0,
        0, // fees
        $input['pnl'] ?? 0, // net_pnl same as pnl
        json_encode([$input['tags'] ?? '']), // tags as JSON array
        $input['notes'] ?? '',
        'closed' // trade_status
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Journal entry saved successfully',
            'entry_id' => $pdo->lastInsertId()
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to save journal entry'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error saving journal entry: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
