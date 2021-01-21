<?php
require_once( 'configuration.php' );
require_once( LANGUAGEFILEPATH ); // Must be here at the top of this file because it outputs the UTF8-BOM!

echo "<!DOCTYPE html>\n";
echo "<html>\n";
echo "<head>\n";
echo "<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" />\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" />\n";
echo "<meta name=\"author\" content=\"Oliver Pfister\" />\n";

// Init page refresh, title and style sheet
require_once( 'setusertz.php' );
echo "<meta http-equiv=\"refresh\" content=\"" . SUMMARYREFRESHSEC . "; URL=" . htmlspecialchars($_SERVER['REQUEST_URI']) . "\" />\n";
echo "<title>" . SUMMARYTITLE . "</title>\n";
echo "<link rel=\"stylesheet\" href=\"" . STYLEFILEPATH . "\" type=\"text/css\" />\n";
$scriptname = basename($_SERVER['SCRIPT_FILENAME']);

// Selected date
$today_year = (int)date('Y');
$today_month = (int)date('m');
$today_day = (int)date('d');
if (isset($_GET['year']) && isset($_GET['month']) && isset($_GET['day'])) {	// Exact Date
	$selected_year = (int)$_GET['year'];
	$selected_month = (int)$_GET['month'];
	$lastdayof_selected_month = date('d',mktime(0, 0, 0, $selected_month + 1, 0, $selected_year));
	$selected_day = min((int)$_GET['day'],(int)$lastdayof_selected_month);
	$dateparams = "year=$selected_year&amp;month=$selected_month&amp;day=$selected_day";
	
	// Reload if today selected (cannot use php reload as UTF8-BOM has already been output)
	if ($selected_year == $today_year && $selected_month == $today_month &&	$selected_day == $today_day) {
		echo "<script type=\"text/javascript\">\n";
		echo "//<![CDATA[\n";
		echo "window.location.href = '$scriptname';\n";
		echo "//]]>\n";
		echo "</script>\n";
	}	
}
else {	// Today
	$selected_year = $today_year;
	$selected_month = $today_month;
	$selected_day = $today_day;
	$dateparams = "";
}
$selected_time = mktime(12,0,0,$selected_month,$selected_day,$selected_year);
$selected_weekday_num = date('w', $selected_time);
$selected_days_elapsed = GetDaysElapsed($selected_time);
$selected_year_string = "$selected_year";
if ($selected_month < 10)
	$selected_month_string = "0"."$selected_month";
else
	$selected_month_string = "$selected_month";
if ($selected_day < 10)
	$selected_day_string = "0"."$selected_day";
else
	$selected_day_string = "$selected_day";

// Max files per page, pages count and page offset
$max_per_page = (int)MAX_PER_PAGE;
$pages = 1; // initialized later on
$page_offset = 0;
if (isset($_GET['pageoffset'])) {
	$page_offset = (int)$_GET['pageoffset'];
	if ($page_offset < 0)
		$page_offset = 0;
	$page_offset = (int)(floor($page_offset / $max_per_page) * $max_per_page);
	// upper bound corrected later on
}

echo "<script type=\"text/javascript\">\n";
echo "//<![CDATA[\n";

echo "function changeStyle(id) {\n";
echo "	if (parent.window.name != '' && document.getElementById(parent.window.name))\n";
echo "		document.getElementById(parent.window.name).className = 'notselected';\n";
echo "	if (document.getElementById(id))\n";
echo "		document.getElementById(id).className = 'lastselected';\n";
echo "	parent.window.name = id; // this var survives between pages!\n";
echo "}\n";

if ($show_camera_commands || $show_trash_command) {
	echo "function preventUserActions() {\n";
	echo "	var anchors = document.getElementsByTagName('a');\n";
	echo "	for (var i = 0; i < anchors.length; i++) {\n";
	echo "		anchors[i].onclick = function() {return false;};\n";
	echo "	}\n";
	echo "	var inputs = document.getElementsByTagName('input');\n";
	echo "	for (var i = 0; i < inputs.length; i++) {\n";
	echo "		inputs[i].disabled = true;\n";
	echo "	}\n";
	echo "}\n";
}

if ($show_camera_commands) {
	echo "function toggleCamera() {\n";
	echo "	preventUserActions();\n";
	echo "	window.location.href = 'camera.php?source=toggle&backuri=" . urlencode(urldecode($_SERVER['REQUEST_URI'])) . "';\n";
	echo "}\n";
}

