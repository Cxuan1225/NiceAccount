<?php

return [
    // tables you never want to audit
    'ignore_tables' => [
        'audit_trails',
        'sessions',
        'cache',
        'jobs',
        'failed_jobs',
        'personal_access_tokens',
        // spatie permission tables
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
    ],
];
