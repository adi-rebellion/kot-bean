<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class StaffService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @return array{user: User, password: string}
     */
    public function create(array $data, User $creator): array
    {
        return DB::transaction(function () use ($data, $creator) {
            $phone = $this->normalizePhone($data['phone']);
            $role = Role::query()
                ->where('restaurant_id', $creator->restaurant_id)
                ->findOrFail($data['role_id']);

            if ($role->slug === 'owner' && ! $creator->isOwner()) {
                throw new InvalidArgumentException('Only owners can assign the owner role.');
            }

            $password = Str::password(10, letters: true, numbers: true, symbols: false);
            $email = $this->generateStaffEmail($phone, $creator->restaurant->slug);

            $user = User::create([
                'restaurant_id' => $creator->restaurant_id,
                'role_id' => $role->id,
                'name' => $data['name'],
                'email' => $email,
                'phone' => $phone,
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            $this->auditLogService->log(
                'staff.created',
                $user,
                null,
                $user->only(['name', 'email', 'phone', 'role_id', 'is_active']),
                "Staff member {$user->name} created",
            );

            return [
                'user' => $user->fresh('role'),
                'password' => $password,
            ];
        });
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    private function generateStaffEmail(string $phone, string $restaurantSlug): string
    {
        $base = "{$phone}@staff.{$restaurantSlug}.kotbean";
        $email = $base;
        $suffix = 1;

        while (User::query()->where('email', $email)->exists()) {
            $email = "{$phone}+{$suffix}@staff.{$restaurantSlug}.kotbean";
            $suffix++;
        }

        return $email;
    }
}
