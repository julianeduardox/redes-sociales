<?php
/**
 * Utility Script: Create Dedicated Test User in Database for API and Login Testing
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

$pdo = Database::getConnection();

$name = 'Usuario de Prueba API';
$email = 'tester@xindro.app';
$plainPassword = 'TesterPassword2026!';
$role = 'tester';
$tenantId = 'tnt_tester_api_01';
$avatarUrl = 'https://ui-avatars.com/api/?name=Tester+API&background=7c3aed&color=fff&size=96';

// Check if user already exists
$stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
$stmtCheck->execute([':email' => $email]);
$existing = $stmtCheck->fetch();

$passwordHash = Auth::hashPassword($plainPassword);

if ($existing) {
    $userId = (int)$existing['id'];
    $stmtUp = $pdo->prepare("
        UPDATE users 
        SET name = :name, 
            password_hash = :hash, 
            role = :role, 
            tenant_id = :tenant_id, 
            avatar_url = :avatar,
            last_login_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");
    $stmtUp->execute([
        ':name' => $name,
        ':hash' => $passwordHash,
        ':role' => $role,
        ':tenant_id' => $tenantId,
        ':avatar' => $avatarUrl,
        ':id' => $userId
    ]);
    echo "✔ Usuario de prueba actualizado (ID: {$userId})\n";
} else {
    $stmtIns = $pdo->prepare("
        INSERT INTO users (tenant_id, name, email, password_hash, role, avatar_url, last_login_at)
        VALUES (:tenant_id, :name, :email, :hash, :role, :avatar, CURRENT_TIMESTAMP)
    ");
    $stmtIns->execute([
        ':tenant_id' => $tenantId,
        ':name' => $name,
        ':email' => $email,
        ':hash' => $passwordHash,
        ':role' => $role,
        ':avatar' => $avatarUrl
    ]);
    $userId = (int)$pdo->lastInsertId();
    echo "✔ Usuario de prueba creado con éxito (ID: {$userId})\n";
}

// Seed initial isolated workspace data for this test tenant
Database::seedInitialData($pdo, $userId);

echo "\n============================================\n";
echo "   CREDENCIALES DE PRUEBA GENERADAS\n";
echo "============================================\n";
echo "Email:       {$email}\n";
echo "Password:    {$plainPassword}\n";
echo "Rol:         {$role}\n";
echo "Tenant ID:   {$tenantId}\n";
echo "User ID:     {$userId}\n";
echo "============================================\n\n";

// Verify login test with Auth::login
$loginTest = Auth::login($email, $plainPassword);
echo "Verificación de Login: " . ($loginTest['success'] ? 'OK (200)' : 'FALLIDO') . "\n";
