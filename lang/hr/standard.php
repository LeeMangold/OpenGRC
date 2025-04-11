<?php

return [
    'navigation' => [
        'label' => 'Standardi',
        'group' => 'Temelji',
    ],
    'model' => [
        'label' => 'Standard',
        'plural_label' => 'Standardi',
    ],
    'breadcrumb' => [
        'title' => 'Standardi',
    ],
    'table' => [
        'description' => 'Standardi definiraju \'što\' u sigurnosti i usklađenosti postavljanjem specifičnih zahtjeva, smjernica ili najboljih praksi koje treba slijediti. Služe kao mjerila prema kojima se može mjeriti sigurnosni položaj organizacije. Standardi mogu potjecati iz različitih izvora, uključujući regulatorna tijela (poput HIPAA ili GDPR-a), industrijske okvire (kao što su ISO 27001 ili NIST) ili interne organizacijske politike. Svaki standard obično opisuje specifične kriterije koji se moraju ispuniti kako bi se postigla usklađenost ili održala sigurnost. Na primjer, standard za lozinke može odrediti zahtjeve minimalne duljine, pravila složenosti i razdoblja isteka. Standardi pružaju temelj za kontrole, koje zatim implementiraju ove zahtjeve na praktičan način.',
        'empty_state' => [
            'heading' => 'Nisu pronađeni standardi',
            'description' => 'Započnite uvozom paketa standarda ili stvaranjem novog standarda.',
        ],
        'columns' => [
            'code' => 'Kod Standarda',
            'name' => 'Naziv Standarda',
            'description' => 'Opis Standarda',
            'authority' => 'Izdavatelj',
            'status' => 'Status Standarda',
        ],
        'filters' => [
            'status' => 'Status Standarda',
            'authority' => 'Izdavatelj',
        ],
        'actions' => [
            'group_label' => 'Akcije',
            'set_in_scope' => [
                'label' => 'Postavi U Opseg',
                'modal_heading' => 'Postavi Standard U Opseg',
                'modal_content' => 'Jeste li sigurni da želite postaviti ovaj standard u opseg? To će ga učiniti dostupnim za reviziju.',
                'submit_label' => 'Postavi U Opseg',
            ],
            'set_out_scope' => [
                'label' => 'Postavi Izvan Opsega',
                'modal_heading' => 'Postavi Standard Izvan Opsega',
                'modal_content' => 'Jeste li sigurni da želite postaviti ovaj standard izvan opsega? To će ga učiniti nedostupnim za reviziju.',
                'submit_label' => 'Postavi Izvan Opsega',
            ],
        ],
    ],
    'infolist' => [
        'section_title' => 'Detalji Standarda',
    ],
]; 