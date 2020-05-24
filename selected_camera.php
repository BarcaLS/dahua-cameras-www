<?php

// It downloads one frame from each camera and shows it to you.

############
# Settings #
############
$url_nvr = "http://192.168.1.50:80/cgi-bin/snapshot.cgi?channel="; // url to NVR (main source of snapshots)
$url_cameras = array("192.168.1.5:80", "192.168.1.8:80", "192.168.1.12:80", "192.168.1.15:80", "192.168.1.24:80", "192.168.1.26:80", "192.168.1.39:80", "192.168.1.41:80"); // cameras' IPs or hostnames listed in the NVR channels order (backup source of snaphots)
$url_www = "http://host.com/dahua-cameras-www"; // url to this webpage
$login = "admin"; // user on NVR or camera able to view live stream
$pass = "password"; // password for user on NVR or camera able to view live stream
$curl = "/usr/local/bin/curl"; // path to curl (check it by executing "which curl" as user www)
$too_old = 30; // time in seconds determining how old can be snapshot to be shown; example: when set at 60 only the snapshots which were made earlier than 60 seconds ago will be refreshed
$too_old_to_show_normally = 300; // time in seconds determining how old can be snapshot to be shown normally; example: when set at 300 only the snapshots which were made earlier than 300 seconds ago will be shown normally
                               // this parameter is needed because not every snapshot exceeding $too_old will be succesfully refreshed (because of NVR lagging etc.)
	        	       // we just want to see old snapshot differentially if it has to be shown because of failure in generating newer snapshot
$background_color = "black"; // background color

#############
# Main part #
#############
print "
<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\">
<html>
<head>
    <title>Dahua</title>
    <meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\">
    <link rel=\"stylesheet\" href=\"default.css\" type=\"text/css\">
</head>";

print "<body oncontextmenu=\"return false;\">"; // block right mouse button (to avoid showing context menu)

// let's check width and height of viewport and let's save it to cookie
print "<script type=text/javascript>
<!--
var viewportwidth;
var viewportheight;
// the more standards compliant browsers (mozilla/netscape/opera/IE7) use window.innerWidth and window.innerHeight
if (typeof window.innerWidth != 'undefined')
{
    viewportwidth = window.innerWidth,
    viewportheight = window.innerHeight
}
// IE6 in standards compliant mode (i.e. with a valid doctype as the first line in the document)
else if (typeof document.documentElement != 'undefined'
&& typeof document.documentElement.clientWidth !=
'undefined' && document.documentElement.clientWidth != 0)
{
    viewportwidth = document.documentElement.clientWidth,
    viewportheight = document.documentElement.clientHeight
}
// older versions of IE
else
{
    viewportwidth = document.getElementsByTagName('body')[0].clientWidth,
    viewportheight = document.getElementsByTagName('body')[0].clientHeight
}
viewportwidth = \"viewportwidth=\" + viewportwidth;
viewportheight = \"viewportheight=\" + viewportheight;
document.cookie = viewportwidth;
document.cookie = viewportheight;
-->
</script>
";
$viewportwidth = $_COOKIE['viewportwidth'];
$viewportheight = $_COOKIE['viewportheight'];

