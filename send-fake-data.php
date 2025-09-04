<?php
require_once 'vendor/autoload.php';

$faker = Faker\Factory::create();

use Dotenv\Dotenv;

// Creates a Dotenv instance, pointing to project root directory
$dotenv = Dotenv::createImmutable(__DIR__);

// Loads vars from the .env file into environment (needed when connecting to api.php via server)
$dotenv->load();

// Defines user agents with weights (dominant user agent first)
$userAgents = [
    ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/90.0.4430.212 Safari/537.36', 0.55],
    ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 Safari/605.1.15', 0.25],
    ['Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:88.0) Gecko/20100101 Firefox/88.0', 0.15],
    ['Mozilla/5.0 (iPhone; CPU iPhone OS 14_3 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148', 0.05],
];

// Selects a random item from a list, weighted by the specified probabilities
function weightedRandomChoice($items) {
    $r = mt_rand() / mt_getrandmax(); // Generates a random float between 0 and 1
    $cumulative = 0.0;

    // The idea here is to use a cumulative total to map every weight to a range of returning
    // For example, if you had weight 0.2, 0.5, 0.3
    // Then the item associated with 0.2 would be returned for $r in [0,0.2],
    // the item associated with 0.5 would be returned for $r in [0.2,0.2+0.5],
    // and the item associated with 0.3 would be returned for $r in [0.2+0.5, 0.2+0.5+0.3]
    foreach ($items as [$item, $weight]) {
        $cumulative += $weight;
        if ($r <= $cumulative) {
            return $item;
        }
    }
    // If not returned in the loop (due to rounding errors), return the last item's value
    return end($items)[0];
}

// ---- FAKE DATA GENERATORS ---- //
// The session id is sent as a param each time in order to connect related records across tables.

// Generates a fake 'static' record simulating a user's device and browser environment
function generateFakeStatic($faker, $userAgents, $id) {
    $userAgent = weightedRandomChoice($userAgents);
    $isMobile = strpos($userAgent, 'iPhone') !== false;
    $screenWidth = $isMobile ? $faker->randomElement([320, 375, 414]) : $faker->numberBetween(1024, 1920);
    $screenHeight = $isMobile ? $faker->randomElement([568, 667, 736]) : $faker->numberBetween(768, 1080);

    return [
        'id' => $id,
        'userAgent' => $userAgent,
        'userLang' => $faker->languageCode,
        'acceptsCookies' => $faker->boolean(90),
        'allowsJavaScript' => $faker->boolean(95),
        'allowsImages' => $faker->boolean(98),
        'allowsCSS' => $faker->boolean(99),
        'userScreenWidth' => $screenWidth,
        'userScreenHeight' => $screenHeight,
        'userWindowWidth' => $screenWidth - $faker->numberBetween(0, 100),
        'userWindowHeight' => $screenHeight - $faker->numberBetween(0, 150),
        'userNetConnType' => $faker->randomElement(['wifi', '4g', '5g', 'ethernet', 'unknown']),
    ];
}

/**
 * Gets a random filename from your real project files under cgi-bin and similar directories, with weighting.
 */