if ($show_trash_command) {
	echo "function toggleCheckBoxes() {\n";
	echo "	var doCheck = false;\n";
	echo "	var checkboxes = document.getElementsByName('checklist');\n";
	echo "	for (var i = 0; i < checkboxes.length; i++) {\n";
	echo "		if (!checkboxes[i].checked) {\n";
	echo "			doCheck = true;\n";
	echo "			break;\n";
	echo "		}\n";
	echo "	}\n";
	echo "	for (var i = 0; i < checkboxes.length; i++) {\n";
	echo "		checkboxes[i].checked = doCheck;\n";
	echo "	}\n";
	echo "}\n";

	echo "function deleteCheckedElements() {\n";
	echo "	var checkboxes = document.getElementsByName('checklist');\n";
	echo "	var doDelete = false;\n";
	echo "	var filePos = 0;\n";
	echo "	var deleteUrl = 'recycle.php?year=$selected_year_string&month=$selected_month_string&day=$selected_day_string';\n";
	echo "	for (var i = 0; i < checkboxes.length; i++) {\n";
	echo "		if (checkboxes[i].checked) {\n";
	echo "			doDelete = true;\n";
	echo "			deleteUrl += '&' + filePos + '=' + encodeURIComponent(checkboxes[i].value);\n";
	echo "			filePos++;\n";
	echo "		}\n";
	echo "	}\n";
	echo "	if (doDelete) {\n";
	echo "		preventUserActions();\n";
	echo "		window.location.href = deleteUrl + '&backuri=" . urlencode(urldecode($_SERVER['REQUEST_URI'])) . "';\n";
	echo "	}\n";
	echo "}\n";
}
echo "//]]>\n";
echo "</script>\n";

echo "</head>\n";

echo "<body>\n";
            
