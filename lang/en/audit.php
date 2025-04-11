<?php

return [
    'navigation' => [
        'label' => 'Audits',
        'group' => 'Foundations',
    ],
    'breadcrumb' => [
        'title' => 'Audits',
    ],
    'table' => [
        'empty_state' => [
            'heading' => 'No Audits Created',
            'description' => 'Try creating a new audit by clicking the "Create an Audit" button above to get started!',
        ],
        'columns' => [
            'title' => 'Title',
            'audit_type' => 'Audit Type',
            'status' => 'Status',
            'manager' => 'Manager',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ],
    ],
    'infolist' => [
        'section' => [
            'title' => 'Audit Details',
        ],
    ],
    'actions' => [
        'create' => 'Create Audit',
    ],
]; 