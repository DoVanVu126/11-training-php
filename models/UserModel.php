<?php
require_once __DIR__ . '/../configs/database.php';

class UserModel
{
    private $pdo;

    public function __construct()
    {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $this->pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public function findUserById(int $id)
    {
        $stmt = $this->pdo->prepare('SELECT id, name, fullname, email, type FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: false;
    }

    public function findUser(string $keyword)
    {
        $kw = '%' . $keyword . '%';
        $stmt = $this->pdo->prepare('SELECT id, name, fullname, email, type FROM users WHERE name LIKE :kw OR email LIKE :kw');
        $stmt->execute([':kw' => $kw]);
        return $stmt->fetchAll();
    }

    public function auth(string $userName, string $password)
    {
        $stmt = $this->pdo->prepare('SELECT id, name, password FROM users WHERE name = :username LIMIT 1');
        $stmt->execute([':username' => $userName]);
        $user = $stmt->fetch();
        if ($user && md5($password) === $user['password']) {
            return [['id' => $user['id'], 'name' => $user['name']]];
        }
        return false;
    }

    public function deleteUserById(int $id)
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function updateUser(array $input)
    {
        $id = (int)($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        if ($id <= 0 || $name === '') {
            throw new InvalidArgumentException('Invalid input');
        }

        if (!empty($input['password'])) {
            $pwHash = md5($input['password']);
            $stmt = $this->pdo->prepare('UPDATE users SET name = :name, password = :pw WHERE id = :id');
            return $stmt->execute([':name' => $name, ':pw' => $pwHash, ':id' => $id]);
        } else {
            $stmt = $this->pdo->prepare('UPDATE users SET name = :name WHERE id = :id');
            return $stmt->execute([':name' => $name, ':id' => $id]);
        }
    }

    public function insertUser(array $input)
    {
        $name = trim($input['name'] ?? '');
        $password = $input['password'] ?? '';
        $fullname = $input['fullname'] ?? null;
        $email = $input['email'] ?? null;
        $type = $input['type'] ?? 'user';

        if ($name === '' || $password === '') {
            throw new InvalidArgumentException('Invalid input');
        }

        $pwHash = md5($password);
        $stmt = $this->pdo->prepare('INSERT INTO users (name, fullname, email, type, password) VALUES (:name, :fullname, :email, :type, :pw)');
        $stmt->execute([
            ':name' => $name,
            ':fullname' => $fullname,
            ':email' => $email,
            ':type' => $type,
            ':pw' => $pwHash
        ]);
        return $this->pdo->lastInsertId();
    }

    public function getUsers(array $params = [])
    {
        $sql = 'SELECT id, name, fullname, email, type FROM users';
        $bindings = [];
        if (!empty($params['keyword'])) {
            $sql .= ' WHERE name LIKE :kw OR fullname LIKE :kw OR email LIKE :kw';
            $bindings[':kw'] = '%' . $params['keyword'] . '%';
        }
        $sql .= ' ORDER BY id DESC';

        $stmt = $this->pdo->prepare($sql);
        foreach ($bindings as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