// javascript function to manage right click of mouse
print "
<script type=\"text/javascript\">
function mouseDown(e) {
    e = e || window.event;
    if ( !e.which && e.button !== undefined ) {
	e.which = ( e.button & 1 ? 1 : ( e.button & 2 ? 3 : ( e.button & 4 ? 2 : 0 ) ) );
    }
    switch (e.which) {
	case 3: window.location.href = (\"$url_www/\"); break;
    }
}
</script>
";

// let's get some variables
$auto_refresh = $_GET['auto_refresh'];
$refresh_time = $_GET['refresh_time'];

// let's check how many cameras have been selected to adjust proportion of image
$camera_quantity = count($_GET); $camera_quantity = $camera_quantity - 2;
if(!empty($auto_refresh)) { $camera_quantity = $camera_quantity - 1; }
if ($camera_quantity == 0) { echo "Nie wybrałeś żadnej kamery."; }
if ($camera_quantity == 1) { $height = $viewportheight; }
elseif ($camera_quantity == 2) { $width = $viewportwidth / 2; }
elseif ($camera_quantity > 2 and $camera_quantity < 5) { $height = $viewportheight / 2; }
elseif ($camera_quantity > 4 and $camera_quantity < 7) { $width = $viewportwidth / 3; }
elseif ($camera_quantity > 6 and $camera_quantity < 9) { $height = $viewportheight / 3; }

print "
<div style=\"position: fixed; background-color: $background_color; top: 0px; left: 0px; width: " . $viewportwidth . "px; height: " . $viewportheight . "px\">
<div style=\"position: relative; background-color: $background_color; margin: 0 auto; width: 100%; height: " . $viewportheight . "px;\"><center>
";

// create random queue of cameras (NVR doesn't like to be asked about snapshots the same manner every time)
$cameras_in_random_order = range(1, 8); shuffle($cameras_in_random_order);

// grap new snapshots
for($counter=0; $counter < 8; $counter++)
{
    $current_camera = $cameras_in_random_order[$counter];
    $camera = "camera$current_camera";
    $value = $_GET["$camera"];
    $current_channel = $current_camera - 1;
    if(!empty($value)) {
	// generation of filename for this particular snapshot
	$date_and_time = shell_exec("date +%s"); $date_and_time = substr($date_and_time, 0, -1);
	
	// let's get time of newest snapshot available
	$filename = glob("snapshots/camera_" . $current_camera . "*.jpg");
	$newest_snapshot_time = $filename[0];
	$newest_snapshot_time = substr($newest_snapshot_time, 0, strrpos($newest_snapshot_time, "."));
	$newest_snapshot_time = str_replace("snapshots/camera_" . $current_camera . "_", "", $newest_snapshot_time);
	
	// let's check if newest snapshot is older than $too_old_to_live seconds
	$how_old = $date_and_time - $newest_snapshot_time;
	if ($how_old > $too_old_to_live)
	{ // old snapshot is too old, let's grab new snapshot
	    $filename = "camera_" . $current_camera . "_" . $date_and_time;

	    // let's grab with NVR
	    $command = "$curl $url_nvr" . $current_channel . " -o snapshots/" . $filename . ".jpg.tmp -u $login:$pass";
	    shell_exec("$command"); clearstatcache();

	    if(filesize("snapshots/" . $filename . ".jpg.tmp")) { // new file isn't empty
		rename("snapshots/" . $filename . ".jpg.tmp", "snapshots/" . $filename . ".jpg");
	    }
	    else // new file is empty so let's grab with specified camera
	    {
		unlink("snapshots/" . $filename . ".jpg.tmp"); // new file is empty so let's delete it
		$command = "$curl http://" . $url_cameras[$current_channel] . "/cgi-bin/snapshot.cgi?1 -o snapshots/" . $filename . ".jpg.tmp -u $login:$pass --digest";
		shell_exec("$command"); clearstatcache();
		
		if(filesize("snapshots/" . $filename . ".jpg.tmp")) { // new file isn't empty
		    rename("snapshots/" . $filename . ".jpg.tmp", "snapshots/" . $filename . ".jpg");
		} // new file is empty so let's delete it
		else { unlink("snapshots/" . $filename . ".jpg.tmp"); }
	    }
	}
    }
}

// remove all .tmp files (cleaning up directory just to be sure);
$prefix = "snapshots/*.tmp"; array_map('unlink', glob($prefix));

// let's preserve last snapshot from every camera
for($counter=0; $counter < 8; $counter++)
{
    $current_camera = $cameras_in_random_order[$counter];
    $camera = "camera$current_camera";
    $all_snapshots_of_this_camera = array_reverse(glob("snapshots/camera_" . $current_camera . "_*"));
    if(!empty($all_snapshots_of_this_camera[0])) { // it's the newest snapshot of not selected camera, let's preserve it
        rename("$all_snapshots_of_this_camera[0]", "$all_snapshots_of_this_camera[0].tmp");
    }
}

// remove all old snapshots
$all_jpg_files = glob("snapshots/*.jpg");
foreach($all_jpg_files as $current_jpg_file) {
    unlink($current_jpg_file);
}

// rename all current snapshots from tmp to jpg (get rid .tmp from the end of filename)
$all_tmp_files = glob("snapshots/*.tmp");
foreach($all_tmp_files as $current_tmp_file) {
    rename("$current_tmp_file", substr($current_tmp_file, 0, strrpos($current_tmp_file, ".")));
}

// show snapshots
for($counter=1; $counter < 9; $counter++)
{
    $camera = "camera$counter";
    $value = $_GET["$camera"];
    
    // let's check if snapshot from this camera is requested by user
    if(!empty($value)) {
	// grab full name of filename with snapshot for current camera
	$filename = glob("snapshots/".'camera_'.$counter.'*.jpg');
    
	// let's check if snapshot is older than $too_old_to_show_normally seconds and then show this old snapshot differently than actual snapshots
        $newest_snapshot_time = $filename[0];
        $newest_snapshot_time = substr($newest_snapshot_time, 0, strrpos($newest_snapshot_time, "."));
        $newest_snapshot_time = str_replace("snapshots/camera_" . $counter . "_", "", $newest_snapshot_time);	
        $date_and_time = shell_exec("date +%s"); $date_and_time = substr($date_and_time, 0, -1);
        $how_old = $date_and_time - $newest_snapshot_time;
        if ($how_old > $too_old_to_show_normally)
        { // old snapshot is too old, let's show it differentially
    	    $show_old_snapshot = " opacity: 0.2; filter: alpha(opacity=40);";
	}
	else { $show_old_snapshot = ""; }
    
	// let's finally show this snapshot
	if(!empty($width)) {
	    print "<a href=\"$url_www/$filename[0]\" target=_blank onmousedown=\"mouseDown(event);\"><img style=\"vertical-align: middle; width: " . $width . "px; " . $show_old_snapshot . "\" src=$url_www/$filename[0] title=\"Kliknij żeby powiększyć\"></a>";
	}
	else
	{
	    print "<a href=\"$url_www/$filename[0]\" target=_blank onmousedown=\"mouseDown(event);\"><img style=\"vertical-align: middle; height: " . $height . "px; " . $show_old_snapshot . "\" src=$url_www/$filename[0] title=\"Kliknij żeby powiększyć\"></a>";
	}
    }
}

// show empty image to make a last row of snapshots aligned to left
$data = getimagesize($filename[0]);
$file_width = $data[0]; $file_height = $data[1];
if(empty($width)) { // we need to have width when we have only height
    $width = $height * $file_width / $file_height;
}
else // we need to have height when we have only width
{
    $height = $width * $file_height / $file_width;
}
if ($camera_quantity == 3) { print "<img src=\"data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=\" style=\"vertical-align: middle; width: " . $width . "px; height: " . $height . "px;\"><br>"; }
elseif ($camera_quantity == 5) { print "<img src=\"data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=\" style=\"vertical-align: middle; width: " . $width . "px; height: " . $height . "px;\"><br>"; }
elseif ($camera_quantity == 7) { print "<img src=\"data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=\" style=\"vertical-align: middle; width: " . $width * 2 . "px; height: " . $height . "px;\"><br>"; }
elseif ($camera_quantity == 8) { print "<img src=\"data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=\" style=\"vertical-align: middle; width: " . $width . "px; height: " . $height . "px;\"><br>"; }

// let's generate $random variable to avoid 500.html error and needing to clearing the cache of web browser - this can happen from time to time when the URL is always the same
$random = rand(1000000, 9999999);
$query = $_GET;
$query['random'] = $random;
$query = http_build_query($query);
$query = $url_www . "/selected_camera.php" . "?" . $query;

print "</div></div></body>";

// refresh
if(!empty($auto_refresh)) {
    print "<meta http-equiv=refresh content=\"$refresh_time;url=$query\">";
}

?>