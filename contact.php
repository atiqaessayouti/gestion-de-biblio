<?php
// Démarrer la session
session_start();

// Configuration de la base de données
$host = 'localhost';
$dbname = 'bibliotheque';
$username = 'root';
$password = '';

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour nettoyer les entrées utilisateur
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Récupérer les catégories pour le filtre
$stmt = $pdo->query("SELECT id, nom FROM categories ORDER BY nom");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement de l'emprunt de livre (si formulaire soumis)
$empruntMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['emprunter']) && isLoggedIn()) {
    $livre_id = isset($_POST['livre_id']) ? (int)$_POST['livre_id'] : 0;
    
    // Vérifier si le livre existe et est disponible
    $stmt = $pdo->prepare("SELECT quantite_disponible FROM livres WHERE id = ?");
    $stmt->execute([$livre_id]);
    $livre = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($livre && $livre['quantite_disponible'] > 0) {
        // Vérifier si l'utilisateur n'a pas déjà emprunté 3 livres
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM emprunts WHERE utilisateur_id = ? AND statut = 'en_cours'");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] < 3) {
            // Calculer la date de retour (14 jours après aujourd'hui)
            $date_retour = date('Y-m-d H:i:s', strtotime('+14 days'));
            
            // Insérer l'emprunt
            $stmt = $pdo->prepare("INSERT INTO emprunts (livre_id, utilisateur_id, date_retour_prevue, statut) VALUES (?, ?, ?, 'en_cours')");
            if ($stmt->execute([$livre_id, $_SESSION['user_id'], $date_retour])) {
                // Mettre à jour la quantité disponible
                $stmt = $pdo->prepare("UPDATE livres SET quantite_disponible = quantite_disponible - 1 WHERE id = ?");
                $stmt->execute([$livre_id]);
                
                $empruntMessage = "Livre emprunté avec succès. À retourner avant le " . date('d/m/Y', strtotime($date_retour));
            } else {
                $empruntMessage = "Erreur lors de l'emprunt du livre.";
            }
        } else {
            $empruntMessage = "Vous avez déjà emprunté le nombre maximum de livres (3).";
        }
    } else {
        $empruntMessage = "Ce livre n'est pas disponible.";
    }
}

// Récupération des livres (avec filtres si présents)
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$categorie = isset($_GET['categorie']) ? (int)$_GET['categorie'] : 0;

$sql = "SELECT l.id, l.titre, l.auteur, l.description, l.image_url, l.quantite_disponible, c.nom as categorie 
        FROM livres l 
        LEFT JOIN categories c ON l.categorie_id = c.id
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (l.titre LIKE ? OR l.auteur LIKE ? OR l.description LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($categorie > 0) {
    $sql .= " AND l.categorie_id = ?";
    $params[] = $categorie;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$livres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les détails d'un livre spécifique si demandé
$livre_details = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $livre_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT l.id, l.titre, l.auteur, l.description, l.image_url, l.quantite_disponible, 
                           c.nom as categorie 
                           FROM livres l 
                           LEFT JOIN categories c ON l.categorie_id = c.id
                           WHERE l.id = ?");
    $stmt->execute([$livre_id]);
    $livre_details = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Inclure le header
include 'includes/header.php';
?>
<link rel="stylesheet" href="css/catalogue-style.css">

<div class="container">
    <br> <br> <br>
    <h1 style="color: #ECEBDE; font-weight: bolder;">Catalogue </h1>
    <br> <br>
    
    <?php if (!empty($empruntMessage)): ?>
        <div class="alert <?php echo strpos($empruntMessage, 'succès') !== false ? 'alert-success' : 'alert-danger'; ?>">
            <?php echo $empruntMessage; ?>
        </div>
    <?php endif; ?>
    
    <form method="get" action="catalogue.php">
        <div class="search-filter">
            <input type="text" id="search" name="search" placeholder="Rechercher par titre, auteur ou description" value="<?php echo htmlspecialchars($search); ?>">
            <select id="category-filter" name="categorie">
                <option value="0">Toutes les catégories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $categorie == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['nom']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" style="width: auto; margin-left: 10px;">Rechercher</button>
        </div>
    </form>
    <br>
    
    <div id="book-grid" class="book-grid">
        <?php if (empty($livres)): ?>
            <p style="grid-column: 1 / -1; text-align: center;">Aucun livre trouvé</p>
        <?php else: ?>
            <?php foreach ($livres as $livre): ?>
                <div class="book-card" onclick="window.location.href='catalogue.php?id=<?php echo $livre['id']; ?>'">
                    <img src="<?php echo !empty($livre['image_url']) ? htmlspecialchars($livre['image_url']) : 'images/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($livre['titre']); ?>" class="book-image">
                    <div class="book-title"><?php echo htmlspecialchars($livre['titre']); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($livre_details): ?>
<div id="book-modal" class="modal" style="display: block;">
    <div class="modal-content">
        <span class="close" onclick="window.location.href='catalogue.php<?php echo !empty($search) || $categorie > 0 ? '?search=' . urlencode($search) . '&categorie=' . $categorie : ''; ?>'">&times;</span>
        <div id="book-details" class="book-details">
            <img src="<?php echo !empty($livre_details['image_url']) ? htmlspecialchars($livre_details['image_url']) : 'images/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($livre_details['titre']); ?>">
            <div class="book-details-info">
                <h2><?php echo htmlspecialchars($livre_details['titre']); ?></h2>
                <p><strong>Auteur:</strong> <?php echo htmlspecialchars($livre_details['auteur']); ?></p>
                <p><strong>Catégorie:</strong> <?php echo htmlspecialchars($livre_details['categorie']); ?></p>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($livre_details['description']); ?></p>
                <p><strong>Exemplaires disponibles:</strong> <?php echo $livre_details['quantite_disponible']; ?></p>
                
                <?php if (isLoggedIn()): ?>
                    <form method="post" action="catalogue.php">
                        <input type="hidden" name="livre_id" value="<?php echo $livre_details['id']; ?>">
                        <button type="submit" name="emprunter" <?php echo $livre_details['quantite_disponible'] <= 0 ? 'disabled' : ''; ?>>
                            <?php echo $livre_details['quantite_disponible'] > 0 ? 'Emprunter' : 'Non disponible'; ?>
                        </button>
                    </form>
                <?php else: ?>
                    <button onclick="window.location.href='login.php'">Se connecter pour emprunter</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    // Fermer la modale lorsque l'utilisateur clique en dehors
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('book-modal');
        if (event.target === modal) {
            window.location.href = 'catalogue.php<?php echo !empty($search) || $categorie > 0 ? '?search=' . urlencode($search) . '&categorie=' . $categorie : ''; ?>';
        }
    });
</script>

<?php
// Inclure le footer
include 'includes/footer.php';
?>