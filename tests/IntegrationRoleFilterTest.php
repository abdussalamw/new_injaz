<?php

use PHPUnit\Framework\TestCase;
use App\Core\RoleBasedQuery;

class IntegrationRoleFilterTest extends TestCase
{
    public function testBuildConditionsAndSqlCompatibility()
    {
        // This test asserts that conditions produced are syntactically compatible with a SQL query
        $conds = RoleBasedQuery::buildRoleBasedConditions('مصمم', 2, '', '', '', '', null);
        $this->assertArrayHasKey('where_clauses', $conds);
        $this->assertIsArray($conds['where_clauses']);

        // Build a sample SQL and ensure placeholders align with types/params
        $sql = "SELECT o.* FROM orders o LEFT JOIN employees e ON o.designer_id = e.employee_id";
        if (!empty($conds['where_clauses'])) {
            $sql .= ' WHERE ' . implode(' AND ', $conds['where_clauses']);
        }
        // Basic sanity: no unmatched placeholders for params count
        $placeholders_count = substr_count($sql, '?');
        $this->assertEquals($placeholders_count, count($conds['params']));
    }
}
