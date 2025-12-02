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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue de la Bibliothèque</title>
    <!-- bootstrap css -->
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
    <!-- style css -->
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <style>
       @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

body {
    font-family: 'Poppins', sans-serif;
    background-color: #362828;
    color: #ECEBDE;
    
}


.container {
    max-width: 1200px;
    margin: 0 auto;
}


h1 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 2.5em;
    font-weight: 600;
}

.search-filter {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
}

input, select {
    padding: 12px;
    border: none;
    border-radius: 25px;
    font-size: 16px;
}

input[type="text"] {
    flex-grow: 1;
    margin-right: 15px;
}

select {
    width: 200px;
}

.book-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 50px;
}

.book-card {
    width: 270px;
    background-color: #D7D3BF;
    border-radius: 10px;
    overflow: hidden;
    transition: transform 0.3s ease;
    cursor: pointer;
    text-align: center;
}

.book-card:hover {
    transform: translateY(-10px);
}

.book-image {
    width: 100%;
    height: 400px;
    object-fit: cover;
}

.book-title {
    padding: 5px;
    font-size: 1em;
    font-weight: bolder;
    color: #362828;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: #362828;
    margin: 5% auto;
    padding: 30px;
    border-radius: 15px;
    width: 70%;
    max-width: 700px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.book-details {
    display: flex;
    gap: 30px;
}

.book-details img {
    width: 200px;
    height: 300px;
    object-fit: cover;
    border-radius: 10px;
}

.book-details-info {
    flex: 1;
}

.book-details h2 {
    color: #fff;
    margin-bottom: 15px;
}
.book-details h3{
    color: #fff;
}
.book-details p {
    margin-bottom: 10px;
    color: #fff;
}

.keywords {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 10px;
}

.keyword {
    background-color: #D7D3BF;
    color: #362828;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8em;
}
button {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
            font-weight: 600;
            margin-top: 15px;
        }

   button:hover {
            background-color: #2980b9;
            transform: scale(1.05);
        }

    button:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            color: #fff;
        }
        
        .alert-success {
            background-color: #2ecc71;
        }
        
        .alert-danger {
            background-color: #e74c3c;
        }
    </style>
</head>
<body>
    <header>
    <div class="header_section">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <a class="logo" href="index.php"><img src="images/Untitled.png" style="width: 80px;" alt="Logo BiblioTech""></a>
           <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
           <span class="navbar-toggler-icon"></span>
           </button>
           <div class="collapse navbar-collapse" id="navbarSupportedContent">
              <ul class="navbar-nav mr-auto">
                 <li class="nav-item active">
                    <a class="nav-link" href="index.php">Accueil</a>
                 </li>
                 <li class="nav-item">
                    <a class="nav-link"  href="catalogue.php">Catalogue</a>
                 </li>
                 
              </ul>
              
              <div class="search_icon" style="color: #362828;">
                <?php if (isLoggedIn()): ?>
                    
                <?php else: ?>
                    <a href="login.php"><img src="images/user-icon.png"><span class="padding_left_15" style="color: #362828;">Connexion</span></a>
                <?php endif; ?>
              </div>
             
           </div>
        </header>
    <div class="container">
      <!-- Bouton pour retourner au tableau de bord -->
      <?php if (isLoggedIn()): ?>
            <button onclick="window.location.href='adherent.php?section=dashboard'" style="margin-bottom: 20px; background-color: #3498db; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease;">
                Retour au Tableau de Bord
            </button>
        <?php endif; ?>
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
</body>
</html>