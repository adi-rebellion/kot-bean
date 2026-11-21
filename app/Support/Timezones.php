<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class Timezones
{
    /**
     * Curated timezones for cafés and restaurants (IANA id => human label).
     *
     * @return array<string, array<string, string>>
     */
    public static function curatedGroups(): array
    {
        return [
            'India & South Asia' => [
                'Asia/Kolkata' => 'India — Kolkata, Mumbai, Delhi (IST)',
                'Asia/Colombo' => 'Sri Lanka — Colombo',
                'Asia/Kathmandu' => 'Nepal — Kathmandu',
                'Asia/Dhaka' => 'Bangladesh — Dhaka',
                'Asia/Karachi' => 'Pakistan — Karachi, Lahore',
            ],
            'Middle East' => [
                'Asia/Dubai' => 'UAE — Dubai, Abu Dhabi (GST)',
                'Asia/Riyadh' => 'Saudi Arabia — Riyadh',
                'Asia/Qatar' => 'Qatar — Doha',
            ],
            'Southeast Asia' => [
                'Asia/Singapore' => 'Singapore',
                'Asia/Bangkok' => 'Thailand — Bangkok',
                'Asia/Jakarta' => 'Indonesia — Jakarta',
                'Asia/Manila' => 'Philippines — Manila',
            ],
            'East Asia' => [
                'Asia/Hong_Kong' => 'Hong Kong',
                'Asia/Tokyo' => 'Japan — Tokyo',
                'Asia/Seoul' => 'South Korea — Seoul',
            ],
            'Europe' => [
                'Europe/London' => 'United Kingdom — London',
                'Europe/Paris' => 'France — Paris',
                'Europe/Berlin' => 'Germany — Berlin',
                'Europe/Rome' => 'Italy — Rome',
            ],
            'Americas' => [
                'America/New_York' => 'USA — Eastern (New York)',
                'America/Chicago' => 'USA — Central (Chicago)',
                'America/Denver' => 'USA — Mountain (Denver)',
                'America/Los_Angeles' => 'USA — Pacific (Los Angeles)',
                'America/Toronto' => 'Canada — Toronto',
            ],
            'Australia & Pacific' => [
                'Australia/Sydney' => 'Australia — Sydney',
                'Australia/Melbourne' => 'Australia — Melbourne',
                'Pacific/Auckland' => 'New Zealand — Auckland',
            ],
            'Other' => [
                'UTC' => 'UTC — Coordinated Universal Time',
            ],
        ];
    }

    /** @return list<string> */
    public static function allowedIdentifiers(): array
    {
        $ids = [];

        foreach (self::curatedGroups() as $options) {
            foreach (array_keys($options) as $id) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    public static function isAllowed(string $timezone): bool
    {
        return in_array($timezone, self::allowedIdentifiers(), true);
    }

    public static function isValidPhpZone(string $timezone): bool
    {
        return in_array($timezone, timezone_identifiers_list(), true);
    }

    public static function resolve(?string $timezone): string
    {
        if ($timezone && self::isValidPhpZone($timezone)) {
            return $timezone;
        }

        return 'Asia/Kolkata';
    }

    public static function labelFor(string $timezone): string
    {
        foreach (self::curatedGroups() as $options) {
            if (isset($options[$timezone])) {
                return $options[$timezone];
            }
        }

        return str_replace('_', ' ', $timezone);
    }

    public static function abbreviation(string $timezone): string
    {
        return Carbon::now($timezone)->format('T');
    }

    public static function preview(string $timezone): string
    {
        $resolved = self::resolve($timezone);

        return Carbon::now($resolved)->format('l, M j, Y · g:i A').' ('.self::abbreviation($resolved).')';
    }
}