<?php
// 核心功能函数库

// 自动加载类
spl_autoload_register(function($class) {
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// 安全函数
class Security {
    // 生成密码哈希
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    // 验证密码
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    // 生成安全令牌
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    // 验证API令牌
    public static function validateApiToken($token) {
        if (empty($token) || strlen($token) !== 64) {
            return false;
        }
        
        $db = Database::getInstance();
        $user = $db->fetchOne("SELECT id, username, status FROM users WHERE api_token = ?", [$token]);
        
        return $user && $user['status'] == 1 ? $user : false;
    }
    
    // 清理输入数据
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    // 验证邮箱
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

// 响应处理类
class Response {
    // 返回JSON响应
    public static function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 成功响应
    public static function success($data = null, $message = 'Success') {
        self::json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ]);
    }
    
    // 错误响应
    public static function error($message = 'Error', $status = 400, $errors = null) {
        self::json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
    
    // 重定向
    public static function redirect($url) {
        header("Location: $url");
        exit;
    }
}

// 会话管理类
class Session {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    public static function remove($key) {
        self::start();
        unset($_SESSION[$key]);
    }
    
    public static function destroy() {
        self::start();
        session_destroy();
    }
    
    public static function isLoggedIn($type = 'user') {
        return self::get($type . '_id') !== null;
    }
    
    public static function getCurrentUser($type = 'user') {
        $id = self::get($type . '_id');
        if (!$id) return null;
        
        $db = Database::getInstance();
        $table = $type === 'admin' ? 'admins' : 'users';
        return $db->fetchOne("SELECT * FROM {$table} WHERE id = ?", [$id]);
    }
}

// 验证类
class Validator {
    private $errors = [];
    
    public function required($field, $value, $message = null) {
        if (empty($value)) {
            $this->errors[$field] = $message ?: "{$field}是必填项";
        }
        return $this;
    }
    
    public function minLength($field, $value, $min, $message = null) {
        if (strlen($value) < $min) {
            $this->errors[$field] = $message ?: "{$field}最少需要{$min}个字符";
        }
        return $this;
    }
    
    public function maxLength($field, $value, $max, $message = null) {
        if (strlen($value) > $max) {
            $this->errors[$field] = $message ?: "{$field}最多{$max}个字符";
        }
        return $this;
    }
    
    public function email($field, $value, $message = null) {
        if (!Security::validateEmail($value)) {
            $this->errors[$field] = $message ?: "请输入有效的邮箱地址";
        }
        return $this;
    }
    
    public function unique($field, $value, $table, $excludeId = null, $message = null) {
        $db = Database::getInstance();
        $sql = "SELECT id FROM {$table} WHERE {$field} = ?";
        $params = [$value];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $db->fetchOne($sql, $params);
        if ($result) {
            $this->errors[$field] = $message ?: "{$field}已存在";
        }
        return $this;
    }
    
    public function json($field, $value, $message = null) {
        if (!empty($value)) {
            json_decode($value);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->errors[$field] = $message ?: "{$field}必须是有效的JSON格式";
            }
        }
        return $this;
    }
    
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    public function getErrors() {
        return $this->errors;
    }
}

// 模板引擎类
class TemplateEngine {
    // 替换模板中的占位符
    public static function replacePlaceholders($template, $values) {
        if (empty($template)) return '';
        
        // 如果是字符串模板，直接进行占位符替换
        if (is_string($template)) {
            // 尝试解析为JSON
            $decoded = json_decode($template, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // 是有效的JSON，递归替换
                $result = self::replaceRecursive($decoded, $values);
                return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } else {
                // 纯字符串模板，直接替换占位符
                return preg_replace_callback('/\{\{(\w+)\}\}/', function($matches) use ($values) {
                    $key = $matches[1];
                    return isset($values[$key]) ? $values[$key] : $matches[0];
                }, $template);
            }
        }
        
        // 如果是数组，递归替换
        $result = self::replaceRecursive($template, $values);
        return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
    private static function replaceRecursive($data, $values) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::replaceRecursive($value, $values);
            }
        } elseif (is_string($data)) {
            // 替换 {{KEY}} 格式的占位符
            $data = preg_replace_callback('/\{\{(\w+)\}\}/', function($matches) use ($values) {
                $key = $matches[1];
                return isset($values[$key]) ? $values[$key] : $matches[0];
            }, $data);
        }
        
        return $data;
    }
    
    // 提取模板中的所有占位符
    public static function extractPlaceholders($template) {
        if (empty($template)) return [];
        
        preg_match_all('/\{\{(\w+)\}\}/', $template, $matches);
        return array_unique($matches[1]);
    }
}

// 日志类
class Logger {
    public static function log($message, $level = 'INFO') {
        $logFile = __DIR__ . '/../logs/app.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public static function error($message) {
        self::log($message, 'ERROR');
    }
    
    public static function info($message) {
        self::log($message, 'INFO');
    }
}

// 全局错误处理
set_error_handler(function($severity, $message, $file, $line) {
    Logger::error("PHP Error: {$message} in {$file} on line {$line}");
});

set_exception_handler(function($exception) {
    Logger::error("Uncaught Exception: " . $exception->getMessage());
    if (!headers_sent()) {
        Response::error('服务器内部错误', 500);
    }
});
?>