<?php
/*
=============================================================================
DIGITAL SIGNAGE SYSTEM - USER MANAGEMENT API
=============================================================================
*/

// Get variables from main router
global $method, $id, $action, $input, $query, $user;

// Check permissions
function checkUserPermission($action) {
    global $user;
    
    $auth = new Auth();
    if (!$auth->hasPermission("user.{$action}")) {
        ApiResponse::forbidden("Insufficient permissions for user.{$action}");
    }
}

switch ($method) {
    case 'GET':
        if ($id) {
            switch ($action) {
                case 'activities':
                    handleGetUserActivities();
                    break;
                case 'tokens':
                    handleGetUserTokens();
                    break;
                default:
                    handleGetUser();
            }
        } else {
            handleGetUserList();
        }
        break;
        
    case 'POST':
        switch ($action) {
            case 'change-password':
                handleChangePassword();
                break;
            case 'generate-token':
                handleGenerateToken();
                break;
            case 'revoke-token':
                handleRevokeToken();
                break;
            default:
                handleCreateUser();
        }
        break;
        
    case 'PUT':
    case 'PATCH':
        handleUpdateUser();
        break;
        
    case 'DELETE':
        handleDeleteUser();
        break;
        
    default:
        ApiResponse::methodNotAllowed();
}

function handleGetUserList() {
    global $query;
    
    checkUserPermission('view');
    
    try {
        $auth = new Auth();
        
        $filters = [];
        if (!empty($query['role'])) $filters['role'] = $query['role'];
        if (!empty($query['search'])) $filters['search'] = $query['search'];
        
        $users = $auth->getUsers($filters);
        
        ApiResponse::success(['users' => $users]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get users: ' . $e->getMessage());
    }
}

function handleGetUser() {
    global $id;
    
    checkUserPermission('view');
    
    try {
        $db = Database::getInstance();
        
        $user = $db->fetchOne(
            "SELECT id, name, email, role, avatar, is_active, last_login_at, created_at 
             FROM users WHERE id = ?",
            [$id]
        );
        
        if (!$user) {
            ApiResponse::notFound('User not found');
        }
        
        ApiResponse::success(['user' => $user]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get user: ' . $e->getMessage());
    }
}

function handleCreateUser() {
    global $input;
    
    checkUserPermission('create');
    
    try {
        $auth = new Auth();
        
        // Validate input
        $errors = Helpers::validate($input, [
            'name' => 'required|max:255',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,manager,editor,viewer'
        ]);
        
        if (!empty($errors)) {
            ApiResponse::validationError($errors);
        }
        
        $userId = $auth->createUser($input);
        
        $user = $db->fetchOne(
            "SELECT id, name, email, role, is_active, created_at 
             FROM users WHERE id = ?",
            [$userId]
        );
        
        ApiResponse::created(['user' => $user], 'User created successfully');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to create user: ' . $e->getMessage());
    }
}

function handleUpdateUser() {
    global $id, $input;
    
    checkUserPermission('edit');
    
    try {
        $auth = new Auth();
        
        $result = $auth->updateUser($id, $input);
        
        if ($result) {
            $db = Database::getInstance();
            $user = $db->fetchOne(
                "SELECT id, name, email, role, avatar, is_active, updated_at 
                 FROM users WHERE id = ?",
                [$id]
            );
            
            ApiResponse::success(['user' => $user], 'User updated successfully');
        } else {
            ApiResponse::notFound('User not found');
        }
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to update user: ' . $e->getMessage());
    }
}

function handleDeleteUser() {
    global $id;
    
    checkUserPermission('delete');
    
    try {
        $auth = new Auth();
        
        $result = $auth->deleteUser($id);
        
        if ($result) {
            ApiResponse::success(null, 'User deleted successfully');
        } else {
            ApiResponse::notFound('User not found');
        }
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to delete user: ' . $e->getMessage());
    }
}

function handleChangePassword() {
    global $input, $user;
    
    try {
        // Users can change their own password, or admins can change any password
        $targetUserId = $input['user_id'] ?? $user['id'];
        
        if ($targetUserId != $user['id'] && $user['role'] !== 'admin') {
            ApiResponse::forbidden('Can only change your own password');
        }
        
        $errors = Helpers::validate($input, [
            'current_password' => 'required',
            'new_password' => 'required|min:6',
            'new_password_confirmation' => 'required'
        ]);
        
        if (!empty($errors)) {
            ApiResponse::validationError($errors);
        }
        
        if ($input['new_password'] !== $input['new_password_confirmation']) {
            ApiResponse::validationError([
                'new_password_confirmation' => ['Password confirmation does not match']
            ]);
        }
        
        $auth = new Auth();
        $result = $auth->changePassword(
            $targetUserId,
            $input['current_password'],
            $input['new_password']
        );
        
        if ($result) {
            ApiResponse::success(null, 'Password changed successfully');
        } else {
            ApiResponse::serverError('Failed to change password');
        }
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to change password: ' . $e->getMessage());
    }
}

function handleGenerateToken() {
    global $input, $user;
    
    try {
        $targetUserId = $input['user_id'] ?? $user['id'];
        
        if ($targetUserId != $user['id'] && $user['role'] !== 'admin') {
            ApiResponse::forbidden('Can only generate tokens for yourself');
        }
        
        $tokenName = $input['name'] ?? 'API Token';
        $expiresInDays = $input['expires_in_days'] ?? 30;
        
        $auth = new Auth();
        $token = $auth->generateApiToken($targetUserId, $tokenName, $expiresInDays);
        
        ApiResponse::created(['token' => $token], 'API token generated successfully');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to generate token: ' . $e->getMessage());
    }
}

function handleRevokeToken() {
    global $input;
    
    try {
        if (empty($input['token'])) {
            ApiResponse::validationError(['token' => ['Token is required']]);
        }
        
        $auth = new Auth();
        $result = $auth->revokeApiToken($input['token']);
        
        if ($result) {
            ApiResponse::success(null, 'Token revoked successfully');
        } else {
            ApiResponse::notFound('Token not found');
        }
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to revoke token: ' . $e->getMessage());
    }
}

function handleGetUserTokens() {
    global $id, $user;
    
    try {
        // Users can view their own tokens, admins can view any user's tokens
        if ($id != $user['id'] && $user['role'] !== 'admin') {
            ApiResponse::forbidden('Can only view your own tokens');
        }
        
        $auth = new Auth();
        $tokens = $auth->getUserApiTokens($id);
        
        // Remove actual token values for security
        foreach ($tokens as &$token) {
            $token['token'] = substr($token['token'], 0, 8) . '...';
        }
        
        ApiResponse::success(['tokens' => $tokens]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get user tokens: ' . $e->getMessage());
    }
}

function handleGetUserActivities() {
    global $id, $query, $user;
    
    try {
        // Users can view their own activities, admins can view any user's activities
        if ($id != $user['id'] && $user['role'] !== 'admin') {
            ApiResponse::forbidden('Can only view your own activities');
        }
        
        $limit = min((int)($query['limit'] ?? 50), 200);
        
        $auth = new Auth();
        $activities = $auth->getUserActivities($id, $limit);
        
        ApiResponse::success(['activities' => $activities]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get user activities: ' . $e->getMessage());
    }
}
?>