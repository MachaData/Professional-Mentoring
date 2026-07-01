<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(protected ?int $organizationId = null, protected ?string $role = null) {}

    public function query()
    {
        return User::query()
            ->when($this->organizationId, fn ($q) => $q->where('organization_id', $this->organizationId))
            ->when($this->role, fn ($q) => $q->where('role', $this->role))
            ->orderBy('name');
    }

    public function headings(): array
    {
        return ['Nombre', 'Correo', 'Celular', 'Rol', 'Cargo', 'Área', 'Unidad de negocio', 'Empresa', 'Estado'];
    }

    /** @param User $user */
    public function map($user): array
    {
        return [
            $user->name, $user->email, $user->phone, $user->role,
            $user->position, $user->area, $user->business_unit, $user->company, $user->status,
        ];
    }
}
