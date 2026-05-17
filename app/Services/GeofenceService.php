<?php

namespace App\Services;

use App\Models\Site;

class GeofenceService
{
    /**
     * Check whether the given coordinates are within the site's geofence.
     * Returns an array with keys: inside, distanceMeters, flagged, flagReason.
     */
    public function check(Site $site, float $lat, float $lng): array
    {
        $distance = $this->haversine($site->lat, $site->lng, $lat, $lng);
        $radius   = $site->geofence_radius_m;

        if ($distance <= $radius) {
            return ['inside' => true, 'distanceMeters' => $distance, 'flagged' => false, 'flagReason' => null];
        }

        if ($distance <= $radius + 50) {
            return ['inside' => true, 'distanceMeters' => $distance, 'flagged' => true, 'flagReason' => 'GPS edge'];
        }

        return ['inside' => false, 'distanceMeters' => $distance, 'flagged' => false, 'flagReason' => null];
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
