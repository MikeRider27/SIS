<?php
// Helpers de autorización para endpoints backend. Requiere sesión iniciada
// (session_start()) antes de incluir este archivo.

if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (empty($_SESSION['idUsuario'])) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
            exit;
        }
    }
}

// Exige que el usuario autenticado tenga el rol indicado (por nombre, ej. 'admin').
if (!function_exists('requireRole')) {
    function requireRole($role) {
        requireLogin();
        if (($_SESSION['rol'] ?? null) !== $role) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'No tiene permisos para realizar esta acción']);
            exit;
        }
    }
}

// Exige que el usuario autenticado tenga el permiso indicado (por code, ej. 'usuarios').
// Los permisos del usuario se cargan en $_SESSION['permisos'] al iniciar sesión (ver ingresar.php).
if (!function_exists('requirePermission')) {
    function requirePermission($code) {
        requireLogin();
        if (!in_array($code, $_SESSION['permisos'] ?? [], true)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'No tiene permisos para realizar esta acción']);
            exit;
        }
    }
}
