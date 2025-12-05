<?php

use App\Livewire\People\Import;
use App\Models\Event;
use App\Models\Person;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

test('can view import page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('people.import'))
        ->assertOk()
        ->assertSee('Import People');
});

test('can parse csv file', function () {
    $user = User::factory()->create();

    $csv = "name,birthday,anniversary,notes\n";
    $csv .= "John Doe,1990-05-15,,Loves tech\n";
    $csv .= "Jane Smith,1985-03-22,,Prefers handmade gifts\n";

    $file = UploadedFile::fake()->createWithContent('test.csv', $csv);

    Livewire::actingAs($user)
        ->test(Import::class)
        ->set('csvFile', $file)
        ->call('parseFile')
        ->assertSet('showPreview', true)
        ->assertSet('parsedPeople', [
            [
                'name' => 'John Doe',
                'birthday' => '1990-05-15',
                'anniversary' => '',
                'notes' => 'Loves tech',
                'photo_data' => null,
                'add_birthday' => false,
                'birthday_budget' => null,
                'add_christmas' => false,
                'christmas_budget' => null,
                'add_anniversary' => false,
                'anniversary_budget' => null,
            ],
            [
                'name' => 'Jane Smith',
                'birthday' => '1985-03-22',
                'anniversary' => '',
                'notes' => 'Prefers handmade gifts',
                'photo_data' => null,
                'add_birthday' => false,
                'birthday_budget' => null,
                'add_christmas' => false,
                'christmas_budget' => null,
                'add_anniversary' => false,
                'anniversary_budget' => null,
            ],
        ]);
});

test('can import people without events', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Import::class)
        ->set('parsedPeople', [
            [
                'name' => 'John Doe',
                'birthday' => '1990-05-15',
                'anniversary' => '',
                'notes' => 'Loves tech',
                'photo_data' => null,
                'add_birthday' => false,
                'birthday_budget' => null,
                'add_christmas' => false,
                'christmas_budget' => null,
                'add_anniversary' => false,
                'anniversary_budget' => null,
            ],
            [
                'name' => 'Jane Smith',
                'birthday' => '',
                'anniversary' => '',
                'notes' => '',
                'photo_data' => null,
                'photo_data' => null,
                'add_birthday' => false,
                'birthday_budget' => null,
                'add_christmas' => false,
                'christmas_budget' => null,
                'add_anniversary' => false,
                'anniversary_budget' => null,
            ],
        ])
        ->set('showPreview', true)
        ->call('import')
        ->assertRedirect(route('people.index'))
        ->assertSessionHas('success');

    expect(Person::count())->toBe(2);
    expect(Event::count())->toBe(0);
});

test('validates csv file is required', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Import::class)
        ->call('parseFile')
        ->assertHasErrors(['csvFile' => 'required']);
});

test('validates csv has name column', function () {
    $user = User::factory()->create();

    $csv = "first_name,last_name\n";
    $csv .= "John,Doe\n";

    $file = UploadedFile::fake()->createWithContent('test.csv', $csv);

    Livewire::actingAs($user)
        ->test(Import::class)
        ->set('csvFile', $file)
        ->call('parseFile')
        ->assertHasErrors(['csvFile']);
});

test('can parse vcard file', function () {
    $user = User::factory()->create();

    $vcard = "BEGIN:VCARD\n";
    $vcard .= "VERSION:3.0\n";
    $vcard .= "FN:John Doe\n";
    $vcard .= "BDAY:19900515\n";
    $vcard .= "NOTE:Loves technology\n";
    $vcard .= "END:VCARD\n";
    $vcard .= "BEGIN:VCARD\n";
    $vcard .= "VERSION:3.0\n";
    $vcard .= "FN;CHARSET=UTF-8:Jane Smith\n";
    $vcard .= "BDAY:1985-03-22\n";
    $vcard .= "ANNIVERSARY:20100815\n";
    $vcard .= "NOTE;CHARSET=UTF-8:Married in 2010\n";
    $vcard .= "END:VCARD\n";

    $file = UploadedFile::fake()->createWithContent('contacts.vcf', $vcard);

    Livewire::actingAs($user)
        ->test(Import::class)
        ->set('csvFile', $file)
        ->call('parseFile')
        ->assertSet('showPreview', true);

    $parsedPeople = Livewire::actingAs($user)
        ->test(Import::class)
        ->set('csvFile', $file)
        ->call('parseFile')
        ->get('parsedPeople');

    expect($parsedPeople)->toHaveCount(2);
    expect($parsedPeople[0]['name'])->toBe('John Doe');
    expect($parsedPeople[0]['birthday'])->toBe('1990-05-15');
    expect($parsedPeople[0]['anniversary'])->toBe('');
    expect($parsedPeople[0]['notes'])->toBe('Loves technology');
    expect($parsedPeople[1]['name'])->toBe('Jane Smith');
    expect($parsedPeople[1]['birthday'])->toBe('1985-03-22');
    expect($parsedPeople[1]['anniversary'])->toBe('2010-08-15');
    expect($parsedPeople[1]['notes'])->toBe('Married in 2010');
});

