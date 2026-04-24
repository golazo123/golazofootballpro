<?php
header("Content-Type: application/json; charset=UTF-8");

/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
*/

// GitHub raw URLs
//https://raw.githubusercontent.com/{user}/{repo}/{branch}/{file}
$EVENTS_URL  = "https://raw.githubusercontent.com/golazo123/golazofootballpro/events.json";
$EVENTC_URL  = "https://raw.githubusercontent.com/golazo123/golazofootballpro/eventc.json";

// City / Country rules (admin controlled)
// true  = allow normal events
// false = blocked → eventc.json
$RULES = [
    'pk' => [
        'default' => true,
        'cities' => [
            'lahore'   => false,
            'karachi' => false
        ]
    ],
    'in' => [
        'default' => false
    ]
];

/*
|--------------------------------------------------------------------------
| IP DETECTION
|--------------------------------------------------------------------------
*/
function getUserIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP']; // Cloudflare
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/*
|--------------------------------------------------------------------------
| GEO LOOKUP (IP → CITY)
|--------------------------------------------------------------------------
| Replace with MaxMind / IP2Location in production
*/
function getGeoByIP($ip) {
    $url = "http://ip-api.com/json/$ip?fields=status,countryCode,city";
    $res = @file_get_contents($url);
    if (!$res) return null;

    $json = json_decode($res, true);
    if ($json['status'] !== 'success') return null;

    return [
        'country' => strtolower($json['countryCode']),
        'city'    => strtolower($json['city'])
    ];
}

/*
|--------------------------------------------------------------------------
| MAIN LOGIC
|--------------------------------------------------------------------------
*/
$ip  = getUserIP();
$geo = getGeoByIP($ip);

// Default: allow normal events
$allowNormal = true;

if ($geo) {
    $country = $geo['country'];
    $city    = $geo['city'];

    if (isset($RULES[$country])) {
        $allowNormal = $RULES[$country]['default'];

        if (isset($RULES[$country]['cities'][$city])) {
            $allowNormal = $RULES[$country]['cities'][$city];
        }
    }
}

// Decide which JSON to serve
$jsonUrl = $allowNormal ? $EVENTS_URL : $EVENTC_URL;

// Fetch and return JSON
$data = @file_get_contents($jsonUrl);

if ($data === false) {
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "message" => "Unable to load events"
    ]);
    exit;
}

echo $data;
