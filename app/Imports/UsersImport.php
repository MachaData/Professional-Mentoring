<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk-imports users from an Excel sheet with columns:
 * nombre, correo, celular, rol, cargo, area, unidad_negocio, empresa, zona_horaria, idioma
 *
 * Rows upsert by email. New users get a temporary password and are left pending
 * invitation (the admin can then send the welcome email).
 */
class UsersImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;

    public function __construct(protected int $organizationId) {}

    /** Human column headers (slug to the keys read below). @return array<int,string> */
    public static function templateHeadings(): array
    {
        return ['Nombre', 'Correo', 'Celular', 'Rol', 'Cargo', 'Area', 'Unidad Negocio', 'Empresa', 'Zona Horaria', 'Idioma'];
    }

    /** @return array<int,array<int,string>> */
    public static function templateExample(): array
    {
        return [
            ['Ana Pérez', 'ana.perez@ejemplo.com', '+51 999 111 222', 'Mentor', 'Gerente', 'Operaciones', 'Mina', 'Las Bambas', 'America/Lima', 'es'],
            ['Luis Gómez', 'luis.gomez@ejemplo.com', '+51 999 333 444', 'Mentee', 'Analista', 'Finanzas', 'Corporativo', 'Las Bambas', 'America/Lima', 'es'],
        ];
    }

    public function collection(\Illuminate\Support\Collection $rows): void
    {
        foreach ($rows as $row) {
            $email = trim((string) ($row['correo'] ?? $row['email'] ?? ''));
            if (! $email) {
                continue;
            }

            $role = $this->normalizeRole((string) ($row['rol'] ?? $row['role'] ?? 'participant'));

            $user = User::firstOrNew(['email' => $email]);
            $isNew = ! $user->exists;

            $user->fill(array_filter([
                'organization_id' => $this->organizationId,
                'name' => $row['nombre'] ?? $row['name'] ?? $email,
                'role' => $role,
                'phone' => $row['celular'] ?? $row['phone'] ?? null,
                'position' => $row['cargo'] ?? null,
                'area' => $row['area'] ?? null,
                'business_unit' => $row['unidad_negocio'] ?? $row['unidad_de_negocio'] ?? null,
                'company' => $row['empresa'] ?? null,
                'timezone' => $row['zona_horaria'] ?? 'America/Lima',
                'locale' => $row['idioma'] ?? 'es',
            ], fn ($v) => $v !== null));

            // New users get a temporary password and stay pending invitation.
            if ($isNew) {
                $user->forceFill([
                    'password' => Hash::make(Str::random(12)),
                    'must_change_password' => true,
                    'invitation_status' => 'pending',
                    'status' => 'active',
                ]);
            }

            $user->save();
            $user->syncRoles([$role]);
            $this->imported++;
        }
    }

    protected function normalizeRole(string $value): string
    {
        return match (Str::of($value)->lower()->trim()->value()) {
            'facilitador', 'facilitator', 'mentor', 'coach', 'tutor' => User::ROLE_FACILITATOR,
            'organization_admin', 'admin', 'administrador' => User::ROLE_ORG_ADMIN,
            default => User::ROLE_PARTICIPANT,
        };
    }
}
