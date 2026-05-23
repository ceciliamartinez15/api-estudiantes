<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

$server   = "michellemendez.database.windows.net";
$database = "michelle";
$username = "michellemendez";
$password = "Tilin123456";  // ← tu contraseña real

$conn = null;
$lastError = "";

// Intento 1: sqlsrv
try {
    $conn = new PDO(
        "sqlsrv:Server=tcp:$server,1433;Database=$database;Encrypt=1;TrustServerCertificate=0",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    $lastError .= "sqlsrv: " . $e->getMessage();
    $conn = null;
}

// Intento 2: ODBC Driver 18
if (!$conn) {
    try {
        $conn = new PDO(
            "odbc:Driver={ODBC Driver 18 for SQL Server};Server=tcp:$server,1433;Database=$database;Uid=$username;Pwd=$password;Encrypt=yes;TrustServerCertificate=no;",
            null, null,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $e) {
        $lastError .= " | odbc18: " . $e->getMessage();
        $conn = null;
    }
}

// Intento 3: ODBC Driver 17
if (!$conn) {
    try {
        $conn = new PDO(
            "odbc:Driver={ODBC Driver 17 for SQL Server};Server=tcp:$server,1433;Database=$database;Uid=$username;Pwd=$password;Encrypt=yes;TrustServerCertificate=no;",
            null, null,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $e) {
        $lastError .= " | odbc17: " . $e->getMessage();
        $conn = null;
    }
}

// Intento 4: ODBC Driver 13
if (!$conn) {
    try {
        $conn = new PDO(
            "odbc:Driver={ODBC Driver 13 for SQL Server};Server=tcp:$server,1433;Database=$database;Uid=$username;Pwd=$password;Encrypt=yes;TrustServerCertificate=no;",
            null, null,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $e) {
        $lastError .= " | odbc13: " . $e->getMessage();
        $conn = null;
    }
}

if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Ningún driver funcionó", "detalle" => $lastError]);
    exit;
}

// ── Router ─────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        try {
            $stmt = $conn->query("SELECT id, nombres, apellidos, carnet, edad FROM estudiantes ORDER BY id");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($data);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);

        if (
            empty($input['nombres']) ||
            empty($input['apellidos']) ||
            empty($input['carnet']) ||
            !isset($input['edad'])
        ) {
            http_response_code(400);
            echo json_encode(["error" => "Faltan campos requeridos"]);
            exit;
        }

        try {
            $sql  = "INSERT INTO estudiantes (nombres, apellidos, carnet, edad) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                trim($input['nombres']),
                trim($input['apellidos']),
                trim($input['carnet']),
                intval($input['edad'])
            ]);
            http_response_code(201);
            echo json_encode(["message" => "Estudiante agregado correctamente"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "ID requerido"]);
            exit;
        }

        try {
            $sql  = "DELETE FROM estudiantes WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([intval($input['id'])]);
            echo json_encode(["message" => "Estudiante eliminado correctamente"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Método no permitido"]);
        break;
}
?>
