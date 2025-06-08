<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accesso non autorizzato']);
    exit();
}

require_once 'db/common-db.php';

// Get and decode JSON data
$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'ID utente non valido']);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM user WHERE id = ?");
    $stmt->execute([$userId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Utente eliminato con successo']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Utente non trovato']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'utente']);
}