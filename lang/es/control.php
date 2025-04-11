<?php

return [
    'navigation' => [
        'label' => 'Controles',
        'group' => 'Fundamentos',
    ],
    'model' => [
        'label' => 'Control',
        'plural_label' => 'Controles',
    ],
    'breadcrumb' => [
        'title' => 'Controles',
    ],
    'form' => [
        'code' => [
            'tooltip' => 'Ingrese un código único para este control. Este código se utilizará para identificar este control en el sistema.',
        ],
        'standard' => [
            'label' => 'Estándar',
            'tooltip' => 'Todos los controles deben pertenecer a un estándar. Si no tiene un estándar para relacionar este control, considere crear uno primero.',
        ],
        'enforcement' => [
            'tooltip' => 'Seleccione una categoría de cumplimiento para este control. Esto ayudará a determinar cómo se aplica este control.',
        ],
        'title' => [
            'tooltip' => 'Ingrese un título para este control.',
        ],
        'description' => [
            'tooltip' => 'Ingrese una descripción para este control. Esto debe describir, en detalle, los requisitos para este control.',
        ],
        'discussion' => [
            'tooltip' => 'Opcional: Proporcione cualquier contexto o información adicional sobre este control que ayude a alguien a determinar cómo implementarlo.',
        ],
        'test' => [
            'label' => 'Plan de Prueba',
            'tooltip' => 'Opcional: ¿Cómo planea probar que este control está implementado y es efectivo?',
        ],
    ],
    'table' => [
        'description' => 'Los controles son medidas específicas de seguridad que implementamos.',
        'empty_state' => [
            'heading' => 'No se encontraron controles',
            'description' => 'Comience importando un paquete de controles o creando un nuevo control.',
        ],
        'columns' => [
            'code' => 'Código del Control',
            'title' => 'Título del Control',
            'type' => 'Tipo de Control',
            'category' => 'Categoría del Control',
            'enforcement' => 'Nivel de Cumplimiento',
            'effectiveness' => 'Efectividad',
            'applicability' => 'Aplicabilidad',
            'assessed' => 'Última Evaluación',
            'created_at' => 'Creado El',
            'updated_at' => 'Actualizado El',
        ],
        'filters' => [
            'type' => 'Tipo de Control',
            'category' => 'Categoría del Control',
            'enforcement' => 'Nivel de Cumplimiento',
        ],
    ],
    'infolist' => [
        'section_title' => 'Detalles del Control',
        'test_plan' => 'Plan de Prueba',
    ],
]; 