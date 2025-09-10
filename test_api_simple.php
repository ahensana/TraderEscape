<?php
// Simple test for the API endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    require_once 'includes/db_functions.php';
    
    $userId = 1;
    
    // Get trading profiles
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM user_trading_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get risk metrics
    $stmt = $db->prepare("SELECT * FROM risk_metrics WHERE user_id = ? ORDER BY calculation_date DESC LIMIT 1");
    $stmt->execute([$userId]);
    $riskMetrics = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent trades
    $stmt = $db->prepare("SELECT * FROM trade_journal WHERE user_id = ? ORDER BY trade_date DESC LIMIT 5");
    $stmt->execute([$userId]);
    $trades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get portfolio positions
    $stmt = $db->prepare("SELECT * FROM portfolio_positions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [
        'success' => true,
        'data' => [
            'account_size' => $profiles[0]['account_size'] ?? 100000,
            'risk_per_trade' => $profiles[0]['risk_percentage'] ?? 2.0,
            'monthly_target' => $profiles[0]['monthly_target'] ?? 10000,
            'win_rate' => $riskMetrics['win_rate'] ?? 65.0,
            'profit_factor' => $riskMetrics['profit_factor'] ?? 2.1,
            'total_trades' => $riskMetrics['total_trades'] ?? 25,
            'total_profit' => $riskMetrics['total_equity'] ?? 1000000,
            'recent_trades' => $trades,
            'portfolio_positions' => $positions
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
