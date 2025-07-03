<?php
/*
=============================================================================
DIGITAL SIGNAGE SYSTEM - AUTHENTICATION CLASS
=============================================================================
*/

require_once 'Database.php';

class Auth {
    private $db;
    private $sessionTimeout = 3600; // 1 hour
    
    public function __construct() {
        $this->db = Database::getInstance();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Login user with email and password
     */
    public function login($email, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            // Update last login
            $this->db->update('users', 
                ['last_login_at' => date('Y-m-d H:i:s')], 
                'id = ?', 
                [$user['id']]
            );
            
            // Log login activity
            $this->logActivity($user['id'], 'login', 'User logged in');
            
            return $user;
        }
        
        // Log failed login attempt
        $this->logActivity(null, 'failed_login', 'Failed login attempt for email: ' . $email);
        
        return false;
    }
    
    /**
     * Logout current user
     */
    public function logout() {
        if ($this->isLoggedIn()) {
            $userId = $_SESSION['user_id'];
            $this->logActivity($userId, 'logout', 'User logged out');
        }
        
        session_destroy();
        return true;
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['login_time']) && 
            (time() - $_SESSION['login_time']) > $this->sessionTimeout) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    /**
     * Get current logged in user
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->db->fetchOne(
            "SELECT id, name, email, role, avatar, is_active FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );
    }
    
    /**
     * Check if user has required role
     */
    public function hasRole($requiredRole) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $roles = ['viewer' => 1, 'editor' => 2, 'manager' => 3, 'admin' => 4];
        $userRole = $_SESSION['user_role'];
        
        return $roles[$userRole] >= $roles[$requiredRole];
    }
    
    /**
     * Check if user has permission for specific action
     */
    public function hasPermission($action) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $role = $_SESSION['user_role'];
        
        $permissions = [
            'admin' => ['*'], // Admin has all permissions
            'manager' => [
                'content.create', 'content.edit', 'content.delete', 'content.view',
                'playlist.create', 'playlist.edit', 'playlist.delete', 'playlist.view',
                'device.view', 'device.edit', 'device.assign',
                'analytics.view', 'layout.view'
            ],
            'editor' => [
                'content.create', 'content.edit', 'content.view',
                'playlist.create', 'playlist.edit', 'playlist.view',
                'device.view', 'layout.view'
            ],
            'viewer' => [
                'content.view', 'playlist.view', 'device.view', 'analytics.view'
            ]
        ];
        
        if (!isset($permissions[$role])) {
            return false;
        }
        
        return in_array('*', $permissions[$role]) || in_array($action, $permissions[$role]);
    }
    
    /**
     * Generate API token for user
     */
    public function generateApiToken($userId, $name = 'API Token', $expiresInDays = 30) {
        $token = bin2hex(random_bytes(32));
        
        $this->db->insert('api_tokens', [
            'user_id' => $userId,
            'token' => $token,
            'name' => $name,
            'expires_at' => $expiresInDays ? date('Y-m-d H:i:s', time() + ($expiresInDays * 24 * 3600)) : null
        ]);
        
        return $token;
    }
    
    /**
     * Validate API token
     */
    public function validateApiToken($token) {
        $tokenData = $this->db->fetchOne(
            "SELECT t.*, u.id as user_id, u.name, u.email, u.role, u.is_active 
             FROM api_tokens t 
             JOIN users u ON t.user_id = u.id 
             WHERE t.token = ? AND u.is_active = 1 
             AND (t.expires_at IS NULL OR t.expires_at > NOW())",
            [$token]
        );
        
        if ($tokenData) {
            // Update last used
            $this->db->update('api_tokens', 
                ['last_used_at' => date('Y-m-d H:i:s')], 
                'token = ?', 
                [$token]
            );
            
            return $tokenData;
        }
        
        return false;
    }
    
    /**
     * Create new user
     */
    public function createUser($data) {
        // Validate required fields
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            throw new Exception('Name, email, and password are required');
        }
        
        // Check if email already exists
        $existingUser = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = ?",
            [$data['email']]
        );
        
        if ($existingUser) {
            throw new Exception('Email already exists');
        }
        
        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => $data['role'] ?? 'viewer',
            'is_active' => $data['is_active'] ?? true
        ];
        
        $userId = $this->db->insert('users', $userData);
        
        $this->logActivity($userId, 'user_created', 'User account created');
        
        return $userId;
    }
    
    /**
     * Update user
     */
    public function updateUser($userId, $data) {
        $updateData = [];
        
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        
        if (isset($data['email'])) {
            // Check if email already exists for other users
            $existingUser = $this->db->fetchOne(
                "SELECT id FROM users WHERE email = ? AND id != ?",
                [$data['email'], $userId]
            );
            
            if ($existingUser) {
                throw new Exception('Email already exists');
            }
            
            $updateData['email'] = $data['email'];
        }
        
        if (isset($data['password']) && !empty($data['password'])) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (isset($data['role'])) {
            $updateData['role'] = $data['role'];
        }
        
        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'];
        }
        
        $updateData['updated_at'] = date('Y-m-d H:i:s');
        
        $result = $this->db->update('users', $updateData, 'id = ?', [$userId]);
        
        if ($result) {
            $this->logActivity($userId, 'user_updated', 'User account updated');
        }
        
        return $result;
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->db->fetchOne(
            "SELECT password FROM users WHERE id = ?",
            [$userId]
        );
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            throw new Exception('Current password is incorrect');
        }
        
        $result = $this->db->update('users', 
            [
                'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                'updated_at' => date('Y-m-d H:i:s')
            ], 
            'id = ?', 
            [$userId]
        );
        
        if ($result) {
            $this->logActivity($userId, 'password_changed', 'Password changed');
        }
        
        return $result;
    }
    
    /**
     * Get all users
     */
    public function getUsers($filters = []) {
        $where = ['is_active = 1'];
        $params = [];
        
        if (!empty($filters['role'])) {
            $where[] = 'role = ?';
            $params[] = $filters['role'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(name LIKE ? OR email LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        
        return $this->db->fetchAll(
            "SELECT id, name, email, role, avatar, last_login_at, created_at 
             FROM users 
             WHERE {$whereClause} 
             ORDER BY created_at DESC",
            $params
        );
    }
    
    /**
     * Log user activity
     */
    private function logActivity($userId, $action, $description) {
        try {
            $this->db->insert('user_activities', [
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            error_log('Failed to log user activity: ' . $e->getMessage());
        }
    }
}
?>