<?php
/*
=============================================================================
DIGITAL SIGNAGE SYSTEM - PLAYLIST API
=============================================================================
*/

require_once '../includes/PlaylistManager.php';

// Get variables from main router
global $method, $id, $action, $input, $query, $user;

$playlistManager = new PlaylistManager();

// Check permissions
function checkPlaylistPermission($action) {
    global $user;
    
    $auth = new Auth();
    if (!$auth->hasPermission("playlist.{$action}")) {
        ApiResponse::forbidden("Insufficient permissions for playlist.{$action}");
    }
}

switch ($method) {
    case 'GET':
        if ($id) {
            if ($action === 'items') {
                handleGetPlaylistItems();
            } else {
                handleGetPlaylist();
            }
        } else {
            if ($action === 'stats') {
                handleGetPlaylistStats();
            } else {
                handleGetPlaylistList();
            }
        }
        break;
        
    case 'POST':
        if ($id) {
            switch ($action) {
                case 'duplicate':
                    handleDuplicatePlaylist();
                    break;
                case 'items':
                    handleAddContentToPlaylist();
                    break;
                case 'reorder':
                    handleReorderPlaylistItems();
                    break;
                default:
                    ApiResponse::notFound('Playlist action not found');
            }
        } else {
            handleCreatePlaylist();
        }
        break;
        
    case 'PUT':
    case 'PATCH':
        if ($action === 'items') {
            handleUpdatePlaylistItem();
        } else {
            handleUpdatePlaylist();
        }
        break;
        
    case 'DELETE':
        if ($action === 'items') {
            handleRemoveContentFromPlaylist();
        } else {
            handleDeletePlaylist();
        }
        break;
        
    default:
        ApiResponse::methodNotAllowed();
}

function handleGetPlaylistList() {
    global $query, $playlistManager;
    
    checkPlaylistPermission('view');
    
    try {
        $page = (int)($query['page'] ?? 1);
        $limit = min((int)($query['limit'] ?? 20), 100);
        
        $filters = [];
        if (!empty($query['search'])) $filters['search'] = $query['search'];
        if (isset($query['is_active'])) $filters['is_active'] = (bool)$query['is_active'];
        if (!empty($query['layout_id'])) $filters['layout_id'] = $query['layout_id'];
        if (!empty($query['created_by'])) $filters['created_by'] = $query['created_by'];
        
        $result = $playlistManager->getPlaylists($filters, $page, $limit);
        
        ApiResponse::paginated($result['data'], $result['pagination']);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get playlists: ' . $e->getMessage());
    }
}

function handleGetPlaylist() {
    global $id, $playlistManager;
    
    checkPlaylistPermission('view');
    
    try {
        $playlist = $playlistManager->getPlaylistById($id);
        
        if (!$playlist) {
            ApiResponse::notFound('Playlist not found');
        }
        
        ApiResponse::success(['playlist' => $playlist]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get playlist: ' . $e->getMessage());
    }
}

function handleCreatePlaylist() {
    global $input, $user, $playlistManager;
    
    checkPlaylistPermission('create');
    
    try {
        $input['created_by'] = $user['id'];
        
        $playlist = $playlistManager->createPlaylist($input);
        
        ApiResponse::created(['playlist' => $playlist], 'Playlist created successfully');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to create playlist: ' . $e->getMessage());
    }
}

function handleUpdatePlaylist() {
    global $id, $input, $playlistManager;
    
    checkPlaylistPermission('edit');
    
    try {
        $playlist = $playlistManager->updatePlaylist($id, $input);
        
        if (!$playlist) {
            ApiResponse::notFound('Playlist not found');
        }
        
        ApiResponse::success(['playlist' => $playlist], 'Playlist updated successfully');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to update playlist: ' . $e->getMessage());
    }
}

function handleDeletePlaylist() {
    global $id, $playlistManager;
    
    checkPlaylistPermission('delete');
    
    try {
        $result = $playlistManager->deletePlaylist($id);
        
        if ($result) {
            ApiResponse::success(null, 'Playlist deleted successfully');
        } else {
            ApiResponse::notFound('Playlist not found');
        }
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to delete playlist: ' . $e->getMessage());
    }
}

function handleGetPlaylistItems() {
    global $id, $playlistManager;
    
    checkPlaylistPermission('view');
    
    try {
        $items = $playlistManager->getPlaylistItems($id);
        
        ApiResponse::success(['items' => $items]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get playlist items: ' . $e->getMessage());
    }
}

function handleAddContentToPlaylist() {
    global $id, $input, $playlistManager;
    
    checkPlaylistPermission('edit');
    
    try {
        if (empty($input['content_id'])) {
            ApiResponse::validationError(['content_id' => ['Content ID is required']]);
        }
        
        $itemId = $playlistManager->addContentToPlaylist($id, $input['content_id'], $input);
        
        ApiResponse::created(['item_id' => $itemId], 'Content added to playlist successfully');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to add content to playlist: ' . $e->getMessage());
    }
}

function handleRemoveContentFromPlaylist() {
    global $id, $input, $playlistManager;
    
    checkPlaylistPermission('edit');
    
    try {
        if (empty($input['content_id'])) {
            ApiResponse::validationError(['content_id' => ['Content ID is required']]);
        }
        
        $result = $playlistManager->removeContentFromPlaylist($id, $input['content_id']);
        
        if ($result) {
            ApiResponse::success(null, 'Content removed from playlist successfully');
        } else {
            ApiResponse::notFound('Content not found in playlist');
        }
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to remove content from playlist: ' . $e->getMessage());
    }
}

function handleUpdatePlaylistItem() {
    global $input, $playlistManager;
    
    checkPlaylistPermission('edit');
    
    try {
        if (empty($input['item_id'])) {
            ApiResponse::validationError(['item_id' => ['Item ID is required']]);
        }
        
        $result = $playlistManager->updatePlaylistItem($input['item_id'], $input);
        
        if ($result) {
            ApiResponse::success(null, 'Playlist item updated successfully');
        } else {
            ApiResponse::notFound('Playlist item not found');
        }
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to update playlist item: ' . $e->getMessage());
    }
}

function handleReorderPlaylistItems() {
    global $id, $input, $playlistManager;
    
    checkPlaylistPermission('edit');
    
    try {
        $itemIds = $input['item_ids'] ?? null;
        
        $result = $playlistManager->reorderPlaylistItems($id, $itemIds);
        
        ApiResponse::success(null, 'Playlist items reordered successfully');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to reorder playlist items: ' . $e->getMessage());
    }
}

function handleDuplicatePlaylist() {
    global $id, $playlistManager;
    
    checkPlaylistPermission('create');
    
    try {
        $playlist = $playlistManager->duplicatePlaylist($id);
        
        ApiResponse::created(['playlist' => $playlist], 'Playlist duplicated successfully');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to duplicate playlist: ' . $e->getMessage());
    }
}

function handleGetPlaylistStats() {
    global $playlistManager;
    
    checkPlaylistPermission('view');
    
    try {
        $stats = $playlistManager->getPlaylistStats();
        
        ApiResponse::success(['stats' => $stats]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get playlist stats: ' . $e->getMessage());
    }
}
?>