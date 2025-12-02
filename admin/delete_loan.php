<?php
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$loanId = $data['id'];

try {
    $db = (new Database())->getConnection();
    $query = "DELETE FROM emprunts WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $loanId, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Emprunt supprimé avec succès.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
}