<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Creates (or updates) a read-only "client" user for an organization. Safe and
 * idempotent: it never touches other users or data. Run it once per client:
 *
 *   php artisan client:create "Nombre Cliente" cliente@empresa.com --org=1
 *
 * If --password is omitted a random one is generated and printed once.
 */
class CreateClientUser extends Command
{
    protected $signature = 'client:create
        {name : Display name of the client user}
        {email : Login email}
        {--org= : Organization id (defaults to the only organization if there is just one)}
        {--password= : Password (a random one is generated if omitted)}';

    protected $description = 'Create a read-only client user (sees reports and indicators, edits nothing)';

    public function handle(): int
    {
        $orgId = $this->option('org');

        if (! $orgId) {
            $orgs = Organization::query()->pluck('name', 'id');
            if ($orgs->count() === 1) {
                $orgId = (int) $orgs->keys()->first();
            } else {
                $this->error('Especifica la organización con --org=ID. Organizaciones disponibles:');
                foreach ($orgs as $id => $name) {
                    $this->line("  {$id} — {$name}");
                }

                return self::FAILURE;
            }
        }

        $org = Organization::find($orgId);
        if (! $org) {
            $this->error("No existe la organización con id {$orgId}.");

            return self::FAILURE;
        }

        $email = strtolower(trim($this->argument('email')));
        $existing = User::where('email', $email)->first();
        if ($existing && $existing->role !== User::ROLE_CLIENT) {
            $this->error("El correo {$email} ya pertenece a un usuario con rol '{$existing->role}'. No se modifica.");

            return self::FAILURE;
        }

        $plainPassword = $this->option('password') ?: Str::password(14);

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $this->argument('name'),
                'role' => User::ROLE_CLIENT,
                'organization_id' => $org->id,
                'locale' => 'es',
                'invitation_status' => 'active',
                'status' => 'active',
                'must_change_password' => false,
                'password' => Hash::make($plainPassword),
                'email_verified_at' => now(),
            ]
        );
        $user->syncRoles([User::ROLE_CLIENT]);

        $this->info("Usuario cliente listo: {$user->email} (organización: {$org->name}).");
        if (! $this->option('password')) {
            $this->warn("Contraseña generada (guárdala, no se vuelve a mostrar): {$plainPassword}");
        }

        return self::SUCCESS;
    }
}
