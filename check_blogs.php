<?php
require_once 'config/database.php';

echo "<h2>Blog Database Check</h2>";
echo "<style>
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
.status-published { background-color: #d4edda; color: #155724; }
.status-draft { background-color: #f8d7da; color: #721c24; }
</style>";

// Check all blogs
$sql = "SELECT id, title, status, category, author, created_at FROM blogs ORDER BY created_at DESC LIMIT 10";
$result = $conn->query($sql);

echo "<h3>Recent Blogs (Last 10):</h3>";
echo "<table>";
echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Category</th><th>Author</th><th>Created</th></tr>";

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $status_class = $row['status'] === 'published' ? 'status-published' : 'status-draft';
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td class='{$status_class}'><strong>" . $row['status'] . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['category']) . "</td>";
        echo "<td>" . htmlspecialchars($row['author']) . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6'>No blogs found!</td></tr>";
}
echo "</table>";

// Count by status
$status_sql = "SELECT status, COUNT(*) as count FROM blogs GROUP BY status";
$status_result = $conn->query($status_sql);

echo "<h3>Blogs by Status:</h3>";
echo "<table>";
echo "<tr><th>Status</th><th>Count</th></tr>";

if ($status_result && $status_result->num_rows > 0) {
    while ($row = $status_result->fetch_assoc()) {
        $status_class = $row['status'] === 'published' ? 'status-published' : 'status-draft';
        echo "<tr>";
        echo "<td class='{$status_class}'><strong>" . $row['status'] . "</strong></td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='2'>No status data found!</td></tr>";
}
echo "</table>";

// Check categories
$cat_sql = "SELECT DISTINCT category FROM blogs WHERE category IS NOT NULL AND category != '' ORDER BY category";
$cat_result = $conn->query($cat_sql);

echo "<h3>Categories Used in Blogs:</h3>";
echo "<ul>";
if ($cat_result && $cat_result->num_rows > 0) {
    while ($row = $cat_result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['category']) . "</li>";
    }
} else {
    echo "<li>No categories found!</li>";
}
echo "</ul>";

// Test the exact query used in blogs.php
echo "<h3>Testing blogs.php Query:</h3>";
$test_sql = "SELECT b.*, b.category as category_name FROM blogs b WHERE status = 'published' ORDER BY b.created_at DESC LIMIT 5";
$test_result = $conn->query($test_sql);

echo "<p><strong>Query:</strong> " . htmlspecialchars($test_sql) . "</p>";
echo "<p><strong>Results found:</strong> " . ($test_result ? $test_result->num_rows : 0) . "</p>";

if ($test_result && $test_result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Category</th></tr>";
    while ($row = $test_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'><strong>❌ No published blogs found! This is why blogs.php is empty.</strong></p>";
    echo "<p><strong>Solution:</strong> Either:</p>";
    echo "<ul>";
    echo "<li>1. Change existing blog status to 'published' in your database</li>";
    echo "<li>2. Create new blogs with 'published' status</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='blogs.php'>→ Go to Blogs Page</a> | <a href='add_blog.php'>→ Add New Blog</a> | <a href='admin-blog.php'>→ Admin Panel</a></p>";
?>