// Functions
function GetDaysElapsed($passed_time) {
	$passed_date  = mktime(12,0,0,date('m',$passed_time),date('d',$passed_time),date('Y',$passed_time));
	$current_date = mktime(13,0,0,date('m'),date('d'),date('Y')); // Leave 13 and not 12 because of rounding problems!
	$days_elapsed = ($current_date - $passed_date) / (60*60*24);
	return ((int)floor($days_elapsed));
}
function GetDeltaUrl($delta_day) {
	global $selected_day;
	global $selected_month;
	global $selected_year;
	global $scriptname;
	$new_time = mktime(12,0,0,$selected_month,$selected_day+$delta_day,$selected_year);
	$new_year = (int)date('Y', $new_time);
	$new_month = (int)date('m', $new_time);
	$new_day = (int)date('d', $new_time);
	return "$scriptname?year=$new_year&month=$new_month&day=$new_day";
}
function PrintNoFilesDate() {
	global $selected_days_elapsed;
	global $selected_weekday_num;
	$daynames = explode(",", str_replace("'", "", DAYNAMES));
	$day_name = $daynames[$selected_weekday_num];
	echo "<div style=\"text-align: center\">\n<h2>";
	if ($selected_days_elapsed == 0) {
		echo NOFILESFOR . " $day_name (" . TODAY . ")";
	}
	else if ($selected_days_elapsed > 0) {
		if ($selected_days_elapsed == 1)
			echo NOFILESFOR . " $day_name ($selected_days_elapsed " . DAYAGO . ")";
		else
			echo NOFILESFOR . " $day_name ($selected_days_elapsed " . DAYSAGO . ")";
	}
	else {
		$in_days = -$selected_days_elapsed;
		if ($selected_days_elapsed == -1)
			echo NOFILESFOR . " $day_name (" . IN . " $in_days " . DAY . ")";
		else
			echo NOFILESFOR . " $day_name (" . IN . " $in_days " . DAYS . ")";
	}
	echo "</h2>\n</div>\n";
}
function PrintPageNavigation() {
	global $file_array; 	// all the files
	global $pages;			// total pages amount
	global $max_per_page;	// the configured maximum number of displayed files per page
	global $page_offset;	// page offset parameter passed to script
	global $dateparams;		// date parameters passed to script
	global $scriptname;		// script name
	
	// Show pages navigation if more than a page available
	if ($pages > 1) {
		echo "<div class=\"pagination\">\n";
		
		// Previous page
		$prev_page_offset = $page_offset - $max_per_page;
		if ($prev_page_offset <= 0) {
			if ($dateparams == "")
				echo "<a href=\"$scriptname\">&lt;</a>\n";
			else
				echo "<a href=\"$scriptname?$dateparams\">&lt;</a>\n";
		}
		else {
			if ($dateparams == "")
				echo "<a href=\"$scriptname?pageoffset=$prev_page_offset\">&lt;</a>\n";
			else
				echo "<a href=\"$scriptname?$dateparams&amp;pageoffset=$prev_page_offset\">&lt;</a>\n";
		}
		
		// Pages
		$current_page_offset = 0;
		$file_time_array = array_values($file_array);
		for ($page=1 ; $page <= $pages ; $page++) {
			$file_date = getdate($file_time_array[$current_page_offset]);
			$page_text = sprintf("%d&nbsp;<small>(%02d:%02d)</small>", $page, $file_date['hours'], $file_date['minutes']);
			if ($page == 1) {
				if ($current_page_offset == $page_offset) {
					if ($dateparams == "")
						echo "<a class=\"highlight\" href=\"$scriptname\">$page_text</a>\n";
					else
						echo "<a class=\"highlight\" href=\"$scriptname?$dateparams\">$page_text</a>\n";
				}
				else {
					if ($dateparams == "")
						echo "<a href=\"$scriptname\">$page_text</a>\n";
					else
						echo "<a href=\"$scriptname?$dateparams\">$page_text</a>\n";
				}
			} else {
				if ($current_page_offset == $page_offset) {
					if ($dateparams == "")
						echo "<a class=\"highlight\" href=\"$scriptname?pageoffset=$current_page_offset\">$page_text</a>\n";
					else
						echo "<a class=\"highlight\" href=\"$scriptname?$dateparams&amp;pageoffset=$current_page_offset\">$page_text</a>\n";
				}
				else {
					if ($dateparams == "")
						echo "<a href=\"$scriptname?pageoffset=$current_page_offset\">$page_text</a>\n";
					else
						echo "<a href=\"$scriptname?$dateparams&amp;pageoffset=$current_page_offset\">$page_text</a>\n";
				}
			}
			$current_page_offset += $max_per_page;
		}
		
		// Next page
		$last_page_offset = ($pages - 1) * $max_per_page;
		$next_page_offset = $page_offset + $max_per_page;
		if ($next_page_offset >= $last_page_offset) {
			if ($dateparams == "")
				echo "<a href=\"$scriptname?pageoffset=$last_page_offset\">&gt;</a>\n";
			else
				echo "<a href=\"$scriptname?$dateparams&amp;pageoffset=$last_page_offset\">&gt;</a>\n";
		}
		else {
			if ($dateparams == "")
				echo "<a href=\"$scriptname?pageoffset=$next_page_offset\">&gt;</a>\n";
			else
				echo "<a href=\"$scriptname?$dateparams&amp;pageoffset=$next_page_offset\">&gt;</a>\n";
		}
		
		echo "</div>\n";
	}
}

function isPersonDetected($detections, $detectionList, $filename) {
	if ($detections == "") return false;
	$timeStr = substr($filename, 15);

	foreach ($detectionList as $detection) {
		if (matchingTime($timeStr, $detection) == 1) {
			//console_log($filename . " " . $timeStr . " " . getSeconds($timeStr, '_') . " " . $detection);
			return 1;
		}
	}

	return 0;
}

function matchingTime($timeStr, $timeSec) {
	$timeSec2 = getSeconds($timeStr, '_');
	$diff = ($timeSec - $timeSec2);

	if ($diff >= -20 && $diff < 30) {
		return 1;
	}

	return 0;
}

function getSeconds($time, $delim) {
	$its = explode($delim, $time);

	return intval(($its[0] * 3600) + ($its[1] * 60) + $its[2]);
}

function console_log( $data ){
	echo '<script>';
	echo 'console.log("' . $data . '")';
	echo '</script>';
}
            
// Header
echo "<div id=\"header\">\n";

// Live Preview
$clickbackurl = urlencode(urldecode($_SERVER['REQUEST_URI']));
$clickurl = urlencode("snapshotfull.php?clickurl=$clickbackurl");
$url_iframe = "snapshot.php?title=no&amp;menu=no&amp;countdown=no&amp;scrolling=no&amp;thumb=yes&amp;clickurl=$clickurl";
echo "<iframe style=\"display: block; float: right; border: 0; overflow: hidden; width: " . THUMBWIDTH . "px; height: " . THUMBHEIGHT . "px;\" id=\"livepreview\" name=\"livepreview\" src=\"$url_iframe\" width=\"" . THUMBWIDTH . "\" height=\"" . THUMBHEIGHT . "\"></iframe>\n";

