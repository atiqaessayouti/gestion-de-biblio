<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT id, nom, email, role FROM utilisateurs WHERE role = 'utilisateur' ORDER BY nom";
    $stmt = $db->query($query);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($members);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur : ' . $e->getMessage()]);
}
?>