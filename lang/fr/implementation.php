<?php

return [
    'navigation' => [
        'label' => 'Implémentations',
        'group' => 'Fondations',
    ],
    'breadcrumb' => [
        'title' => 'Implémentations',
    ],
    'table' => [
        'description' => 'Les implémentations représentent le déploiement et le fonctionnement réels des contrôles de sécurité au sein d\'une organisation. Ce sont les instances spécifiques de la mise en pratique des contrôles, y compris les outils, les configurations, les processus et les procédures utilisés. Chaque implémentation doit être documentée avec suffisamment de détails pour comprendre comment le contrôle fonctionne, qui est responsable de son maintien et comment son efficacité peut être vérifiée. Par exemple, alors qu\'un contrôle peut spécifier la nécessité d\'examens d\'accès, une implémentation détaillerait le processus exact, y compris l\'outil utilisé, qui effectue les examens, à quelle fréquence ils ont lieu et quelle documentation est maintenue. Les implémentations comblent le fossé entre les contrôles de sécurité théoriques et leur application pratique dans l\'organisation.',
        'empty_state' => [
            'heading' => 'Aucune implémentation trouvée',
            'description' => 'Essayez de créer une nouvelle implémentation en cliquant sur le bouton "Créer une Implémentation" ci-dessus.',
        ],
        'columns' => [
            'code' => 'Code',
            'title' => 'Titre',
            'effectiveness' => 'Efficacité',
            'last_assessed' => 'Dernier Audit',
            'status' => 'Statut',
            'created_at' => 'Créé Le',
            'updated_at' => 'Mis à Jour Le',
        ],
    ],
    'actions' => [
        'create' => 'Créer une Implémentation',
    ],
]; 