<!-- 
// session_start();
// header('Content-Type: application/json');

// require_once __DIR__ . '/../../config/db.php';

// if (!isset($_SESSION['user_id'])) {

//     echo json_encode([
//         'status' => 'error',
//         'message' => 'Not logged in'
//     ]);
//     exit;
// }

// $data = json_decode(file_get_contents('php://input'), true);

// $username = trim($data['username'] ?? '');

// if(strlen($username) < 3){

//     echo json_encode([
//         'status' => 'error',
//         'message' => 'Username too short'
//     ]);
//     exit;
// }

// try {

//     $stmt = $pdo->prepare("
//         UPDATE users
//         SET username = ?
//         WHERE id = ?
//     ");

//     $stmt->execute([
//         $username,
//         $_SESSION['user_id']
//     ]);

//     // IMPORTANT
//     $_SESSION['username'] = $username;

//     echo json_encode([
//         'status' => 'success'
//     ]);

// } catch(PDOException $e){

//     echo json_encode([
//         'status' => 'error',
//         'message' => 'Username already used'
//     ]);
// } -->