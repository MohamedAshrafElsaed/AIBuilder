<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

final readonly class FacebookUserData
{
    public function __construct(
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $country = null,
        public ?string $zip = null,
        public ?string $clientIpAddress = null,
        public ?string $clientUserAgent = null,
        public ?string $fbc = null,
        public ?string $fbp = null,
    ) {
    }

    /**
     * Create a new instance from an array of data.
     */
    public static function from(array $data): self
    {
        return new self(
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            country: $data['country'] ?? null,
            zip: $data['zip'] ?? null,
            clientIpAddress: $data['client_ip_address'] ?? null,
            clientUserAgent: $data['client_user_agent'] ?? null,
            fbc: $data['fbc'] ?? null,
            fbp: $data['fbp'] ?? null,
        );
    }

    /**
     * Convert the DTO to an array with hashed values for Facebook Conversions API.
     */
    public function toHashedArray(): array
    {
        $data = [];

        if ($this->email !== null) {
            $data['em'] = $this->hashValue($this->normalizeEmail($this->email));
        }

        if ($this->phone !== null) {
            $data['ph'] = $this->hashValue($this->normalizePhone($this->phone));
        }

        if ($this->firstName !== null) {
            $data['fn'] = $this->hashValue($this->normalizeString($this->firstName));
        }

        if ($this->lastName !== null) {
            $data['ln'] = $this->hashValue($this->normalizeString($this->lastName));
        }

        if ($this->city !== null) {
            $data['ct'] = $this->hashValue($this->normalizeString($this->city));
        }

        if ($this->state !== null) {
            $data['st'] = $this->hashValue($this->normalizeString($this->state));
        }

        if ($this->country !== null) {
            $data['country'] = $this->hashValue($this->normalizeString($this->country));
        }

        if ($this->zip !== null) {
            $data['zp'] = $this->hashValue($this->normalizeString($this->zip));
        }

        if ($this->clientIpAddress !== null) {
            $data['client_ip_address'] = $this->clientIpAddress;
        }

        if ($this->clientUserAgent !== null) {
            $data['client_user_agent'] = $this->clientUserAgent;
        }

        if ($this->fbc !== null) {
            $data['fbc'] = $this->fbc;
        }

        if ($this->fbp !== null) {
            $data['fbp'] = $this->fbp;
        }

        return $data;
    }

    /**
     * Hash a value using SHA-256.
     */
    private function hashValue(string $value): string
    {
        return hash('sha256', $value);
    }

    /**
     * Normalize email address.
     */
    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Normalize phone number (remove non-numeric characters).
     */
    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }

    /**
     * Normalize string (lowercase, trim, remove spaces).
     */
    private function normalizeString(string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', '', $value)));
    }
}