// Top Menu
echo "<div>\n";
echo "<span class=\"globalbuttons\">";
if (isset($_SESSION['username'])) {
	echo "<a href=\"" . getParentUrl() . "authenticate.php\">[&#x2192;</a>&nbsp;";
}
echo "<a style=\"font-size: 20px;\" href=\"" . getParentUrl() . "\" target=\"_top\">&#x2302;</a>&nbsp;";
if ($show_camera_commands) {
	echo "<a class=\"camoffbuttons\" href=\"#\" onclick=\"toggleCamera(); return false;\">&nbsp;</a>&nbsp;";
}
if ($show_trash_command) {
	echo "<a style=\"font-size: 12px; position: relative;\" href=\"#\" onclick=\"toggleCheckBoxes(); return false;\"><span style=\"display: inline-block; position: absolute; left: 12px; top: 7px; width: 12px; height: 12px; border: 1px solid #000000;\">&nbsp;</span>&#x2713;</a>&nbsp;";
	echo "<a style=\"font-size: 18px;\" href=\"#\" onclick=\"deleteCheckedElements(); return false;\">&#x1F5D1;</a>&nbsp;";
}
echo "</span>\n";
echo "</div>\n";

// Centered Header
echo "<div style=\"text-align: center\">\n";

// Title
echo "<h1>" . SUMMARYTITLE . "</h1>\n";

// Date picker
echo "<form>\n";
echo "<span class=\"globalbuttons\">\n";
echo "<a href=\"" . htmlspecialchars(GetDeltaUrl(-1)) . "\">&lt;</a>\n";
echo "<input id=\"DatePicker\" type=\"date\" value=\"$selected_year_string-$selected_month_string-$selected_day_string\" />\n";
echo "<a href=\"" . htmlspecialchars(GetDeltaUrl(1)) . "\">&gt;</a>\n";
echo "</span>\n";
echo "<div id=\"day\">\n";
$daynames = explode(",", str_replace("'", "", DAYNAMES));
$day_name = $daynames[$selected_weekday_num];	
if ($selected_days_elapsed == 0)
	echo "<span>$day_name (" . TODAY . ")</span>\n";
else if ($selected_days_elapsed > 0) {
	if ($selected_days_elapsed == 1)
		echo "<span>$day_name ($selected_days_elapsed " . DAYAGO . ") | </span><a href=\"$scriptname\">" . TODAY . "</a>\n";
	else
		echo "<span>$day_name ($selected_days_elapsed " . DAYSAGO . ") | </span><a href=\"$scriptname\">" . TODAY . "</a>\n";
}
else {
	$in_days = -$selected_days_elapsed;
	if ($selected_days_elapsed == -1)
		echo "<span>$day_name (" . IN . " $in_days " . DAY . ") | </span><a href=\"$scriptname\">" . TODAY . "</a>\n";
	else
		echo "<span>$day_name (" . IN . " $in_days " . DAYS . ") | </span><a href=\"$scriptname\">" . TODAY . "</a>\n";
}
echo "</div>\n";
echo "</form>\n";

// End Centered Header
echo "</div>\n";

// Separator
echo "<hr style=\"clear: both\" />\n";

// End Header
echo "</div>\n";

$detections = "";
$detectionList = "";

// Loop through the directory's files and display them
$doc_root = $_SERVER['DOCUMENT_ROOT'];
if ($doc_root == "")
	$dir = "$filesdirpath/".$selected_year_string."/".$selected_month_string."/".$selected_day_string;
else
	$dir = rtrim($doc_root,"\\/")."/".ltrim($filesdirpath,"\\/")."/".$selected_year_string."/".$selected_month_string."/".$selected_day_string;
