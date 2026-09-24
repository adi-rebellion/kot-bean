<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TwilioWhatsAppService
{
    public function isConfigured(): bool
    {
        return filled(config('services.twilio.sid'))
            && filled(config('services.twilio.auth_token'))
            && filled(config('services.twilio.whatsapp_from'))
            && filled(config('services.twilio.whatsapp_content_sid'));
    }

    /**
     * @param  array<string, string>  $variables
     */
    public function sendTemplate(string $toPhone, array $variables, ?string $contentSid = null): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Twilio WhatsApp is not configured.');
        }

        $contentSid ??= config('services.twilio.whatsapp_content_sid');

        $payload = [
            'From' => $this->formatFromNumber(config('services.twilio.whatsapp_from')),
            'To' => $this->formatWhatsAppNumber($toPhone),
            'ContentSid' => $contentSid,
        ];

        if ($variables !== []) {
            $payload['ContentVariables'] = json_encode($variables, JSON_THROW_ON_ERROR);
        }

        $response = Http::withBasicAuth(
            config('services.twilio.sid'),
            config('services.twilio.auth_token'),
        )->asForm()->post($this->messagesUrl(), $payload);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Twilio error: '.($response->json('message') ?? $response->body())
            );
        }

        return (string) $response->json('sid');
    }

    public function formatWhatsAppNumber(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) === 10) {
            $digits = '91'.$digits;
        }

        return 'whatsapp:+'.$digits;
    }

    private function formatFromNumber(string $from): string
    {
        if (str_starts_with($from, 'whatsapp:')) {
            return $from;
        }

        if (str_starts_with($from, '+')) {
            return 'whatsapp:'.$from;
        }

        return 'whatsapp:+'.$from;
    }

    private function messagesUrl(): string
    {
        $sid = config('services.twilio.sid');

        return "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
    }
}
