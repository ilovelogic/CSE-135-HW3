<?php
require __DIR__ . '/vendor/autoload.php';

$faker = Faker\Factory::create();

use Dotenv\Dotenv;

// Creates a Dotenv instance, pointing to project root directory
$dotenv = Dotenv::createImmutable(__DIR__);

// Loads vars from the .env file into environment (needed when connecting to api.php via server)
$dotenv->load();

$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$dbname = $_ENV['DB_NAME'];
$port = 25060;
$cert = "ca-certificate.crt";

header("Content-Type: application/json");

// Connects to mySQL database
$conn = new mysqli($servername, $username, $password, $dbname, $port, $cert);
// Note that ca-certificate.crt is not on the repo and is kept only on the server itself


// Checks connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Defines user agents with weights
$userAgents = [
    // Chrome (single dominant version), 75%
    ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36', 0.75],

    // Safari (Mac and iOS), 15%
    ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15', 0.08],
    ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1', 0.05],
    ['Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1', 0.02],

    // Firefox (Windows and Linux), 10%
    ['Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:127.0) Gecko/20100101 Firefox/127.0', 0.07],
    ['Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:126.0) Gecko/20100101 Firefox/126.0', 0.02],
    ['Mozilla/5.0 (X11; Linux x86_64; rv:127.0) Gecko/20100101 Firefox/127.0', 0.01],
];



