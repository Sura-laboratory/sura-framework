<?php
namespace Sura\Services;

use Sura\Http\Request;
use Sura\Container;

use function session_status;
use function session_unset;
use function session_destroy;
use function setcookie;
use function password_verify;
use function password_hash;

class AuthService
{

    public ?bool $isLogged = null;
    public $user = null;

    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->isLogged = false;
        $this->user = null;
    }

    /**
     * Простая проверка аутентификации:
     * - Если в сессии есть 'user_id' -> пользователь авторизован
     */
    public function check(): bool
    {
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
            return true;
        }

        if (isset($_COOKIE['user_id'], $_COOKIE['password']) && $_COOKIE['user_id'] > 0) {
            return true;
        }

        return false;
    }

    /**
     * Получение информации об авторизованном пользователе.
     * Возвращает null, если пользователь не авторизован.
     */
    public function getUser()
    {
        if ($this->user) {
            return $this->user;
        }

        if (!$this->check()) {
            return null;
        }

        $db = Container::getInstance()->get('db.query');
        $userId = null;
        $useCookieAuth = false;

        if (isset($_SESSION['user_id'])) {
            $userId = (int)$_SESSION['user_id'];
        } elseif (isset($_COOKIE['user_id'], $_COOKIE['password'])) {
            $userId = (int)$_COOKIE['user_id'];
            $useCookieAuth = true;
        }

        if (!$userId) {
            return null;
        }

        $user = $db->fetchOne("SELECT * FROM users WHERE user_id = ?", [$userId]);

        if (!$user || !isset($user['user_id'])) {
            return null;
        }

        // Проверка пароля из cookie, если используется аутентификация по cookie
        if ($useCookieAuth && !password_verify($_COOKIE['password'], $user['user_password'])) {
            $this->logout();
            return null;
        }

        $this->isLogged = true;
        $this->user = $user;

        return $user;
    }

    /**
     * Получает информацию о пользователе по его ID.
     * Проверяет, существует ли пользователь в базе данных, и возвращает его данные.
     *
     * @param int $userId Идентификатор пользователя
     * @return array|null Данные пользователя или null, если не найден
     */
    public function login($credentials): int|false
    {
        $email = trim($credentials['email'] ?? '');
        $password = $credentials['password'] ?? '';

        if (empty($email) || empty($password)) {
            return false;
        }

        $db = Container::getInstance()->get('db.query');
        $user = $db->fetchOne("SELECT * FROM users WHERE user_email = ?", [$email]);

        if (!$user || !password_verify($password, $user['user_password'])) {
            return false;
        }

        // Уничтожаем старую сессию и начинаем новую
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $_SESSION['user_id'] = (int)$user['user_id'];

        // Устанавливаем безопасные cookies (на год)
        $cookieParams = [
            'expires' => time() + 365 * 24 * 60 * 60,
            'path' => '/',
            'domain' => '',
            'secure' => true, // Установите true в продакшене с HTTPS
            'httponly' => true,
            'samesite' => 'Lax'
        ];

        setcookie('user_id', $user['user_id'], $cookieParams);
        setcookie('password', $user['user_password'], $cookieParams);

        $this->isLogged = true;
        $this->user = $user;

        return $user['user_id'];
    }

    /**
     * Выход пользователя: уничтожение сессии и удаление cookie
     */
    public function logout(): bool
    {
        // Очистка сессии
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_unset();
            session_destroy();
        }

        // Удаление cookies
        $past = time() - 3600;
        if (isset($_COOKIE['user_id'])) {
            setcookie('user_id', '', $past, '/', '', false, true);
        }
        if (isset($_COOKIE['password'])) {
            setcookie('password', '', $past, '/', '', false, true);
        }

        // Очистка данных сервиса
        $this->isLogged = false;
        $this->user = null;

        return true;
    }

    /**
     * Регистрация нового пользователя
     */
    public function register($credentials): int|false
    {
        $email = trim($credentials['email'] ?? '');
        $password = $credentials['password'] ?? '';
        $name = trim($credentials['name'] ?? '');
        $last_name = trim($credentials['last_name'] ?? '');

        if (empty($email) || empty($password) || empty($name)) {
            return false;
        }

        // Валидация email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $db = Container::getInstance()->get('db.query');

        // Проверяем, существует ли уже пользователь
        $existingUser = $db->fetchOne("SELECT user_id FROM users WHERE user_email = ?", [$email]);
        if ($existingUser) {
            return false;
        }

        // Хешируем пароль
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Вставляем нового пользователя
        $success = $db->execute(
            "INSERT INTO users (user_email, user_password, user_name, user_last_name, user_age, user_group) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [$email, $hashedPassword, $name, $last_name, 0, 5]
        );

        if (!$success) {
            return false;
        }

        $lastId = $db->lastInsertId();

        // Автоматическая авторизация после регистрации
        $_SESSION['user_id'] = $lastId;
        $this->isLogged = true;
        $this->user = [
            'user_id' => $lastId,
            'user_email' => $email,
            'user_name' => $name,
            'user_last_name' => $last_name,
            'user_age' => 0,
            'user_group' => 5
        ];

        return $lastId;
    }

    /**
     * Найти пользователя по email
     *
     * @param string $email Email пользователя
     * @return array|null Данные пользователя или null, если не найден
     */
    public function findByEmail(string $email): ?array
    {
        $db = Container::getInstance()->get('db.query');
        $user = $db->fetchOne("SELECT * FROM users WHERE user_email = ?", [$email]);

        if ($user) {
            // Убираем хеш пароля из возвращаемых данных
            unset($user['user_password']);
            return $user;
        }

        return null;
    }    
}