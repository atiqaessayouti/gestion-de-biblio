<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT e.id, l.titre AS livre, u.nom AS adherent, e.date_emprunt, e.date_retour_prevue 
              FROM emprunts e
              JOIN livres l ON e.livre_id = l.id
              JOIN utilisateurs u ON e.utilisateur_id = u.id
              WHERE e.date_retour_effective IS NULL
              ORDER BY e.date_emprunt DESC";
    $stmt = $db->query($query);
    $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($loans);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur : ' . $e->getMessage()]);
}
?>