function getRandomProjectFilename() {
    $files = [
        // C files
        ['cgi-bin/c-destroy-session.c', 1],
        ['cgi-bin/c-destroy-session.cgi', 1],
        ['cgi-bin/c-env.c', 1],
        ['cgi-bin/c-env.cgi', 1],
        ['cgi-bin/c-general-request-echo.c', 1],
        ['cgi-bin/c-general-request-echo.cgi', 1],
        ['cgi-bin/c-get-echo.c', 1],
        ['cgi-bin/c-get-echo.cgi', 1],
        ['cgi-bin/c-hello-html-world.c', 1],
        ['cgi-bin/c-hello-html-world.cgi', 1],
        ['cgi-bin/c-hello-json-world.c', 1],
        ['cgi-bin/c-hello-json-world.cgi', 1],
        ['cgi-bin/c-post-echo.c', 1],
        ['cgi-bin/c-post-echo.cgi', 1],
        ['cgi-bin/c-sessions-1.c', 1],
        ['cgi-bin/c-sessions-1.cgi', 1],
        ['cgi-bin/c-sessions-2.c', 1],
        ['cgi-bin/c-sessions-2.cgi', 1],

        // Perl files
        ['cgi-bin/perl-destroy-session.pl', 1],
        ['cgi-bin/perl-env-pm.pl', 1],
        ['cgi-bin/perl-env.pl', 1],
        ['cgi-bin/perl-general-echo.pl', 1],
        ['cgi-bin/perl-get-echo.pl', 1],
        ['cgi-bin/perl-html-world.pl', 1],
        ['cgi-bin/perl-json-world.pl', 1],
        ['cgi-bin/perl-post-echo.pl', 1],
        ['cgi-bin/perl-sessions-1.pl', 1],
        ['cgi-bin/perl-sessions-2.pl', 1],

        // PHP files (twice the weight)
        ['cgi-bin/php-URL-sessions-1.php', 2],
        ['cgi-bin/php-URL-sessions-2.php', 2],
        ['cgi-bin/php-cookie-sessions-1.php', 2],
        ['cgi-bin/php-cookie-sessions-2.php', 2],
        ['cgi-bin/php-destroy-URL-session.php', 2],
        ['cgi-bin/php-destroy-cookie-session.php', 2],
        ['cgi-bin/php-environment.php', 2],
        ['cgi-bin/php-general-request-echo.php', 2],
        ['cgi-bin/php-get-echo.php', 2],
        ['cgi-bin/php-hello-html-world.php', 2],
        ['cgi-bin/php-hello-json-world.php', 2],
        ['cgi-bin/php-post-echo.php', 2],

        // Node files
        ['node/node-destroy-session.js', 1],
        ['node/node-environment.js', 1],
        ['node/node-general-request-echo.js', 1],
        ['node/node-get-echo-form.js', 1],
        ['node/node-get-echo.js', 1],
        ['node/node-hello-html-world.js', 1],
        ['node/node-hello-json-world.js', 1],
        ['node/node-post-echo-form.js', 1],
        ['node/node-post-echo.js', 1],
        ['node/node-sessions-1.js', 1],
    ];

    // Normalizes weights and selects
    $totalWeight = array_sum(array_column($files, 1));
    $rand = mt_rand() / mt_getrandmax(); // random float in [0,1]
    $cumulative = 0.0;

    foreach ($files as [$file, $weight]) {
        $cumulative += $weight / $totalWeight; // normalized, which keeps it in [0,1]
        if ($rand <= $cumulative) {
            return $file;
        }
    }
    return $files[array_key_last($files)][0]; // fallback if none in the loop were returned
}


// Generates an array of fake 'activity' records, each simulating a user event (click, scroll, etc.) 
// within a session
function generateFakeActivity($faker, $id, $userAgent) {
    $isMobile = strpos($userAgent, 'iPhone') !== false;
    $ip = $faker->ipv4;

    return [
        'id' => $id,
        'eventType' => $faker->randomElement(['click', 'scroll', 'keypress', 'mousemove', 'error']),
        'eventTimestamp' => $faker->dateTimeThisMonth()->format('Y-m-d H:i:s'),
        'message' => $faker->optional(0.3, '')->sentence(),
        'filename' => getRandomProjectFilename(),
        'lineno' => $faker->optional()->numberBetween(1, 500),
        'colno' => $faker->optional()->numberBetween(1, 80),
        'error' => $faker->optional(0.1, '')->catchPhrase(),
        'clientX' => $faker->numberBetween(0, $isMobile ? 375 : 1920),
        'clientY' => $faker->numberBetween(0, $isMobile ? 667 : 1080),
        'button' => $faker->numberBetween(0, 2),
        'scrollX' => $faker->numberBetween(0, 10000),
        'scrollY' => $faker->numberBetween(0, 10000),
        'keyVal' => $faker->optional()->randomElement(['a', 'b', 'c', 'Enter', 'Shift', 'Ctrl', 'Alt']),
        'keyCode' => $faker->optional()->randomElement(['KeyA', 'KeyB', 'KeyC', 'Enter', 'ShiftLeft', 'ControlLeft', 'AltLeft']),
        'eventTimeMs' => $faker->numberBetween(0, 5000),
        'userState' => $faker->randomElement(['active', 'idle', 'away']),
        'screenState' => $faker->randomElement(['visible', 'hidden']),
        'idleDuration' => $faker->numberBetween(0, 10000),
        'url' => $faker->url(),
        'title' => $faker->sentence(6),
        'eventCount' => $faker->unique()->numberBetween(1, 10000),
        'clientIP' => $ip,
        'authUser' => $faker->optional()->userName(),
        'vhost' => $faker->domainName(),
        'port' => $faker->optional()->numberBetween(80, 8080),
        'httpStatus' => $faker->optional(0.9, 200)->randomElement([200, 301, 404, 500]),
        'bytesSent' => $faker->optional()->numberBetween(100, 1000000),
        'connStatus' => $faker->optional()->randomElement(['S', 'C']),
        'cookie' => $faker->optional()->regexify('[a-f0-9]{32}'),
    ];
}