test('can reset import', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Import::class)
        ->set('parsedPeople', [[
            'name' => 'Test',
            'birthday' => '',
            'anniversary' => '',
            'notes' => '',
            'photo_data' => null,
            'photo_data' => null,
            'add_birthday' => false,
            'birthday_budget' => null,
            'add_christmas' => false,
            'christmas_budget' => null,
            'add_anniversary' => false,
            'anniversary_budget' => null,
        ]])
        ->set('showPreview', true)
        ->set('headerAddBirthday', true)
        ->set('headerBirthdayBudget', '50.00')
        ->call('resetImport')
        ->assertSet('parsedPeople', [])
        ->assertSet('showPreview', false)
        ->assertSet('headerAddBirthday', false)
        ->assertSet('headerBirthdayBudget', null);
});

test('toggle all birthday checks all people with birthdays', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Import::class)
        ->set('parsedPeople', [
            [
                'name' => 'John Doe',
                'birthday' => '1990-05-15',
                'anniversary' => '',
                'notes' => '',
                'photo_data' => null,
                'add_birthday' => false,
                'birthday_budget' => null,
                'add_christmas' => false,
                'christmas_budget' => null,
                'add_anniversary' => false,
                'anniversary_budget' => null,
            ],
            [
                'name' => 'Jane Smith',
                'birthday' => '1985-03-22',
                'anniversary' => '',
                'notes' => '',
                'photo_data' => null,
                'add_birthday' => false,
                'birthday_budget' => null,
                'add_christmas' => false,
                'christmas_budget' => null,
                'add_anniversary' => false,
                'anniversary_budget' => null,
            ],
            [
                'name' => 'Bob Johnson',
                'birthday' => '',
                'anniversary' => '',
                'notes' => '',
                'photo_data' => null,
                'add_birthday' => false,
                'birthday_budget' => null,
                'add_christmas' => false,
                'christmas_budget' => null,
                'add_anniversary' => false,
                'anniversary_budget' => null,
            ],
        ])
        ->call('toggleAllBirthday');

    expect($component->get('parsedPeople.0.add_birthday'))->toBeTrue();
    expect($component->get('parsedPeople.1.add_birthday'))->toBeTrue();
    expect($component->get('parsedPeople.2.add_birthday'))->toBeFalse(); // No birthday, so not checked
});

test('toggle all christmas checks all people', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Import::class)
        ->set('parsedPeople', [
            [
                'name' => 'John Doe',
                'birthday' => '1990-05-15',
                'anniversary' => '',
                'notes' => '',
                'photo_data' => null,
                'add_birthday' => false,
                'birthday_budget' => null,
                'add_christmas' => false,
                'christmas_budget' => null,
                'add_anniversary' => false,
                'anniversary_budget' => null,
            ],
            [
                'name' => 'Jane Smith',
                'birthday' => '',
                'anniversary' => '',
                'notes' => '',
                'photo_data' => null,
                'add_birthday' => false,
                'birthday_budget' => null,
                'add_christmas' => false,
                'christmas_budget' => null,
                'add_anniversary' => false,
                'anniversary_budget' => null,
            ],
        ])
        ->call('toggleAllChristmas');

    expect($component->get('parsedPeople.0.add_christmas'))->toBeTrue();
    expect($component->get('parsedPeople.1.add_christmas'))->toBeTrue();
});

test('toggle all anniversary checks all people with anniversaries', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Import::class)
        ->set('parsedPeople', [
            [
                'name' => 'John Doe',
                'birthday' => '1990-05-15',
                'anniversary' => '2015-06-20',
                'notes' => '',
                'photo_data' => null,
                'add_birthday' => false,
                'birthday_budget' => null,
                'add_christmas' => false,
                'christmas_budget' => null,
                'add_anniversary' => false,
                'anniversary_budget' => null,
            ],
            [
                'name' => 'Jane Smith',
                'birthday' => '',
                'anniversary' => '2010-08-15',
                'notes' => '',
                'photo_data' => null,
                'add_birthday' => false,
                'birthday_budget' => null,
                'add_christmas' => false,
                'christmas_budget' => null,
                'add_anniversary' => false,
                'anniversary_budget' => null,
            ],
            [
                'name' => 'Bob Johnson',
                'birthday' => '',
                'anniversary' => '',
                'notes' => '',
                'photo_data' => null,
                'add_birthday' => false,
                'birthday_budget' => null,
                'add_christmas' => false,
                'christmas_budget' => null,
                'add_anniversary' => false,
                'anniversary_budget' => null,
            ],
        ])
        ->call('toggleAllAnniversary');

    expect($component->get('parsedPeople.0.add_anniversary'))->toBeTrue();
    expect($component->get('parsedPeople.1.add_anniversary'))->toBeTrue();
    expect($component->get('parsedPeople.2.add_anniversary'))->toBeFalse(); // No anniversary, so not checked
});

