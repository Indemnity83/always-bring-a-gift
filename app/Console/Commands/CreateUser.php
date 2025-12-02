<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create
                            {--email= : The email address of the user}
                            {--name= : The name of the user}
                            {--password= : The password (WARNING: visible in shell history)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user (for development or emergency access)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating a new user...');
        $this->newLine();

        // Get user details
        $name = $this->option('name') ?: text(
            label: 'Name',
            placeholder: 'John Doe',
            required: true
        );

        $email = $this->option('email') ?: text(
            label: 'Email',
            placeholder: 'user@example.com',
            required: true,
            validate: fn (string $value) => match (true) {
                ! filter_var($value, FILTER_VALIDATE_EMAIL) => 'Please enter a valid email address.',
                User::where('email', $value)->exists() => 'A user with this email already exists.',
                default => null
            }
        );

        $password = $this->option('password') ?: password(
            label: 'Password',
            placeholder: 'Enter a secure password',
            required: true,
            validate: fn (string $value) => match (true) {
                strlen($value) < 8 => 'Password must be at least 8 characters.',
                default => null
            }
        );

        // Validate all inputs
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->error('  - '.$error);
            }

            return Command::FAILURE;
        }

        // Create the user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(), // Auto-verify for local users
        ]);

        $this->newLine();
        $this->info('User created successfully!');
        $this->newLine();
        $this->table(
            ['ID', 'Name', 'Email', 'Created'],
            [[$user->id, $user->name, $user->email, $user->created_at->format('Y-m-d H:i:s')]]
        );

        $this->newLine();
        $this->comment('The user can now log in with their email and password.');
        $this->comment('This user will not have SSO configured - they use local authentication.');

        return Command::SUCCESS;
    }
}
