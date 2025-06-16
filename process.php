<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    switch ($action) {
        case 'login':
            $data = json_decode(file_get_contents('php://input'), true);
            $username = $data['username'];
            $password = $data['password'];
            
            // In a real app, you would hash the password
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
            $stmt->bind_param("ss", $username, $password);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                echo json_encode(['success' => true, 'user' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            }
            break;
            
        case 'register':
            $data = json_decode(file_get_contents('php://input'), true);
            $username = $data['username'];
            $email = $data['email'];
            $password = $data['password'];
            
            // Check if user exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
                break;
            }
            
            // Create user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $password);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Registration successful']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Registration failed']);
            }
            break;
            
        case 'get_accounts':
            $stmt = $conn->prepare("SELECT * FROM accounts WHERE sold = 0");
            $stmt->execute();
            $result = $stmt->get_result();
            $accounts = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'accounts' => $accounts]);
            break;
            
        case 'get_all_accounts':
            $stmt = $conn->prepare("SELECT * FROM accounts");
            $stmt->execute();
            $result = $stmt->get_result();
            $accounts = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'accounts' => $accounts]);
            break;
            
        case 'add_account':
            if (!isset($_SESSION['user']) || !$_SESSION['user']['is_admin']) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $phone = $data['phone'];
            $otleg = $data['otleg'];
            $supplier = $data['supplier'];
            $price = $data['price'];
            
            $stmt = $conn->prepare("INSERT INTO accounts (phone_number, otleg, supplier, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssd", $phone, $otleg, $supplier, $price);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Account added']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add account']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
?>
