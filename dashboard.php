<?php
session_start();
include_once 'includes/db.php';

$error = '';
$success = '';

function executeTransaction($ontvanger, $bedrag): void
{
    global $error, $success, $pdo;

    $stmt = $pdo->prepare('SELECT * FROM user WHERE username = ?');
    $stmt->execute([$ontvanger]);
    $ontvangerUser = $stmt->fetch();

    if ($stmt->rowCount() <= 0) {
        $error = 'Deze gebruiker bestaat niet';
        return;
    }

    if ($bedrag <= 0) {
        $error = 'Vul een bedrag hoger dan 0 in.';
        return;
    }

    if ($_SESSION['user']['balance'] < $bedrag) {
        $error = 'Je hebt niet genoeg saldo om dit bedrag over te maken';
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO transaction (sender, receiver, amount, description) VALUES (?, ?, ?, ?)');
    $stmt->execute([$_SESSION['user']['id'], $ontvangerUser['id'], $bedrag, htmlspecialchars($_POST['omschrijving'], ENT_QUOTES, 'UTF-8')]);

    $stmt = $pdo->prepare('SELECT balance FROM user WHERE username = ?');
    $stmt->execute([$ontvangerUser['username']]);
    $saldo = $stmt->fetchColumn();
    $saldo = $saldo + $bedrag;

    $stmt = $pdo->prepare('UPDATE user SET balance = ? WHERE username = ?');
    $stmt->execute([$saldo, $ontvangerUser['username']]);

    $stmt = $pdo->prepare('SELECT balance FROM user WHERE id = ?');
    $stmt->execute([$_SESSION['user']['id']]);
    $saldo = $stmt->fetchColumn();
    $saldo = $saldo - $bedrag;

    $stmt = $pdo->prepare('UPDATE user SET balance = ? WHERE id = ?');
    $stmt->execute([$saldo, $_SESSION['user']['id']]);

    $_SESSION['user']['balance'] = $saldo;
    $success = 'Het bedrag is succesvol overgemaakt';
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ontvanger = htmlspecialchars($_POST['ontvanger'] ?? '', ENT_QUOTES, 'UTF-8');
    $bedrag = (float)($_POST['bedrag'] ?? 0);

    executeTransaction($ontvanger, $bedrag);
}

$stmt = $pdo->prepare('SELECT balance FROM user WHERE id = ?');
$stmt->execute([$_SESSION['user']['id']]);
$saldo = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Omanido</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto p-4">
        <div class="flex flex-wrap -mx-2">
            <div class="w-full md:w-1/3 px-2 mb-4">
                <div class="bg-white p-6 rounded-lg shadow-md h-full flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-xl mb-2">Mijn Saldo</h3>
                        <p class="text-sm text-gray-600 mb-4">Actueel Beschikbaar Saldo</p>
                    </div>
                    <p class="text-4xl font-bold mb-4 <?php echo $saldo >= 0 ? 'text-green-500' : 'text-red-500'; ?> self-center">
                        €<?php echo number_format($saldo, 2, ',', '.'); ?>
                    </p>
                    <div class="text-center">
                        <a href="transacties.php?id=<?= $_SESSION['user']['id'] ?>" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                            Transactieoverzicht
                        </a>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-2/3 px-2 mb-4">
                <div class="bg-white p-6 rounded-lg shadow-md h-full">
                    <h3 class="font-bold text-xl mb-4">Geld Overmaken</h3>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <div class="mb-4">
                            <label for="ontvanger" class="block text-sm font-medium text-gray-700">Ontvanger:</label>
                            <input type="text" id="ontvanger" name="ontvanger" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        </div>
                        <div class="mb-4">
                            <label for="bedrag" class="block text-sm font-medium text-gray-700">Bedrag(€):</label>
                            <input type="number" id="bedrag" name="bedrag" step="0.01" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        </div>
                        <div class="mb-4">
                            <label for="omschrijving" class="block text-sm font-medium text-gray-700">Omschrijving:</label>
                            <input type="text" id="omschrijving" name="omschrijving" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                        </div>
                        <input type="submit" value="Overmaken" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 focus:outline-none focus:shadow-outline">
                        <?php
                        if (!empty($error)) {
                            echo '<p class="text-red-500 text-sm mt-2">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</p>';
                        } elseif (!empty($success)) {
                            echo '<p class="text-green-500 text-sm mt-2">' . htmlspecialchars($success, ENT_QUOTES, 'UTF-8') . '</p>';
                        }
                        ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
