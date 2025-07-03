<?php
/*
=============================================================================
DIGITAL SIGNAGE SYSTEM - LAYOUT API
=============================================================================
*/

// Get variables from main router
global $method, $id, $action, $input, $query, $user;

// Check permissions
function checkLayoutPermission($action) {
    global $user;
    
    $auth = new Auth();
    if (!$auth->hasPermission("layout.{$action}")) {
        ApiResponse::forbidden("Insufficient permissions for layout.{$action}");
    }
}

switch ($method) {
    case 'GET':
        if ($id) {
            handleGetLayout();
        } else {
            handleGetLayoutList();
        }
        break;
        
    case 'POST':
        if ($id && $action === 'duplicate') {
            handleDuplicateLayout();
        } else {
            handleCreateLayout();
        }
        break;
        
    case 'PUT':
    case 'PATCH':
        handleUpdateLayout();
        break;
        
    case 'DELETE':
        handleDeleteLayout();
        break;
        
    default:
        ApiResponse::methodNotAllowed();
}

function handleGetLayoutList() {
    global $query;
    
    checkLayoutPermission('view');
    
    try {
        $db = Database::getInstance();
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($query['type'])) {
            $where[] = 'type = ?';
            $params[] = $query['type'];
        }
        
        if (!empty($query['search'])) {
            $where[] = '(name LIKE ? OR description LIKE ?)';
            $params[] = '%' . $query['search'] . '%';
            $params[] = '%' . $query['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        
        $layouts = $db->fetchAll(
            "SELECT l.*, u.name as creator_name,
                    (SELECT COUNT(*) FROM playlists p WHERE p.layout_id = l.id) as playlist_count
             FROM layouts l 
             LEFT JOIN users u ON l.created_by = u.id 
             WHERE {$whereClause} 
             ORDER BY l.is_default DESC, l.created_at DESC",
            $params
        );
        
        ApiResponse::success(['layouts' => $layouts]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get layouts: ' . $e->getMessage());
    }
}

function handleGetLayout() {
    global $id;
    
    checkLayoutPermission('view');
    
    try {
        $db = Database::getInstance();
        
        $layout = $db->fetchOne(
            "SELECT l.*, u.name as creator_name 
             FROM layouts l 
             LEFT JOIN users u ON l.created_by = u.id 
             WHERE l.id = ?",
            [$id]
        );
        
        if (!$layout) {
            ApiResponse::notFound('Layout not found');
        }
        
        // Parse zones JSON
        if ($layout['zones']) {
            $layout['zones'] = json_decode($layout['zones'], true);
        }
        
        if ($layout['settings']) {
            $layout['settings'] = json_decode($layout['settings'], true);
        }
        
        ApiResponse::success(['layout' => $layout]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get layout: ' . $e->getMessage());
    }
}

function handleCreateLayout() {
    global $input, $user;
    
    checkLayoutPermission('create');
    
    try {
        // Validate required fields
        $errors = Helpers::validate($input, [
            'name' => 'required|max:255',
            'type' => 'required|in:grid,rotation,multi-zone,interactive,fullscreen',
            'template' => 'required|max:100',
            'zones' => 'required'
        ]);
        
        if (!empty($errors)) {
            ApiResponse::validationError($errors);
        }
        
        $db = Database::getInstance();
        
        $layoutData = [
            'name' => Helpers::sanitize($input['name']),
            'description' => Helpers::sanitize($input['description'] ?? ''),
            'type' => $input['type'],
            'template' => $input['template'],
            'orientation' => $input['orientation'] ?? 'landscape',
            'resolution' => $input['resolution'] ?? '1920x1080',
            'zones' => json_encode($input['zones']),
            'is_default' => $input['is_default'] ?? false,
            'created_by' => $user['id']
        ];
        
        if (!empty($input['settings'])) {
            $layoutData['settings'] = json_encode($input['settings']);
        }
        
        // If setting as default, unset other defaults
        if ($layoutData['is_default']) {
            $db->update('layouts', ['is_default' => false], 'is_default = 1');
        }
        
        $layoutId = $db->insert('layouts', $layoutData);
        
        Helpers::logActivity("Layout created: {$layoutData['name']}", 'info', ['layout_id' => $layoutId]);
        
        $layout = $db->fetchOne(
            "SELECT * FROM layouts WHERE id = ?",
            [$layoutId]
        );
        
        if ($layout['zones']) {
            $layout['zones'] = json_decode($layout['zones'], true);
        }
        
        ApiResponse::created(['layout' => $layout], 'Layout created successfully');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to create layout: ' . $e->getMessage());
    }
}

function handleUpdateLayout() {
    global $id, $input;
    
    checkLayoutPermission('edit');
    
    try {
        $db = Database::getInstance();
        
        $existingLayout = $db->fetchOne(
            "SELECT * FROM layouts WHERE id = ?",
            [$id]
        );
        
        if (!$existingLayout) {
            ApiResponse::notFound('Layout not found');
        }
        
        $updateData = [];
        
        if (isset($input['name'])) {
            $updateData['name'] = Helpers::sanitize($input['name']);
        }
        
        if (isset($input['description'])) {
            $updateData['description'] = Helpers::sanitize($input['description']);
        }
        
        if (isset($input['type'])) {
            $updateData['type'] = $input['type'];
        }
        
        if (isset($input['template'])) {
            $updateData['template'] = $input['template'];
        }
        
        if (isset($input['orientation'])) {
            $updateData['orientation'] = $input['orientation'];
        }
        
        if (isset($input['resolution'])) {
            $updateData['resolution'] = $input['resolution'];
        }
        
        if (isset($input['zones'])) {
            $updateData['zones'] = json_encode($input['zones']);
        }
        
        if (isset($input['settings'])) {
            $updateData['settings'] = json_encode($input['settings']);
        }
        
        if (isset($input['is_default'])) {
            $updateData['is_default'] = $input['is_default'];
            
            // If setting as default, unset other defaults
            if ($input['is_default']) {
                $db->update('layouts', ['is_default' => false], 'is_default = 1 AND id != ?', [$id]);
            }
        }
        
        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $db->update('layouts', $updateData, 'id = ?', [$id]);
            
            Helpers::logActivity("Layout updated: {$updateData['name']}", 'info', ['layout_id' => $id]);
        }
        
        $layout = $db->fetchOne(
            "SELECT * FROM layouts WHERE id = ?",
            [$id]
        );
        
        if ($layout['zones']) {
            $layout['zones'] = json_decode($layout['zones'], true);
        }
        
        if ($layout['settings']) {
            $layout['settings'] = json_decode($layout['settings'], true);
        }
        
        ApiResponse::success(['layout' => $layout], 'Layout updated successfully');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to update layout: ' . $e->getMessage());
    }
}

function handleDeleteLayout() {
    global $id;
    
    checkLayoutPermission('delete');
    
    try {
        $db = Database::getInstance();
        
        $layout = $db->fetchOne(
            "SELECT * FROM layouts WHERE id = ?",
            [$id]
        );
        
        if (!$layout) {
            ApiResponse::notFound('Layout not found');
        }
        
        // Check if layout is being used
        $playlistsUsingLayout = $db->fetchAll(
            "SELECT name FROM playlists WHERE layout_id = ?",
            [$id]
        );
        
        if (!empty($playlistsUsingLayout)) {
            $playlistNames = array_column($playlistsUsingLayout, 'name');
            ApiResponse::error(
                'Layout is being used by playlists: ' . implode(', ', $playlistNames),
                400
            );
        }
        
        $devicesUsingLayout = $db->fetchAll(
            "SELECT name FROM devices WHERE current_layout_id = ?",
            [$id]
        );
        
        if (!empty($devicesUsingLayout)) {
            $deviceNames = array_column($devicesUsingLayout, 'name');
            ApiResponse::error(
                'Layout is being used by devices: ' . implode(', ', $deviceNames),
                400
            );
        }
        
        // Don't allow deleting default layout
        if ($layout['is_default']) {
            ApiResponse::error('Cannot delete default layout', 400);
        }
        
        $result = $db->delete('layouts', 'id = ?', [$id]);
        
        if ($result) {
            Helpers::logActivity("Layout deleted: {$layout['name']}", 'warning', ['layout_id' => $id]);
            ApiResponse::success(null, 'Layout deleted successfully');
        } else {
            ApiResponse::serverError('Failed to delete layout');
        }
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to delete layout: ' . $e->getMessage());
    }
}

function handleDuplicateLayout() {
    global $id, $user;
    
    checkLayoutPermission('create');
    
    try {
        $db = Database::getInstance();
        
        $layout = $db->fetchOne(
            "SELECT * FROM layouts WHERE id = ?",
            [$id]
        );
        
        if (!$layout) {
            ApiResponse::notFound('Layout not found');
        }
        
        $newLayoutData = [
            'name' => $layout['name'] . ' (Copy)',
            'description' => $layout['description'],
            'type' => $layout['type'],
            'template' => $layout['template'],
            'orientation' => $layout['orientation'],
            'resolution' => $layout['resolution'],
            'zones' => $layout['zones'],
            'settings' => $layout['settings'],
            'is_default' => false, // Copies are never default
            'created_by' => $user['id']
        ];
        
        $newLayoutId = $db->insert('layouts', $newLayoutData);
        
        Helpers::logActivity("Layout duplicated: {$layout['name']}", 'info', [
            'original_id' => $id,
            'new_id' => $newLayoutId
        ]);
        
        $newLayout = $db->fetchOne(
            "SELECT * FROM layouts WHERE id = ?",
            [$newLayoutId]
        );
        
        if ($newLayout['zones']) {
            $newLayout['zones'] = json_decode($newLayout['zones'], true);
        }
        
        ApiResponse::created(['layout' => $newLayout], 'Layout duplicated successfully');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to duplicate layout: ' . $e->getMessage());
    }
}
?>