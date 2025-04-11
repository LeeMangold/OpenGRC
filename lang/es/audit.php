<?php

return [
    'navigation' => [
        'label' => 'Auditorías',
        'group' => 'Fundamentos',
    ],
    'breadcrumb' => [
        'title' => 'Auditorías',
    ],
    'table' => [
        'empty_state' => [
            'heading' => 'No hay auditorías creadas',
            'description' => '¡Intente crear una nueva auditoría haciendo clic en el botón "Crear una Auditoría" de arriba para comenzar!',
        ],
        'columns' => [
            'title' => 'Título',
            'audit_type' => 'Tipo de Auditoría',
            'status' => 'Estado',
            'manager' => 'Gerente',
            'start_date' => 'Fecha de Inicio',
            'end_date' => 'Fecha de Finalización',
            'created_at' => 'Creado El',
            'updated_at' => 'Actualizado El',
        ],
    ],
    'infolist' => [
        'section' => [
            'title' => 'Detalles de la Auditoría',
        ],
    ],
    'actions' => [
        'create' => 'Crear Auditoría',
    ],
]; 