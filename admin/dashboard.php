<?php
require_once '../auth/auth.php';
require_once '../config/database.php';
require_once '../models/Statistics.php';

// Initialiser l'objet Auth
$auth = new Auth();

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php?error=' . urlencode('Vous devez être administrateur pour accéder à cette page'));
    exit();
}

// Récupérer les informations de l'utilisateur connecté
$currentUser = $auth->getCurrentUser();

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

// Récupérer les statistiques
$statistics = new Statistics($db);
$stats = $statistics->getDashboardStats();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Admin - Gestion de Bibliothèque</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #362828;
            --secondary-color: #362828;
            --background-color: #AF9284;
            --text-color: #0F090B;
            --transition-speed: 0.3s;
        }
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            color: var(--text-color);
            transition: background-color var(--transition-speed);
        }
        .container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background-color: var(--secondary-color);
            color: white;
            padding: 20px;
            transition: width var(--transition-speed);
        }
        .sidebar.collapsed {
            width: 60px;
        }
        .sidebar h2 {
            margin-bottom: 20px;
            font-size: 1.5em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }
        .sidebar ul li {
            margin-bottom: 10px;
        }
        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 5px;
            transition: background-color var(--transition-speed);
        }
        .sidebar ul li a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .sidebar ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        h1 {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }
        .card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: box-shadow var(--transition-speed);
        }
        .card:hover {
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }
        .btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color var(--transition-speed);
        }
        .btn:hover {
            background-color: #2980b9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: var(--primary-color);
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        form {
            display: grid;
            gap: 10px;
        }
        input[type="text"], input[type="email"], input[type="password"], select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: border-color var(--transition-speed);
        }
        input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus, select:focus, textarea:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform var(--transition-speed);
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card h3 {
            margin: 0;
            color: var(--primary-color);
        }
        .stat-card p {
            font-size: 2em;
            margin: 10px 0;
            font-weight: bold;
        }
        .search-bar {
            margin-bottom: 20px;
        }
        .search-bar input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .pagination button {
            margin: 0 5px;
            padding: 5px 10px;
            border: 1px solid #ddd;
            background-color: white;
            cursor: pointer;
        }
        .pagination button.active {
            background-color: var(--primary-color);
            color: white;
        }
        #notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border-radius: 5px;
            display: none;
            z-index: 1000;
        }
        .chart-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar" id="sidebar">
            <h2>Menu Admin</h2>
            <ul>
                <li><a href="#" onclick="showSection('dashboard')"><i class="fas fa-tachometer-alt"></i> <span>Tableau de Bord</span></a></li>
                <li><a href="#" onclick="showSection('books')"><i class="fas fa-book"></i> <span>Gestion des Livres</span></a></li>
                <li><a href="#" onclick="showSection('members')"><i class="fas fa-users"></i> <span>Gestion des Adhérents</span></a></li>
                <li><a href="#" onclick="showSection('loans')"><i class="fas fa-exchange-alt"></i> <span>Gestion des Emprunts</span></a></li>
                <li><a href="../login.php?action=logout"><i class="fas fa-sign-out-alt"></i> <span>Se Déconnecter</span></a></li>
            </ul>
        </div>
        <div class="main-content">
            <div id="dashboard" class="section">
                <h1>Bienvenue <?php echo htmlspecialchars($currentUser['nom']); ?> dans votre Espace Admin!</h1>
                <div class="stats">
                    <div class="stat-card">
                        <h3>Total des Livres</h3>
                        <p><?php echo $stats['total_books']; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Total des Adhérents</h3>
                        <p><?php echo $stats['total_members']; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Emprunts en cours</h3>
                        <p><?php echo $stats['active_loans']; ?></p>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="statsChart"></canvas>
                </div>
            </div>
            <div id="books" class="section" style="display:none;">
                <h1>Gestion des Livres</h1>
                <div class="card">
                    <h2>Ajouter un Livre</h2>
                    <form id="addBookForm">
                        <input type="text" name="title" id="bookTitle" placeholder="Titre" required>
                        <input type="text" name="author" id="bookAuthor" placeholder="Auteur" required>
                        <textarea name="description" id="bookDescription" placeholder="Description" rows="3"></textarea>
                        <input type="text" name="image_url" id="bookImageUrl" placeholder="URL de l'image">
                        <select name="category_id" id="bookCategory" required>
                            <option value="">Sélectionner une catégorie</option>
                            <?php
                            // Récupérer les catégories depuis la base de données
                            $stmt = $db->query("SELECT * FROM categories ORDER BY nom");
                            while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="' . $category['id'] . '">' . htmlspecialchars($category['nom']) . '</option>';
                            }
                            ?>
                        </select>
                        <input type="number" name="quantity" id="bookQuantity" placeholder="Quantité disponible" min="0" required>
                        <button type="submit" class="btn">Ajouter</button>
                    </form>
                </div>
                <div class="card">
                    <h2>Liste des Livres</h2>
                    <div class="search-bar">
                        <input type="text" id="searchBooks" placeholder="Rechercher un livre...">
                    </div>
                    <table id="booksList">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Auteur</th>
                                <th>Catégorie</th>
                                <th>Disponibilité</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <div class="pagination" id="booksPagination"></div>
                </div>
            </div>
            <div id="members" class="section" style="display:none;">
                <h1>Gestion des Adhérents</h1>
                <div class="card">
                    <h2>Ajouter un Adhérent</h2>
                    <form id="addMemberForm">
                        <input type="text" name="name" id="memberName" placeholder="Nom" required>
                        <input type="email" name="email" id="memberEmail" placeholder="Email" required>
                        <input type="password" name="password" id="memberPassword" placeholder="Mot de passe" required>
                        <button type="submit" class="btn">Ajouter</button>
                    </form>
                </div>
                <div class="card">
                    <h2>Liste des Adhérents</h2>
                    <div class="search-bar">
                        <input type="text" id="searchMembers" placeholder="Rechercher un adhérent...">
                    </div>
                    <table id="membersList">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <div class="pagination" id="membersPagination"></div>
                </div>
            </div>
            <div id="loans" class="section" style="display:none;">
                <h1>Gestion des Emprunts</h1>
                <div class="card">
                    <h2>Nouvel Emprunt</h2>
                    <form id="newLoanForm">
                        <select id="loanBook" required>
                            <option value="">Sélectionner un livre</option>
                            <?php
                            // Récupérer les livres disponibles depuis la base de données
                            $stmt = $db->query("SELECT * FROM livres WHERE quantite_disponible > 0 ORDER BY titre");
                            while ($book = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="' . $book['id'] . '">' . htmlspecialchars($book['titre']) . ' (' . $book['quantite_disponible'] . ' disponibles)</option>';
                            }
                            ?>
                        </select>
                        <select id="loanMember" required>
                            <option value="">Sélectionner un adhérent</option>
                            <?php
                            // Récupérer les adhérents depuis la base de données
                            $stmt = $db->query("SELECT * FROM utilisateurs WHERE role = 'utilisateur' ORDER BY nom");
                            while ($member = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="' . $member['id'] . '">' . htmlspecialchars($member['nom']) . ' (' . $member['email'] . ')</option>';
                            }
                            ?>
                        </select>
                        <button type="submit" class="btn">Enregistrer l'emprunt</button>
                    </form>
                </div>
                <div class="card">
                    <h2>Emprunts en cours</h2>
                    <div class="search-bar">
                        <input type="text" id="searchLoans" placeholder="Rechercher un emprunt...">
                    </div>
                    <table id="loansList">
                        <thead>
                            <tr>
                                <th>Livre</th>
                                <th>Adhérent</th>
                                <th>Date d'emprunt</th>
                                <th>Date de retour</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <div class="pagination" id="loansPagination"></div>
                </div>
            </div>
        </div>
    </div>
    <div id="notification"></div>

    <script>
        // Fonction pour afficher une section
        function showSection(sectionId) {
            document.querySelectorAll('.section').forEach(section => {
                section.style.display = 'none';
            });
            document.getElementById(sectionId).style.display = 'block';
        }

        // Mettre à jour le graphique des statistiques
        const ctx = document.getElementById('statsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Livres', 'Adhérents', 'Emprunts'],
                datasets: [{
                    label: 'Statistiques',
                    data: [
                        <?php echo $stats['total_books']; ?>,
                        <?php echo $stats['total_members']; ?>,
                        <?php echo $stats['active_loans']; ?>
                    ],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Fonction pour charger les livres
        function loadBooks() {
            fetch('get_books.php')
                .then(response => response.json())
                .then(data => {
                    const booksList = document.querySelector('#booksList tbody');
                    booksList.innerHTML = ''; // Vider le tableau

                    data.forEach(book => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${book.titre}</td>
                            <td>${book.auteur}</td>
                            <td>${book.categorie}</td>
                            <td>${book.quantite_disponible}</td>
                            <td>
                                <button class="btn btn-edit" data-id="${book.id}">Modifier</button>
                                <button class="btn btn-delete" data-id="${book.id}">Supprimer</button>
                            </td>
                        `;
                        booksList.appendChild(row);

                        // Ajouter les gestionnaires d'événements
                        row.querySelector('.btn-edit').addEventListener('click', () => editBook(book.id));
                        row.querySelector('.btn-delete').addEventListener('click', () => deleteBook(book.id));
                    });
                })
                .catch(error => {
                    console.error('Erreur:', error);
                });
        }

        // Fonction pour modifier un livre
        function editBook(bookId) {
            const newTitle = prompt('Entrez le nouveau titre du livre :');
            const newAuthor = prompt('Entrez le nouvel auteur du livre :');
            const newQuantity = prompt('Entrez la nouvelle quantité disponible :');

            if (!newTitle || !newAuthor || !newQuantity) {
                alert('Tous les champs sont obligatoires.');
                return;
            }

            fetch('edit_book.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: bookId,
                    titre: newTitle,
                    auteur: newAuthor,
                    quantite: newQuantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    loadBooks(); // Recharger la liste des livres
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue.');
            });
        }

        // Fonction pour supprimer un livre
        function deleteBook(bookId) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce livre ?')) return;

            fetch('delete_book.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: bookId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    loadBooks(); // Recharger la liste des livres
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue.');
            });
        }

        // Charger les livres au chargement de la page
        document.addEventListener('DOMContentLoaded', loadBooks);

        // Gestion de l'ajout de livre
        document.getElementById('addBookForm').addEventListener('submit', function (e) {
            e.preventDefault(); // Empêcher le rechargement de la page

            const formData = new FormData(this);

            fetch('add_book.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message); // Afficher un message de succès
                    loadBooks(); // Recharger la liste des livres
                    this.reset(); // Réinitialiser le formulaire
                } else {
                    alert(data.message); // Afficher un message d'erreur
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue.');
            });
        });

        // Fonction pour charger les adhérents
        function loadMembers() {
            fetch('get_members.php')
                .then(response => response.json())
                .then(data => {
                    const membersList = document.querySelector('#membersList tbody');
                    membersList.innerHTML = ''; // Vider le tableau

                    data.forEach(member => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${member.nom}</td>
                            <td>${member.email}</td>
                            <td>${member.role === 'admin' ? 'Administrateur' : 'Adhérent'}</td>
                            <td>
                                <button class="btn btn-delete" data-id="${member.id}">Supprimer</button>
                            </td>
                        `;
                        membersList.appendChild(row);
                    });

                    // Ajouter des gestionnaires d'événements pour les boutons Supprimer
                    document.querySelectorAll('.btn-delete').forEach(button => {
                        button.addEventListener('click', function () {
                            const memberId = this.getAttribute('data-id');
                            deleteMember(memberId);
                        });
                    });
                })
                .catch(error => {
                    console.error('Erreur:', error);
                });
        }

        // Fonction pour ajouter un adhérent
        document.getElementById('addMemberForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('add_member.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    loadMembers(); // Recharger la liste des adhérents
                    this.reset(); // Réinitialiser le formulaire
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Succes.');
            });
        });

        // Fonction pour supprimer un adhérent
        function deleteMember(memberId) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet adhérent ?')) return;

            fetch('delete_member.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: memberId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    loadMembers(); // Recharger la liste des adhérents
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue.');
            });
        }

        // Charger les adhérents au chargement de la page
        document.addEventListener('DOMContentLoaded', loadMembers);

        // Fonction pour charger les emprunts
        function loadLoans() {
            fetch('get_loans.php')
                .then(response => response.json())
                .then(data => {
                    const loansList = document.querySelector('#loansList tbody');
                    loansList.innerHTML = ''; // Vider le tableau

                    if (data.error) {
                        console.error('Erreur:', data.error);
                        return;
                    }

                    data.forEach(loan => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${loan.livre}</td>
                            <td>${loan.adherent}</td>
                            <td>${loan.date_emprunt}</td>
                            <td>${loan.date_retour_prevue}</td>
                            <td>
                                <button class="btn btn-edit" data-id="${loan.id}">Modifier</button>
                                <button class="btn btn-delete" data-id="${loan.id}">Supprimer</button>
                            </td>
                        `;
                        loansList.appendChild(row);

                        // Ajouter les gestionnaires d'événements
                        row.querySelector('.btn-edit').addEventListener('click', () => editLoan(loan.id));
                        row.querySelector('.btn-delete').addEventListener('click', () => deleteLoan(loan.id));
                    });
                })
                .catch(error => {
                    console.error('Erreur:', error);
                });
        }

        // Fonction pour modifier un emprunt
        function editLoan(loanId) {
            const newReturnDate = prompt('Entrez la nouvelle date de retour (YYYY-MM-DD) :');
            if (!newReturnDate) return;

            fetch('edit_loan.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: loanId, date_retour: newReturnDate })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    loadLoans(); // Recharger la liste des emprunts
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue.');
            });
        }

        // Fonction pour supprimer un emprunt
        function deleteLoan(loanId) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet emprunt ?')) return;

            fetch('delete_loan.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: loanId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    loadLoans(); // Recharger la liste des emprunts
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue.');
            });
        }

        // Charger les emprunts au chargement de la page
        document.addEventListener('DOMContentLoaded', loadLoans);
    </script>
</body>
</html>