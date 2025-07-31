<?php

return [
    '/' => [
        'file' => 'src/View/dashboard.php',
        'auth' => true
    ],
    '/login' => [
        'file' => 'src/Auth/Login.php',
        'auth' => false
    ],
    '/logout' => [
        'file' => 'src/Auth/Logout.php',
        'auth' => true
    ],
    '/api/tasks' => [
        'file' => 'src/Api/FilterTasks.php',
        'auth' => true
    ],
    '/api/clients/search' => [
        'file' => 'src/Api/SearchClient.php',
        'auth' => true
    ],
    '/api/ratings' => [
        'file' => 'src/Api/UpdateRating.php',
        'auth' => true
    ],
    '/api/subscriptions' => [
        'file' => 'src/Api/SaveSubscription.php',
        'auth' => true
    ],
    '/reports/financial' => [
        'file' => 'src/Reports/Financial.php',
        'auth' => true
    ],
    '/reports/stats' => [
        'file' => 'src/Reports/Stats.php',
        'auth' => true
    ],
    '/reports/timeline' => [
        'file' => 'src/Reports/Timeline.php',
        'auth' => true
    ],
];
