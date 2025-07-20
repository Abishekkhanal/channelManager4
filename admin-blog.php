<?php
session_start();
require_once 'config/database.php';

// Use your existing admin authentication
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

// Handle status changes
if (isset($_POST['action']) && isset($_POST['blog_id'])) {
    $blog_id = (int)$_POST['blog_id'];
    $action = $_POST['action'];
    
    if ($action === 'publish') {
        $update_sql = "UPDATE blogs SET status = 'published' WHERE id = ?";
    } elseif ($action === 'draft') {
        $update_sql = "UPDATE blogs SET status = 'draft' WHERE id = ?";
    } elseif ($action === 'delete') {
        $update_sql = "DELETE FROM blogs WHERE id = ?";
    }
    
    if (isset($update_sql)) {
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $blog_id);
        $stmt->execute();
        $stmt->close();
    }
    
    header('Location: admin-blog.php');
    exit();
}

// Pagination and filtering
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query conditions
$where_conditions = [];
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search)) {
    $where_conditions[] = "(title LIKE ? OR content LIKE ? OR author LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM blogs $where_clause";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_blogs = $count_result->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $count_result = $conn->query($count_sql);
    $total_blogs = $count_result->fetch_assoc()['total'];
}

$total_pages = ceil($total_blogs / $limit);

// Get blogs with limit and offset
$sql = "SELECT b.*, bc.name as category_name 
        FROM blogs b 
        LEFT JOIN blog_categories bc ON b.category = bc.id 
        $where_clause 
        ORDER BY b.created_at DESC 
        LIMIT ? OFFSET ?";

$limit_params = $params;
$limit_params[] = $limit;
$limit_params[] = $offset;
$limit_types = $types . "ii";

$stmt = $conn->prepare($sql);
if (!empty($limit_params)) {
    $stmt->bind_param($limit_types, ...$limit_params);
}
$stmt->execute();
$result = $stmt->get_result();
$blogs = [];
while ($row = $result->fetch_assoc()) {
    $blogs[] = $row;
}
$stmt->close();

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft
    FROM blogs";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Management - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #1a202c;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .admin-header {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            position: relative;
        }

        .admin-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .breadcrumb {
            color: #718096;
            font-size: 1rem;
        }

        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }

        .back-to-panel {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-to-panel:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-left: 4px solid;
        }

        .stat-card.total { border-left-color: #667eea; }
        .stat-card.published { border-left-color: #48bb78; }
        .stat-card.draft { border-left-color: #ed8936; }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #718096;
            font-weight: 500;
        }

        .controls-bar {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .search-controls {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 5px;
            transition: border-color 0.3s ease;
            min-width: 300px;
        }

        .search-box:focus-within {
            border-color: #667eea;
        }

        .search-box input {
            border: none;
            outline: none;
            padding: 10px 15px;
            flex: 1;
            background: transparent;
            font-size: 0.95rem;
        }

        .search-box button {
            background: #667eea;
            border: none;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .filter-select {
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background: white;
            font-size: 0.95rem;
            min-width: 150px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-success {
            background: #48bb78;
            color: white;
        }

        .btn-warning {
            background: #ed8936;
            color: white;
        }

        .btn-danger {
            background: #f56565;
            color: white;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
        }

        .blogs-table {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .table-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 15px 25px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .table th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody tr:hover {
            background: #f7fafc;
        }

        .blog-title {
            font-weight: 600;
            color: #2d3748;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .blog-excerpt {
            color: #718096;
            font-size: 0.9rem;
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-published {
            background: #c6f6d5;
            color: #22543d;
        }

        .status-draft {
            background: #fed7aa;
            color: #9c4221;
        }

        .actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 30px 0;
        }

        .pagination a,
        .pagination span {
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .pagination a {
            background: white;
            color: #4a5568;
            border: 2px solid #e2e8f0;
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .pagination .current {
            background: #667eea;
            color: white;
            border: 2px solid #667eea;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 15px;
            }

            .controls-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-controls {
                flex-direction: column;
            }

            .search-box {
                min-width: 100%;
            }

            .table-responsive {
                overflow-x: auto;
            }

            .table th,
            .table td {
                padding: 10px 15px;
                font-size: 0.9rem;
            }

            .actions {
                flex-direction: column;
            }

            .back-to-panel {
                position: static;
                margin-top: 20px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <h1><i class="fas fa-blog"></i> Blog Management</h1>
            <div class="breadcrumb">
                <a href="admin_panel.php">Admin Panel</a> / Blog Management
            </div>
            <a href="admin_panel.php" class="back-to-panel">
                <i class="fas fa-arrow-left"></i> Back to Panel
            </a>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Blogs</div>
            </div>
            <div class="stat-card published">
                <div class="stat-number"><?php echo $stats['published']; ?></div>
                <div class="stat-label">Published</div>
            </div>
            <div class="stat-card draft">
                <div class="stat-number"><?php echo $stats['draft']; ?></div>
                <div class="stat-label">Drafts</div>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls-bar">
            <div class="search-controls">
                <form method="GET" class="search-box">
                    <input type="text" name="search" placeholder="Search blogs..." value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
                
                <form method="GET">
                    <select name="status" class="filter-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    </select>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>

            <a href="add_blog.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Blog
            </a>
        </div>

        <!-- Blogs Table -->
        <div class="blogs-table">
            <div class="table-header">
                <div class="table-title">
                    Blogs (<?php echo count($blogs); ?> of <?php echo $total_blogs; ?>)
                </div>
            </div>

            <?php if (empty($blogs)): ?>
                <div class="no-results">
                    <i class="fas fa-file-alt"></i>
                    <h3>No blogs found</h3>
                    <p>Start by creating your first blog post!</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($blogs as $blog): ?>
                                <tr>
                                    <td>
                                        <div class="blog-title" title="<?php echo htmlspecialchars($blog['title']); ?>">
                                            <?php echo htmlspecialchars($blog['title']); ?>
                                        </div>
                                        <div class="blog-excerpt">
                                            <?php echo htmlspecialchars(substr(strip_tags($blog['content']), 0, 100)); ?>...
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($blog['author']); ?></td>
                                    <td><?php echo htmlspecialchars($blog['category_name'] ?: 'Uncategorized'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $blog['status']; ?>">
                                            <?php echo ucfirst($blog['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($blog['created_at'])); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="blog-view.php?slug=<?php echo urlencode($blog['slug']); ?>" 
                                               class="btn btn-sm" style="background: #4299e1; color: white;" 
                                               target="_blank" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <a href="edit_blog.php?id=<?php echo $blog['id']; ?>" 
                                               class="btn btn-sm" style="background: #38b2ac; color: white;" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <?php if ($blog['status'] === 'draft'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="blog_id" value="<?php echo $blog['id']; ?>">
                                                    <input type="hidden" name="action" value="publish">
                                                    <button type="submit" class="btn btn-success btn-sm" title="Publish">
                                                        <i class="fas fa-share"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="blog_id" value="<?php echo $blog['id']; ?>">
                                                    <input type="hidden" name="action" value="draft">
                                                    <button type="submit" class="btn btn-warning btn-sm" title="Move to Draft">
                                                        <i class="fas fa-archive"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this blog?')">
                                                <input type="hidden" name="blog_id" value="<?php echo $blog['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Confirm delete actions
        document.querySelectorAll('form[onsubmit*="confirm"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to delete this blog? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>