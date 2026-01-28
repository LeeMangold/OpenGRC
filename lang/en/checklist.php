<?php

return [
    // Checklist Template
    'template' => [
        'navigation' => [
            'label' => 'Checklist Templates',
            'group' => 'Checklists',
        ],
        'model' => [
            'label' => 'Checklist Template',
            'plural_label' => 'Checklist Templates',
        ],
        'form' => [
            'details_section' => 'Template Details',
            'assignment_section' => 'Default Assignment',
            'title' => [
                'label' => 'Title',
            ],
            'description' => [
                'label' => 'Description',
            ],
            'status' => [
                'label' => 'Status',
            ],
            'default_assignee' => [
                'label' => 'Default Assignee',
                'helper' => 'Automatically assign new checklists to this user.',
            ],
            'recurrence_section' => 'Recurrence Settings',
            'recurrence_description' => 'Configure automatic checklist generation on a schedule.',
            'recurrence_frequency' => [
                'label' => 'Frequency',
                'helper' => 'How often should checklists be generated?',
            ],
            'recurrence_interval' => [
                'label' => 'Interval',
                'helper' => 'Generate every X periods (e.g., every 2 weeks).',
            ],
            'recurrence_day_of_week' => [
                'label' => 'Day of Week',
                'helper' => 'For weekly recurrence, which day?',
            ],
            'recurrence_day_of_month' => [
                'label' => 'Day of Month',
                'helper' => 'For monthly recurrence, which day?',
            ],
            'next_due_at' => [
                'label' => 'Next Due At',
            ],
            'items_section' => 'Checklist Items',
            'items_description' => 'Add items to your checklist template. You can reorder items by dragging them.',
            'items_locked' => [
                'message' => 'Checklist items cannot be modified because checklists have already been created from this template.',
            ],
            'new_item' => 'New Item',
            'item_text' => [
                'label' => 'Item Text',
            ],
            'item_type' => [
                'label' => 'Response Type',
            ],
            'is_required' => [
                'label' => 'Required',
            ],
            'allow_comments' => [
                'label' => 'Allow Notes',
                'helper' => 'Let users add notes to their response.',
            ],
            'help_text' => [
                'label' => 'Help Text (optional)',
            ],
            'options' => [
                'label' => 'Options',
            ],
            'option_label' => 'Option Label',
            'add_option' => 'Add Option',
            'add_item' => 'Add Item',
        ],
        'table' => [
            'columns' => [
                'title' => 'Title',
                'status' => 'Status',
                'items_count' => 'Items',
                'checklists_count' => 'Checklists',
                'default_assignee' => 'Default Assignee',
                'recurrence' => 'Recurrence',
                'next_due_at' => 'Next Due',
                'created_by' => 'Created By',
                'created_at' => 'Created At',
            ],
            'filters' => [
                'status' => 'Status',
                'recurrence' => 'Recurring Only',
            ],
            'empty_state' => [
                'heading' => 'No checklist templates',
                'description' => 'Get started by creating your first checklist template.',
            ],
        ],
        'infolist' => [
            'section_title' => 'Template Details',
            'next_due' => 'Next Due At',
        ],
        'actions' => [
            'create_checklist' => 'Create Checklist',
            'duplicate' => 'Duplicate',
        ],
        'notifications' => [
            'locked_title' => 'Template Locked',
            'locked_body' => 'Checklist items cannot be modified and this template cannot be deleted because checklists have been created from it.',
        ],
    ],

    // Checklist
    'checklist' => [
        'navigation' => [
            'label' => 'Checklists',
            'group' => 'Checklists',
        ],
        'model' => [
            'label' => 'Checklist',
            'plural_label' => 'Checklists',
        ],
        'form' => [
            'details_section' => 'Checklist Details',
            'assignment_section' => 'Assignment',
            'template' => [
                'label' => 'Template',
            ],
            'title' => [
                'label' => 'Title Override',
                'helper' => 'Leave blank to use the template title.',
            ],
            'description' => [
                'label' => 'Description Override',
                'helper' => 'Leave blank to use the template description.',
            ],
            'status' => [
                'label' => 'Status',
            ],
            'assigned_to' => [
                'label' => 'Assigned To',
                'helper' => 'The user responsible for completing this checklist.',
            ],
            'due_date' => [
                'label' => 'Due Date',
            ],
            'approver' => [
                'label' => 'Approver',
                'helper' => 'Only this user can approve the checklist. Leave blank to allow anyone with permission.',
            ],
            'approval_signature' => [
                'label' => 'Digital Signature',
                'helper' => 'Enter your name to digitally sign this approval.',
                'placeholder' => 'Type your full name',
            ],
            'approval_notes' => [
                'label' => 'Approval Notes',
                'helper' => 'Optional notes about this approval.',
            ],
            'additional_comments' => 'Additional Notes',
            'additional_comments_placeholder' => 'Add any additional notes or comments here...',
            'item_number' => 'Item :number',
        ],
        'table' => [
            'columns' => [
                'title' => 'Title',
                'template' => 'Template',
                'assigned_to' => 'Assigned To',
                'status' => 'Status',
                'progress' => 'Progress',
                'due_date' => 'Due Date',
                'completed_at' => 'Completed At',
                'approved' => 'Approved',
                'approved_by' => 'Approved By',
                'approved_at' => 'Approved At',
                'created_by' => 'Created By',
                'created_at' => 'Created At',
            ],
            'filters' => [
                'status' => 'Status',
                'template' => 'Template',
                'assigned_to' => 'Assigned To',
                'approved' => 'Approval Status',
            ],
            'empty_state' => [
                'heading' => 'No checklists',
                'description' => 'Create a checklist from an active template to get started.',
            ],
        ],
        'infolist' => [
            'section_title' => 'Checklist Details',
            'general_section' => 'Additional Information',
            'approval_section' => 'Approval Information',
            'approved' => 'Approved',
            'approved_by' => 'Approved By',
            'approved_at' => 'Approved At',
            'approval_notes' => 'Approval Notes',
            'digital_signature' => 'Digital Signature',
            'anyone_can_approve' => 'Anyone with permission',
            'no_approver_assigned' => 'No approver assigned',
        ],
        'actions' => [
            'complete' => 'Complete Checklist',
            'approve' => 'Approve',
            'respond' => 'Fill Out',
            'mark_complete' => 'Mark Complete',
            'templates' => 'Templates',
            'all_templates' => 'All Templates',
            'create_template' => 'Create Template',
            'save_progress' => 'Save Progress',
            'submit' => 'Submit Checklist',
        ],
        'help' => [
            'title' => 'Checklist User Guide',
        ],
        'pages' => [
            'respond' => [
                'title' => 'Complete Checklist',
                'breadcrumb' => 'Complete',
            ],
            'approve' => [
                'title' => 'Approve Checklist',
                'breadcrumb' => 'Approve',
                'summary_section' => 'Checklist Summary',
                'signature_section' => 'Digital Signature',
                'signature_description' => 'By signing below, you confirm that you have reviewed this checklist and approve its completion.',
            ],
        ],
        'modals' => [
            'approve' => [
                'heading' => 'Approve Checklist',
                'description' => 'Are you sure you want to approve this checklist? This action cannot be undone.',
                'confirm' => 'Approve',
            ],
            'submit' => [
                'heading' => 'Submit Checklist',
                'description' => 'Are you sure you want to submit this checklist? Once submitted, you will not be able to modify your responses.',
                'confirm' => 'Submit',
            ],
        ],
        'notifications' => [
            'submitted' => 'Checklist submitted successfully',
            'submitted_body' => 'Your responses have been saved and the checklist has been marked as complete.',
            'approved' => 'Checklist Approved',
            'approved_body' => 'The checklist has been approved and signed.',
            'cannot_approve' => 'Cannot Approve',
            'cannot_approve_incomplete' => 'The checklist must be completed before it can be approved.',
            'already_approved' => 'Already Approved',
            'already_approved_body' => 'This checklist has already been approved.',
            'signature_required' => 'Signature Required',
            'signature_required_body' => 'Please enter your digital signature to approve this checklist.',
            'cannot_modify' => 'Cannot Modify',
            'cannot_modify_body' => 'This checklist cannot be modified in its current state.',
            'progress_saved' => 'Progress Saved',
            'progress_saved_body' => 'Your responses have been saved. You can continue later.',
            'required_missing' => 'Required Items Missing',
            'required_missing_body' => 'Please complete all required items before submitting.',
            'not_authorized_approver' => 'Not Authorized',
            'not_authorized_approver_body' => 'You are not designated as the approver for this checklist.',
        ],
        'answers' => [
            'columns' => [
                'question' => 'Item',
                'type' => 'Type',
                'answer' => 'Response',
                'comment' => 'Notes',
                'answered_at' => 'Responded At',
            ],
        ],
    ],
];
