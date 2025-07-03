<?php
/*
=============================================================================
DIGITAL SIGNAGE SYSTEM - CONTENT API
=============================================================================
*/

require_once '../includes/ContentManager.php';

// Get variables from main router
global $method, $id, $action, $input, $query, $user;

$contentManager = new ContentManager();

// Check permissions
function checkContentPermission($action) {
    global $user;
    
    $auth = new Auth();
    if (!$auth->hasPermission("content.{$action}")) {
        ApiResponse::forbidden("Insufficient permissions for content.{$action}");
    }
}

switch ($method) {
    case 'GET':
        if ($id) {
            if ($action === 'analytics') {
                handleGetContentAnalytics();
            } else {
                handleGetContent();
            }
        } else {
            if ($action === 'stats') {
                handleGetContentStats();
            } else {
                handleGetContentList();
            }
        }
        break;
        
    case 'POST':
        if ($id && $action === 'duplicate') {
            handleDuplicateContent();
        } else {
            handleCreateContent();
        }
        break;
        
    case 'PUT':
    case 'PATCH':
        handleUpdateContent();
        break;
        
    case 'DELETE':
        if ($action === 'permanent') {
            handlePermanentDeleteContent();
        } else {
            handleDeleteContent();
        }
        break;
        
    default:
        ApiResponse::methodNotAllowed();
}

function handleGetContentList() {
    global $query, $contentManager;
    
    checkContentPermission('view');
    
    try {
        $page = (int)($query['page'] ?? 1);
        $limit = min((int)($query['limit'] ?? 20), 100);
        
        $filters = [];
        if (!empty($query['type'])) $filters['type'] = $query['type'];
        if (!empty($query['status'])) $filters['status'] = $query['status'];
        if (!empty($query['search'])) $filters['search'] = $query['search'];
        if (!empty($query['created_by'])) $filters['created_by'] = $query['created_by'];
        
        $result = $contentManager->getContent($filters, $page, $limit);
        
        ApiResponse::paginated($result['data'], $result['pagination']);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get content: ' . $e->getMessage());
    }
}

function handleGetContent() {
    global $id, $contentManager;
    
    checkContentPermission('view');
    
    try {
        $content = $contentManager->getContentById($id);
        
        if (!$content) {
            ApiResponse::notFound('Content not found');
        }
        
        ApiResponse::success(['content' => $content]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get content: ' . $e->getMessage());
    }
}

function handleCreateContent() {
    global $input, $user, $contentManager;
    
    checkContentPermission('create');
    
    try {
        $input['created_by'] = $user['id'];
        
        // Handle file upload
        $file = $_FILES['file'] ?? null;
        
        $content = $contentManager->createContent($input, $file);
        
        ApiResponse::created(['content' => $content], 'Content created successfully');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to create content: ' . $e->getMessage());
    }
}

function handleUpdateContent() {
    global $id, $input, $contentManager;
    
    checkContentPermission('edit');
    
    try {
        // Handle file upload
        $file = $_FILES['file'] ?? null;
        
        $content = $contentManager->updateContent($id, $input, $file);
        
        if (!$content) {
            ApiResponse::notFound('Content not found');
        }
        
        ApiResponse::success(['content' => $content], 'Content updated successfully');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to update content: ' . $e->getMessage());
    }
}

function handleDeleteContent() {
    global $id, $contentManager;
    
    checkContentPermission('delete');
    
    try {
        $result = $contentManager->deleteContent($id);
        
        if ($result) {
            ApiResponse::success(null, 'Content deleted successfully');
        } else {
            ApiResponse::notFound('Content not found');
        }
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to delete content: ' . $e->getMessage());
    }
}

function handlePermanentDeleteContent() {
    global $id, $contentManager;
    
    checkContentPermission('delete');
    
    try {
        $result = $contentManager->permanentlyDeleteContent($id);
        
        if ($result) {
            ApiResponse::success(null, 'Content permanently deleted');
        } else {
            ApiResponse::notFound('Content not found');
        }
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to permanently delete content: ' . $e->getMessage());
    }
}

function handleDuplicateContent() {
    global $id, $contentManager;
    
    checkContentPermission('create');
    
    try {
        $content = $contentManager->duplicateContent($id);
        
        ApiResponse::created(['content' => $content], 'Content duplicated successfully');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to duplicate content: ' . $e->getMessage());
    }
}

function handleGetContentStats() {
    global $contentManager;
    
    checkContentPermission('view');
    
    try {
        $stats = $contentManager->getContentStats();
        
        ApiResponse::success(['stats' => $stats]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get content stats: ' . $e->getMessage());
    }
}

function handleGetContentAnalytics() {
    global $id, $query, $contentManager;
    
    checkContentPermission('view');
    
    try {
        $days = (int)($query['days'] ?? 30);
        $analytics = $contentManager->getContentAnalytics($id, $days);
        
        ApiResponse::success(['analytics' => $analytics]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get content analytics: ' . $e->getMessage());
    }
}
?>