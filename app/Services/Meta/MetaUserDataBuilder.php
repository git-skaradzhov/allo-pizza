<?php

namespace App\Services\Meta;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class MetaUserDataBuilder
{
    /**
     * @return array<string, string>
     */
    public function fromRequest(Request $request, ?Order $order = null): array
    {
        $user = $request->user();
        $customer = $user instanceof User ? $user->customer : null;

        $email = $order?->customer_email
            ?: $customer?->email
            ?: ($user instanceof User ? $user->email : null);
        $phone = $order?->customer_phone ?: $customer?->phone;
        $externalId = $order?->customer_id ?: $customer?->id;

        return $this->filter([
            'client_ip_address' => $request->ip(),
            'client_user_agent' => $request->userAgent(),
            'fbp' => $request->cookie('_fbp'),
            'fbc' => $request->cookie('_fbc'),
            'em' => $this->hashEmail(is_string($email) ? $email : null),
            'ph' => $this->hashPhone(is_string($phone) ? $phone : null),
            'external_id' => $this->hashExternalId($externalId),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function fromOrder(Order $order, ?Request $request = null): array
    {
        $request ??= request();

        return $this->fromRequest($request, $order);
    }

    public function hashEmail(?string $email): ?string
    {
        $normalized = $this->normalizeEmail($email);

        return $normalized === null ? null : $this->hash($normalized);
    }

    public function hashPhone(?string $phone): ?string
    {
        $normalized = $this->normalizePhone($phone);

        return $normalized === null ? null : $this->hash($normalized);
    }

    public function hashExternalId(int|string|null $id): ?string
    {
        if ($id === null || $id === '') {
            return null;
        }

        return $this->hash((string) $id);
    }

    public function normalizeEmail(?string $email): ?string
    {
        if (! is_string($email)) {
            return null;
        }

        $normalized = strtolower(trim($email));

        return $normalized === '' ? null : $normalized;
    }

    public function normalizePhone(?string $phone): ?string
    {
        if (! is_string($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0')) {
            $digits = '359'.substr($digits, 1);
        }

        return $digits === '' ? null : $digits;
    }

    /**
     * @param  array<string, string|null>  $data
     * @return array<string, string>
     */
    protected function filter(array $data): array
    {
        return array_filter(
            $data,
            fn ($value) => is_string($value) && $value !== ''
        );
    }

    protected function hash(string $value): string
    {
        return hash('sha256', $value);
    }
}
