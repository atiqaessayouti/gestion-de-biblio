<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs requis.']);
        exit;
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        $query = "INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (:name, :email, :password, 'utilisateur')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', password_hash($password, PASSWORD_DEFAULT));

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Adhérent ajouté avec succès.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout de l\'adhérent.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
    }
}
?>