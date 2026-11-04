<?php

return [
    'sito' => [
        'states' => [
            'in_preparazione' => 'In preparazione',
            'pronto_da_sviluppare' => 'Pronto da sviluppare',
            'in_sviluppo' => 'In sviluppo',
            'in_attesa_feedback' => 'Presentazione sito: in attesa di feedback',
            'feedback_modifiche' => 'Feedback cliente: modifiche',
            'in_pubblicazione' => 'In pubblicazione',
            'pubblicato' => 'Sito pubblicato',
        ],
        'initial' => 'in_preparazione',
        'ready' => 'pronto_da_sviluppare',
        'final' => 'pubblicato',
        'ready_message' => 'Il sito :project è pronto da sviluppare, puoi iniziare.',
        'deadlines' => [
            'in_sviluppo' => [7, "Hai finito? Contatta il cliente per l'appuntamento."],
            'feedback_modifiche' => [7, 'Hai finito le modifiche? Contatta il cliente.'],
        ],
        'checklist_trigger' => 'in_pubblicazione',
    ],
    'modifica' => [
        'states' => [
            'in_preparazione' => 'In preparazione',
            'pronto_da_modificare' => 'Pronto da modificare',
            'in_modifica' => 'In modifica',
            'in_attesa_feedback' => 'In attesa di feedback',
            'feedback_altre_modifiche' => 'Feedback cliente: altre modifiche',
            'modifiche_pubblicate' => 'Modifiche pubblicate',
        ],
        'initial' => 'in_preparazione',
        'ready' => 'pronto_da_modificare',
        'final' => 'modifiche_pubblicate',
        'ready_message' => 'La modifica :project è pronta, puoi iniziare.',
        'deadlines' => [
            'in_modifica' => [7, 'Hai finito? Contatta il cliente.'],
            'feedback_altre_modifiche' => [7, 'Hai finito le modifiche? Contatta il cliente.'],
        ],
        'checklist_trigger' => null,
    ],
];
