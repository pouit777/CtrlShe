 <!-- // 

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

// $data = json_decode(file_get_contents("php://input"), true);

// $avatar = trim($data['avatar'] ?? '');

// if ($avatar === '') {
//     echo json_encode([
//         'status' => 'error',
//         'message' => 'No avatar selected'
//     ]);
//     exit;
// }

// try {

//     $stmt = $pdo->prepare("
//         UPDATE users
//         SET avatar = ?
//         WHERE id = ?
//     ");

//     $success = $stmt->execute([
//         $avatar,
//         $_SESSION['user_id']
//     ]);

//     if(!$success){

//         echo json_encode([
//             'status' => 'error',
//             'message' => 'Database update failed'
//         ]);
//         exit;
//     }

//     $_SESSION['avatar'] = $avatar;

//     echo json_encode([
//         'status' => 'success',
//         'avatar' => $avatar
//     ]);

// } catch(PDOException $e){

//     echo json_encode([
//         'status' => 'error',
//         'message' => $e->getMessage()
//     ]);

// }