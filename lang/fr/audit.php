<?php

return [
    'navigation' => [
        'label' => 'Audits',
        'group' => 'Fondations',
    ],
    'breadcrumb' => [
        'title' => 'Audits',
    ],
    'table' => [
        'empty_state' => [
            'heading' => 'Aucun audit créé',
            'description' => 'Essayez de créer un nouvel audit en cliquant sur le bouton "Créer un Audit" ci-dessus pour commencer !',
        ],
        'columns' => [
            'title' => 'Titre',
            'audit_type' => 'Type d\'Audit',
            'status' => 'Statut',
            'manager' => 'Gestionnaire',
            'start_date' => 'Date de Début',
            'end_date' => 'Date de Fin',
            'created_at' => 'Créé Le',
            'updated_at' => 'Mis à Jour Le',
        ],
    ],
    'infolist' => [
        'section' => [
            'title' => 'Détails de l\'Audit',
        ],
    ],
    'actions' => [
        'create' => 'Créer un Audit',
    ],
]; 