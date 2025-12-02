<?php
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$bookId = $data['id'];

try {
    $db = (new Database())->getConnection();
    $query = "DELETE FROM livres WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $bookId, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Livre supprimé avec succès.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
}