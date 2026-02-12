<?php

return [
    'guard' => 'web',

    'domains' => [
        'beneficiaries',
        'stakeholders',
        'facilitators',
        'human-resources',
        'assets',
        'programs',
        'projects',
        'staff',
        'leave',
        'settings',
    ],

    'department_domain_map' => [
        'technical' => ['projects', 'programs', 'assets'],
        'marketing' => ['beneficiaries', 'stakeholders', 'facilitators', 'programs'],
        'admin' => ['staff', 'human-resources', 'leave', 'settings'],
        'business development' => ['beneficiaries', 'stakeholders', 'projects', 'programs'],
        'default' => ['leave', 'settings'],
    ],
];
