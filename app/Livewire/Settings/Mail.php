<?php

namespace App\Livewire\Settings;

use App\Models\MailSetting;
use App\Notifications\TestChannelNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;

class Mail extends Component
{
    public string $driver = 'smtp';

    public ?string $host = null;

    public ?string $port = null;

    public ?string $username = null;

    public ?string $password = null;

    public ?string $encryption = null;

    public ?string $fromAddress = null;

    public ?string $fromName = null;

    public ?string $testRecipient = null;

    /**
     * Gate: only admins can access mail settings.
     */
    public function mount(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $settings = MailSetting::query()->latest()->first();

        if (! $settings) {
            return;
        }

        $this->driver = $settings->driver;
        $this->host = $settings->host;
        $this->port = $settings->port ? (string) $settings->port : null;
        $this->username = $settings->username;
        $this->password = $settings->password ? '********' : null; // do not reveal, only replace if changed
        $this->encryption = $settings->encryption;
        $this->fromAddress = $settings->from_address;
        $this->fromName = $settings->from_name;
        $this->testRecipient = Auth::user()->email;
    }

    /**
     * Persist mail settings.
     */
    public function save(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $validated = $this->validate([
            'driver' => ['required', 'in:smtp,log,sendmail'],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'encryption' => ['nullable', 'in:tls,ssl'],
            'fromAddress' => ['nullable', 'email', 'max:255'],
            'fromName' => ['nullable', 'string', 'max:255'],
            'testRecipient' => ['nullable', 'email', 'max:255'],
        ]);

        $settings = MailSetting::query()->latest()->first() ?? new MailSetting;

        // Only update password if user provided a new value
        $password = $settings->password;
        if (! empty($validated['password']) && $validated['password'] !== '********') {
            $password = $validated['password'];
        }

        $settings->fill([
            'driver' => $validated['driver'],
            'host' => $validated['host'],
            'port' => $validated['port'],
            'username' => $validated['username'],
            'password' => $password,
            'encryption' => $validated['encryption'],
            'from_address' => $validated['fromAddress'],
            'from_name' => $validated['fromName'],
        ])->save();

        $this->dispatch('mail-settings-saved');
    }

    /**
     * Send a test email using the current form values (without requiring save).
     */
    public function sendTestEmail(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);

        $validated = $this->validate([
            'driver' => ['required', 'in:smtp,log,sendmail'],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'encryption' => ['nullable', 'in:tls,ssl'],
            'fromAddress' => ['nullable', 'email', 'max:255'],
            'fromName' => ['nullable', 'string', 'max:255'],
            'testRecipient' => ['nullable', 'email', 'max:255'],
        ]);

        $existing = MailSetting::query()->latest()->first();
        $password = $validated['password'] && $validated['password'] !== '********'
            ? $validated['password']
            : ($existing?->password);

        $scheme = $validated['encryption'] === 'ssl' ? 'smtps' : 'smtp';

        Config::set('mail.default', $validated['driver']);
        Config::set('mail.mailers.smtp.scheme', $scheme);
        Config::set('mail.mailers.smtp.host', $validated['host'] ?: config('mail.mailers.smtp.host'));
        Config::set('mail.mailers.smtp.port', $validated['port'] ?: config('mail.mailers.smtp.port'));
        Config::set('mail.mailers.smtp.username', $validated['username'] ?: config('mail.mailers.smtp.username'));
        Config::set('mail.mailers.smtp.password', $password ?: config('mail.mailers.smtp.password'));
        Config::set('mail.from.address', $validated['fromAddress'] ?: config('mail.from.address'));
        Config::set('mail.from.name', $validated['fromName'] ?: config('mail.from.name'));

        $recipient = $validated['testRecipient'] ?? $this->testRecipient ?? Auth::user()->email;

        try {
            Notification::route('mail', $recipient)->notify(new TestChannelNotification('mail', Auth::user()->name));
            $this->dispatch('mail-settings-saved');
        } catch (\Throwable $e) {
            report($e);
            $this->addError('test', __('Unable to send test email. Please verify your SMTP settings and try again.'));
        }
    }

    public function render()
    {
        return view('livewire.settings.mail');
    }
}