if ($handle = @opendir($dir)) {
	// Clear flag
	$day_has_files = false;

	$detectFile = "$dir/$file/personDetections.txt";
	if (is_file($detectFile)) {
		$myfile = fopen($detectFile, "r");
		$detections = fread($myfile,filesize($detectFile));
		$detectionList = explode("\r\n", $detections);
		fclose($myfile);

		for ($i = 0; $i < count($detectionList); $i += 1) {
			$detectionList[$i] = getSeconds(substr($detectionList[$i], 0, 8), ':');
		}

	} else {
		$detections = "";
	}
			
	// First catch all the wanted files
	$gif_width = 0;
	$gif_height = 0;
	$file_array = array();
	while (false !== ($file = readdir($handle))) {
		$path_parts = pathinfo($file);
		if (is_file("$dir/$file")) {
			// Split filename
			list($file_prefix, $file_year, $file_month, $file_day, $file_hour, $file_min, $file_sec, $file_postfix) = sscanf($file, "%[a-z,A-Z]_%d_%d_%d_%d_%d_%d_%[a-z,A-Z]");
			if (!isset($file_year))
				$file_year = 2000;
			if (!isset($file_month))
				$file_month = 1;
			if (!isset($file_day))
				$file_day = 1;
			if (!isset($file_hour))
				$file_hour = 0;
			if (!isset($file_min))
				$file_min = 0;
			if (!isset($file_sec))
				$file_sec = 0;
			
			// Get gif thumbs width and height
			if (($gif_width <= 0 || $gif_height <= 0) && $path_parts['extension'] == 'gif')
				list($gif_width, $gif_height) = getimagesize("$dir/$file");
				
			// Fill array
			$hasgif = is_file($dir."/".basename($file, ".".$path_parts['extension']).".gif");
			if ($path_parts['extension'] == 'gif' ||				// Gif thumb
				($path_parts['extension'] == 'mp4' && !$hasgif)) {	// Mp4 without Gif thumb
				$file_time = mktime($file_hour, $file_min, $file_sec, $file_month, $file_day, $file_year); 
				$file_array[$file] = $file_time;
			}
		}
	}
			
	// Now order them and display the wanted page
	if (!empty($file_array)) {
		// Sort by file time
		if (SORT_OLDEST_FIRST == 1)
			asort($file_array);
		else
			arsort($file_array);
		
		// Pages count
		$pages_float = (float)count($file_array) / (float)$max_per_page;
		$pages = floor($pages_float);
		if (($pages_float - $pages) > 0.0)
			$pages++;
		settype($pages, 'int');
	
		// Correct maximum page offset
		$max_page_offset = ($pages - 1) * $max_per_page;
		if ($page_offset > $max_page_offset)
			$page_offset = $max_page_offset;
		
		// Get html query string of mp4s pointed by gifs
		$pos = 0;
		$count = 0;
		$mp4pos = 0;
		$mp4s = "";
		foreach($file_array as $file => $file_time) {
			$path_parts = pathinfo($file);
			if (!isset($path_parts['filename']))
				$path_parts['filename'] = substr($path_parts['basename'], 0, strrpos($path_parts['basename'], '.'));
			
			if ($pos >= $page_offset && $count < $max_per_page) {
				$filenamenoext = basename($file, ".".$path_parts['extension']);
				if ($path_parts['extension'] == 'gif') { // Gif thumb
					if (is_file("$dir/$filenamenoext.mp4")) {
						$mp4s .= "&amp;" . $mp4pos . '=' . urlencode($filenamenoext);
						$mp4pos++;
					}
				}
				$count++;
			}
			$pos++;
		}
		
		// Show top pages navigation
		PrintPageNavigation();
		
		// Display
		$pos = 0;
		$count = 0;
		echo "<div style=\"text-align: center\">\n";
		foreach($file_array as $file => $file_time) {
			$path_parts = pathinfo($file);
			if (!isset($path_parts['filename']))
				$path_parts['filename'] = substr($path_parts['basename'], 0, strrpos($path_parts['basename'], '.'));
			$day_has_files = true;			
			if ($pos >= $page_offset && $count < $max_per_page) {
				$file_date = getdate($file_time);
				$file_timestamp = sprintf("%02d:%02d:%02d", $file_date['hours'], $file_date['minutes'], $file_date['seconds']);
				$filenamenoext = basename($file, ".".$path_parts['extension']);
				list($file_prefix, $file_year, $file_month, $file_day, $file_hour, $file_min, $file_sec, $file_postfix) = sscanf($filenamenoext, "%[a-z,A-Z]_%d_%d_%d_%d_%d_%d_%[a-z,A-Z]");
				$uribasenoext = "$filesdirpath/$selected_year_string/$selected_month_string/$selected_day_string/$filenamenoext";
				$mp4uri = "$uribasenoext.mp4"; $mp4uri_get = urlencode($mp4uri);
				$gifuri = "$uribasenoext.gif"; $gifuri_get = urlencode($gifuri);
				echo "<span class=\"thumbcontainer\">";
				$person = isPersonDetected($detections, $detectionList, $filenamenoext);
				if ($path_parts['extension'] == 'gif') {
					if (is_file("$dir/$filenamenoext.mp4")) {
						if ($person == 1) {
							echo "<a href=\"mp4.php?file=$mp4uri_get&amp;backuri=" . urlencode(urldecode($_SERVER['REQUEST_URI'])) . $mp4s . "\" class=\"personDetected\" id=\"" . $path_parts['filename'] . "\" onclick=\"changeStyle(this.id);\"><img src=\"download.php?embed=yes&amp;file=$gifuri_get\" title=\"$file_timestamp\" alt=\"$file_timestamp\" style=\"vertical-align: middle\" /></a>";
						} else {
							echo "<a href=\"mp4.php?file=$mp4uri_get&amp;backuri=" . urlencode(urldecode($_SERVER['REQUEST_URI'])) . $mp4s . "\" class=\"notselected\" id=\"" . $path_parts['filename'] . "\" onclick=\"changeStyle(this.id);\"><img src=\"download.php?embed=yes&amp;file=$gifuri_get\" title=\"$file_timestamp\" alt=\"$file_timestamp\" style=\"vertical-align: middle\" /></a>";
						}
					} else {
						echo "<a href=\"#\" class=\"notselected\" id=\"" . $path_parts['filename'] . "\" onclick=\"changeStyle(this.id);\"><img src=\"download.php?embed=yes&amp;file=$gifuri_get\" title=\"$file_timestamp\" alt=\"$file_timestamp\" style=\"vertical-align: middle\" /></a>";
					}
				}
				else if ($path_parts['extension'] == 'mp4') {
					$file_prefix_upper = strtoupper($file_prefix);
					if ($gif_width > 0 && $gif_height > 0)
						echo "<span style=\"width: {$gif_width}px; height: {$gif_height}px\">";
					else
						echo "<span>";
					if ($file_prefix_upper == 'SHOT')
						echo "REC<br /><a href=\"mp4.php?file=$mp4uri_get&amp;backuri=" . urlencode(urldecode($_SERVER['REQUEST_URI'])) . "\" >" . FULLDAY . "</a>";
					else
						echo "$file_prefix_upper<br /><a href=\"mp4.php?file=$mp4uri_get&amp;backuri=" . urlencode(urldecode($_SERVER['REQUEST_URI'])) . "\" >$file_timestamp</a>";
					echo "</span>";
				}
				if ($show_trash_command)
					echo "&nbsp;<input style=\"vertical-align: bottom\" type=\"checkbox\" name=\"checklist\" value=\"" . htmlspecialchars($filenamenoext) . "\" />";
				echo "</span>";
				$count++;
			}
			$pos++;
		}
		echo "</div>\n";
	}
			
	// If no files found
	if (!$day_has_files)
		PrintNoFilesDate();
	
	// Show bottom pages navigation
	PrintPageNavigation();
}
// Given day doesn't exist
else
	PrintNoFilesDate();