test('apply birthday budget to all sets budget for all checked birthday events', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Import::class)
        ->set('parsedPeople', [
            [
                'name' => 'John Doe',
                'birthday' => '1990-05-15',
                'anniversary' => '',
                'notes' => '',
                'photo_data' => null,
                'add_birthday' => true,
                'birthday_budget' => null,
                'add_christmas' => false,
                'christmas_budget' => null,
                'add_anniversary' => false,
                'anniversary_budget' => null,
            ],
            [
                'name' => 'Jane Smith',
                'birthday' => '1985-03-22',
                'anniversary' => '',
                'notes' => '',
                'photo_data' => null,
                'add_birthday' => true,
                'birthday_budget' => null,
                'add_christmas' => false,
                'christmas_budget' => null,
                'add_anniversary' => false,
                'anniversary_budget' => null,
            ],
            [
                'name' => 'Bob Johnson',
                'birthday' => '1978-11-30',
                'anniversary' => '',
                'notes' => '',
                'photo_data' => null,
                'add_birthday' => false,
                'birthday_budget' => null,
                'add_christmas' => false,
                'christmas_budget' => null,
                'add_anniversary' => false,
                'anniversary_budget' => null,
            ],
        ])
        ->set('headerBirthdayBudget', '75.00')
        ->call('applyBirthdayBudgetToAll');

    expect($component->get('parsedPeople.0.birthday_budget'))->toBe('75.00');
    expect($component->get('parsedPeople.1.birthday_budget'))->toBe('75.00');
    expect($component->get('parsedPeople.2.birthday_budget'))->toBeNull(); // Not checked
});

test('apply christmas budget to all sets budget for all checked christmas events', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Import::class)
        ->set('parsedPeople', [
            [
                'name' => 'John Doe',
                'birthday' => '',
                'anniversary' => '',
                'notes' => '',
                'photo_data' => null,
                'add_birthday' => false,
                'birthday_budget' => null,
                'add_christmas' => true,
                'christmas_budget' => null,
                'add_anniversary' => false,
                'anniversary_budget' => null,
            ],
            [
                'name' => 'Jane Smith',
                'birthday' => '',
                'anniversary' => '',
                'notes' => '',
                'photo_data' => null,
                'add_birthday' => false,
                'birthday_budget' => null,
                'add_christmas' => true,
                'christmas_budget' => null,
                'add_anniversary' => false,
                'anniversary_budget' => null,
            ],
            [
                'name' => 'Bob Johnson',
                'birthday' => '',
                'anniversary' => '',
                'notes' => '',
                'photo_data' => null,
                'add_birthday' => false,
                'birthday_budget' => null,
                'add_christmas' => false,
                'christmas_budget' => null,
                'add_anniversary' => false,
                'anniversary_budget' => null,
            ],
        ])
        ->set('headerChristmasBudget', '50.00')
        ->call('applyChristmasBudgetToAll');

    expect($component->get('parsedPeople.0.christmas_budget'))->toBe('50.00');
    expect($component->get('parsedPeople.1.christmas_budget'))->toBe('50.00');
    expect($component->get('parsedPeople.2.christmas_budget'))->toBeNull(); // Not checked
});

test('apply anniversary budget to all sets budget for all checked anniversary events', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Import::class)
        ->set('parsedPeople', [
            [
                'name' => 'John Doe',
                'birthday' => '',
                'anniversary' => '',
                'notes' => '',
                'photo_data' => null,
                'add_birthday' => false,
                'birthday_budget' => null,
                'add_christmas' => false,
                'christmas_budget' => null,
                'add_anniversary' => true,
                'anniversary_budget' => null,
            ],
            [
                'name' => 'Jane Smith',
                'birthday' => '',
                'anniversary' => '',
                'notes' => '',
                'photo_data' => null,
                'add_birthday' => false,
                'birthday_budget' => null,
                'add_christmas' => false,
                'christmas_budget' => null,
                'add_anniversary' => true,
                'anniversary_budget' => null,
            ],
            [
                'name' => 'Bob Johnson',
                'birthday' => '',
                'anniversary' => '',
                'notes' => '',
                'photo_data' => null,
                'add_birthday' => false,
                'birthday_budget' => null,
                'add_christmas' => false,
                'christmas_budget' => null,
                'add_anniversary' => false,
                'anniversary_budget' => null,
            ],
        ])
        ->set('headerAnniversaryBudget', '100.00')
        ->call('applyAnniversaryBudgetToAll');

    expect($component->get('parsedPeople.0.anniversary_budget'))->toBe('100.00');
    expect($component->get('parsedPeople.1.anniversary_budget'))->toBe('100.00');
    expect($component->get('parsedPeople.2.anniversary_budget'))->toBeNull(); // Not checked
});
