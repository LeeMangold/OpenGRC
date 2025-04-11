<?php

return [
    'form' => [
        'name' => [
            'placeholder' => 'e.g. Critical Security Controls, Version 8',
            'hint' => 'Enter the name of the standard.',
            'tooltip' => 'Need some more information?',
        ],
        'code' => [
            'placeholder' => 'e.g. CSCv8',
            'tooltip' => 'Give the standard a unique ID or Code.',
        ],
        'authority' => [
            'placeholder' => 'e.g. Center for Internet Security',
            'tooltip' => 'Enter the name of the organization that maintains the standard.',
        ],
        'status' => [
            'tooltip' => 'Select the relevance of this standard to your organization.',
        ],
        'reference_url' => [
            'placeholder' => 'e.g. https://www.cisecurity.org/controls/',
            'tooltip' => 'Enter the URL of the official standard document.',
        ],
        'description' => [
            'hint' => 'Describe the purpose and scope of the standard.',
            'placeholder' => 'Description',
        ],
    ],
    'table' => [
        'description' => 'Standards define the \'what\' in security and compliance by establishing specific requirements, guidelines, or best practices that need to be followed. They serve as benchmarks against which an organization\'s security posture can be measured. Standards can originate from various sources, including regulatory bodies (like HIPAA or GDPR), industry frameworks (such as ISO 27001 or NIST), or internal organizational policies. Each standard typically outlines specific criteria that must be met to achieve compliance or maintain security. For example, a password standard might specify minimum length requirements, complexity rules, and expiration periods. Standards provide the foundation for controls, which then implement these requirements in practical ways.',
        'empty_state' => [
            'heading' => 'No standards found',
            'description' => 'Get started by importing a standard bundle or creating a new standard.',
        ],
        'columns' => [
            'code' => 'Standard Code',
            'name' => 'Standard Name',
            'description' => 'Standard Description',
            'authority' => 'Issuing Authority',
            'status' => 'Standard Status',
        ],
        'filters' => [
            'status' => 'Standard Status',
            'authority' => 'Issuing Authority',
        ],
        'actions' => [
            'group_label' => 'Actions',
            'set_in_scope' => [
                'label' => 'Set In Scope',
                'modal_heading' => 'Set Standard In Scope',
                'modal_content' => 'Are you sure you want to set this standard in scope? This will make it available for auditing.',
                'submit_label' => 'Set In Scope',
            ],
            'set_out_scope' => [
                'label' => 'Set Out of Scope',
                'modal_heading' => 'Set Standard Out of Scope',
                'modal_content' => 'Are you sure you want to set this standard out of scope? This will make it unavailable for auditing.',
                'submit_label' => 'Set Out of Scope',
            ],
        ],
    ],
    'infolist' => [
        'section_title' => 'Standard Details',
    ],
    'navigation' => [
        'label' => 'Standards',
        'group' => 'Foundations',
    ],
    'model' => [
        'label' => 'Standard',
        'plural_label' => 'Standards',
    ],
    'breadcrumb' => [
        'title' => 'Standards',
    ],
]; 