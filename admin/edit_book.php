<?php
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$bookId = $data['id'];
$newTitle = $data['titre'];
$newAuthor = $data['auteur'];
$newQuantity = $data['quantite'];

try {
    $db = (new Database())->getConnection();
    $query = "UPDATE livres SET titre = :titre, auteur = :auteur, quantite_disponible = :quantite WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':titre', $newTitle, PDO::PARAM_STR);
    $stmt->bindParam(':auteur', $newAuthor, PDO::PARAM_STR);
    $stmt->bindParam(':quantite', $newQuantity, PDO::PARAM_INT);
    $stmt->bindParam(':id', $bookId, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Livre modifié avec succès.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification : ' . $e->getMessage()]);
}