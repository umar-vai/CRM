<?php

// Reviewed, code-shipped catalog only. Versions are append-only: never edit a released manifest.
return [
    'core_version' => '0.1.0',
    // Preview infrastructure, NOT a billing entitlement or tenant activation mechanism.
    'preview_entitlements' => ['crm', 'configuration'],
    'manifests' => [
        [
            'schema_version' => 1,
            'id' => 'service-crm',
            'version' => '1.0.0',
            'name' => ['en' => 'Service CRM', 'bn' => 'সেবা ব্যবসার CRM'],
            'core_min' => '0.1.0',
            'core_max' => '0.1.99',
            'modules' => ['crm', 'configuration'],
            'permissions' => ['leads.view', 'configuration.preview'],
            'terminology' => ['lead' => 'Inquiry', 'customer' => 'Client'],
            'fields' => [
                ['key' => 'service_interest', 'entity' => 'lead', 'type' => 'text', 'label' => ['en' => 'Service interest', 'bn' => 'আগ্রহের সেবা'], 'required' => false],
            ],
        ],
        [
            'schema_version' => 1,
            'id' => 'clinic-crm',
            'version' => '1.0.0',
            'name' => ['en' => 'Clinic / Wellness CRM', 'bn' => 'ক্লিনিক / ওয়েলনেস CRM'],
            'core_min' => '0.1.0',
            'core_max' => '0.1.99',
            'modules' => ['crm', 'configuration'],
            'permissions' => ['leads.view', 'configuration.preview'],
            'terminology' => ['lead' => 'Inquiry', 'customer' => 'Patient'],
            'fields' => [
                ['key' => 'service_interest', 'entity' => 'lead', 'type' => 'select', 'label' => ['en' => 'Service interest', 'bn' => 'আগ্রহের সেবা'], 'required' => false, 'options' => ['consultation', 'wellness']],
                ['key' => 'preferred_date', 'entity' => 'lead', 'type' => 'date', 'label' => ['en' => 'Preferred date', 'bn' => 'পছন্দের তারিখ'], 'required' => false],
            ],
        ],
    ],
];
