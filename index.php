<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'firebase_auth.php';

// 🔐 VALIDACIÓN JWT REAL
$uid = verificarFirebaseJWT();

echo json_encode(["uid_detectado" => $uid]);
exit;

// Configuración DB
$host = "mysql.railway.internal";
$user = "root";
$pass = "kXkVEuHihxdgdFmpkxhmhUDOmrNkmfLz";
$db   = "railway";
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["status" => "error", "message" => "Conexión fallida"]));
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data) {

    $accion = $data['accion'];

    // --- SINCRONIZACION DE USUARIOS ---
    if ($accion === 'sync_usuario') {

        $sql = "INSERT INTO usuarios_remotos (id_usuario, name, image, email) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE name=?, image=?, email=?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssss",
            $uid,
            $data['nombre'],
            $data['foto_url'],
            $data['email'],
            $data['nombre'],
            $data['foto_url'],
            $data['email']
        );
        $stmt->execute();
    }

    // --- CATEGORIAS ---
    else if ($accion === 'sync_categoria') {

        $sql = "INSERT INTO Categorias (id_local_sqlite, id_usuario, nombre, tipo) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), tipo=VALUES(tipo)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $data['id_local'], $uid, $data['nombre'], $data['tipo']);
        $stmt->execute();
    }

    else if ($accion === 'delete_categoria') {

        $sql = "DELETE FROM Categorias WHERE id_local_sqlite = ? AND id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $data['id_local'], $uid);
        $stmt->execute();

        echo json_encode(["status" => "success"]);
        exit;
    }

    // --- TRANSACCIONES ---
    else if ($accion === 'sync_transaccion') {

        $sql = "INSERT INTO Transacciones 
                (id_local_sqlite, id_usuario, descripcion, monto, fecha, nombre_categoria, tipo_transaccion, clasificacion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    descripcion=VALUES(descripcion),
                    monto=VALUES(monto),
                    fecha=VALUES(fecha),
                    nombre_categoria=VALUES(nombre_categoria),
                    tipo_transaccion=VALUES(tipo_transaccion),
                    clasificacion=VALUES(clasificacion)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "issdssss",
            $data['id_local'],
            $uid,
            $data['descripcion'],
            $data['monto'],
            $data['fecha'],
            $data['nombre_categoria'],
            $data['tipo_transaccion'],
            $data['clasificacion']
        );
        $stmt->execute();

        echo json_encode(["status" => "success"]);
        exit;
    }

    else if ($accion === 'delete_transaccion') {

        $sql = "DELETE FROM Transacciones WHERE id_local_sqlite = ? AND id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $data['id_local'], $uid);
        $stmt->execute();

        echo json_encode(["status" => "success"]);
        exit;
    }

    // --- FETCH ALL ---
    else if ($accion === 'fetch_all') {

        $stmt = $conn->prepare("SELECT id_local_sqlite as id, nombre, tipo FROM Categorias WHERE id_usuario = ?");
        $stmt->bind_param("s", $uid);
        $stmt->execute();
        $categorias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmt = $conn->prepare("SELECT id_local_sqlite as id, descripcion, monto, fecha, nombre_categoria, tipo_transaccion, clasificacion FROM Transacciones WHERE id_usuario = ?");
        $stmt->bind_param("s", $uid);
        $stmt->execute();
        $transacciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmt = $conn->prepare("SELECT * FROM Presupuestos WHERE id_usuario = ?");
        $stmt->bind_param("s", $uid);
        $stmt->execute();
        $presupuestos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            "status" => "success",
            "categorias" => $categorias,
            "transacciones" => $transacciones,
            "presupuestos" => $presupuestos
        ]);
        exit;
    }

    // --- PRESUPUESTOS ---
    else if ($accion === 'sync_presupuesto') {

        $sql = "INSERT INTO Presupuestos (id_usuario, mes, anio, monto)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE monto = VALUES(monto)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siid", $uid, $data['mes'], $data['anio'], $data['monto']);
        $stmt->execute();

        echo json_encode(["status" => "success"]);
        exit;
    }

    echo json_encode(["status" => "success"]);
}


















