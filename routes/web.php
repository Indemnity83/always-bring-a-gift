<?php

use App\Livewire\Dashboard;
use App\Livewire\People;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use App\Livewire\Settings\Users;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::redirect('/', 'dashboard')->name('home');

// Authentik OAuth routes
Route::get('auth/authentik', [App\Http\Controllers\Auth\AuthentikController::class, 'redirect'])
    ->name('authentik.redirect');
Route::get('auth/authentik/callback', [App\Http\Controllers\Auth\AuthentikController::class, 'callback'])
    ->name('authentik.callback');

Route::get('dashboard', Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // People routes
    Route::get('people', People\Index::class)->name('people.index');
    Route::get('people/create', People\Create::class)->name('people.create');
    Route::get('people/import', People\Import::class)->name('people.import');
    Route::get('people/{person}', People\Show::class)->name('people.show');
    Route::get('people/{person}/edit', People\Edit::class)->name('people.edit');

    // Event routes
    Route::get('events/create/{person}', App\Livewire\Events\Create::class)->name('events.create');
    Route::get('events/{event}', App\Livewire\Events\Show::class)->name('events.show');
    Route::get('events/{event}/edit', App\Livewire\Events\Edit::class)->name('events.edit');
    Route::get('past-events', App\Livewire\Events\Past::class)->name('events.past');

    // Gift routes
    Route::get('gifts/create/{event}/{year}', App\Livewire\Gifts\Create::class)->name('gifts.create');
    Route::get('gifts/{gift}/edit', App\Livewire\Gifts\Edit::class)->name('gifts.edit');

    // Settings routes
    Route::redirect('settings', 'settings/profile');
    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    // Admin routes
    Route::middleware('can:viewAdmin,App\Models\User')->prefix('admin')->name('admin.')->group(function () {
        Route::get('users', Users::class)->name('users.index');
    });

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
