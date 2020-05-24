<?php

// dahua-cameras-www
//
// Allows to watch Dahua cameras on WWW. It downloads one frame from each camera and shows it to you.
// Settings are stored in selected_camera.php.

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
</head>
";
print "<body oncontextmenu=\"return false;\">"; // block right mouse button (to avoid showing context menu after return to this page from selected_camera.php)

// let's generate $random variable to avoid 500.html error and needing to clearing the cache of web browser - this can happen from time to time when the URL is always the same
$random = rand(1000000, 9999999);

print "
<center><a href=\"..\">Main page</a><br><br>
During watching cameras:<br>
<table><tr><td>1)</td><td>click left mouse's button to show picture from camera in new window</td></tr>
<tr><td>2)</td><td>click right mouse's button on camera to choose cameras to show</td></tr></table>
<form action=selected_camera.php method=get><table width=500px>
<tr height=80px><td width=50px align=center><input type=checkbox name=\"camera1\" value=\"1\" style=\"transform:scale(4)\" checked></td><td align=center><b>Gate</b></td>
<td width=50px align=center><input type=checkbox name=\"camera2\" value=\"1\" style=\"transform:scale(4)\" checked></td><td align=center><b>Front</b></td></tr>
<tr height=80px><td width=50px align=center><input type=checkbox name=\"camera3\" value=\"1\" style=\"transform:scale(4)\" checked></td><td align=center><b>Street</b></td>
<td width=50px align=center><input type=checkbox name=\"camera4\" value=\"1\" style=\"transform:scale(4)\" checked></td><td align=center><b>Garage</b></td></tr>
<tr height=80px><td width=50px align=center><input type=checkbox name=\"camera5\" value=\"1\" style=\"transform:scale(4)\" checked></td><td align=center><b>Kitchen</b></td>
<td width=50px align=center><input type=checkbox name=\"camera6\" value=\"1\" style=\"transform:scale(4)\" checked></td><td align=center><b>Tarrace</b></td></tr>
<tr height=80px><td width=50px align=center><input type=checkbox name=\"camera7\" value=\"1\" style=\"transform:scale(4)\" checked></td><td align=center><b>Tree</b></td>
<td width=50px align=center><input type=checkbox name=\"camera8\" value=\"1\" style=\"transform:scale(4)\" checked></td><td align=center><b>Garden</b></td></tr></table>
<br>
<input type=checkbox name=\"auto_refresh\" value=\"1\" style=\"transform:scale(4); margin: 30px 30px 30px 30px\" checked>Automatic refresh every <input type=text name=refresh_time size=1 value=1> seconds.
<input type=hidden name=random value=$random><br><br>
<input type=submit value=\"Let's watch\" style=\"width:400px; height:50px\"></form>";

?>
