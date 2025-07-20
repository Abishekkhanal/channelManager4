<?php
session_start();
require_once 'config/database.php';

// Check admin authentication
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}

$success = '';
$error = '';

// Get categories for dropdown
$categories_sql = "SELECT id, name FROM blog_categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $content = trim($_POST['content']);
    $author = trim($_POST['author']);
    $category = (int)$_POST['category'];
    $seo_title = trim($_POST['seo_title']);
    $seo_description = trim($_POST['seo_description']);
    $tags = trim($_POST['tags']);
    $status = $_POST['status'];
    
    // Generate slug if not provided
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    }
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $image_path = $upload_path;
        } else {
            $error = "Failed to upload image.";
        }
    }
    
    if (empty($error) && !empty($title) && !empty($content) && !empty($author)) {
        // Check if slug already exists
        $check_sql = "SELECT id FROM blogs WHERE slug = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $slug);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $slug = $slug . '-' . time();
        }
        $check_stmt->close();
        
        // Insert new blog
        $insert_sql = "INSERT INTO blogs (title, slug, content, image, author, seo_title, seo_description, tags, created_at, updated_at, status, category) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?)";
        
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sssssssssi", $title, $slug, $content, $image_path, $author, $seo_title, $seo_description, $tags, $status, $category);
        
        if ($insert_stmt->execute()) {
            $success = "Blog post created successfully!";
            // Clear form data
            $_POST = [];
        } else {
            $error = "Error creating blog post: " . $conn->error;
        }
        $insert_stmt->close();
    } elseif (empty($error)) {
        $error = "Please fill in all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Blog - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- TinyMCE Rich Text Editor -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    
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
            max-width: 1200px;
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

        .back-btn {
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

        .back-btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .form-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .form-header h2 {
            font-size: 1.8rem;
            font-weight: 600;
        }

        .form-body {
            padding: 40px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
        }

        .main-fields {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .sidebar-fields {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.95rem;
        }

        .form-group.required label::after {
            content: '*';
            color: #e53e3e;
            margin-left: 4px;
        }

        .form-control {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-control.large {
            min-height: 120px;
            resize: vertical;
        }

        .file-upload {
            position: relative;
            display: inline-block;
            cursor: pointer;
            width: 100%;
        }

        .file-upload input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 40px 20px;
            border: 2px dashed #cbd5e0;
            border-radius: 10px;
            background: #f7fafc;
            transition: all 0.3s ease;
            min-height: 120px;
            flex-direction: column;
        }

        .file-upload:hover .file-upload-label,
        .file-upload-label.dragover {
            border-color: #667eea;
            background: #edf2f7;
        }

        .preview-container {
            margin-top: 15px;
            text-align: center;
        }

        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .sidebar-widget {
            background: #f7fafc;
            border-radius: 15px;
            padding: 25px;
            border: 1px solid #e2e8f0;
        }

        .widget-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-options {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .status-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .status-option:hover {
            border-color: #cbd5e0;
        }

        .status-option.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }

        .status-option input {
            margin: 0;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e2e8f0;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .btn-success {
            background: #48bb78;
            color: white;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .alert-error {
            background: #fed7d7;
            color: #c53030;
            border: 1px solid #feb2b2;
        }

        .help-text {
            font-size: 0.85rem;
            color: #718096;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .form-body {
                padding: 30px 20px;
            }

            .form-actions {
                flex-direction: column;
            }

            .back-btn {
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
            <h1><i class="fas fa-plus-circle"></i> Add New Blog Post</h1>
            <div class="breadcrumb">
                <a href="admin_panel.php">Admin Panel</a> / 
                <a href="admin-blog.php">Blog Management</a> / Add New
            </div>
            <a href="admin-blog.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Blogs
            </a>
        </div>

        <!-- Form Container -->
        <div class="form-container">
            <div class="form-header">
                <h2>Create Amazing Content</h2>
            </div>

            <div class="form-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <!-- Main Content Fields -->
                        <div class="main-fields">
                            <div class="form-group required">
                                <label for="title">Blog Title</label>
                                <input type="text" id="title" name="title" class="form-control" 
                                       placeholder="Enter an engaging title..." 
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                                <div class="help-text">Make it catchy and SEO-friendly</div>
                            </div>

                            <div class="form-group">
                                <label for="slug">URL Slug</label>
                                <input type="text" id="slug" name="slug" class="form-control" 
                                       placeholder="auto-generated-from-title" 
                                       value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>">
                                <div class="help-text">Leave empty to auto-generate from title</div>
                            </div>

                            <div class="form-group required">
                                <label for="content">Blog Content</label>
                                <textarea id="content" name="content" class="form-control large" 
                                          placeholder="Write your amazing content here..."><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                            </div>

                            <!-- SEO Fields -->
                            <div class="sidebar-widget">
                                <div class="widget-title">
                                    <i class="fas fa-search"></i> SEO Optimization
                                </div>
                                
                                <div class="form-group">
                                    <label for="seo_title">SEO Title</label>
                                    <input type="text" id="seo_title" name="seo_title" class="form-control" 
                                           placeholder="SEO optimized title" maxlength="60"
                                           value="<?php echo htmlspecialchars($_POST['seo_title'] ?? ''); ?>">
                                    <div class="help-text">Recommended: 50-60 characters</div>
                                </div>

                                <div class="form-group">
                                    <label for="seo_description">Meta Description</label>
                                    <textarea id="seo_description" name="seo_description" class="form-control" 
                                              placeholder="Brief description for search engines" maxlength="160" rows="3"><?php echo htmlspecialchars($_POST['seo_description'] ?? ''); ?></textarea>
                                    <div class="help-text">Recommended: 150-160 characters</div>
                                </div>

                                <div class="form-group">
                                    <label for="tags">Tags</label>
                                    <input type="text" id="tags" name="tags" class="form-control" 
                                           placeholder="travel, sikkim, adventure (comma separated)" 
                                           value="<?php echo htmlspecialchars($_POST['tags'] ?? ''); ?>">
                                    <div class="help-text">Separate multiple tags with commas</div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar Fields -->
                        <div class="sidebar-fields">
                            <!-- Publication Status -->
                            <div class="sidebar-widget">
                                <div class="widget-title">
                                    <i class="fas fa-eye"></i> Publication
                                </div>
                                
                                <div class="status-options">
                                    <label class="status-option">
                                        <input type="radio" name="status" value="draft" 
                                               <?php echo (!isset($_POST['status']) || $_POST['status'] === 'draft') ? 'checked' : ''; ?>>
                                        <div>
                                            <strong>Save as Draft</strong>
                                            <div class="help-text">Not visible to public</div>
                                        </div>
                                    </label>
                                    
                                    <label class="status-option">
                                        <input type="radio" name="status" value="published"
                                               <?php echo (isset($_POST['status']) && $_POST['status'] === 'published') ? 'checked' : ''; ?>>
                                        <div>
                                            <strong>Publish Now</strong>
                                            <div class="help-text">Make it live immediately</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Category -->
                            <div class="sidebar-widget">
                                <div class="widget-title">
                                    <i class="fas fa-folder"></i> Category
                                </div>
                                
                                <div class="form-group">
                                    <select name="category" class="form-control">
                                        <option value="0">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"
                                                    <?php echo (isset($_POST['category']) && $_POST['category'] == $category['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Author -->
                            <div class="sidebar-widget">
                                <div class="widget-title">
                                    <i class="fas fa-user"></i> Author
                                </div>
                                
                                <div class="form-group required">
                                    <input type="text" name="author" class="form-control" 
                                           placeholder="Author name" 
                                           value="<?php echo htmlspecialchars($_POST['author'] ?? $_SESSION['admin']); ?>" required>
                                </div>
                            </div>

                            <!-- Featured Image -->
                            <div class="sidebar-widget">
                                <div class="widget-title">
                                    <i class="fas fa-image"></i> Featured Image
                                </div>
                                
                                <div class="file-upload">
                                    <input type="file" name="image" id="image" accept="image/*">
                                    <div class="file-upload-label">
                                        <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: #667eea;"></i>
                                        <div>
                                            <strong>Choose Image</strong>
                                            <div class="help-text">or drag and drop</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-container" id="imagePreview"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="admin-blog.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Blog Post
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Initialize TinyMCE
        tinymce.init({
            selector: '#content',
            height: 400,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
            content_style: 'body { font-family: Inter, sans-serif; font-size: 14px; }',
            branding: false
        });

        // Auto-generate slug from title
        document.getElementById('title').addEventListener('input', function() {
            const title = this.value;
            const slug = title.toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim('-');
            document.getElementById('slug').value = slug;
        });

        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagePreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" class="preview-image" alt="Preview">`;
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        });

        // Drag and drop functionality
        const fileUpload = document.querySelector('.file-upload');
        const fileUploadLabel = document.querySelector('.file-upload-label');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUpload.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileUpload.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileUpload.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            fileUploadLabel.classList.add('dragover');
        }

        function unhighlight(e) {
            fileUploadLabel.classList.remove('dragover');
        }

        fileUpload.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                document.getElementById('image').files = files;
                document.getElementById('image').dispatchEvent(new Event('change'));
            }
        }

        // Status option selection styling
        document.querySelectorAll('input[name="status"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.status-option').forEach(option => {
                    option.classList.remove('selected');
                });
                this.closest('.status-option').classList.add('selected');
            });
        });

        // Initialize selected status option
        document.querySelector('input[name="status"]:checked').closest('.status-option').classList.add('selected');
    </script>
</body>
</html>