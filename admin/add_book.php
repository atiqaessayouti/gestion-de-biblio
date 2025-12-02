<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $description = $_POST['description'] ?? '';
    $image_url = $_POST['image_url'] ?? '';
    $category_id = $_POST['category_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 0;

    if (empty($title) || empty($author) || $category_id == 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs requis.']);
        exit;
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        $query = "INSERT INTO livres (titre, auteur, description, image_url, categorie_id, quantite_disponible) 
                  VALUES (:title, :author, :description, :image_url, :category_id, :quantity)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':author', $author);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':image_url', $image_url);
        $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Livre ajouté avec succès.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du livre.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
    }
}
?>