?>
<a id="back2top" href="#" onclick="window.scrollTo(0, 0); return false;">&#x276f;</a>
<script type="text/javascript">
//<![CDATA[
window.addEventListener('scroll', function() {
	var back2TopButton = document.getElementById('back2top');
	var minScrollHeight = 2 * <?php echo THUMBHEIGHT; ?>;
	if (document.body.scrollTop > minScrollHeight || document.documentElement.scrollTop > minScrollHeight)
		back2TopButton.style.display = 'block';
	else
		back2TopButton.style.display = 'none';
}, false);
if (parent.window.name != '' && document.getElementById(parent.window.name))
	document.getElementById(parent.window.name).className = 'lastselected';
document.getElementById('DatePicker').addEventListener('input', function(ev) {
	if (ev.target.value == '') {// if pressing X in date picker
		window.location.href = '<?php echo "$scriptname"; ?>';
	}
	else {
		var parts = ev.target.value.split('-'); // parse date in yyyy-mm-dd format
		parts[0] = parseInt(parts[0], 10);
		parts[1] = parseInt(parts[1], 10);
		parts[2] = parseInt(parts[2], 10);
		if (parts[0] > 0 && parts[1] > 0 && parts[2] > 0)
			window.location.href = '<?php echo "$scriptname"; ?>?year=' + parts[0] + '&month=' + parts[1] + '&day=' + parts[2];
	}
}, false);
//]]>
</script>
</body>
</html>

