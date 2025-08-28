<?php
/**
 * فئة مركزية لإدارة قاعدة البيانات
 * توحد جميع عمليات قاعدة البيانات في مكان واحد
 */

namespace App\Config;

use App\Config\Config;
use PDO;
use PDOException;

/**
 * فئة إدارة قاعدة البيانات
 */
class DatabaseManager
{
    private static ?PDO $connection = null;
    private static array $config = [];

    /**
     * الحصول على اتصال قاعدة البيانات
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::initializeConnection();
        }

        return self::$connection;
    }

    /**
     * تهيئة الاتصال بقاعدة البيانات
     */
    private static function initializeConnection(): void
    {
        try {
            self::$config = Config::getDatabaseConfig();

            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                self::$config['host'],
                self::$config['database'],
                self::$config['charset'] ?? 'utf8mb4'
            );

            self::$connection = new PDO(
                $dsn,
                self::$config['username'],
                self::$config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );

        } catch (PDOException $e) {
            throw new PDOException("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
        }
    }

    /**
     * تنفيذ استعلام SELECT
     */
    public static function select(string $query, array $params = []): array
    {
        try {
            $stmt = self::getConnection()->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new PDOException("خطأ في استعلام SELECT: " . $e->getMessage());
        }
    }

    /**
     * تنفيذ استعلام SELECT واحد
     */
    public static function selectOne(string $query, array $params = []): ?array
    {
        try {
            $stmt = self::getConnection()->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            throw new PDOException("خطأ في استعلام SELECT ONE: " . $e->getMessage());
        }
    }

    /**
     * تنفيذ استعلام INSERT
     */
    public static function insert(string $table, array $data): int
    {
        try {
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));

            $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

            $stmt = self::getConnection()->prepare($query);
            $stmt->execute(array_values($data));

            return (int) self::getConnection()->lastInsertId();
        } catch (PDOException $e) {
            throw new PDOException("خطأ في استعلام INSERT: " . $e->getMessage());
        }
    }

    /**
     * تنفيذ استعلام UPDATE
     */
    public static function update(string $table, array $data, array $conditions): bool
    {
        try {
            $set_parts = [];
            $params = [];

            foreach ($data as $column => $value) {
                $set_parts[] = "{$column} = ?";
                $params[] = $value;
            }

            $where_parts = [];
            foreach ($conditions as $column => $value) {
                $where_parts[] = "{$column} = ?";
                $params[] = $value;
            }

            $query = "UPDATE {$table} SET " . implode(', ', $set_parts) . " WHERE " . implode(' AND ', $where_parts);

            $stmt = self::getConnection()->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new PDOException("خطأ في استعلام UPDATE: " . $e->getMessage());
        }
    }

    /**
     * تنفيذ استعلام DELETE
     */
    public static function delete(string $table, array $conditions): bool
    {
        try {
            $where_parts = [];
            $params = [];

            foreach ($conditions as $column => $value) {
                $where_parts[] = "{$column} = ?";
                $params[] = $value;
            }

            $query = "DELETE FROM {$table} WHERE " . implode(' AND ', $where_parts);

            $stmt = self::getConnection()->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new PDOException("خطأ في استعلام DELETE: " . $e->getMessage());
        }
    }

    /**
     * تنفيذ استعلام عام
     */
    public static function execute(string $query, array $params = []): bool
    {
        try {
            $stmt = self::getConnection()->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new PDOException("خطأ في تنفيذ الاستعلام: " . $e->getMessage());
        }
    }

    /**
     * الحصول على عدد الصفوف المتأثرة
     */
    public static function getRowCount(string $table, array $conditions = []): int
    {
        try {
            $query = "SELECT COUNT(*) as count FROM {$table}";

            if (!empty($conditions)) {
                $where_parts = [];
                $params = [];

                foreach ($conditions as $column => $value) {
                    $where_parts[] = "{$column} = ?";
                    $params[] = $value;
                }

                $query .= " WHERE " . implode(' AND ', $where_parts);

                $result = self::selectOne($query, $params);
            } else {
                $result = self::selectOne($query);
            }

            return (int) ($result['count'] ?? 0);
        } catch (PDOException $e) {
            throw new PDOException("خطأ في حساب عدد الصفوف: " . $e->getMessage());
        }
    }

    /**
     * التحقق من وجود سجل
     */
    public static function exists(string $table, array $conditions): bool
    {
        return self::getRowCount($table, $conditions) > 0;
    }

    /**
     * بدء معاملة
     */
    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    /**
     * تأكيد المعاملة
     */
    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    /**
     * إلغاء المعاملة
     */
    public static function rollback(): bool
    {
        return self::getConnection()->rollBack();
    }

    /**
     * تنظيف الاتصال
     */
    public static function close(): void
    {
        self::$connection = null;
    }

    /**
     * الحصول على معلومات قاعدة البيانات
     */
    public static function getDatabaseInfo(): array
    {
        try {
            $stmt = self::getConnection()->query("SELECT DATABASE() as db_name, VERSION() as version");
            $info = $stmt->fetch();

            return [
                'database' => $info['db_name'] ?? 'غير محدد',
                'version' => $info['version'] ?? 'غير محدد',
                'host' => self::$config['host'] ?? 'غير محدد',
                'charset' => self::$config['charset'] ?? 'utf8mb4'
            ];
        } catch (PDOException $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * اختبار الاتصال بقاعدة البيانات
     */
    public static function testConnection(): bool
    {
        try {
            self::getConnection()->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * إنشاء نسخة احتياطية من قاعدة البيانات
     */
    public static function createBackup(string $filename = null): string
    {
        if ($filename === null) {
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        }

        $backup_path = Config::getBackupPath() . '/' . $filename;

        try {
            // الحصول على قائمة الجداول
            $tables = self::select("SHOW TABLES");
            $backup_content = "";

            foreach ($tables as $table) {
                $table_name = current($table);

                // إنشاء بنية الجدول
                $create_table = self::selectOne("SHOW CREATE TABLE {$table_name}");
                $backup_content .= "\n\n" . $create_table['Create Table'] . ";\n\n";

                // إدراج البيانات
                $data = self::select("SELECT * FROM {$table_name}");

                if (!empty($data)) {
                    $backup_content .= "INSERT INTO {$table_name} VALUES ";

                    $rows = [];
                    foreach ($data as $row) {
                        $values = array_map(function($value) {
                            return $value === null ? 'NULL' : "'" . addslashes($value) . "'";
                        }, $row);
                        $rows[] = "(" . implode(", ", $values) . ")";
                    }

                    $backup_content .= implode(",\n", $rows) . ";\n";
                }
            }

            file_put_contents($backup_path, $backup_content);

            return $backup_path;

        } catch (PDOException $e) {
            throw new PDOException("فشل إنشاء النسخة الاحتياطية: " . $e->getMessage());
        }
    }
}
?>
