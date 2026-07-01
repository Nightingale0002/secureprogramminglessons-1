<?php 
session_start();
include 'includes/db.php';

//Tables aanmaken
include 'includes/userTable.php';
include 'includes/transactionTable.php';

$error = '';

if (isset($_SESSION['isBlocked']) && $_SESSION['isBlocked'] === true) {
    $currentTime = time();
    $blockTime = $_SESSION['blockStartedAt'] ?? 0;
    $expiryTime = 300;

    if (($currentTime - $blockTime) >= $expiryTime) {
        $_SESSION['isBlocked'] = false;
        unset($_SESSION['blockStartedAt']);
        $_SESSION['mistakes'] = 0;
    }
}

if ($_SESSION['isBlocked'] ?? false) {
    $currentTime = time();
    $blockStartedAt = $_SESSION['blockStartedAt'] ?? 0;
    $expiryTime = 300;
    $remaining = ($blockStartedAt + $expiryTime) - $currentTime;
    if ($remaining < 0) {
        $remaining = 0;
    }

    $minutes = floor($remaining / 60);
    $seconds = $remaining % 60;
    $error = 'Je bent geblockeerd. Time remaining ' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ':' . str_pad($seconds, 2, '0', STR_PAD_LEFT);
    die($error);
}

// Controleer of post is geset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8');
    $password = $_POST['password'] ?? '';

    if (!isset($_SESSION['mistakes'])) {
        $_SESSION['mistakes'] = 0;
    }

    $sql = 'SELECT * FROM user WHERE username = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['loggedin'] = true;
        $_SESSION['id'] = $user['id'];
        $_SESSION['username'] = $username;
        $_SESSION['user'] = $user;
        unset($_SESSION['mistakes']);

        header('Location: dashboard.php');
        exit();
    }

    $_SESSION['mistakes'] += 1;

    if ($_SESSION['mistakes'] < 5) {
        $error = 'Gebruikersnaam of wachtwoord is onjuist';
    } else {
        $_SESSION['isBlocked'] = true;
        $_SESSION['blockStartedAt'] = time();
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Omanido</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto mt-20 p-6 bg-white max-w-sm shadow-md rounded-md">
        <div class="flex justify-center">
            <img src="img/Omanido1.png" alt="Omanido Logo" class="mb-6 w-1/2">
        </div>
        <h2 class="text-lg text-center font-bold mb-6">Inloggen bij Omanido</h2>

        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Fout!</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-gray-700">Gebruikersnaam:</label>
                <input type="text" id="username" name="username" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700">Wachtwoord:</label>
                <input type="password" id="password" name="password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <input type="submit" value="Inloggen" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 focus:outline-none focus:shadow-outline">
        </form>
        <a href="register.php" class="block text-center text-sm text-blue-600 hover:underline mt-4">Nog geen account? Registreer hier</a>
    </div>

    <div class="mt-4 p-2 border border-gray-300 rounded">
        <label class="block text-sm font-medium text-gray-700">Uitgevoerde SQL-query:</label>
        <textarea readonly class="mt-1 block w-full border rounded-md py-2 px-3 resize-none" rows="4"><?php
        if (isset($sql)) {
            echo htmlspecialchars($sql, ENT_QUOTES, 'UTF-8');
        } else {
            echo "Log in om je SQL query te zien";
        }
        ?></textarea>
    </div>
</body>
</html>