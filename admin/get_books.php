<?php
require_once '../config/database.php';

try {
    // Connexion à la base de données
    $database = new Database();
    $db = $database->getConnection();

    // Récupérer les livres
    $query = "SELECT l.id, l.titre, l.auteur, c.nom AS categorie, l.quantite_disponible 
              FROM livres l
              LEFT JOIN categories c ON l.categorie_id = c.id
              ORDER BY l.titre";
    $stmt = $db->query($query);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Retourner les livres au format JSON
    header('Content-Type: application/json');
    echo json_encode($books);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur : ' . $e->getMessage()]);
}
?>