<?php

return [
    'temporary_file_upload' => [
        // Livewire's default (12 MB) would cut below the 20 MB Filament limit of material uploads.
        'rules' => ['required', 'file', 'max:20480'],
    ],
];
