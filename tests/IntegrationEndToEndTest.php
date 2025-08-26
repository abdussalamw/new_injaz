<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Core\RoleBasedQuery;

class IntegrationEndToEndTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        // create in-memory SQLite DB
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // create tables
        $this->pdo->exec("CREATE TABLE employees (employee_id INTEGER PRIMARY KEY, name TEXT, role TEXT, view_scope TEXT);");
        $this->pdo->exec("CREATE TABLE clients (client_id INTEGER PRIMARY KEY, company_name TEXT, phone TEXT);");
        $this->pdo->exec("CREATE TABLE products (product_id INTEGER PRIMARY KEY, name TEXT);");
        $this->pdo->exec("CREATE TABLE orders (order_id INTEGER PRIMARY KEY, client_id INTEGER, designer_id INTEGER, workshop_id INTEGER, status TEXT, payment_status TEXT, payment_settled_at TEXT, total_amount REAL);");
        $this->pdo->exec("CREATE TABLE order_items (id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER, product_id INTEGER);");

        // seed employees
        $stmt = $this->pdo->prepare("INSERT INTO employees (employee_id, name, role, view_scope) VALUES (?, ?, ?, ?)");
        $stmt->execute([1, 'Admin', 'مدير', 'all']);
        $stmt->execute([2, 'Designer A', 'مصمم', 'role']);
        $stmt->execute([3, 'Workshop A', 'معمل', 'role']);
        $stmt->execute([4, 'Accountant A', 'محاسب', 'self']);

        // seed clients
        $stmt = $this->pdo->prepare("INSERT INTO clients (client_id, company_name, phone) VALUES (?, ?, ?)");
        $stmt->execute([1, 'Client1', '111']);

        // seed products
        $stmt = $this->pdo->prepare("INSERT INTO products (product_id, name) VALUES (?, ?)");
        $stmt->execute([1, 'Product1']);

        // seed orders: designer_id =2, workshop_id=3, various statuses and payment
        $stmt = $this->pdo->prepare("INSERT INTO orders (order_id, client_id, designer_id, workshop_id, status, payment_status, payment_settled_at, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        // Order for designer 2, status قيد التصميم, unpaid
        $stmt->execute([100, 1, 2, null, 'قيد التصميم', 'غير مدفوع', null, 100]);
        // Order for workshop 3, status قيد التنفيذ, unpaid
        $stmt->execute([101, 1, null, 3, 'قيد التنفيذ', 'غير مدفوع', null, 200]);
        // Completed & paid order (should be visible depending on filters)
        $stmt->execute([102, 1, 2, 3, 'مكتمل', 'مدفوع', '2025-01-01', 300]);
        // Order for accountant unpaid large
        $stmt->execute([103, 1, null, null, 'جديد', 'غير مدفوع', null, 500]);

        // order items
        $stmt = $this->pdo->prepare("INSERT INTO order_items (order_id, product_id) VALUES (?, ?)");
        $stmt->execute([100, 1]);
        $stmt->execute([101, 1]);
        $stmt->execute([102, 1]);
        $stmt->execute([103, 1]);
    }

    private function runQueryWithConditions(array $conds)
    {
        // Build SQL similar to InitialTasksQuery but compatible with SQLite and aliases
        $sql = "SELECT o.* FROM orders o
            LEFT JOIN clients c ON o.client_id = c.client_id
            LEFT JOIN order_items oi ON o.order_id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.product_id
            LEFT JOIN employees ed ON o.designer_id = ed.employee_id
            LEFT JOIN employees ew ON o.workshop_id = ew.employee_id";

        if (!empty($conds['where_clauses'])) {
            $sql .= " WHERE " . implode(' AND ', $conds['where_clauses']);
        }

        $stmt = $this->pdo->prepare($sql);
        // bind params sequentially
        foreach (array_values($conds['params']) as $idx => $p) {
            // PDO params are 1-based
            $stmt->bindValue($idx+1, $p);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function testDesignerSeesOnlyTheirOrders()
    {
        $conds = RoleBasedQuery::buildRoleBasedConditions('مصمم', 2, '', '', '', '', null);
        $rows = $this->runQueryWithConditions($conds);
        // Expect orders 100 and 102 (both have designer_id=2)
        $ids = array_column($rows, 'order_id');
        sort($ids);
        $this->assertEquals([100,102], $ids);
    }

    public function testWorkshopSeesOnlyTheirOrders()
    {
        $conds = RoleBasedQuery::buildRoleBasedConditions('معمل', 3, '', '', '', '', null);
        $rows = $this->runQueryWithConditions($conds);
        // Expect orders 101 and 102 (workshop_id=3)
        $ids = array_column($rows, 'order_id');
        sort($ids);
        $this->assertEquals([101,102], $ids);
    }

    public function testAccountantSeesUnsettledPayments()
    {
        $conds = RoleBasedQuery::buildRoleBasedConditions('محاسب', 4, '', '', '', '', null);
        $rows = $this->runQueryWithConditions($conds);
        // Expect orders where payment_settled_at IS NULL and total_amount > 0: 100,101,103
        $ids = array_column($rows, 'order_id');
        sort($ids);
        $this->assertEquals([100,101,103], $ids);
    }
}
