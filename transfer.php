<?php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Intentional syntax error
echo "This is a test";

error_reporting(0);
ini_set('display_errors', 0);

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
    // Deduct amount from the sender's account
$stmt = $conn->prepare('UPDATE users SET account_balance = account_balance - ? WHERE account_number = ?');
$stmt->bind_param('ds', $amount, $fromAccount);
if (!$stmt->execute()) {
    throw new Exception("Failed to deduct amount from sender's account.");
}

// Add amount to the recipient's account
$stmt = $conn->prepare('UPDATE users SET account_balance = account_balance + ? WHERE account_number = ?');
$stmt->bind_param('ds', $amount, $toAccount);
if (!$stmt->execute()) {
    throw new Exception("Failed to add amount to recipient's account.");
}
    
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli('localhost', 'root', '', 'atm_system');
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    $fromAccount = $_SESSION['account_number'];
    $toAccount = $_POST['to_account'];
    $amount = (float) $_POST['amount'];

    if ($fromAccount === $toAccount) {
        echo "You cannot transfer money to the same account.";
        exit();
    }

    // Start a transaction to ensure atomicity
    $conn->begin_transaction();

    try {
        // Check if the recipient account exists
        $stmt = $conn->prepare('SELECT account_balance FROM users WHERE account_number = ?');
        $stmt->bind_param('s', $toAccount);
        $stmt->execute();
        $recipient = $stmt->get_result()->fetch_assoc();

        if (!$recipient) {
            throw new Exception("Recipient account does not exist.");
        }

        // Check if the sender has sufficient balance
        $stmt = $conn->prepare('SELECT account_balance FROM users WHERE account_number = ?');
        $stmt->bind_param('s', $fromAccount);
        $stmt->execute();
        $sender = $stmt->get_result()->fetch_assoc();

        if ($sender['account_balance'] < $amount) {
            throw new Exception("Insufficient balance.");
        }

        // Deduct the amount from the sender's account
        $stmt = $conn->prepare('UPDATE users SET account_balance = account_balance - ? WHERE account_number = ?');
        $stmt->bind_param('ds', $amount, $fromAccount);
        if (!$stmt->execute()) {
            throw new Exception("Failed to deduct amount from sender's account.");
        }

        // Add the amount to the recipient's account
        $stmt = $conn->prepare('UPDATE users SET account_balance = account_balance + ? WHERE account_number = ?');
        $stmt->bind_param('ds', $amount, $toAccount);
        if (!$stmt->execute()) {
            throw new Exception("Failed to add amount to recipient's account.");
        }

        // Commit the transaction
        $conn->commit();
        echo "Transfer successful!";
    } catch (Exception $e) {
        // Roll back the transaction in case of an error
        $conn->rollback();
        echo "Transfer failed: " . $e->getMessage();
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Transfer Money</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="transfer-container">
    <h2>Transfer Money</h2>
    <form method="POST" action="">
      <div class="input-group">
        <label for="to_account">Recipient Account Number</label>
        <input type="text" id="to_account" name="to_account" required>
      </div>
      <div class="input-group">
        <label for="amount">Amount</label>
        <input type="number" step="0.01" id="amount" name="amount" required>
      </div>
      <button type="submit" class="transfer-button">Transfer</button>
    </form>
    <a href="personal-info.php" class="button">Back to Account Info</a>
  </div>
</body>
</html>
