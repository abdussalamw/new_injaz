<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Core\RoleBasedQuery;

class RoleBasedQueryTest extends TestCase
{
    private $conn;

    protected function setUp(): void
    {
        // إعداد اتصال قاعدة البيانات الوهمية
        $this->conn = $this->createMock(mysqli::class);

        // إعداد كائن وهمي لـ mysqli_stmt
        $stmt = $this->createMock(mysqli_stmt::class);
        $stmt->method('bind_param')->willReturn(true);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('get_result')->willReturn($this->createMock(mysqli_result::class));

        // إعداد طريقة prepare لإرجاع الكائن الوهمي
        $this->conn->method('prepare')->willReturn($stmt);
    }

    public function testManagerCanViewAllTasks(): void
    {
        $result = RoleBasedQuery::buildRoleBasedConditions(
            'مدير',
            1,
            '',
            '',
            '',
            '',
            $this->conn
        );

        $this->assertNotEmpty($result['where_clauses']);
        $this->assertStringNotContainsString("o.status = 'مكتمل' AND o.payment_status = 'مدفوع'", implode(' ', $result['where_clauses']));
    }

    public function testDesignerCanViewOnlyDesignTasks(): void
    {
        $result = RoleBasedQuery::buildRoleBasedConditions(
            'مصمم',
            2,
            '',
            '',
            '',
            '',
            $this->conn
        );

        $this->assertNotEmpty($result['where_clauses']);
        $this->assertStringContainsString("o.designer_id = ?", implode(' ', $result['where_clauses']));
    }

    public function testWorkshopCanViewOnlyWorkshopTasks(): void
    {
        $result = RoleBasedQuery::buildRoleBasedConditions(
            'معمل',
            3,
            '',
            '',
            '',
            '',
            $this->conn
        );

        $this->assertNotEmpty($result['where_clauses']);
        $this->assertStringContainsString("o.workshop_id = ?", implode(' ', $result['where_clauses']));
    }

    public function testAccountantCanViewOnlyUnsettledPayments(): void
    {
        $result = RoleBasedQuery::buildRoleBasedConditions(
            'محاسب',
            4,
            '',
            '',
            '',
            '',
            $this->conn
        );

        $this->assertNotEmpty($result['where_clauses']);
        $this->assertStringContainsString("o.payment_settled_at IS NULL", implode(' ', $result['where_clauses']));
    }
}
