<?php
header("Content-Type: application/json");

// 1. Configuración de conexión 
$host = "mysql.railway.internal";
$user = "root";
$pass = "kXkVEuHihxdgdFmpkxhmhUDOmrNkmfLz";
$db   = "railway";
$port = 3306;

/* PRUEBA
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "El servidor funciona. Intentando conectar...";

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die(" Fallo de conexión: " . $conn->connect_error);
}
echo " ¡Conexión exitosa!";
*/ //FIN PRUEBA

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Conexión fallida"]));
}

// 2. Recibir datos JSON de Flutter
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data) {
    $accion = $data['accion'];
    $uid = $data['id_usuario'];

    if ($accion === 'sync_usuario') {
        $nombre = $data['nombre'];
        $foto = $data['foto_url'];
        $email = $data['email'];

        $sql = "INSERT INTO usuarios_remotos (id_usuario, name, image, email) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE name=?, image=?, email=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $uid, $nombre, $foto, $email, $nombre, $foto, $email);
        $stmt->execute();
    } 
    
    else if ($accion === 'sync_categoria') {
        $id_local = $data['id_local'];
        $nombre = $data['nombre'];
        $tipo = $data['tipo'];

        $sql = "INSERT INTO Categorias (id_local_sqlite, id_usuario, nombre, tipo) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), tipo=VALUES(tipo)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $id_local, $uid, $nombre, $tipo);
        $stmt->execute();
    }

    else if ($accion === 'sync_transaccion') {
        $id_local = (int)$data['id_local'];
        $desc = $data['descripcion'];
        $monto = (double)$data['monto'];
        $fecha = $data['fecha'];
        $cat_nom = $data['nombre_categoria'];
        $tipo_t = $data['tipo_transaccion'];
        $clasif = $data['clasificacion'];

        $sql = "INSERT INTO Transacciones (id_local_sqlite, id_usuario, descripcion, monto, fecha, nombre_categoria, tipo_transaccion, clasificacion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                descripcion = VALUES(descripcion), 
                monto = VALUES(monto), 
                fecha = VALUES(fecha),
                nombre_categoria = VALUES(nombre_categoria), 
                tipo_transaccion = VALUES(tipo_transaccion), 
                clasificacion = VALUES(clasificacion)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issdssss", $id_local, $uid, $desc, $monto, $fecha, $cat_nom, $tipo_t, $clasif);
        $stmt->execute();
        
        echo json_encode(["status" => "success", "message" => "Sincronizado correctamente"]);
        exit; 
    }

    else if ($accion === 'delete_transaccion') {
        $id_local = (int)$data['id_local'];
        $sql = "DELETE FROM Transacciones WHERE id_local_sqlite = ? AND id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $id_local, $uid);
        $stmt->execute();
        echo json_encode(["status" => "success", "message" => "Eliminado"]);
        exit; // IMPORTANTE
    }
    
    echo json_encode(["status" => "success", "message" => "Datos procesados"]);
}

?>










