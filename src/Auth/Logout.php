<?php
declare(strict_types=1);

namespace App\Auth;

class Logout
{
    public function handle(): void
    {
        session_start();
        session_destroy();
        header("Location: /login");
        exit;
    }
}

$logout = new Logout();
$logout->handle();