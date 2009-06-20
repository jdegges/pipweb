<?php

/* 
 * starts the recording/processing:
 * filter=edges         (filter to use)
 * resolution=640x480   (resolution)
 * frames=100           (number of frames to record)
 * duration=3600        (number of seconds to record for)
 * uspf=30000           (microseconds per frame)
 */

error_reporting( E_ALL );
require_once("config.php");

$m = -1;
$h = -1;
$d = -1;
$mo = -1;
$y = -1;
$cron = -1;

function validate_arg($x) {
    if( is_numeric($x) )
        return (integer) $x;
    return -1;
}

# returns 1 if valid 0 if invalid
function validate_cron() {
    global $m, $h, $d, $mo, $y, $cron;
    global $frames, $duration, $uspf;

    # if an existing scheduled recording exists, just exit
    if( file_exists("cron_record.sh") )
        return 0;

    # find the minimum record length
    $length = $frames * $uspf;
    if( $length == 0 && $duration != 0 )
        $length = $duration;
    else if( $length != 0 && $duration == 0 )
        $length = $length;
    else if( $length != 0 && $duration != 0 )
        $length = min($length, $duration);
    else
        $length = 0;

    $cron_options = array("hourly"  => 3600,
                          "daily"   => 86400,
                          "weekly"  => 604800,
                          "monthly" => 2629744,
                          "yearly"  => 31556926);

    if( isset($_GET['m']) )
        $m = validate_arg($_GET['m']);

    if( isset($_GET['h']) )
        $h = validate_arg($_GET['h']);

    if( isset($_GET['d']) )
        $d = validate_arg($_GET['d']);

    if( isset($_GET['mo']) )
        $mo = validate_arg($_GET['mo']);

    if( isset($_GET['y']) )
        $y = validate_arg($_GET['y']);

    if( isset($_GET['cron']) && isset($cron_options[$_GET['cron']]) )
        $cron = $cron_options[$_GET['cron']];

    # XXX if cron is invalid then just make it repeat yearly
    if( $cron < 0 )
        $cron = 31556926;

    # if cron is valid and overlapping would occur then do nothing
    if( $cron < $length )
        return 0;

    echo "$m<br/>$h<br/>$d<br/>$mo<br/>$y<br/>$cron<br/><br/>";

    # if the time is invalid just pick some time 60 seconds into the future
    if( $m == -1 || $h == -1 || $d == -1 || $mo == -1 || $y == -1
        || $cron == -1 || mktime($h, $m, 0, $mo, $d, $y)+10 < time() ) {
        $timestr = strftime("%M-%H-%d-%m-%Y", time()+60);
        $timearr = explode("-", $timestr);
        $m = $timearr[0];
        $h = $timearr[1];
        $d = $timearr[2];
        $mo = $timearr[3];
        $y = $timearr[4];
        echo "$m<br/>$h<br/>$d<br/>$mo<br/>$y<br/>$cron<br/>";
    }

    return 1;
}

$ps = `ps aux | grep -i ia | wc -l`;
if( $ps == 4 ) {
    echo "You are currently recording from the camera. Please either wait for this session to finish before scheduling a new job or stop the current job.";
    return;
}

/* allowed filters */
$filters = array( "edges" => "edges",
                  "copy"  => "copy" ,
                  "flow"  => "flow"
                );

/* allowed resolutions */
$resolutions = array( "320x240" => "320x240",
                      "640x480" => "640x480"
                    );

/* set the filter */
$filter = "copy";
if( isset($_GET["filter"]) && //isset($filters[$_GET["filter"]]) )
    (!strcmp($_GET["filter"],"edges") || !strcmp($_GET["filter"],"copy")) )
    $filter = $_GET["filter"];
else
    $filter = "copy";

/* set the resolution */
$resolution = explode('x', "320x240");
if( isset($_GET["resolution"]) && isset($resolutions[$_GET["resolution"]]) )
    $resolution = explode('x', $resolutions[$_GET["resolution"]]);

