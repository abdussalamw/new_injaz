<?php

return [
    // === Auth Routes ===
    '/' => [
        'GET' => ['file' => 'src/View/dashboard.php', 'auth' => true]
    ],
    '/dashboard' => [
        'GET' => ['file' => 'src/View/dashboard.php', 'auth' => true]
    ],
    '/login' => [
        'GET' => ['controller' => ['App\Auth\Login', 'show'], 'auth' => false],
        'POST' => ['controller' => ['App\Auth\Login', 'handle'], 'auth' => false]
    ],
    '/logout' => [
        'GET' => ['file' => 'src/Auth/Logout.php', 'auth' => true]
    ],

    // === API Routes ===
    '/api/tasks' => [
        'GET' => ['file' => 'src/Api/FilterTasks.php', 'auth' => true]
    ],
    '/api/clients/search' => [
        'GET' => ['file' => 'src/Api/SearchClient.php', 'auth' => true]
    ],
    '/api/ratings' => [
        'POST' => ['file' => 'src/Api/UpdateRating.php', 'auth' => true]
    ],
    '/api/subscriptions' => [
        'POST' => ['file' => 'src/Api/SaveSubscription.php', 'auth' => true]
    ],
    '/api/orders/status' => [
        'POST' => ['controller' => ['App\Api\ApiController', 'changeOrderStatus'], 'auth' => true]
    ],
    '/api/orders/payment' => [
        'POST' => ['controller' => ['App\Api\ApiController', 'updatePayment'], 'auth' => true]
    ],
    '/api/orders/details' => [
        'GET' => ['controller' => ['App\Api\ApiController', 'getOrderDetails'], 'auth' => true]
    ],
    '/api/orders/confirm-payment' => [
        'POST' => ['controller' => ['App\Api\ApiController', 'confirmPayment'], 'auth' => true]
    ],
    '/api/orders/confirm-delivery' => [
        'POST' => ['controller' => ['App\Api\ApiController', 'confirmDelivery'], 'auth' => true]
    ],

    // === Report Routes ===
    '/reports/financial' => [
        'GET' => ['file' => 'src/Reports/Financial.php', 'auth' => true]
    ],
    '/reports/stats' => [
        'GET' => ['file' => 'src/Reports/Stats.php', 'auth' => true]
    ],
    '/reports/timeline' => [
        'GET' => ['file' => 'src/Reports/Timeline.php', 'auth' => true]
    ],

    // === Main List & Store Routes ===
    '/orders' => [
        'GET' => ['controller' => ['App\Controller\OrderController', 'index'], 'auth' => true],
        'POST' => ['controller' => ['App\Controller\OrderController', 'store'], 'auth' => true]
    ],
    '/clients' => [
        'GET' => ['controller' => ['App\Controller\ClientController', 'index'], 'auth' => true],
        'POST' => ['controller' => ['App\Controller\ClientController', 'store'], 'auth' => true]
    ],
    '/products' => [
        'GET' => ['controller' => ['App\Controller\ProductController', 'index'], 'auth' => true],
        'POST' => ['controller' => ['App\Controller\ProductController', 'store'], 'auth' => true]
    ],
    '/employees' => [
        'GET' => ['controller' => ['App\Controller\EmployeeController', 'index'], 'auth' => true],
        'POST' => ['controller' => ['App\Controller\EmployeeController', 'store'], 'auth' => true]
    ],

    // === Add Routes ===
    '/orders/add' => [
        'GET' => ['controller' => ['App\Controller\OrderController', 'add'], 'auth' => true]
    ],
    '/clients/add' => [
        'GET' => ['controller' => ['App\Controller\ClientController', 'add'], 'auth' => true]
    ],
    '/products/add' => [
        'GET' => ['controller' => ['App\Controller\ProductController', 'add'], 'auth' => true]
    ],
    '/employees/add' => [
        'GET' => ['controller' => ['App\Controller\EmployeeController', 'add'], 'auth' => true]
    ],

    // === Edit Routes ===
    '/orders/edit' => [
        'GET' => ['controller' => ['App\Controller\OrderController', 'edit'], 'auth' => true]
    ],
    '/clients/edit' => [
        'GET' => ['controller' => ['App\Controller\ClientController', 'edit'], 'auth' => true]
    ],
    '/products/edit' => [
        'GET' => ['controller' => ['App\Controller\ProductController', 'edit'], 'auth' => true]
    ],
    '/employees/edit' => [
        'GET' => ['controller' => ['App\Controller\EmployeeController', 'edit'], 'auth' => true]
    ],
    '/employees/permissions' => [
        'GET' => ['controller' => ['App\Controller\EmployeeController', 'permissions'], 'auth' => true],
        'POST' => ['controller' => ['App\Controller\EmployeeController', 'updatePermissions'], 'auth' => true]
    ],

    // === Update Routes ===
    '/orders/update' => [
        'POST' => ['controller' => ['App\Controller\OrderController', 'update'], 'auth' => true]
    ],
    '/clients/update' => [
        'POST' => ['controller' => ['App\Controller\ClientController', 'update'], 'auth' => true]
    ],
    '/products/update' => [
        'POST' => ['controller' => ['App\Controller\ProductController', 'update'], 'auth' => true]
    ],
    '/employees/update' => [
        'POST' => ['controller' => ['App\Controller\EmployeeController', 'update'], 'auth' => true]
    ],

    // === Confirm Delete Routes ===
    '/orders/confirm-delete' => [
        'GET' => ['controller' => ['App\Controller\OrderController', 'confirmDelete'], 'auth' => true]
    ],
    '/clients/confirm-delete' => [
        'GET' => ['controller' => ['App\Controller\ClientController', 'confirmDelete'], 'auth' => true]
    ],
    '/products/confirm-delete' => [
        'GET' => ['controller' => ['App\Controller\ProductController', 'confirmDelete'], 'auth' => true]
    ],
    '/employees/confirm-delete' => [
        'GET' => ['controller' => ['App\Controller\EmployeeController', 'confirmDelete'], 'auth' => true]
    ],

    // === Delete Routes ===
    '/orders/delete' => [
        'POST' => ['controller' => ['App\Controller\OrderController', 'destroy'], 'auth' => true]
    ],
    '/clients/delete' => [
        'POST' => ['controller' => ['App\Controller\ClientController', 'destroy'], 'auth' => true]
    ],
    '/products/delete' => [
        'POST' => ['controller' => ['App\Controller\ProductController', 'destroy'], 'auth' => true]
    ],
    '/employees/delete' => [
        'POST' => ['controller' => ['App\Controller\EmployeeController', 'destroy'], 'auth' => true]
    ],

    // === Export/Import Routes ===
    '/clients/export' => [
        'GET' => ['controller' => ['App\Controller\ClientController', 'export'], 'auth' => true]
    ],
    '/clients/import' => [
        'POST' => ['controller' => ['App\Controller\ClientController', 'import'], 'auth' => true]
    ]
];
