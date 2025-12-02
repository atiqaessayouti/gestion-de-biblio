<?php
require_once 'auth/auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$pageTitle = "Inscription";
require_once 'includes/header.php';
?>

<style>
    body {
        background-image: url('images/TAB.png');
        background-size: cover;
        background-position: center;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #fdfdfd;
        margin: 0;
        padding: 0;
    }

    .container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background-color: rgba(0, 0, 0, 0.1);
    }

    .form-box {
        background-color: rgba(88, 61, 61, 0.8);
        padding: 40px;
        border-radius: 12px;
        max-width: 450px;
        width: 100%;
        box-shadow: 0 8px 16px rgba(0,0,0,0.3);
    }

    .logo {
        font-size: 50px;
        color: #6f42c1;
        text-align: center;
        margin-bottom: 10px;
    }

    .form-title {
        text-align: center;
        font-size: 1.3rem;
        margin-bottom: 25px;
        color: #f8f8f8;
    }

    .form-group {
        margin-bottom: 18px;
    }

    label {
        display: block;
        margin-bottom: 5px;
        color: #f1eaea;
    }

    input {
        width: 100%;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #ccc;
        background-color: #fdfdfd;
        color: #333;
        font-size: 1rem;
    }

    input:focus {
        outline: none;
        border-color: #6f42c1;
        box-shadow: 0 0 5px #6f42c1;
    }

    .btn {
        background-color: #6f42c1;
        color: white;
        padding: 12px;
        border: none;
        border-radius: 8px;
        width: 100%;
        font-size: 1rem;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .btn:hover {
        background-color: #5932a1;
    }

    .alert {
        background-color: #f8d7da;
        color: #721c24;
        padding: 12px;
        border-radius: 6px;
        text-align: center;
        margin-bottom: 20px;
    }

</style>

<div class="container">
    <div class="form-box">
        <div class="logo">📚</div>
        <div class="form-title">
            ✨ Inscrivez-vous sur notre bibliothèque et explorez un monde de livres passionnants ✨
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form action="auth/auth.php" method="POST" onsubmit="return validateForm()">
            <input type="hidden" name="action" value="register">

            <div class="form-group">
                <label for="nom">Votre nom</label>
                <input type="text" id="nom" name="nom" placeholder="Nom complet" required>
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" placeholder="E-mail" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" placeholder="Mot de passe" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmer le mot de passe" required>
            </div>

            <button type="submit" class="btn">Créer le compte</button>
        </form>
    </div>
</div>

<script>
function validateForm() {
    var password = document.getElementById("password").value;
    var confirm_password = document.getElementById("confirm_password").value;

    if (password != confirm_password) {
        alert("Les mots de passe ne correspondent pas !");
        return false;
    }
    return true;
}
</script>

<?php require_once 'includes/footer.php'; ?>
