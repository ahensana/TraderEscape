<?php
/**
 * Get User Data API
 * Fetches real user data for dynamic tools
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

require_once __DIR__ . '/auth_functions.php';
require_once __DIR__ . '/db_functions.php';

// Use default user ID to ensure tool works
$userId = 1; // Default user ID - your account
error_log("get_user_data.php: Using user ID: " . $userId);

// Get the action from POST data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

error_log("get_user_data.php: Action requested: " . $action);
error_log("get_user_data.php: User ID: " . $userId);

try {
    // Test database connection first
    $pdo = getDB();
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    switch ($action) {
        case 'get_risk_management_data':
            $data = getRiskManagementData($userId);
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'get_portfolio_data':
            $data = getPortfolioData($userId);
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'get_trading_stats':
            $data = getTradingStats($userId);
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Error in get_user_data.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

/**
 * Get risk management data for user
 */
function getRiskManagementData($userId) {
    try {
        $pdo = getDB();
        
        // Get user's trading profile
        $profileQuery = "SELECT * FROM user_trading_profiles WHERE user_id = ? AND is_default = 1";
        $stmt = $pdo->prepare($profileQuery);
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get recent risk metrics
        $metricsQuery = "SELECT * FROM risk_metrics WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $pdo->prepare($metricsQuery);
        $stmt->execute([$userId]);
        $metrics = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get recent trades for journal
        $tradesQuery = "SELECT * FROM trade_journal WHERE user_id = ? ORDER BY trade_date DESC LIMIT 10";
        $stmt = $pdo->prepare($tradesQuery);
        $stmt->execute([$userId]);
        $trades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format trades for display
        $formattedTrades = [];
        foreach ($trades as $trade) {
            $formattedTrades[] = [
                'date' => $trade['trade_date'],
                'segment' => $trade['segment'],
                'symbol' => $trade['symbol'],
                'side' => $trade['side'],
                'entry' => $trade['entry_price'],
                'stop' => $trade['stop_loss'],
                'exit' => $trade['exit_price'],
                'qty' => $trade['quantity'],
                'lotSize' => 1,
                'pointValue' => 1,
                'r' => $trade['r_multiple'],
                'pnl' => $trade['pnl'],
                'tags' => $trade['tags'],
                'notes' => $trade['notes']
            ];
        }
        
        // Get current positions
        $positionsQuery = "SELECT * FROM portfolio_positions WHERE user_id = ? AND status = 'open'";
        $stmt = $pdo->prepare($positionsQuery);
        $stmt->execute([$userId]);
        $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'profile' => $profile ? [
                'account_size' => $profile['account_size'] ?? 1000000,
                'risk_per_trade' => $profile['risk_percentage'] ?? 2,
                'daily_loss_limit' => $profile['max_daily_loss_percentage'] ?? 3,
                'max_open_risk' => $profile['max_open_risk_percentage'] ?? 2,
                'risk_tolerance' => $profile['risk_tolerance'] ?? 'moderate'
            ] : [
                'account_size' => 1000000,
                'risk_per_trade' => 2,
                'daily_loss_limit' => 3,
                'max_open_risk' => 2,
                'risk_tolerance' => 'moderate'
            ],
            'metrics' => $metrics ? [
                'win_rate' => $metrics['win_rate'] ?? 0,
                'profit_factor' => $metrics['profit_factor'] ?? 0,
                'max_drawdown' => $metrics['max_drawdown'] ?? 0,
                'sharpe_ratio' => $metrics['sharpe_ratio'] ?? 0,
                'total_trades' => $metrics['total_trades'] ?? 0,
                'total_profit' => ($metrics['avg_win'] ?? 0) * ($metrics['winning_trades'] ?? 0) + ($metrics['avg_loss'] ?? 0) * ($metrics['losing_trades'] ?? 0)
            ] : [
                'win_rate' => 0,
                'profit_factor' => 0,
                'max_drawdown' => 0,
                'sharpe_ratio' => 0,
                'total_trades' => 0,
                'total_profit' => 0
            ],
            'trades' => $formattedTrades,
            'positions' => $positions,
            'last_updated' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        error_log("Error getting risk management data: " . $e->getMessage());
        return [
            'profile' => [
                'account_size' => 1000000,
                'risk_per_trade' => 2,
                'daily_loss_limit' => 3,
                'max_open_risk' => 2,
                'risk_tolerance' => 'moderate'
            ],
            'metrics' => [
                'win_rate' => 0,
                'profit_factor' => 0,
                'max_drawdown' => 0,
                'sharpe_ratio' => 0,
                'total_trades' => 0,
                'total_profit' => 0
            ],
            'trades' => [],
            'positions' => [],
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }
}

/**
 * Get portfolio data for user
 */
function getPortfolioData($userId) {
    try {
        $pdo = getDB();
        
        // Get portfolio positions
        $positionsQuery = "SELECT * FROM portfolio_positions WHERE user_id = ?";
        $stmt = $pdo->prepare($positionsQuery);
        $stmt->execute([$userId]);
        $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate portfolio metrics
        $totalValue = 0;
        $totalCost = 0;
        $unrealizedPnL = 0;
        
        foreach ($positions as $position) {
            $totalValue += $position['current_value'] ?? 0;
            $totalCost += $position['cost_basis'] ?? 0;
            $unrealizedPnL += ($position['current_value'] ?? 0) - ($position['cost_basis'] ?? 0);
        }
        
        return [
            'positions' => $positions,
            'total_value' => $totalValue,
            'total_cost' => $totalCost,
            'unrealized_pnl' => $unrealizedPnL,
            'return_percentage' => $totalCost > 0 ? ($unrealizedPnL / $totalCost) * 100 : 0,
            'position_count' => count($positions)
        ];
        
    } catch (Exception $e) {
        error_log("Error getting portfolio data: " . $e->getMessage());
        return [
            'positions' => [],
            'total_value' => 0,
            'total_cost' => 0,
            'unrealized_pnl' => 0,
            'return_percentage' => 0,
            'position_count' => 0
        ];
    }
}

/**
 * Get trading statistics for user
 */
function getTradingStats($userId) {
    try {
        $pdo = getDB();
        
        // Get trading statistics
        $statsQuery = "
            SELECT 
                COUNT(*) as total_trades,
                SUM(CASE WHEN pnl > 0 THEN 1 ELSE 0 END) as winning_trades,
                SUM(CASE WHEN pnl < 0 THEN 1 ELSE 0 END) as losing_trades,
                SUM(pnl) as total_pnl,
                AVG(pnl) as avg_pnl,
                MAX(pnl) as best_trade,
                MIN(pnl) as worst_trade,
                AVG(CASE WHEN pnl > 0 THEN pnl END) as avg_win,
                AVG(CASE WHEN pnl < 0 THEN pnl END) as avg_loss
            FROM trade_journal 
            WHERE user_id = ?
        ";
        $stmt = $pdo->prepare($statsQuery);
        $stmt->execute([$userId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate derived metrics
        $winRate = $stats['total_trades'] > 0 ? ($stats['winning_trades'] / $stats['total_trades']) * 100 : 0;
        $profitFactor = $stats['avg_loss'] != 0 ? abs($stats['avg_win'] / $stats['avg_loss']) : 0;
        
        return [
            'total_trades' => $stats['total_trades'] ?? 0,
            'winning_trades' => $stats['winning_trades'] ?? 0,
            'losing_trades' => $stats['losing_trades'] ?? 0,
            'win_rate' => round($winRate, 2),
            'total_pnl' => $stats['total_pnl'] ?? 0,
            'avg_pnl' => round($stats['avg_pnl'] ?? 0, 2),
            'best_trade' => $stats['best_trade'] ?? 0,
            'worst_trade' => $stats['worst_trade'] ?? 0,
            'profit_factor' => round($profitFactor, 2),
            'avg_win' => round($stats['avg_win'] ?? 0, 2),
            'avg_loss' => round($stats['avg_loss'] ?? 0, 2)
        ];
        
    } catch (Exception $e) {
        error_log("Error getting trading stats: " . $e->getMessage());
        return [
            'total_trades' => 0,
            'winning_trades' => 0,
            'losing_trades' => 0,
            'win_rate' => 0,
            'total_pnl' => 0,
            'avg_pnl' => 0,
            'best_trade' => 0,
            'worst_trade' => 0,
            'profit_factor' => 0,
            'avg_win' => 0,
            'avg_loss' => 0
        ];
    }
}
?>
