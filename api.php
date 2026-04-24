<?php
header("Content-Type: application/json; charset=UTF-8");

/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
*/

$EVENTS_URL  = "https://raw.githubusercontent.com/golazo123/golazofootballpro/main/events.json";
$EVENTC_URL  = "https://raw.githubusercontent.com/golazo123/golazofootballpro/main/eventc.json";

/*
|--------------------------------------------------------------------------
| RULES (CITY / COUNTRY)
|--------------------------------------------------------------------------
*/
$RULES = [
    'pk' => [
        'default' => true,
        'cities' => [
            'lahore' => false,
            'karachi' => false
        ]
    ],
    'in' => [
        'default' => false
    ]
];

/*
|--------------------------------------------------------------------------
| GET IP
|--------------------------------------------------------------------------
*/
function getIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/*
|--------------------------------------------------------------------------
| GEO LOOKUP
|--------------------------------------------------------------------------
*/
function getGeo($ip) {
    $url = "http://ip-api.com/json/$ip?fields=status,countryCode,city";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    $res = curl_exec($ch);
    curl_close($ch);

    if (!$res) return null;

    $json = json_decode($res, true);
    if (!$json || $json['status'] !== 'success') return null;

    return [
        'country' => strtolower($json['countryCode']),
        'city' => strtolower($json['city'])
    ];
}

/*
|--------------------------------------------------------------------------
| FETCH URL
|--------------------------------------------------------------------------
*/
function fetch($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

/*
|--------------------------------------------------------------------------
| MAIN LOGIC
|--------------------------------------------------------------------------
*/
$ip = getIP();
$geo = getGeo($ip);

$allow = true;

if ($geo) {
    $country = $geo['country'];
    $city = $geo['city'];

    if (isset($RULES[$country])) {
        $allow = $RULES[$country]['default'];

        if (isset($RULES[$country]['cities'][$city])) {
            $allow = $RULES[$country]['cities'][$city];
        }
    }
}

$url = $allow ? $EVENTS_URL : $EVENTC_URL;
$json = fetch($url);

if (!$json) {
    echo json_encode([
        "data" => base64_encode(json_encode([]))
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| WRAP FOR ANDROID APP (IMPORTANT PART)
|--------------------------------------------------------------------------
*/
echo json_encode([
    "data" => base64_encode($json)
]);
?>
