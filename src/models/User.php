<?php
class User
{
    private $db; // Conexión a la base de datos

    // Constructor: obtiene la conexión a la base de datos
    public function __construct()
    {
        $this->db = getConnection(); // Obtiene conexión PDO
    }

    // Método para autenticar un usuario
    public function login($user, $password)
    {
        // Sanitiza y limpia los inputs
        $input_user = trim($user);
        $input_password = trim($password);

        // Valida que no estén vacíos
        if (empty($input_user) || empty($input_password)) {
            return false;
        }

        // Determina si el input es email o nombre de usuario
        $login = filter_var($input_user, FILTER_VALIDATE_EMAIL) ? 'u.email' : 'u.username';
        $user_value = filter_var($input_user, FILTER_VALIDATE_EMAIL) ? $input_user : mb_strtoupper(trim($input_user), 'UTF-8');
        $password_hash = sha1($input_password);
        
        try {
            // Consulta SQL para buscar el usuario
            $sql = "SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.role_id, r.name, r.description, u.is_active 
                    FROM users u INNER JOIN roles r ON u.role_id = r.id
                    WHERE $login = :login AND u.password_hash = :password";

            // Prepara y ejecuta la consulta
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':login', $user_value, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password_hash, PDO::PARAM_STR);
            $stmt->execute();
   
            // Obtiene el resultado
            $user = $stmt->fetch(PDO::FETCH_OBJ);

            // Si encuentra el usuario, retorna un array estructurado
            if ($user) {
                return [
                    'id' => $user->id,
                    'rol_id' => $user->role_id,
                    'rol' => $user->description,
                    'nombre' => $user->first_name,
                    'apellido' => $user->last_name,
                    'nick' => $user->username,
                    'email' => $user->email,
                    'estado' => $user->is_active
                ];
            }

            return false; // Retorna false si no encuentra usuario
        } catch (PDOException $e) {
            error_log("Error en login: " . $e->getMessage()); // Log de errores
            return false;
        }
    }

    // Método para autenticar un usuario
    public function ObtenerUserById($id)
    {
        try {
            // Consulta SQL para buscar el usuario
            $sql = "SELECT id_usuario, nombre, email FROM usuarios WHERE id_usuario = :id_usuario";

            // Prepara y ejecuta la consulta
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id_usuario', $id);
            $stmt->execute();

            // Obtiene el resultado
            $user = $stmt->fetch(PDO::FETCH_OBJ);

            // Si encuentra el usuario, retorna un array estructurado
            if ($user) {
                return [
                    'id' => $user->id_usuario,
                    'nombre' => $user->nombre,
                    'email' => $user->email
                ];
            }

            return false; // Retorna false si no encuentra usuario
        } catch (PDOException $e) {
            error_log("Error en traer datos: " . $e->getMessage()); // Log de errores
            return false;
        }
    }

    // Método para autenticar un usuario
    public function change($id, $password)
    {
        // Sanitiza y limpia los inputs
        $id = intval($id);
        $input_password = trim($password);
        $estado = 1;

        $password_hash = sha1($input_password);

        try {
            // Consulta SQL para actualizar la contraseña y estado
            $sql = "UPDATE public.usuarios 
                SET password = :password, id_estado = :id_estado 
                WHERE id_usuario = :id_usuario";

            // Prepara y ejecuta la consulta
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':password', $password_hash, PDO::PARAM_STR);
            $stmt->bindParam(':id_estado', $estado, PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario', $id, PDO::PARAM_INT);

            $result = $stmt->execute();

            // Verifica si se actualizó alguna fila
            return $result && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al cambiar contraseña: " . $e->getMessage());
            return false;
        }
    }

    // Método para autenticar un usuario
    public function saveUser($data)
    {
        $password_hash = sha1($data['password']); // 👉 usar password_hash en producción
        $conn = $this->db;
        $conn->beginTransaction();

        try {
            // verificamos si el usuario ya existe con la misma cédula o email
            $sqlCheck = "SELECT COUNT(*) FROM usuarios WHERE cedula = :cedula OR email = :email";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bindValue(":cedula", $data['cedula']);
            $stmtCheck->bindValue(":email", $data['email']);
            $stmtCheck->execute();
            $count = $stmtCheck->fetchColumn();

            if ($count > 0) {
                $conn->rollBack();
                return "exists"; // 🚨 devolvemos un string para diferenciar
            }

            $sql = "INSERT INTO usuarios (cedula, nick, nombre, email, password, id_rol, id_estado, sexo)
                VALUES(:cedula, :nick, :nombre, :email, :password, :id_rol, :id_estado, :sexo)";

            $stmt = $conn->prepare($sql);

            $stmt->bindValue(":cedula", $data['cedula']);
            $stmt->bindValue(":nick", $data['nick']);
            $stmt->bindValue(":nombre", $data['nombre']);
            $stmt->bindValue(":email", $data['email']);
            $stmt->bindValue(":password", $password_hash);
            $stmt->bindValue(":id_rol", $data['id_rol']);
            $stmt->bindValue(":id_estado", $data['estado']);
            $stmt->bindValue(":sexo", $data['sexo']);

            $stmt->execute();
            $conn->commit();

            return true;
        } catch (Exception $e) {
            $conn->rollBack();
            return false;
        }
    }



    // Método privado para formatear el nombre completo
    private function formatName($nombre, $apellido)
    {
        $nombreParts = explode(" ", $nombre); // Divide el nombre
        $apellidoParts = explode(" ", $apellido); // Divide el apellido
        return $nombreParts[0] . ' ' . $apellidoParts[0]; // Retorna primer nombre + primer apellido
    }

    public function getPaginated($start, $length, $search = ''): array
    {
        $params = [];
        $sqlBase = "FROM usuarios u INNER JOIN roles r ON u.id_rol = r.id_rol";

        // Construir la cláusula WHERE correctamente
        $whereConditions = [];
        $whereConditions[] = "u.id_estado = 1"; // Siempre filtrar por estado 1

        if (!empty($search)) {
            $whereConditions[] = "LOWER(u.nombre) LIKE :search";
            $params[':search'] = '%' . strtolower($search) . '%';
        }

        $where = '';
        if (!empty($whereConditions)) {
            $where = " WHERE " . implode(" AND ", $whereConditions);
        }

        // Total de registros (sin paginación) - Solo estado 1
        $stmtCount = $this->db->prepare("SELECT COUNT(*) $sqlBase $where");
        foreach ($params as $key => $val) {
            $stmtCount->bindValue($key, $val);
        }
        $stmtCount->execute();
        $total = $stmtCount->fetchColumn();

        // Consulta con ORDER BY + LIMIT + OFFSET - Solo estado 1
        $sql = "SELECT u.id_usuario, u.cedula, u.nick, u.nombre, u.email, u.id_rol, r.descripcion, u.id_estado
                $sqlBase $where
                ORDER BY u.id_usuario ASC
                LIMIT :length OFFSET :start";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
        $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
        $stmt->execute();

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user['id_usuario'],
                'nick' => $user['nick'],
                'cedula' => $user['cedula'],
                'nombre' => $user['nombre'],
                'email' => $user['email'],
                'rol' => $user['descripcion'],
                'estado' => $user['id_estado'] // Para verificación
            ];
        }

        return [
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data
        ];
    }
}