// Selects a random item from a list, weighted by the specified probabilities
function weightedRandomItem($items) {
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
    $userAgent = weightedRandomItem($userAgents);
    $isMobile = strpos($userAgent, 'iPhone') !== false;
    $screenWidth = $isMobile ? $faker->randomElement([320, 375, 414]) : $faker->randomElement([1280, 1920, 1440]);
    $screenHeight = $isMobile ? $faker->randomElement([568, 667, 736]) : $faker->randomElement([1024, 800, 720, 1080]);

    $langs = [["en-US", 0.5], ["es",0.45], ["de", 0.01], ["ja", 0.01], ["fr", 0.03]];
    $lang = weightedRandomItem($langs);

    return [
        'id' => $id,
        'userAgent' => $userAgent,
        'userLang' => $lang,
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
function getRandomFilenameTitle() {
    // In order to avoid manually determining the title for each file,
    // I set the liklihood factor to 0 for most of the filenames.
    // Later, if I want them to show up in the entries, I can add the title and change the factor
    $files = [    
    // PHP endpoints (no weight, can change later if visits at these places needed)
    ['cgi-bin/php-URL-sessions-1.php', '', 0],
    ['cgi-bin/php-URL-sessions-2.php', '', 0],
    ['cgi-bin/php-cookie-sessions-1.php', '', 0],
    ['cgi-bin/php-cookie-sessions-2.php', '', 0],
    ['cgi-bin/php-destroy-URL-session.php', '', 0],
    ['cgi-bin/php-destroy-cookie-session.php', '', 0],
    ['cgi-bin/php-environment.php', '', 0],
    ['cgi-bin/php-general-request-echo.php', '', 0],
    ['cgi-bin/php-get-echo.php', '', 0],
    ['cgi-bin/php-hello-html-world.php', '', 0],
    ['cgi-bin/php-hello-json-world.php', '', 0],
    ['cgi-bin/php-post-echo.php', '', 0],

    // C CGI scripts no weight, can change later if visits at these places needed
    ['cgi-bin/c-destroy-session.cgi', '', 0],
    ['cgi-bin/c-env.cgi', '', 0],
    ['cgi-bin/c-general-request-echo.cgi', '', 0],
    ['cgi-bin/c-get-echo.cgi', '', 0],
    ['cgi-bin/c-hello-html-world.cgi', '', 0],
    ['cgi-bin/c-hello-json-world.cgi', '', 0],
    ['cgi-bin/c-post-echo.cgi', '', 0],
    ['cgi-bin/c-sessions-1.cgi', '', 0],
    ['cgi-bin/c-sessions-2.cgi', '', 0],

    // Perl CGI endpoints (varied weight, 1-3)
    ['cgi-bin/perl-destroy-session.pl', 'Perl Session Destroyed', 2],
    ['cgi-bin/perl-env-pm.pl', 'Environment Variables', 2],
    ['cgi-bin/perl-env.pl', 'Environment Variables', 2],
    ['cgi-bin/perl-general-echo.pl', 'General Request Echo', 3],
    ['cgi-bin/perl-get-echo.pl', 'GET Request Echo', 2],
    ['cgi-bin/perl-html-world.pl',' Hello, Perl!', 3],
    ['cgi-bin/perl-json-world.pl', '', 1],
    ['cgi-bin/perl-post-echo.pl', 'POST Request Echo', 2],
    ['cgi-bin/perl-sessions-1.pl', 'Perl Sessions 1', 2],
    ['cgi-bin/perl-sessions-2.pl', 'Perl Sessions 2', 3],

    // Files at root directory
    ['404.html', 'Oh no!', 6], // second highest weight on error page
    ['api.php', '', 0],
    ['c-cgiform.html', '', 0],
    ['database.html', '', 0],
    ['dont_go_here.html', 'Naughty Bot!', 3], // nonzero weight corresponds to bots being present
    ['hello.php', '', 0],
    ['hellodataviz.html', '', 0],
    ['index.html', 'CSE 135', 4], // same level as cgi-bin php files
    ['node-cgiform.html', '', 0],
    ['perl-cgiform.html', 'CGI Form', 3],
    ['php-cgiform.html', '', 0],
    ['robots.txt', '', 8], // highest weight on bot file
    ['server.js', '', 0],
    ['styles.css', '', 0],
    ['members/annekelley.html', 'Anne Kelley', 4], 
];

    // Normalizes weights and selects
    $totalWeight = array_sum(array_column($files, 2)); // index 2 is where weight is in the array
    $rand = mt_rand() / mt_getrandmax(); // random float in [0,1]
    $cumulative = 0.0;

    foreach ($files as [$file, $title, $weight]) {
        $cumulative += $weight / $totalWeight; // normalized, which keeps it in [0,1]
        if ($rand <= $cumulative) {
            return [$file, $title];
        }
    }
    return $files[array_key_last($files)][0]; // fallback if none in the loop were returned
}


// Generates an array of fake 'activity' records, each simulating a user event (click, scroll, etc.) 
// within a session
function generateFakeActivity($faker, $id, $userAgent) {
    $isMobile = strpos($userAgent, 'iPhone') !== false;
    [$filename, $title] = getRandomFilenameTitle();

    $eventType = $faker->randomElement(['click', 'scroll', 'keypress', 'mousemove', 'error']);
    if ($eventType === 'error') {
        $message = "Uncaught TypeError: Assignment to constant variable.";
    }
    else {
        $message = null;
    }

    return [
        'id' => $id,
        'eventType' => $eventType,
        'eventTimestamp' => $faker->dateTimeThisMonth()->format('Y-m-d H:i:s'),
        'message' => $message,
        'filename' => $filename,
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
        'url' => "https://annekelley.site/" . $filename,
        'title' => $title,
        'eventCount' => $faker->unique()->numberBetween(1, 10000)
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
        'pageLoadTimeTotal' => $baseLoadTime,
    ];
}

// Generates fake log entry with all the details currently collected by Apache
function generateFakeApacheLog($faker, $id, $userAgent) {
    $isMobile = strpos($userAgent, 'iPhone') !== false;
    $ip = $faker->ipv4;
    $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']; // CRUD HTTP methods
    $httpMethod = $methods[array_rand($methods)];
    [$filename, $title] = getRandomFilenameTitle(); // We do not use title here, but that is the format
    // of the return since it is needed for activity generation

    return [
        'entryNum' => null, // Auto-generated by DB, since we're using auto_increment
        'vhost' => "annekelley.site",
        'port' => $faker->numberBetween(8000, 9000),
        'clientIP' => $ip,
        'authUser' => $faker->randomElement(["William", "Anne", ""]),
        'datetimeReqReceived' => $faker->dateTimeThisMonth()->format('Y-m-d H:i:s'),
        'requestLine' => $httpMethod . ' https://annekelley.site/' . $filename,
        'httpStatus' => $faker->randomElement([200, 301, 404, 500]),
        'bytesSent' => $faker->numberBetween(100, 100000),
        'referer' => $faker->url,
        'userAgent' => $userAgent,
        'timeToServeMS' => $faker->numberBetween(50, 1000),
        'filename' => $filename,
        'connStatus' => $faker->randomElement(['S', 'C']),
        'cookie' => $faker->regexify('[a-f0-9]{32}'),
    ];
}

// ---- SENDING DATA BATCH of 100 ---- //
$model = new \Model\AnalyticsModel($conn);
for ($i = 0; $i < 100; $i++) {
    $id = $faker->uuid;

    // Static entry
    $staticEntry = generateFakeStatic($faker, $userAgents, $id);
    $model->insert('static', $staticEntry);

    echo "static entry completed";
    // Performance entry
    $perfEntry = generateFakePerformance($faker, $id, $staticEntry['userAgent']);
    $model->insert('performance', $perfEntry);

    // Activity entry (array of events per session)
    $activityPack = [];
    $activityPack['activityLog'] = [];
    for ($j = 0; $j < 5; $j++) {
        array_push($activityPack['activityLog'], generateFakeActivity($faker, $id, $staticEntry['userAgent']));
    }
    $model->insert('activity', $activityPack);

    // Apache log entry
    $apacheLog = generateFakeApacheLog($faker, $id, $staticEntry['userAgent']);
    $model->insert('apacheLogs', $apacheLog);

    // Monitor progress
    echo "Sent session $i/100\n";
}

?>