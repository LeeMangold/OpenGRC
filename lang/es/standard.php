<?php

return [
    'navigation' => [
        'label' => 'Estándares',
        'group' => 'Fundamentos',
    ],
    'model' => [
        'label' => 'Estándar',
        'plural_label' => 'Estándares',
    ],
    'breadcrumb' => [
        'title' => 'Estándares',
    ],
    'table' => [
        'description' => 'Los estándares definen el \'qué\' en seguridad y cumplimiento al establecer requisitos específicos, pautas o mejores prácticas que deben seguirse. Sirven como puntos de referencia contra los cuales se puede medir la postura de seguridad de una organización. Los estándares pueden originarse de diversas fuentes, incluyendo organismos reguladores (como HIPAA o GDPR), marcos de la industria (como ISO 27001 o NIST), o políticas organizativas internas. Cada estándar típicamente describe criterios específicos que deben cumplirse para lograr el cumplimiento o mantener la seguridad. Por ejemplo, un estándar de contraseñas puede especificar requisitos de longitud mínima, reglas de complejidad y períodos de caducidad. Los estándares proporcionan la base para los controles, que luego implementan estos requisitos de manera práctica.',
        'empty_state' => [
            'heading' => 'No se encontraron estándares',
            'description' => 'Comience importando un paquete de estándares o creando un nuevo estándar.',
        ],
        'columns' => [
            'code' => 'Código del Estándar',
            'name' => 'Nombre del Estándar',
            'description' => 'Descripción del Estándar',
            'authority' => 'Autoridad Emisora',
            'status' => 'Estado del Estándar',
        ],
        'filters' => [
            'status' => 'Estado del Estándar',
            'authority' => 'Autoridad Emisora',
        ],
        'actions' => [
            'group_label' => 'Acciones',
            'set_in_scope' => [
                'label' => 'Establecer En Alcance',
                'modal_heading' => 'Establecer Estándar En Alcance',
                'modal_content' => '¿Está seguro de que desea establecer este estándar en alcance? Esto lo hará disponible para auditoría.',
                'submit_label' => 'Establecer En Alcance',
            ],
            'set_out_scope' => [
                'label' => 'Establecer Fuera de Alcance',
                'modal_heading' => 'Establecer Estándar Fuera de Alcance',
                'modal_content' => '¿Está seguro de que desea establecer este estándar fuera de alcance? Esto lo hará no disponible para auditoría.',
                'submit_label' => 'Establecer Fuera de Alcance',
            ],
        ],
    ],
    'infolist' => [
        'section_title' => 'Detalles del Estándar',
    ],
]; 