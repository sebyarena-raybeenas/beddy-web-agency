<?php

namespace App\Console\Commands;

use App\Domain\Clients\ClientImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

#[Signature('beddy:import-clients {file : File JSON con l\'elenco dei clienti}')]
#[Description('Importa l\'anagrafica dei clienti (con referenti e domini) da un file JSON; non crea progetti')]
class ImportClientsCommand extends Command
{
    public function handle(ClientImporter $importer): int
    {
        $path = $this->argument('file');

        if (! is_file($path)) {
            $this->error("File non trovato: {$path}");

            return self::FAILURE;
        }

        $rows = json_decode((string) file_get_contents($path), true);

        if (! is_array($rows)) {
            $this->error('Il file non contiene un JSON valido.');

            return self::FAILURE;
        }

        try {
            $result = $importer->import($rows);
        } catch (ValidationException $e) {
            $this->error('Importazione annullata, nessun cliente salvato:');

            foreach ($e->validator->errors()->all() as $message) {
                $this->line(" - {$message}");
            }

            return self::FAILURE;
        }

        $this->info('Clienti importati: '.count($result['created']));

        foreach ($result['created'] as $name) {
            $this->line(" + {$name}");
        }

        if ($result['skipped'] !== []) {
            $this->warn('Già presenti, saltati: '.count($result['skipped']));

            foreach ($result['skipped'] as $name) {
                $this->line(" = {$name}");
            }
        }

        return self::SUCCESS;
    }
}
