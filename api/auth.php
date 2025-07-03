<?php
/*
=============================================================================
DIGITAL SIGNAGE SYSTEM - AUTHENTICATION API
=============================================================================
*/

// Get variables from main router
global $method, $action, $input, $query, $auth, $user;

switch ($method) {
    case 'POST':
        switch ($action) {
            case 'login':
                handleLogin();
                break;
                
            case 'logout':
                handleLogout();
                break;
                
            case 'register':
                handleRegister();
                break;
                
            case 'refresh':
                handleRefreshToken();
                break;
                
            case 'forgot-password':
                handleForgotPassword();
                break;
                
            case 'reset-password':
                handleResetPassword();
                break;
                
            default:
                ApiResponse::notFound('Auth action not found');
        }
        break;
        
    case 'GET':
        switch ($action) {
            case 'me':
                handleGetCurrentUser();
                break;
                
            case 'tokens':
                handleGetUserTokens();
                break;
                
            default:
                ApiResponse::notFound('Auth action not found');
        }
        break;
        
    case 'DELETE':
        switch ($action) {
            case 'token':
                handleRevokeToken();
                break;
                
            default:
                ApiResponse::notFound('Auth action not found');
        }
        break;
        
    default:
        ApiResponse::methodNotAllowed();
}

function handleLogin() {
    global $input, $auth;
    
    // Validate input
    $errors = Helpers::validate($input, [
        'email' => 'required|email',
        'password' => 'required'
    ]);
    
    if (!empty($errors)) {
        ApiResponse::validationError($errors);
    }
    
    try {
        $user = $auth->login($input['email'], $input['password']);
        
        if ($user) {
            // Generate API token if requested
            $token = null;
            if (!empty($input['generate_token'])) {
                $tokenName = $input['token_name'] ?? 'Login Token';
                $token = $auth->generateApiToken($user['id'], $tokenName);
            }
            
            $response = [
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'avatar' => $user['avatar']
                ]
            ];
            
            if ($token) {
                $response['token'] = $token;
            }
            
            ApiResponse::success($response, 'Login successful');
        } else {
            ApiResponse::unauthorized('Invalid email or password');
        }
        
    } catch (Exception $e) {
        ApiResponse::serverError('Login failed: ' . $e->getMessage());
    }
}

function handleLogout() {
    global $auth;
    
    try {
        $auth->logout();
        ApiResponse::success(null, 'Logout successful');
    } catch (Exception $e) {
        ApiResponse::serverError('Logout failed: ' . $e->getMessage());
    }
}

function handleRegister() {
    global $input, $auth;
    
    // Validate input
    $errors = Helpers::validate($input, [
        'name' => 'required|max:255',
        'email' => 'required|email',
        'password' => 'required|min:6',
        'password_confirmation' => 'required'
    ]);
    
    if (!empty($errors)) {
        ApiResponse::validationError($errors);
    }
    
    if ($input['password'] !== $input['password_confirmation']) {
        ApiResponse::validationError(['password_confirmation' => ['Password confirmation does not match']]);
    }
    
    try {
        $userData = [
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'role' => $input['role'] ?? 'viewer'
        ];
        
        $userId = $auth->createUser($userData);
        $user = $auth->getCurrentUser();
        
        ApiResponse::created([
            'user' => [
                'id' => $userId,
                'name' => $userData['name'],
                'email' => $userData['email'],
                'role' => $userData['role']
            ]
        ], 'User registered successfully');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Registration failed: ' . $e->getMessage());
    }
}

function handleGetCurrentUser() {
    global $user;
    
    if (!$user) {
        ApiResponse::unauthorized();
    }
    
    ApiResponse::success([
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'avatar' => $user['avatar'],
            'last_login_at' => $user['last_login_at']
        ]
    ]);
}

function handleGetUserTokens() {
    global $user, $auth;
    
    if (!$user) {
        ApiResponse::unauthorized();
    }
    
    try {
        $tokens = $auth->getUserApiTokens($user['id']);
        
        // Remove actual token values for security
        foreach ($tokens as &$token) {
            $token['token'] = substr($token['token'], 0, 8) . '...';
        }
        
        ApiResponse::success(['tokens' => $tokens]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get tokens: ' . $e->getMessage());
    }
}

function handleRevokeToken() {
    global $input, $auth;
    
    if (empty($input['token'])) {
        ApiResponse::validationError(['token' => ['Token is required']]);
    }
    
    try {
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

function handleRefreshToken() {
    global $user, $auth, $input;
    
    if (!$user) {
        ApiResponse::unauthorized();
    }
    
    try {
        $tokenName = $input['token_name'] ?? 'Refresh Token';
        $token = $auth->generateApiToken($user['id'], $tokenName);
        
        ApiResponse::success(['token' => $token], 'Token refreshed successfully');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to refresh token: ' . $e->getMessage());
    }
}

function handleForgotPassword() {
    global $input;
    
    // Validate input
    $errors = Helpers::validate($input, [
        'email' => 'required|email'
    ]);
    
    if (!empty($errors)) {
        ApiResponse::validationError($errors);
    }
    
    // TODO: Implement password reset email functionality
    ApiResponse::success(null, 'Password reset email sent (if email exists)');
}

function handleResetPassword() {
    global $input;
    
    // Validate input
    $errors = Helpers::validate($input, [
        'token' => 'required',
        'password' => 'required|min:6',
        'password_confirmation' => 'required'
    ]);
    
    if (!empty($errors)) {
        ApiResponse::validationError($errors);
    }
    
    if ($input['password'] !== $input['password_confirmation']) {
        ApiResponse::validationError(['password_confirmation' => ['Password confirmation does not match']]);
    }
    
    // TODO: Implement password reset functionality
    ApiResponse::success(null, 'Password reset successfully');
}
?>