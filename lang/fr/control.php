<?php

return [
    'navigation' => [
        'label' => 'Contrôles',
        'group' => 'Fondations',
    ],
    'model' => [
        'label' => 'Contrôle',
        'plural_label' => 'Contrôles',
    ],
    'breadcrumb' => [
        'title' => 'Contrôles',
    ],
    'form' => [
        'code' => [
            'tooltip' => 'Entrez un code unique pour ce contrôle. Ce code sera utilisé pour identifier ce contrôle dans le système.',
        ],
        'standard' => [
            'label' => 'Norme',
            'tooltip' => 'Tous les contrôles doivent appartenir à une norme. Si vous n\'avez pas de norme à laquelle rattacher ce contrôle, envisagez d\'en créer une d\'abord.',
        ],
        'enforcement' => [
            'tooltip' => 'Sélectionnez une catégorie d\'application pour ce contrôle. Cela aidera à déterminer comment ce contrôle est appliqué.',
        ],
        'title' => [
            'tooltip' => 'Entrez un titre pour ce contrôle.',
        ],
        'description' => [
            'tooltip' => 'Entrez une description pour ce contrôle. Cela doit décrire, en détail, les exigences pour ce contrôle.',
        ],
        'discussion' => [
            'tooltip' => 'Optionnel : Fournissez tout contexte ou information supplémentaire sur ce contrôle qui aiderait quelqu\'un à déterminer comment le mettre en œuvre.',
        ],
        'test' => [
            'label' => 'Plan de Test',
            'tooltip' => 'Optionnel : Comment prévoyez-vous de tester que ce contrôle est en place et efficace ?',
        ],
    ],
    'table' => [
        'description' => 'Les contrôles représentent les mesures de sécurité spécifiques mises en œuvre au sein de votre organisation.',
        'empty_state' => [
            'heading' => 'Aucun contrôle trouvé',
            'description' => 'Commencez par importer un ensemble de standards ou créer un nouveau contrôle.',
        ],
        'columns' => [
            'code' => 'Code du Contrôle',
            'title' => 'Titre du Contrôle',
            'standard' => 'Standard',
            'type' => 'Type de Contrôle',
            'category' => 'Catégorie du Contrôle',
            'enforcement' => 'Niveau d\'Application',
            'effectiveness' => 'Efficacité',
            'applicability' => 'Applicabilité',
            'assessed' => 'Dernière Évaluation',
            'created_at' => 'Créé Le',
            'updated_at' => 'Mis à Jour Le',
        ],
        'filters' => [
            'standard' => 'Standard',
            'effectiveness' => 'Efficacité',
            'type' => 'Type',
            'category' => 'Catégorie',
            'enforcement' => 'Application',
            'applicability' => 'Applicabilité',
        ],
    ],
    'infolist' => [
        'section_title' => 'Détails du Contrôle',
        'test_plan' => 'Plan de Test',
    ],
]; 