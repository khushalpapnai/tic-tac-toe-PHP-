<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "tic_tac_toe");
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}


// Handle player name submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_game'])) {
    $_SESSION['player1'] = $_POST['player1'];
    $_SESSION['player2'] = $_POST['player2'];

    // Initialize the game state in the database
    $mysqli->query("UPDATE game_state SET board = '_________', current_player = 'X'");
    header("Location: tic_tac.php");
    exit;
}

// Fetch player names from the session
$player1 = $_SESSION['player1'] ?? '';
$player2 = $_SESSION['player2'] ?? '';

if (!$player1 || !$player2) {
    // Display the name input form
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Enter Player Names</title>
        <link rel="stylesheet" href="form-style.css">
    </head>
    <body>
        <form method="post">
            <h1>Enter Player Names</h1>
            <label for="player1">Player 1 (X):</label>
            <input type="text" id="player1" name="player1" required>
            <br>
            <label for="player2">Player 2 (O):</label>
            <input type="text" id="player2" name="player2" required>
            <br>
            <button type="submit" name="start_game">Start Game</button>
        </form>
    </body>
    </html>';
    exit;
}

// Fetch the game state
$result = $mysqli->query("SELECT * FROM game_state LIMIT 1");
if (!$result) {
    die("Error: " . $mysqli->error);
}

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $board = str_split($row['board']);
    $current_player = $row['current_player'];
} else {
    die("Error: Game state is not initialized.");
}

$winner = null;

// Function to check for a winner
function check_winner($board) {
    $winning_combinations = [
        [0, 1, 2], [3, 4, 5], [6, 7, 8], // Rows
        [0, 3, 6], [1, 4, 7], [2, 5, 8], // Columns
        [0, 4, 8], [2, 4, 6]             // Diagonals
    ];

    foreach ($winning_combinations as $combo) {
        if ($board[$combo[0]] !== '_' && $board[$combo[0]] === $board[$combo[1]] && $board[$combo[1]] === $board[$combo[2]]) {
            return $board[$combo[0]]; // Return the winner (X or O)
        }
    }

    return null;
}

// Function to check for a draw
function check_draw($board) {
    return !in_array('_', $board); // Returns true if no empty cells are left
}

// Handle the player's move
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['position'])) {
        $position = intval($_POST['position']);
        if ($board[$position] === '_' && !$winner) {
            $board[$position] = $current_player;
            $current_player = ($current_player === 'X') ? 'O' : 'X';
            $board_str = implode('', $board);

            // Update the game state in the database
            $stmt = $mysqli->prepare("UPDATE game_state SET board = ?, current_player = ?");
            $stmt->bind_param("ss", $board_str, $current_player);
            $stmt->execute();
        }
    }

    if (isset($_POST['restart'])) {
        // Reset the game
        $mysqli->query("UPDATE game_state SET board = '_________', current_player = 'X'");
        session_unset(); // Reset player names
        header("Location: tic_tac.php");
        exit;
    }
}

// Check for a winner
$winner = check_winner($board);

// Check for a draw (only if there's no winner)
$draw = !$winner && check_draw($board);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tic-Tac-Toe</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Tic-Tac-Toe</h1>
    <?php if ($winner): ?>
        <h2><?php echo ($winner === 'X' ? $player1 : $player2); ?> wins!</h2>
        <form method="post">
            <button name="restart">Restart Game</button>
        </form>
    <?php elseif ($draw): ?>
        <h2>It's a draw!</h2>
        <form method="post">
            <button name="restart">Restart Game</button>
        </form>
    <?php else: ?>
        <p>Current Player: <?php echo ($current_player === 'X' ? $player1 : $player2); ?></p>
        <form method="post">
            <div class="grid">
                <?php foreach ($board as $index => $cell): ?>
                    <div class="cell">
                        <?php if ($cell === '_'): ?>
                            <button name="position" value="<?php echo $index; ?>"></button>
                        <?php else: ?>
                            <?php echo $cell; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </form>
    <?php endif; ?>
</body>
</html>






