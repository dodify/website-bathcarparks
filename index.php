<?php

/**
 * Bath Car Parks index file.
 *
 * The Bath Car Parks website back end has a single index file. The index
 * is responsible for querying the Socrata back end, storing the results in a 
 * local txt file (as an additional cache), and out putting the data in json
 * format for the JavaScirpt interface.
 *
 * @copyright   2014 dodify Ltd.
 * @license     See LICENSE in repository root
 */

// Global settings
define('DATA_URL' , 'https://data.bathhacked.org/api/datasets/8/rows');
define('DATA_FILE', 'data.txt');
define('TIMEOUT'  , 300); // 5 minutes

$cps   = NULL;
$ctime = time();

// If we don't have local copy fetch the data
if(!file_exists(DATA_FILE)) {
    $cps = fetch();
} else {
    $file = fopen(DATA_FILE, "r");
    $time = trim(fgets($file));
    
    // If data is older than the time out fetch again
    if($ctime - $time > TIMEOUT) {
        fclose($file);
        $cps = fetch();
    } else {
        $cps = json_decode(fgets($file));
        fclose($file);
    }
}

// Show error if something went wrong
if(is_null($cps)) {
    echo "<h1>We are sorry but an error has occurred</h1>";
    exit;
}

// Save data to file
file_put_contents(DATA_FILE, $ctime . "\n" . json_encode($cps));

/**
 * Fetch data from Socrata and clean.
 *
 * Builds an HTTP GET request for the Socrata JSON end point for the given 
 * URL and cleans/removes known errors in the data.
 *
 * @return      mixed       Returns the data in a PHP object or NULL on error
 */
function fetch() {
    $cps = json_decode(file_get_contents(DATA_URL, false,
        stream_context_create(array(
            "ssl"=>array(
                "verify_peer"      => false,
                "verify_peer_name" => false,
            ),
        )
    )));

    // Fix/improve data
    if(!is_null($cps)) {
        foreach($cps->data as $cp) {

            // Custom colour logic
            $cp->available = $cp->capacity - $cp->occupancy;

            if($cp->available < 0) {
                $cp->available = 0;
            }

            if($cp->available > 50) {
                $cp->cper = 'p100'; 
            } elseif($cp->available > 10) {
                $cp->cper = 'p50'; 
            } else {
                $cp->cper = 'p0';
            }
            $cp->icon = $cp->cper . '.png';

            // Sometimes location is missing
            if(!property_exists($cp, "location")) {

                // Hard coded locations as missing from data store
                if($cp->name == "Podium CP") {
                    $cp->location = array(
                        "latitude"  => "51.384322",
                        "longitude" => "-2.359572"
                    );
                }
                if($cp->name == "Newbridge P+R") {
                    $cp->location = array(
                        "latitude"  => "51.390423",
                        "longitude" => "-2.405904"
                    );
                }
            }
        }
    }

    return $cps;
}

?>
<!DOCTYPE html>
<html>
    <head>
        <title>Bath Car Parks</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="description" content="Bath Car Parks tells you how many car park spaces are available in all of Bath's biggest car parks. You'll never have to waste time driving around in circles looking for parking space again!" />
        <meta name="keywords" content="Bath parking, Bath car park, Bath parking lot, Parking in Bath, Southgate Parking" />
        <meta name="author" content="www.dodify.com" />
        <meta name="google-site-verification" content="lWurGZbU5Znf2MxvH4Rbx_HLUXKoKKBD4iEms2099Io" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
        <link href="Images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
        <link href="http://cdnjs.cloudflare.com/ajax/libs/meyer-reset/2.0/reset.min.css" rel="stylesheet" type="text/css" />
        <link href='http://fonts.googleapis.com/css?family=Lobster' rel='stylesheet' type='text/css' />
        <link href='http://fonts.googleapis.com/css?family=Dosis:500,700' rel='stylesheet' type='text/css' />
        <link href="Style/main.css" rel="stylesheet" type="text/css" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js" type="text/javascript"></script>
        <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDTwa1H-mf39xfqvvsa4K1tABbx2G7WWlk&amp;sensor=false" type="text/javascript"></script>
        <script type="text/javascript">var cps = <?= json_encode($cps->data) ?>;</script>
        <script src="JavaScript/interface.js" type="text/javascript"></script>
    </head>
    <body>
        <div id="info">
            <h1>Bath Car Parks</h1>
            <h2>Easy Check. Easy Park.</h2>
            <table>
                <thead>
                    <tr>
                        <th>Car Park</th>
                        <th class="aval" colspan="2">Available</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        // Output each car park as a table for left column
                        foreach($cps->data as $cp) {
                            $lupt = substr($cp->lastupdate, 11);
                            $cpName = str_replace("CP", "Car Park", $cp->name); 
                            echo <<<HTML
<tr id="{$cp->id}" class="cp">
    <td>$cpName</td>
    <td class="aval"><span class="sq {$cp->cper}">{$cp->available}</span></td>
    <td><img src="Images/arrow.png" alt="Arrow" /></td>
</tr>
<tr class="info even {$cp->id}">
    <td colspan="3" class="addr">{$cp->description}</td>
</tr>
<tr class="info even {$cp->id}">
    <td>Last Update</td>
    <td class="data" colspan="2">$lupt</td>
</tr>
<tr class="info odd {$cp->id}">
    <td>Capacity</td>
    <td class="data" colspan="2">{$cp->capacity}</td>
</tr>
<tr class="info even {$cp->id}">
    <td>Occupancy</td>
    <td class="data" colspan="2">{$cp->occupancy}</td>
</tr>
<tr class="info odd {$cp->id}">
    <td>% Used</td>
    <td class="data" colspan="2">{$cp->percentage}%</td>
</tr>
<tr class="info even {$cp->id}">
    <td>Status</td>
    <td class="data" colspan="2">{$cp->status}</td>
</tr>
<tr class="info {$cp->id}">
    <td colspan="3" class="directions">
        <a href="#" id="{$cp->id}dir" class="dirlink" rel="external">Go!</a>
    </td>
</tr>
HTML;
                        }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">
                            <a href="http://www.dodify.com" rel="external">Powered and Designed by <span>dodify</span>
                                <img src="Images/dodify.png" alt="dodify" />
                           </a>
                           <br />
                           <a href="http://www.bathhacked.org" rel="external">Data provided by Bath: Hacked</a>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div id="map"></div>
        <script>
            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
            })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

            ga('create', 'UA-17393798-20', 'auto');
            ga('send', 'pageview');
        </script>
    </body>
</html>