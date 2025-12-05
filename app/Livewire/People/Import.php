<?php

namespace App\Livewire\People;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Person;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Import extends Component
{
    use WithFileUploads;

    #[Title('Import People')]
    public ?TemporaryUploadedFile $csvFile = null;

    public array $parsedPeople = [];

    public bool $showPreview = false;

    // Header-level defaults
    public bool $headerAddBirthday = false;

    public ?string $headerBirthdayBudget = null;

    public bool $headerAddChristmas = false;

    public ?string $headerChristmasBudget = null;

    public bool $headerAddAnniversary = false;

    public ?string $headerAnniversaryBudget = null;

    // UI state for showing budget inputs
    public bool $showBirthdayBudgetInput = false;

    public bool $showChristmasBudgetInput = false;

    public bool $showAnniversaryBudgetInput = false;

    public function mount(): void
    {
        // Handle template download
        if (request()->has('download') && request()->get('download') === 'template') {
            $this->downloadTemplate();
        }
    }

    public function parseFile(): void
    {
        $this->validate([
            'csvFile' => ['required', 'file', 'mimes:csv,txt,vcf', 'max:2048'],
        ]);

        try {
            $extension = $this->csvFile->getClientOriginalExtension();

            if (in_array($extension, ['vcf', 'vcard'])) {
                $this->parseVCard();
            } else {
                $this->parseCsv();
            }

            if (! empty($this->parsedPeople)) {
                $this->showPreview = true;
            }
        } catch (\Exception $e) {
            $this->addError('csvFile', 'Error processing file: '.$e->getMessage());
        }
    }

