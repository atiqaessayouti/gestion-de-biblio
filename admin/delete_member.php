<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;

    if ($id == 0) {
        echo json_encode(['success' => false, 'message' => 'ID invalide.']);
        exit;
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        $query = "DELETE FROM utilisateurs WHERE id = :id AND role = 'utilisateur'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Adhérent supprimé avec succès.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de l\'adhérent.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
    }
}
?>