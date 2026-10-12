<?php

return [
    'roles' => [
        'owner' => ['leads.view', 'leads.create', 'leads.update', 'leads.activities.create', 'leads.convert', 'customers.view', 'configuration.publish', 'configuration.preview', 'audit.view', 'team.manage'],
        'manager' => ['leads.view', 'leads.create', 'leads.update', 'leads.activities.create', 'leads.convert', 'customers.view', 'audit.view'],
        'agent' => ['leads.view', 'leads.create', 'leads.update', 'leads.activities.create', 'customers.view'],
        'auditor' => ['leads.view', 'customers.view', 'audit.view'],
    ],
    'modules' => [
        'crm' => ['name' => 'CRM', 'status' => 'available', 'dependencies' => []],
        'configuration' => ['name' => 'Customization', 'status' => 'available', 'dependencies' => []],
        'appointments' => ['name' => 'Appointments', 'status' => 'planned', 'dependencies' => ['crm']],
        'sales' => ['name' => 'Sales & payments', 'status' => 'planned', 'dependencies' => ['crm']],
        'inventory' => ['name' => 'Inventory', 'status' => 'planned', 'dependencies' => []],
        'accounting' => ['name' => 'Accounting', 'status' => 'planned', 'dependencies' => []],
    ],
];
