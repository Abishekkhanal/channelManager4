<?php
require_once 'config/database.php';

echo "<h2>Blog Database Debug</h2>";

// Check if blogs table exists and its structure
echo "<h3>1. Blogs Table Structure:</h3>";
try {
    $result = $conn->query("DESCRIBE blogs");
    if ($result) {
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Check sample blog data
echo "<h3>2. Sample Blog Data:</h3>";
try {
    $result = $conn->query("SELECT id, title, image, status, created_at FROM blogs LIMIT 5");
    if ($result && $result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Title</th><th>Image Path</th><th>Status</th><th>Created</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>" . htmlspecialchars($row['image']) . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No blog data found.</p>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Check if uploads directory exists
echo "<h3>3. Upload Directory Check:</h3>";
if (is_dir('uploads')) {
    echo "<p>✅ uploads/ directory exists</p>";
    if (is_writable('uploads')) {
        echo "<p>✅ uploads/ directory is writable</p>";
    } else {
        echo "<p>❌ uploads/ directory is not writable</p>";
    }
    
    // List files in uploads
    $files = scandir('uploads');
    echo "<p>Files in uploads/:</p><ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>❌ uploads/ directory does not exist</p>";
}

// Check other required tables
echo "<h3>4. Other Tables Check:</h3>";
$tables = ['blog_categories', 'blog_comments', 'likes'];
foreach ($tables as $table) {
    try {
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "<p>✅ $table table exists with $count records</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ $table table: " . $e->getMessage() . "</p>";
    }
}

echo "<h3>5. Test Image Display:</h3>";
try {
    $result = $conn->query("SELECT id, title, image FROM blogs WHERE image IS NOT NULL AND image != '' LIMIT 3");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
            echo "<h4>" . htmlspecialchars($row['title']) . "</h4>";
            echo "<p>Image path: " . htmlspecialchars($row['image']) . "</p>";
            
            if (!empty($row['image'])) {
                $image_path = $row['image'];
                echo "<p>Full path check: ";
                if (file_exists($image_path)) {
                    echo "✅ File exists";
                    echo "<br><img src='" . htmlspecialchars($image_path) . "' style='max-width: 200px; max-height: 150px;' alt='Test image'>";
                } else {
                    echo "❌ File does not exist at: " . $image_path;
                }
                echo "</p>";
            }
            echo "</div>";
        }
    } else {
        echo "<p>No blogs with images found.</p>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<style>
table { border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>