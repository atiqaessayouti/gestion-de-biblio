<?php
class Statistics {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getDashboardStats() {
        $stats = [];

        // Nombre total de livres
        $query = "SELECT COUNT(*) AS total_books FROM livres";
        $stmt = $this->conn->query($query);
        $stats['total_books'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_books'];

        // Nombre total d'adhérents
        $query = "SELECT COUNT(*) AS total_members FROM utilisateurs WHERE role = 'utilisateur'";
        $stmt = $this->conn->query($query);
        $stats['total_members'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_members'];

        // Nombre d'emprunts en cours
        $query = "SELECT COUNT(*) AS active_loans FROM emprunts WHERE statut = 'en_cours'";
        $stmt = $this->conn->query($query);
        $stats['active_loans'] = $stmt->fetch(PDO::FETCH_ASSOC)['active_loans'];

        return $stats;
    }
}
?>