<?php

return [
    'navigation' => [
        'label' => 'Revizije',
        'group' => 'Temelji',
    ],
    'breadcrumb' => [
        'title' => 'Revizije',
    ],
    'table' => [
        'empty_state' => [
            'heading' => 'Nema kreiranih revizija',
            'description' => 'Pokušajte kreirati novu reviziju klikom na gumb "Kreiraj Reviziju" iznad za početak!',
        ],
        'columns' => [
            'title' => 'Naslov',
            'audit_type' => 'Vrsta Revizije',
            'status' => 'Status',
            'manager' => 'Upravitelj',
            'start_date' => 'Datum Početka',
            'end_date' => 'Datum Završetka',
            'created_at' => 'Kreirano',
            'updated_at' => 'Ažurirano',
        ],
    ],
    'infolist' => [
        'section' => [
            'title' => 'Detalji Revizije',
        ],
    ],
    'actions' => [
        'create' => 'Kreiraj Reviziju',
    ],
]; 