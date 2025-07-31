<?php
declare(strict_types=1);

namespace App\Core;

use mysqli;

class Database
{
    private mysqli $connection;

    public function __construct(
        private string $host,
        private string $username,
        private string $password,
        private string $database
    ) {
        $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);
        $this->connection->set_charset("utf8mb4");

        if ($this->connection->connect_error) {
            // In production, log the error instead of displaying it
            if (getenv('APP_ENV') === 'production') {
                error_log("Database connection failed: " . $this->connection->connect_error);
                die("حدث خطأ في الاتصال بقاعدة البيانات. يرجى المحاولة لاحقاً.");
            } else {
                die("فشل الاتصال بقاعدة البيانات: " . $this->connection->connect_error);
            }
        }
    }

    public function getConnection(): mysqli
    {
        return $this->connection;
    }
}