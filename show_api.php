#!/usr/bin/php
<?php
function main() {
    global $argv, $argc;
    $help = "
Usage: 
php {$argv[0]} \"OPTION=\$input\"
Displays the latest Malaysian Air Pollution Index (API) readings.

Option:
area      Shows API for a certain area.
state     Shows API for all areas in the given state.\n";
    
    if ($argc == 2) {
        $input_arr = array_map(function($value) { 
            return trim(strtolower($value)); 
        }, explode("=", $argv[1])); // Argument formatting
        if (sizeof($input_arr) == 2) $srcterm = preg_replace('/\s+/', ' ', $input_arr[1]); // Remove excess whitespace on input area/state
    }

    if ($argc != 2 || 
    in_array($argv[1], array('--help', '-help', '-h', '-?')) || 
    !(in_array($input_arr[0], array('area', 'state')))) return $help;

    $cl = curl_init('http://apims.doe.gov.my/data/public/CAQM/last24hours.json');
    curl_setopt($cl, CURLOPT_FAILONERROR, true);
    curl_setopt($cl, CURLOPT_HEADER, false);
    curl_setopt($cl, CURLOPT_RETURNTRANSFER, true);

    $json = curl_exec($cl);
    if ($json == false) {
        if (curl_errno($cl) == 22) return 'http error: ' . curl_getinfo($cl, CURLINFO_HTTP_CODE);
        else return 'curl error: ' . curl_error($cl);
    }
    curl_close($cl);

    $json = json_decode($json)->{'24hour_api'}; // Neaten up received data
    $states = $areas = [];
    for ($i = 1; $i < sizeof($json); $i++) { // Ignore first array
        $states[] = strtolower($json[$i][0]);
        $areas[] = strtolower($json[$i][1]);
    }

    if ($input_arr[0] == 'area') {
        if (in_array($srcterm, $areas)) {
            return 'API Reading for ' . $json[array_search($srcterm, $areas)+1][1] . ' at ' . end($json[0]) . ': ' . end($json[array_search($srcterm, $areas)+1]);
        }
        else return 'error: area not found';
    }
    else if ($input_arr[0] == 'state') {
        if (in_array($srcterm, $states)) {
            $stateres = 'API Readings for ' . strtoupper($srcterm) . ' at ' . end($json[0]) . ":\n\n";
            $stateres_arr = array_keys($states, $srcterm);
            for ($i = 0; $i < sizeof($stateres_arr); $i++) { 
                $stateres_arr[$i] = $json[$stateres_arr[$i]][1] . ': '. end($json[$stateres_arr[$i]]) . "\n";
            }
            return $stateres . join($stateres_arr);
        }
        else return 'error: state not found';
    }
}
echo main();
?>