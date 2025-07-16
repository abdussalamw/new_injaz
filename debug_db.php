<?php
include 'db_connection.php';

echo "<h3>بنية جدول products:</h3>";
$result = $conn->query("DESCRIBE products");
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

echo "<h3>بنية جدول order_items:</h3>";
$result = $conn->query("DESCRIBE order_items");
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

echo "<h3>عينة من البيانات في جدول products:</h3>";
$result = $conn->query("SELECT * FROM products LIMIT 5");
if ($result) {
    echo "<table border='1'>";
    if ($result->num_rows > 0) {
        $first_row = true;
        while ($row = $result->fetch_assoc()) {
            if ($first_row) {
                echo "<tr>";
                foreach (array_keys($row) as $key) {
                    echo "<th>" . $key . "</th>";
                }
                echo "</tr>";
                $first_row = false;
            }
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
    } else {
        echo "<tr><td>لا توجد بيانات</td></tr>";
    }
    echo "</table>";
}

echo "<h3>عينة من البيانات في جدول order_items مع أسماء المنتجات:</h3>";
$result = $conn->query("SELECT oi.*, p.name as product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id LIMIT 10");
if ($result) {
    echo "<table border='1'>";
    if ($result->num_rows > 0) {
        $first_row = true;
        while ($row = $result->fetch_assoc()) {
            if ($first_row) {
                echo "<tr>";
                foreach (array_keys($row) as $key) {
                    echo "<th>" . $key . "</th>";
                }
                echo "</tr>";
                $first_row = false;
            }
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
    } else {
        echo "<tr><td>لا توجد بيانات</td></tr>";
    }
    echo "</table>";
}

echo "<h3>اختبار الاستعلام المستخدم في orders.php:</h3>";
$sql = "SELECT o.order_id, o.client_id,
        COALESCE(GROUP_CONCAT(DISTINCT p.name SEPARATOR ', '), 'لا يوجد منتجات') as products_summary
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        GROUP BY o.order_id 
        LIMIT 5";

$result = $conn->query($sql);
if ($result) {
    echo "<table border='1'>";
    if ($result->num_rows > 0) {
        $first_row = true;
        while ($row = $result->fetch_assoc()) {
            if ($first_row) {
                echo "<tr>";
                foreach (array_keys($row) as $key) {
                    echo "<th>" . $key . "</th>";
                }
                echo "</tr>";
                $first_row = false;
            }
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
    } else {
        echo "<tr><td>لا توجد بيانات</td></tr>";
    }
    echo "</table>";
}
?>