// Generates a fake 'performance' record correlated to browser type and linked to a session/user
function generateFakePerformance($faker, $id, $userAgent) {
    // Assigns base timing based on userAgent to simulate some browsers/devices slower than others
    if (strpos($userAgent, 'Chrome') !== false) {
        $baseLoadTime = $faker->numberBetween(500, 1500); // faster
    } elseif (strpos($userAgent, 'Safari') !== false) {
        $baseLoadTime = $faker->numberBetween(700, 1800);
    } elseif (strpos($userAgent, 'Firefox') !== false) {
        $baseLoadTime = $faker->numberBetween(600, 1700);
    } else {
        $baseLoadTime = $faker->numberBetween(800, 2500); // slower/mobile likely
    }

    $pageLoadStart = $faker->dateTimeThisMonth()->format('Y-m-d H:i:s');
    $pageLoadEnd = date('Y-m-d H:i:s', strtotime($pageLoadStart) + $baseLoadTime / 1000);

    return [
        'id' => $id, // parameter passed in, allows for connection to the other tables
        'pageLoadTimingObject' => json_encode([
            'start' => $pageLoadStart,
            'end' => $pageLoadEnd,
            'durationMs' => $baseLoadTime,
        ]),
        'pageLoadStart' => $pageLoadStart,
        'pageLoadEnd' => $pageLoadEnd,
        'pageLoadTotal' => $baseLoadTime,
    ];
}

// Generates fake log entry with all the details currently collected by Apache
function generateFakeApacheLog($faker, $id, $userAgent) {
    $isMobile = strpos($userAgent, 'iPhone') !== false;
    $ip = $faker->ipv4;

    return [
        'entryNum' => null, // Auto-generated by DB, since we're using auto_increment
        'vhost' => $faker->domainName,
        'port' => $faker->numberBetween(8000, 9000),
        'clientIP' => $ip,
        'authUser' => $faker->userName,
        'datetimeReqReceived' => $faker->dateTimeThisMonth()->format('Y-m-d H:i:s'),
        'requestLine' => $faker->httpMethod . ' ' . $faker->url,
        'httpStatus' => $faker->randomElement([200, 301, 404, 500]),
        'bytesSent' => $faker->numberBetween(100, 100000),
        'referer' => $faker->url,
        'userAgent' => $userAgent,
        'timeToServeMS' => $faker->numberBetween(50, 2000),
        'filename' => $faker->filePath(),
        'connStatus' => $faker->randomElement(['S', 'C']),
        'cookie' => $faker->regexify('[a-f0-9]{32}'),
    ];
}

                                                                                                                                       /**
 * Sends data to the specified resource endpoint of our API using a POST request.
 * @param string $resource  The database table resource ("static", "performance", "activity", "apacheLogs").
 * @param array $data       The associative array of data to send as JSON payload.
 */
function sendToApi($resource, $data) {
    $domain = "annekelley.site";   // Fallback to localhost
    $url = "https://$domain/api.php/$resource";

    $ch = curl_init($url);

    // Handles authentication (HTTP Basic Auth)
    curl_setopt($ch, CURLOPT_USERPWD, $_ENV['WEB_USER'] . ':' . $_ENV['WEB_PASS']);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

    // Sets POST and payload
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json' ]);

    $response = curl_exec($ch);

    if ($response === false) {
        echo "Error sending to $resource: " . curl_error($ch) . "\n";
    } else {
        echo "Response from $resource: $response\n";
    }
    curl_close($ch);
}

// ---- SENDING DATA BATCH of 100 ---- //
for ($i = 0; $i < 100; $i++) {
    $id = $faker->uuid;

    // Static entry
    $staticEntry = generateFakeStatic($faker, $userAgents, $id);
    sendToApi('static', $staticEntry);

    // Performance entry
    $perfEntry = generateFakePerformance($faker, $id, $staticEntry['userAgent']);
    sendToApi('performance', $perfEntry);

    // Activity entry (array of events per session)
    $activityPack = [ 'activityLog' => generateFakeActivity($faker, $id, $staticEntry['userAgent']) ];
    sendToApi('activity', $activityPack);

    // Monitor progress
    echo "Sent session $i/100\n";
}
?>
?>