<?php

return [
    'navigation' => [
        'label' => 'Implementations',
        'group' => 'Foundations',
    ],
    'breadcrumb' => [
        'title' => 'Implementations',
    ],
    'table' => [
        'description' => 'Implementations represent the actual deployment and operation of security controls within an organization. They are the specific instances of how controls are put into practice, including the tools, configurations, processes, and procedures used. Each implementation should be documented with sufficient detail to understand how the control is operating, who is responsible for maintaining it, and how its effectiveness can be verified. For example, while a control might specify the need for access reviews, an implementation would detail the exact process, including which tool is used, who conducts the reviews, how often they occur, and what documentation is maintained. Implementations bridge the gap between theoretical security controls and their practical application in the organization.',
        'empty_state' => [
            'heading' => 'No implementations found',
            'description' => 'Try creating a new implementation by clicking the "Create Implementation" button above.',
        ],
        'columns' => [
            'code' => 'Code',
            'title' => 'Title',
            'effectiveness' => 'Effectiveness',
            'last_assessed' => 'Last Audit',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ],
    ],
    'actions' => [
        'create' => 'Create Implementation',
    ],
]; 