    protected function parseCsv(): void
    {
        $content = file_get_contents($this->csvFile->getRealPath());
        $rows = array_map('str_getcsv', explode("\n", $content));

        // Remove empty rows
        $rows = array_filter($rows, fn ($row) => ! empty(array_filter($row)));

        if (empty($rows)) {
            $this->addError('csvFile', 'The CSV file is empty.');

            return;
        }

        // Get header row
        $header = array_shift($rows);
        $header = array_map('trim', $header);

        // Validate required columns
        if (! in_array('name', $header)) {
            $this->addError('csvFile', 'CSV must have a "name" column.');

            return;
        }

        // Get column indices
        $columns = array_flip($header);

        $errors = [];
        $this->parsedPeople = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $name = trim($row[$columns['name']] ?? '');

                if (empty($name)) {
                    $errors[] = "Row {$rowNumber}: Name is required";

                    continue;
                }

                $this->parsedPeople[] = [
                    'name' => $name,
                    'birthday' => trim($row[$columns['birthday'] ?? ''] ?? ''),
                    'anniversary' => trim($row[$columns['anniversary'] ?? ''] ?? ''),
                    'notes' => trim($row[$columns['notes'] ?? ''] ?? ''),
                    'add_birthday' => false,
                    'birthday_budget' => null,
                    'add_christmas' => false,
                    'christmas_budget' => null,
                    'add_anniversary' => false,
                    'anniversary_budget' => null,
                ];
            } catch (\Exception $e) {
                $errors[] = "Row {$rowNumber}: {$e->getMessage()}";
            }
        }

        if (! empty($errors)) {
            foreach ($errors as $error) {
                $this->addError('parse', $error);
            }
        }
    }

    protected function parseVCard(): void
    {
        $content = file_get_contents($this->csvFile->getRealPath());

        // Split into individual vCards
        $vcards = preg_split('/END:VCARD\s*/i', $content);

        $this->parsedPeople = [];

        foreach ($vcards as $vcard) {
            if (empty(trim($vcard))) {
                continue;
            }

            $lines = explode("\n", $vcard);
            $person = ['name' => '', 'birthday' => '', 'anniversary' => '', 'notes' => ''];

            foreach ($lines as $line) {
                $line = trim($line);

                // Parse FN (Full Name)
                if (preg_match('/^FN[;:](.+)$/i', $line, $matches)) {
                    $person['name'] = trim($matches[1]);
                }

                // Parse BDAY (Birthday)
                if (preg_match('/^BDAY[;:](.+)$/i', $line, $matches)) {
                    $date = trim($matches[1]);
                    // Convert YYYYMMDD to YYYY-MM-DD
                    if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $date, $dateMatches)) {
                        $person['birthday'] = "{$dateMatches[1]}-{$dateMatches[2]}-{$dateMatches[3]}";
                    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                        $person['birthday'] = $date;
                    }
                }

                // Parse NOTE
                if (preg_match('/^NOTE[;:](.+)$/i', $line, $matches)) {
                    $person['notes'] = trim($matches[1]);
                }
            }

            if (! empty($person['name'])) {
                $person['add_birthday'] = false;
                $person['birthday_budget'] = null;
                $person['add_christmas'] = false;
                $person['christmas_budget'] = null;
                $person['add_anniversary'] = false;
                $person['anniversary_budget'] = null;
                $this->parsedPeople[] = $person;
            }
        }
    }

    public function toggleAllBirthday(): void
    {
        $newValue = ! $this->headerAddBirthday;
        $this->headerAddBirthday = $newValue;

        foreach ($this->parsedPeople as $index => $person) {
            if ($person['birthday']) {
                $this->parsedPeople[$index]['add_birthday'] = $newValue;
            }
        }
    }

    public function toggleAllChristmas(): void
    {
        $newValue = ! $this->headerAddChristmas;
        $this->headerAddChristmas = $newValue;

        foreach ($this->parsedPeople as $index => $person) {
            $this->parsedPeople[$index]['add_christmas'] = $newValue;
        }
    }

    public function toggleAllAnniversary(): void
    {
        $newValue = ! $this->headerAddAnniversary;
        $this->headerAddAnniversary = $newValue;

        foreach ($this->parsedPeople as $index => $person) {
            if ($person['anniversary']) {
                $this->parsedPeople[$index]['add_anniversary'] = $newValue;
            }
        }
    }

    public function applyBirthdayBudgetToAll(): void
    {
        foreach ($this->parsedPeople as $index => $person) {
            if ($this->parsedPeople[$index]['add_birthday']) {
                $this->parsedPeople[$index]['birthday_budget'] = $this->headerBirthdayBudget;
            }
        }
        $this->showBirthdayBudgetInput = false;
    }

    public function applyChristmasBudgetToAll(): void
    {
        foreach ($this->parsedPeople as $index => $person) {
            if ($this->parsedPeople[$index]['add_christmas']) {
                $this->parsedPeople[$index]['christmas_budget'] = $this->headerChristmasBudget;
            }
        }
        $this->showChristmasBudgetInput = false;
    }

    public function applyAnniversaryBudgetToAll(): void
    {
        foreach ($this->parsedPeople as $index => $person) {
            if ($this->parsedPeople[$index]['add_anniversary']) {
                $this->parsedPeople[$index]['anniversary_budget'] = $this->headerAnniversaryBudget;
            }
        }
        $this->showAnniversaryBudgetInput = false;
    }

    public function import(): void
    {
        if (empty($this->parsedPeople)) {
            $this->addError('import', 'No people to import. Please parse a file first.');

            return;
        }

        $importedPeople = 0;
        $importedEvents = 0;

        DB::transaction(function () use (&$importedPeople, &$importedEvents) {
            // Get event types
            $birthdayType = EventType::where('name', 'Birthday')->first();
            $christmasType = EventType::where('name', 'Christmas')->first();
            $anniversaryType = EventType::where('name', 'Anniversary')->first();

            foreach ($this->parsedPeople as $personData) {
                // Create or update person
                $person = Person::firstOrCreate(
                    ['name' => $personData['name']],
                    [
                        'birthday' => $personData['birthday'] ?: null,
                        'anniversary' => $personData['anniversary'] ?: null,
                        'notes' => $personData['notes'] ?: null,
                    ]
                );

                if ($person->wasRecentlyCreated) {
                    $importedPeople++;
                }

                // Add Birthday event if checked for this person and they have a birthday
                if ($personData['add_birthday'] && $birthdayType && $person->birthday) {
                    Event::create([
                        'person_id' => $person->id,
                        'event_type_id' => $birthdayType->id,
                        'date' => $person->birthday,
                        'is_annual' => true,
                        'show_milestone' => true,
                        'budget' => $personData['birthday_budget'] ?: null,
                    ]);
                    $importedEvents++;
                }

                // Add Christmas event if checked for this person
                if ($personData['add_christmas'] && $christmasType) {
                    Event::create([
                        'person_id' => $person->id,
                        'event_type_id' => $christmasType->id,
                        'date' => now()->year.'-12-25',
                        'is_annual' => true,
                        'show_milestone' => false,
                        'budget' => $personData['christmas_budget'] ?: null,
                    ]);
                    $importedEvents++;
                }

                // Add Anniversary event if checked for this person and they have an anniversary
                if ($personData['add_anniversary'] && $anniversaryType && $person->anniversary) {
                    Event::create([
                        'person_id' => $person->id,
                        'event_type_id' => $anniversaryType->id,
                        'date' => $person->anniversary,
                        'is_annual' => true,
                        'show_milestone' => true,
                        'budget' => $personData['anniversary_budget'] ?: null,
                    ]);
                    $importedEvents++;
                }
            }
        });

        session()->flash('success', "Successfully imported {$importedPeople} people and created {$importedEvents} events.");
        $this->redirect(route('people.index'));
    }

    public function resetImport(): void
    {
        $this->csvFile = null;
        $this->parsedPeople = [];
        $this->showPreview = false;
        $this->headerAddBirthday = false;
        $this->headerBirthdayBudget = null;
        $this->headerAddChristmas = false;
        $this->headerChristmasBudget = null;
        $this->headerAddAnniversary = false;
        $this->headerAnniversaryBudget = null;
        $this->showBirthdayBudgetInput = false;
        $this->showChristmasBudgetInput = false;
        $this->showAnniversaryBudgetInput = false;
        $this->resetErrorBag();
    }

    protected function downloadTemplate(): void
    {
        $csv = "name,birthday,anniversary,notes\n";
        $csv .= "John Doe,1990-05-15,2015-06-20,Loves tech gadgets\n";
        $csv .= "Jane Smith,1985-03-22,2010-08-15,Prefers handmade gifts\n";
        $csv .= "Bob Johnson,1978-11-30,,Coffee enthusiast\n";
        $csv .= "Alice Williams,,,Works from home\n";

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="people_import_template.csv"');
        echo $csv;
        exit;
    }

    public function render()
    {
        return view('livewire.people.import');
    }
}
