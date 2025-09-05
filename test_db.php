<?php
/**
 * Database Test Script for TraderEscape
 * Tests database connection and shows table status
 */

// Include database functions
require_once __DIR__ . '/includes/db_functions.php';

// Set content type
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Test - TraderEscape</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #0f172a; color: #ffffff; }
        .container { max-width: 1200px; margin: 0 auto; }
        .status { padding: 15px; margin: 10px 0; border-radius: 8px; }
        .success { background: rgba(34, 197, 94, 0.2); border: 1px solid rgba(34, 197, 94, 0.3); }
        .error { background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.3); }
        .info { background: rgba(59, 130, 246, 0.2); border: 1px solid rgba(59, 130, 246, 0.3); }
        .warning { background: rgba(245, 158, 11, 0.2); border: 1px solid rgba(245, 158, 11, 0.3); }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; background: rgba(15, 23, 42, 0.8); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(59, 130, 246, 0.2); }
        th { background: rgba(59, 130, 246, 0.1); font-weight: bold; }
        .btn { display: inline-block; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Database Test Results</h1>";

try {
    // Test database connection
    echo "<div class='status info'>Testing database connection...</div>";
    
    if (isDatabaseAvailable()) {
        echo "<div class='status success'>✅ Database connection successful!</div>";
        
        // Get database status
        $dbStatus = getDatabaseStatus();
        
        if ($dbStatus['connected']) {
            echo "<div class='status success'>
                <h3>Database Status</h3>
                <p><strong>Tables:</strong> {$dbStatus['tables']}</p>
                <p><strong>Pages:</strong> {$dbStatus['pages']}</p>
                <p><strong>Tools:</strong> {$dbStatus['tools']}</p>
                <p><strong>Settings:</strong> {$dbStatus['settings']}</p>
            </div>";
            
            // Test specific tables
            $pdo = getDB();
            
            // Test pages table
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM pages");
                $result = $stmt->fetch();
                echo "<div class='status success'>✅ Pages table: {$result['count']} records</div>";
            } catch (Exception $e) {
                echo "<div class='status error'>❌ Pages table error: " . $e->getMessage() . "</div>";
            }
            
            // Test trading_tools table
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM trading_tools");
                $result = $stmt->fetch();
                echo "<div class='status success'>✅ Trading tools table: {$result['count']} records</div>";
            } catch (Exception $e) {
                echo "<div class='status error'>❌ Trading tools table error: " . $e->getMessage() . "</div>";
            }
            
            // Test site_settings table
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM site_settings");
                $result = $stmt->fetch();
                echo "<div class='status success'>✅ Site settings table: {$result['count']} records</div>";
            } catch (Exception $e) {
                echo "<div class='status error'>❌ Site settings table error: " . $e->getMessage() . "</div>";
            }
            
            // Show sample data
            echo "<div class='status info'>
                <h3>Sample Data</h3>";
            
            // Show pages
            try {
                $stmt = $pdo->query("SELECT slug, title, is_published FROM pages LIMIT 5");
                $pages = $stmt->fetchAll();
                
                if ($pages) {
                    echo "<h4>Pages:</h4>
                    <table>
                        <tr><th>Slug</th><th>Title</th><th>Published</th></tr>";
                    foreach ($pages as $page) {
                        echo "<tr>
                            <td>{$page['slug']}</td>
                            <td>{$page['title']}</td>
                            <td>" . ($page['is_published'] ? 'Yes' : 'No') . "</td>
                        </tr>";
                    }
                    echo "</table>";
                }
            } catch (Exception $e) {
                echo "<p>Error loading pages: " . $e->getMessage() . "</p>";
            }
            
            // Show trading tools
            try {
                $stmt = $pdo->query("SELECT name, slug, tool_type, requires_auth FROM trading_tools LIMIT 5");
                $tools = $stmt->fetchAll();
                
                if ($tools) {
                    echo "<h4>Trading Tools:</h4>
                    <table>
                        <tr><th>Name</th><th>Slug</th><th>Type</th><th>Auth Required</th></tr>";
                    foreach ($tools as $tool) {
                        echo "<tr>
                            <td>{$tool['name']}</td>
                            <td>{$tool['slug']}</td>
                            <td>{$tool['tool_type']}</td>
                            <td>" . ($tool['requires_auth'] ? 'Yes' : 'No') . "</td>
                        </tr>";
                    }
                    echo "</table>";
                }
            } catch (Exception $e) {
                echo "<p>Error loading tools: " . $e->getMessage() . "</p>";
            }
            
            echo "</div>";
            
        } else {
            echo "<div class='status error'>❌ Database status error: " . ($dbStatus['error'] ?? 'Unknown error') . "</div>";
        }
        
    } else {
        echo "<div class='status error'>❌ Database connection failed!</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='status error'>❌ Critical error: " . $e->getMessage() . "</div>";
}

echo "<div class='status info'>
    <h3>Actions</h3>
    <a href='./' class='btn'>Go to Homepage</a>
    <a href='./tools.php' class='btn'>Go to Tools</a>
    <a href='./login.php' class='btn'>Go to Login</a>
    <a href='./contact.php' class='btn'>Go to Contact</a>
</div>

</div>
</body>
</html>";
?>
