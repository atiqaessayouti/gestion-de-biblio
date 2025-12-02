<?php
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$loanId = $data['id'];
$newReturnDate = $data['date_retour'];

try {
    $db = (new Database())->getConnection();
    $query = "UPDATE emprunts SET date_retour_prevue = :date_retour WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':date_retour', $newReturnDate, PDO::PARAM_STR);
    $stmt->bindParam(':id', $loanId, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Emprunt modifié avec succès.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification : ' . $e->getMessage()]);
}