/* set number of frames to record */
$frames = 0;
if( isset($_GET["frames"]) && is_numeric($_GET["frames"]) )
    $frames = (integer) $_GET["frames"];

/* set record duration */
$duration = 0;
if( isset($_GET["duration"]) && is_numeric($_GET["duration"]) )
    $duration = (integer) $_GET["duration"];

/* set frame rate */
$uspf = 0;
if( isset($_GET["uspf"]) && is_numeric($_GET["uspf"]) )
    $uspf = (integer) $_GET["uspf"];


# the command line used to execute the capture & processing
$exec = "$IA"
        . " --width " . $resolution[0]
        . " --height " . $resolution[1]
        . " --thumbnail"
        . " --threads 1"
        . " --refs 1"
        . " --ext jpg"
    #   . " --input /src/image-analyzer/foo.txt"
        . " --output \${output_base_directory}/images/\${output_directory}"
        . " --filter $filter"
        . " --vframes $frames"
        . " --duration $duration"
        . " --spf $uspf";

# the command used to generate a list of the images which resulted from the
# capture & processing
$genlog = "$FIND \${output_base_directory}/images/\${output_directory} | $GREP -v thumb | $GREP -i jpg | $SORT > /tmp/\${tarball}.txt";

# command used to take the output images and convert them into a movie file
$genmovie = "$IA -analyzer --input /tmp/\${tarball}.txt --output \${output_base_directory}/images/\${output_directory}/video.mjpg --stream --filter copy --threads 1 --refs 1 --ext jpg";

# command used to create an archive of the output images & output movie
$tar = "$TAR -cz -f \${output_base_directory}/archives/\${tarball} -C \${output_base_directory}/images/ \${output_directory}";

# command to remove the temporary log file
$rmlog = "$RM /tmp/\${tarball}.txt";

if( validate_cron() ) {

    # create new crontab file
    if( $cron == 3600 )
        $crontab = "$m * $d $mo *\t";
    else if( $cron == 604800 )
        $crontab = "$m $h * $mo *\t";
    else if( $cron == 2629744)
        $crontab = "$m $h $d * *\t";
    else
        $crontab = "$m $h $d $mo *\t";

    $crontab .= "cron_record.sh";

    $h = fopen("cron_record.sh", "w");
    if( $h == NULL )
        return;

    fwrite($h, "#! /bin/bash
# set output base directory
output_base_directory=/media/mmcblk0p2/pipweb/filebrowser

# set output direcotry
output_direcotry=date +%Y-%m-%d-%H-%M-%S

# set tarball name
tarball=\${output_directory}.tar.gz

# create the directory to store images into
mkdir \${output_base_direcotry}/images/\${output_directory}\n\n");

    fwrite($h, "# exec the image capture & analysis program\n");
    fwrite($h, "$exec\n\n");

    fwrite($h, "# the command used to generate a list of the images which resulted from the
# capture & processing\n");
    fwrite($h, "$genlog\n\n");

    fwrite($h, "# command used to take the output images and convert them into a movie file\n");
    fwrite($h, "$genmovie\n\n");

    fwrite($h, "# command used to create an archive of the output images & output movie\n");
    fwrite($h, "$tar\n\n");

    fwrite($h, "# command to remove the temporary log file\n");
    fwrite($h, "$rmlog\n\n");

    fwrite($h, "# command to put the gumstix into sleep mode. it will be woken up from either
# a signal from GPIO 1 or the RTC alarm\n");
    fwrite($h, "#pxaregs PWER_WERTC 1
#pxaregs PWER_WE0 1
#pxaregs GPDR0_0 0
#pxaregs PRER_RE0 1
#pxaregs PFER_FE0 0
#echo;
#echo \"standby\" > /sys/power/state\n\n");

    fwrite($h, "# reset crontab\n");
    fwrite($h, "crontab -r\n");

    fclose($h);
    
    # install new crontab file
    $h = fopen("new_crontab.txt", "w");
    fwrite($h, "$crontab");
    fclose($h);

    exec( "crontab -r" );
    exec( "crontab new_crontab.txt" );
}

?>
