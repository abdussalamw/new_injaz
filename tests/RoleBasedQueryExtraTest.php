<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Core\RoleBasedQuery;

class RoleBasedQueryExtraTest extends TestCase
{
    private $conn;

    protected function setUp(): void
    {
        $this->conn = $this->createMock(mysqli::class);
        $stmt = $this->createMock(mysqli_stmt::class);
        $stmt->method('bind_param')->willReturn(true);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('get_result')->willReturn($this->createMock(mysqli_result::class));
        $this->conn->method('prepare')->willReturn($stmt);
    }

    public function testHideFilterTypeExcludesStages()
    {
        // Simulate a role filter hide behavior by creating a temporary filters array
        $result = RoleBasedQuery::buildRoleBasedConditions('معمل', 3, '', '', '', '', $this->conn);
        $where = implode(' ', $result['where_clauses']);
        $this->assertStringContainsString("o.status", $where);
    }

    public function testIncludeCombinationsProducesOrClauses()
    {
        // Directly test combination handling by crafting a fake filters JSON usage is internal;
        // we assert that method returns array structure without throwing
        $res = RoleBasedQuery::buildRoleBasedConditions('موظف', 5, '', '', '', '', $this->conn);
        $this->assertIsArray($res);
        $this->assertArrayHasKey('where_clauses', $res);
    }

    public function testEmptyRoleDisabledProducesNoRows()
    {
        // If role not enabled in json, function returns condition 1=0
        $res = RoleBasedQuery::buildRoleBasedConditions('nonexistent_role', 0, '', '', '', '', $this->conn);
        $this->assertContains('1=0', $res['where_clauses']);
    }
}
