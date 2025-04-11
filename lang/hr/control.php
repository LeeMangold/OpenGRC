<?php

return [
    'navigation' => [
        'label' => 'Kontrole',
        'group' => 'Temelji',
    ],
    'model' => [
        'label' => 'Kontrola',
        'plural_label' => 'Kontrole',
    ],
    'breadcrumb' => [
        'title' => 'Kontrole',
    ],
    'form' => [
        'code' => [
            'tooltip' => 'Unesite jedinstveni kod za ovu kontrolu. Ovaj kod će se koristiti za identifikaciju ove kontrole u sustavu.',
        ],
        'standard' => [
            'label' => 'Standard',
            'tooltip' => 'Sve kontrole moraju pripadati standardu. Ako nemate standard za povezivanje s ovom kontrolom, prvo razmislite o stvaranju novog.',
        ],
        'enforcement' => [
            'tooltip' => 'Odaberite kategoriju provedbe za ovu kontrolu. To će pomoći u određivanju kako se ova kontrola provodi.',
        ],
        'title' => [
            'tooltip' => 'Unesite naslov za ovu kontrolu.',
        ],
        'description' => [
            'tooltip' => 'Unesite opis za ovu kontrolu. Ovo treba detaljno opisati zahtjeve za ovu kontrolu.',
        ],
        'discussion' => [
            'tooltip' => 'Opcionalno: Navedite bilo koji kontekst ili dodatne informacije o ovoj kontroli koje bi nekome pomogle odrediti kako je implementirati.',
        ],
        'test' => [
            'label' => 'Plan Testiranja',
            'tooltip' => 'Opcionalno: Kako planirate testirati je li ova kontrola implementirana i učinkovita?',
        ],
    ],
    'table' => [
        'description' => 'Kontrole predstavljaju specifične sigurnosne mjere implementirane unutar vaše organizacije.',
        'empty_state' => [
            'heading' => 'Nisu pronađene kontrole',
            'description' => 'Započnite uvozom paketa standarda ili kreiranjem nove kontrole.',
        ],
        'columns' => [
            'code' => 'Kod',
            'title' => 'Naslov',
            'standard' => 'Standard',
            'type' => 'Tip',
            'category' => 'Kategorija',
            'enforcement' => 'Provedba',
            'effectiveness' => 'Učinkovitost',
            'applicability' => 'Primjenjivost',
            'assessed' => 'Zadnja Procjena',
            'created_at' => 'Kreirano',
            'updated_at' => 'Ažurirano',
        ],
        'filters' => [
            'standard' => 'Standard',
            'effectiveness' => 'Učinkovitost',
            'type' => 'Tip',
            'category' => 'Kategorija',
            'enforcement' => 'Provedba',
            'applicability' => 'Primjenjivost',
        ],
    ],
    'infolist' => [
        'section_title' => 'Detalji Kontrole',
        'test_plan' => 'Plan Testiranja',
    ],
]; 