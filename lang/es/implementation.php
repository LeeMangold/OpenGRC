<?php

return [
    'navigation' => [
        'label' => 'Implementaciones',
        'group' => 'Fundamentos',
    ],
    'breadcrumb' => [
        'title' => 'Implementaciones',
    ],
    'table' => [
        'description' => 'Las implementaciones representan el despliegue y operación real de los controles de seguridad dentro de una organización. Son las instancias específicas de cómo se ponen en práctica los controles, incluyendo las herramientas, configuraciones, procesos y procedimientos utilizados. Cada implementación debe documentarse con suficiente detalle para comprender cómo está operando el control, quién es responsable de mantenerlo y cómo se puede verificar su efectividad. Por ejemplo, mientras que un control puede especificar la necesidad de revisiones de acceso, una implementación detallaría el proceso exacto, incluyendo qué herramienta se utiliza, quién realiza las revisiones, con qué frecuencia ocurren y qué documentación se mantiene. Las implementaciones conectan la brecha entre los controles de seguridad teóricos y su aplicación práctica en la organización.',
        'empty_state' => [
            'heading' => 'No se encontraron implementaciones',
            'description' => 'Intente crear una nueva implementación haciendo clic en el botón "Crear Implementación" de arriba.',
        ],
        'columns' => [
            'code' => 'Código',
            'title' => 'Título',
            'effectiveness' => 'Efectividad',
            'last_assessed' => 'Última Auditoría',
            'status' => 'Estado',
            'created_at' => 'Creado El',
            'updated_at' => 'Actualizado El',
        ],
    ],
    'actions' => [
        'create' => 'Crear Implementación',
    ],
]; 