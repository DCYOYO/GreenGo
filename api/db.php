<?php
function getPDO()
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $host = 'localhost';
    $dbname = 'carbon_tracker';
    $username = 'root';
    $password = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        debug_log("Database connection failed: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => '資料庫連線失敗']);
        exit;
    }
}

function executeQuery($sql, $params = [], $fetchMode = 'one', $returnType = PDO::FETCH_ASSOC)
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if ($fetchMode === 'one') {
            return $stmt->fetch($returnType) ?: null;
        } elseif ($fetchMode === 'all') {
            return $stmt->fetchAll($returnType) ?: [];
        } elseif ($fetchMode === 'count') {
            return $stmt->rowCount();
        }
        return null;
    } catch (PDOException $e) {
        debug_log("Query failed: " . $e->getMessage() . " | SQL: $sql | Params: " . json_encode($params));
        return null;
    }
}

function executeNonQuery($sql, $params = [])
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        debug_log("Non-query failed: " . $e->getMessage() . " | SQL: $sql | Params: " . json_encode($params));
        return false;
    }
}
