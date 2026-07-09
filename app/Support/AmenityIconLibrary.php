<?php

namespace App\Support;

class AmenityIconLibrary
{
    /**
     * @return array<string, array{label: string, path: string, tags: string}>
     */
    public static function all(): array
    {
        return [
            'wifi' => ['label' => 'Wi-Fi', 'tags' => 'internet network connectivity', 'path' => 'M1 9l2 2a13 13 0 0118 0l2-2A16 16 0 001 9zm4 4l2 2a7 7 0 0110 0l2-2a10 10 0 00-14 0zm4 4l3 3 3-3a4 4 0 00-6 0z'],
            'parking' => ['label' => 'Parking', 'tags' => 'car vehicle transport', 'path' => 'M5 3h8a6 6 0 010 12H9v6H5V3zm4 4v4h4a2 2 0 100-4H9z'],
            'snowflake' => ['label' => 'Air Conditioning', 'tags' => 'ac cold cooling comfort', 'path' => 'M11 2h2v4.2l3.6-2.1 1 1.7L14 7.9l3.6 2.1-1 1.7L13 9.6V14l3.6-2.1 1 1.7-3.6 2.1 3.6 2.1-1 1.7L13 17.4V22h-2v-4.6l-3.6 2.1-1-1.7 3.6-2.1-3.6-2.1 1-1.7L11 14V9.6l-3.6 2.1-1-1.7L10 7.9 6.4 5.8l1-1.7L11 6.2V2z'],
            'utensils' => ['label' => 'Restaurant', 'tags' => 'food dining meal', 'path' => 'M7 2h2v8a3 3 0 01-2 2.8V22H5v-9.2A3 3 0 013 10V2h2v7h1V2h1zm10 0c2.2 0 4 2.2 4 5v5h-3v10h-2V2h1z'],
            'bell' => ['label' => 'Room Service', 'tags' => 'service call concierge', 'path' => 'M12 3a7 7 0 00-7 7v4l-2 3v1h18v-1l-2-3v-4a7 7 0 00-7-7zm-2 17a2 2 0 004 0h-4z'],
            'bolt' => ['label' => 'Power Backup', 'tags' => 'electric generator utility', 'path' => 'M13 2L4 14h7l-1 8 10-13h-7l1-7z'],
            'shield' => ['label' => 'Security', 'tags' => 'safe safety guard', 'path' => 'M12 2l8 3v6c0 5-3.4 9.4-8 11-4.6-1.6-8-6-8-11V5l8-3z'],
            'lift' => ['label' => 'Lift', 'tags' => 'elevator accessibility', 'path' => 'M6 3h12v18H6V3zm2 2v14h8V5H8zm2 4l2-2 2 2h-4zm0 6h4l-2 2-2-2z'],
            'laundry' => ['label' => 'Laundry', 'tags' => 'wash service', 'path' => 'M5 3h14v18H5V3zm2 2v14h10V5H7zm2 8a3 3 0 106 0 3 3 0 00-6 0zm1-6h2v2h-2V7zm4 0h2v2h-2V7z'],
            'tv' => ['label' => 'TV', 'tags' => 'television entertainment', 'path' => 'M3 5h18v12H3V5zm2 2v8h14V7H5zm3 12h8v2H8v-2z'],
            'banquet' => ['label' => 'Banquet', 'tags' => 'event hall party conference', 'path' => 'M4 4h16v4H4V4zm1 6h14v10h-2v-6H7v6H5V10zm4 6h6v4H9v-4z'],
            'pool' => ['label' => 'Pool', 'tags' => 'swimming leisure water', 'path' => 'M4 16c1.3 0 1.9-.8 3-.8s1.7.8 3 .8 1.9-.8 3-.8 1.7.8 3 .8 1.9-.8 3-.8V18c-1.3 0-1.9.8-3 .8s-1.7-.8-3-.8-1.9.8-3 .8-1.7-.8-3-.8-1.9.8-3 .8v-2.8zM7 4h5a3 3 0 010 6H9v4H7V4zm2 2v2h3a1 1 0 100-2H9z'],
            'bed' => ['label' => 'Bed', 'tags' => 'room sleep stay', 'path' => 'M3 5h2v8h14V9a3 3 0 00-3-3h-5a3 3 0 00-3 3v4H5V5H3zm0 10h18v6h-2v-2H5v2H3v-6z'],
            'bath' => ['label' => 'Bathroom', 'tags' => 'bath shower toilet', 'path' => 'M7 3a3 3 0 00-3 3v7H2v2h20v-2H6V6a1 1 0 012 0v1h2V6a3 3 0 00-3-3zm-3 14h2v3H4v-3zm5 0h2v3H9v-3zm5 0h2v3h-2v-3zm5 0h2v3h-2v-3z'],
            'coffee' => ['label' => 'Coffee', 'tags' => 'tea breakfast cafe', 'path' => 'M4 5h12v7a5 5 0 01-5 5H9a5 5 0 01-5-5V5zm14 2h1a3 3 0 010 6h-1V7zm0 2v2h1a1 1 0 000-2h-1zM3 19h16v2H3v-2z'],
            'breakfast' => ['label' => 'Breakfast', 'tags' => 'food morning meal', 'path' => 'M4 10a8 8 0 0116 0v1H4v-1zm-2 3h20v2H2v-2zm3 4h14v2H5v-2z'],
            'bar' => ['label' => 'Bar', 'tags' => 'drink lounge beverage', 'path' => 'M5 3h14l-5 8v8h3v2H7v-2h3v-8L5 3zm4 2l2.5 4h1L15 5H9z'],
            'gym' => ['label' => 'Gym', 'tags' => 'fitness health workout', 'path' => 'M2 10h3V7h2v10H5v-3H2v-4zm15-3h2v3h3v4h-3v3h-2V7zM8 11h8v2H8v-2z'],
            'spa' => ['label' => 'Spa', 'tags' => 'wellness massage relax', 'path' => 'M12 3c3 2 5 5 5 8a5 5 0 01-10 0c0-3 2-6 5-8zm-8 9c3 0 6 2 8 5-4 1-8 0-10-3l2-2zm16 0l2 2c-2 3-6 4-10 3 2-3 5-5 8-5z'],
            'pet' => ['label' => 'Pet Friendly', 'tags' => 'dog cat animal pet', 'path' => 'M7 10a2 2 0 110-4 2 2 0 010 4zm10 0a2 2 0 110-4 2 2 0 010 4zM9 7a2 2 0 114 0 2 2 0 01-4 0zm-5 7a2 2 0 114 0 2 2 0 01-4 0zm12 0a2 2 0 114 0 2 2 0 01-4 0zm-8 4c0-2 2-4 4-4s4 2 4 4c0 2-2 3-4 3s-4-1-4-3z'],
            'wheelchair' => ['label' => 'Accessible', 'tags' => 'wheelchair accessibility disabled', 'path' => 'M12 4a2 2 0 110-4 2 2 0 010 4zM9 6h4l1 5h4v2h-5l-.4-2H10a5 5 0 105 5h2a7 7 0 11-8-6.9V6z'],
            'kitchen' => ['label' => 'Kitchen', 'tags' => 'cook pantry kitchenette', 'path' => 'M5 3h14v18H5V3zm2 2v6h10V5H7zm0 8v6h10v-6H7zm2-6h2v2H9V7zm4 0h2v2h-2V7z'],
            'fridge' => ['label' => 'Refrigerator', 'tags' => 'fridge mini bar cooling', 'path' => 'M7 2h10a2 2 0 012 2v18H5V4a2 2 0 012-2zm0 2v7h10V4H7zm0 9v7h10v-7H7zm2-7h2v3H9V6zm0 9h2v3H9v-3z'],
            'safe' => ['label' => 'Locker', 'tags' => 'safe locker vault', 'path' => 'M4 5a2 2 0 012-2h12a2 2 0 012 2v14H4V5zm2 0v12h12V5H6zm6 3a4 4 0 110 8 4 4 0 010-8zm0 2a2 2 0 100 4 2 2 0 000-4z'],
            'camera' => ['label' => 'CCTV', 'tags' => 'camera security surveillance', 'path' => 'M4 7h10a3 3 0 013 3v1l4-3v8l-4-3v1a3 3 0 01-3 3H4V7zm2 2v6h8a1 1 0 001-1v-4a1 1 0 00-1-1H6z'],
            'phone' => ['label' => 'Telephone', 'tags' => 'phone call contact', 'path' => 'M6 2h4l1 5-3 2a13 13 0 007 7l2-3 5 1v4a3 3 0 01-3 3A17 17 0 013 5a3 3 0 013-3z'],
            'desk' => ['label' => 'Work Desk', 'tags' => 'office table business', 'path' => 'M3 7h18v3H3V7zm2 5h14v9h-2v-7H7v7H5v-9z'],
            'garden' => ['label' => 'Garden', 'tags' => 'outdoor lawn nature', 'path' => 'M12 21c-4-3-6-6-6-10 0-4 3-7 6-8 3 1 6 4 6 8 0 4-2 7-6 10zm0-4c2-2 3-4 3-6s-1-4-3-5c-2 1-3 3-3 5s1 4 3 6z'],
            'terrace' => ['label' => 'Terrace', 'tags' => 'balcony rooftop outdoor', 'path' => 'M4 12h16v8h-2v-6H6v6H4v-8zm2-6h12l2 4H4l2-4z'],
            'fire' => ['label' => 'Fire Safety', 'tags' => 'fire extinguisher safety', 'path' => 'M13 2c1 3-2 4-2 7 0 1 .8 2 2 2 0-2 2-3 3-5 3 3 4 6 4 9a8 8 0 11-16 0c0-4 3-6 5-9 0 3 2 4 4 5-2-4 2-5 0-9z'],
            'medical' => ['label' => 'First Aid', 'tags' => 'medical health emergency', 'path' => 'M9 3h6v4h4v14H5V7h4V3zm2 2v4H7v10h10V9h-4V5h-2zm0 7h2v2h2v2h-2v2h-2v-2H9v-2h2v-2z'],
            'map-pin' => ['label' => 'Location', 'tags' => 'map pin address nearby', 'path' => 'M12 2a7 7 0 00-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 00-7-7zm0 9.5A2.5 2.5 0 1112 6a2.5 2.5 0 010 5.5z'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::all())
            ->mapWithKeys(fn (array $icon, string $key): array => [$key => $icon['label']])
            ->all();
    }

    public static function path(?string $icon): string
    {
        return self::all()[$icon ?? 'banquet']['path'] ?? self::all()['banquet']['path'];
    }
}
