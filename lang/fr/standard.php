<?php

return [
    'navigation' => [
        'label' => 'Standards',
        'group' => 'Fondations',
    ],
    'model' => [
        'label' => 'Standard',
        'plural_label' => 'Standards',
    ],
    'breadcrumb' => [
        'title' => 'Standards',
    ],
    'table' => [
        'description' => 'Les normes définissent le \'quoi\' en matière de sécurité et de conformité en établissant des exigences spécifiques, des directives ou des meilleures pratiques à suivre. Elles servent de points de référence permettant de mesurer la posture de sécurité d\'une organisation. Les normes peuvent provenir de diverses sources, notamment des organismes de réglementation (comme HIPAA ou RGPD), des cadres industriels (tels que ISO 27001 ou NIST), ou des politiques organisationnelles internes. Chaque norme décrit généralement des critères spécifiques qui doivent être respectés pour assurer la conformité ou maintenir la sécurité. Par exemple, une norme de mot de passe peut spécifier des exigences de longueur minimale, des règles de complexité et des périodes d\'expiration. Les normes fournissent la base des contrôles, qui mettent ensuite en œuvre ces exigences de manière pratique.',
        'empty_state' => [
            'heading' => 'Aucune norme trouvée',
            'description' => 'Commencez par importer un ensemble de normes ou créer une nouvelle norme.',
        ],
        'columns' => [
            'code' => 'Code de la Norme',
            'name' => 'Nom de la Norme',
            'description' => 'Description de la Norme',
            'authority' => 'Autorité Émettrice',
            'status' => 'Statut de la Norme',
        ],
        'filters' => [
            'status' => 'Statut de la Norme',
            'authority' => 'Autorité Émettrice',
        ],
        'actions' => [
            'group_label' => 'Actions',
            'set_in_scope' => [
                'label' => 'Mettre Dans le Périmètre',
                'modal_heading' => 'Mettre le Standard Dans le Périmètre',
                'modal_content' => 'Êtes-vous sûr de vouloir mettre ce standard dans le périmètre ? Cela le rendra disponible pour l\'audit.',
                'submit_label' => 'Mettre Dans le Périmètre',
            ],
            'set_out_scope' => [
                'label' => 'Mettre Hors Périmètre',
                'modal_heading' => 'Mettre le Standard Hors Périmètre',
                'modal_content' => 'Êtes-vous sûr de vouloir mettre ce standard hors périmètre ? Cela le rendra indisponible pour l\'audit.',
                'submit_label' => 'Mettre Hors Périmètre',
            ],
        ],
    ],
    'infolist' => [
        'section_title' => 'Détails du Standard',
    ],
]; 