<?php

return [
    'guard' => 'web',

    'domains' => [
        'beneficiaries',
        'organization',
        'stakeholders',
        'facilitators',
        'human-resources',
        'assets',
        'programs',
        'projects',
        'business-development',
        'task-management',
        'marketing',
        'finance',
        'staff',
        'leave',
        'events',
        'settings',
    ],

    'department_domain_map' => [
        'technical' => ['organization', 'projects', 'programs', 'assets', 'task-management', 'events'],
        'marketing' => ['organization', 'beneficiaries', 'stakeholders', 'facilitators', 'programs', 'task-management', 'events', 'marketing'],
        'admin' => ['organization', 'staff', 'human-resources', 'leave', 'settings', 'task-management', 'finance', 'events'],
        'finance' => ['organization', 'finance', 'settings', 'task-management', 'events'],
        'business development' => ['organization', 'beneficiaries', 'stakeholders', 'projects', 'programs', 'business-development', 'task-management', 'events'],
        'default' => ['organization', 'leave', 'settings', 'task-management'],
    ],
];
