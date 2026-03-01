<?php

require __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;

function verificarFirebaseJWT() {

    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (!$authHeader) {
        http_response_code(401);
        echo json_encode(["error" => "Token faltante"]);
        exit;
    }

    $idTokenString = str_replace('Bearer ', '', $authHeader);

    try {

        $firebaseJson = getenv('FIREBASE_CREDENTIALS');

        if (!$firebaseJson) {
            throw new Exception("Credenciales Firebase no configuradas");
        }

        $factory = (new Factory)->withServiceAccount(json_decode($firebaseJson, true));
        $auth = $factory->createAuth();

        $verifiedIdToken = $auth->verifyIdToken($idTokenString);

        return $verifiedIdToken->claims()->get('sub');

    } catch (Throwable $e) {
        http_response_code(401);
        echo json_encode([
            "error" => "Token inválido",
            "detalle" => $e->getMessage()
        ]);
        exit;
    }
}
