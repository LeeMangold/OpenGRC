<?php

return [
    'navigation' => [
        'label' => 'Implementacije',
        'group' => 'Temelji',
    ],
    'breadcrumb' => [
        'title' => 'Implementacije',
    ],
    'table' => [
        'description' => 'Implementacije predstavljaju stvarno uvođenje i rad sigurnosnih kontrola unutar organizacije. To su specifični primjeri kako se kontrole provode u praksi, uključujući korištene alate, konfiguracije, procese i procedure. Svaka implementacija mora biti dokumentirana s dovoljno detalja da se razumije kako kontrola funkcionira, tko je odgovoran za njeno održavanje i kako se može provjeriti njena učinkovitost. Na primjer, dok kontrola može specificirati potrebu za pregledima pristupa, implementacija bi detaljno opisala točan proces, uključujući koji se alat koristi, tko provodi preglede, koliko često se događaju i koja se dokumentacija održava. Implementacije premošćuju jaz između teoretskih sigurnosnih kontrola i njihove praktične primjene u organizaciji.',
        'empty_state' => [
            'heading' => 'Nisu pronađene implementacije',
            'description' => 'Pokušajte kreirati novu implementaciju klikom na gumb "Kreiraj Implementaciju" iznad.',
        ],
        'columns' => [
            'code' => 'Kod',
            'title' => 'Naslov',
            'effectiveness' => 'Učinkovitost',
            'last_assessed' => 'Zadnja Revizija',
            'status' => 'Status',
            'created_at' => 'Kreirano',
            'updated_at' => 'Ažurirano',
        ],
    ],
    'actions' => [
        'create' => 'Kreiraj Implementaciju',
    ],
]; 