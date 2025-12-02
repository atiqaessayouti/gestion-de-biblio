<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = $_POST['book_id'] ?? 0;
    $member_id = $_POST['member_id'] ?? 0;

    if ($book_id == 0 || $member_id == 0) {
        echo json_encode(['success' => false, 'message' => 'Veuillez sélectionner un livre et un adhérent.']);
        exit;
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        // Vérifier la disponibilité du livre
        $query = "SELECT quantite_disponible FROM livres WHERE id = :book_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':book_id', $book_id, PDO::PARAM_INT);
        $stmt->execute();
        $book = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$book || $book['quantite_disponible'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'Le livre sélectionné n\'est pas disponible.']);
            exit;
        }

        // Insérer l'emprunt
        $query = "INSERT INTO emprunts (livre_id, utilisateur_id, date_emprunt, date_retour_prevue) 
                  VALUES (:book_id, :member_id, NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY))";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':book_id', $book_id, PDO::PARAM_INT);
        $stmt->bindParam(':member_id', $member_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            // Mettre à jour la quantité disponible du livre
            $query = "UPDATE livres SET quantite_disponible = quantite_disponible - 1 WHERE id = :book_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':book_id', $book_id, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Emprunt enregistré avec succès.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement de l\'emprunt.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
    }
}
?>