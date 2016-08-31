<?php
	//include $_SERVER['DOCUMENT_ROOT'] . '/intech/hr/people/contacts/functions/config.php';
	//include 'config.php';
    require( plugin_dir_path( __FILE__ ) . 'inc/config.php');
	$db = connectDB('RO');

	$org = '';
	$div = '';
	$unt = '';
	$prevunt = '';
	$topLink = "<h6><a href=\"#top\"><img src=\"/intech/hr/people/images/up.gif\" width=\"9\" height=\"9\" border=\"0\"></a> <a href=\"#top\" class=\"slnk\">Top</a></h6>\n";

	// Web page title and introductory text
 	echo "\n<h1>Organizational Chart - UW Information Technology</h1>";	
	
	// URL parameter options
	if ($_REQUEST[org]) $org = strtoupper($_REQUEST[org]);
	if ($_REQUEST[div]) $div = strtoupper($_REQUEST[div]);
	if ($_REQUEST[unt]) {
		if(strpos($_REQUEST[unt], '-') !== FALSE ) {
			list($div, $unt) = explode("-", strtoupper($_REQUEST[unt]));
		// Define OVP as the parent div; needed because only divs have nested units
		} else {
			$org = 'OVP';
			$unt = strtoupper($_REQUEST[unt]);
		}
	}
		
	// If there are parameters
	if (isset($_REQUEST['org']) || isset($_REQUEST['div']) || isset($_REQUEST['unt'])) {
		echo "\n<a name=\"top\"></a>";
		echo "\n<div id=\"chartleft\">";
		echo "\n	<a href=\"org.php\"><img src=\"../images/uwit_thumbnail.gif\" width=\"78\" height=\"55\" border=\"0\" alt=\"Return to UW Information Technology orgchart\" /></a>";
		echo "\n	<p>Staff names are linked to Who's Who staff directory - online business cards</p>";

		if ($div) echo "<p><a href=\"http://uw.edu/uwit/division/".strtolower($div)."/\">About ".strtoupper($div)."&nbsp;>></a></p>";
		echo "\n</div>";
		
		// Open orgchart div and treeview
		echo "\n<div id=\"orgchart\">";
		echo "\n<ul class=\"treeview\">";
		
		// Select organizational elements for org or div/unt for division
		if ($org) $orgQuery = "SELECT * from depts WHERE dept ILIKE '$org' OR (orglevel = 'unit' AND dept NOT ILIKE '%-%' AND orgchart <> '') ORDER BY orglevel!= 'org', lower(fullname)";
		if ($div) $orgQuery = "SELECT * from depts WHERE dept ILIKE '$div' OR dept ILIKE '$div-%' AND orglevel <> 'group' ORDER BY orglevel!= 'div', lower(fullname)";
		
		$orgResult = runQuery($db, $orgQuery);
		$orgNum = pg_numrows($orgResult);
		
		// For each nested organizational element  
		while ($orgRow = pg_fetch_array($orgResult)) {
			
			// Determine number of staff at this orglevel; if only one don't look for directReports when displaySupervisors
			$staffQuery = "SELECT netid FROM allcontacts WHERE dept ILIKE '$orgRow[dept]%'";
			$staffResult = runQuery($db, $staffQuery);
			$numStaff = pg_numrows($staffResult);
			
			// Div or Org orglevel; 
			if ($orgRow[orglevel] == 'div' || $orgRow[orglevel] == 'org'){
				echo "\n<li class=\"divisionlevel\">";
				if ($orgRow[orglevel] == "div") echo "\n	<p><h2><a href=\"org.php?div=$orgRow[dept]\">$orgRow[fullname]</a></h2></p>";
				if ($orgRow[orglevel] == "org")	echo "\n	<p><h2 class=\"ovp\"><a href=\"org.php?org=$orgRow[dept]\">$orgRow[fullname]</a></h2></p>";		

				echo "\n	<p><div class=\"task\">";
				if ($orgRow[orglevel] == "div") echo "(Div: $orgRow[dept] | UW Group: <a href=\"https://groups.uw.edu/group/uw_it_div_".strtolower($orgRow[dept])."\">uw_it_div_".strtolower($orgRow[dept])."</a> | Task: $orgRow[task_code])</div></p>";
				if ($orgRow[orglevel] == "org") echo "(Org: $orgRow[dept] | UW Group: <a href=\"https://groups.uw.edu/group/uw_it_org_".strtolower($orgRow[dept])."\">uw_it_org_".strtolower($orgRow[dept])."</a> | Task: $orgRow[task_code])</div></p>";
				
				// Display all orglevels or just unit
				if (!$unt) {
					displaySupervisors ($db, $orgRow[dept], $orgRow[orglevel], $numStaff);
				} else {
					displaySupervisors ($db, $orgRow[dept], divhead, $numStaff);
					echo "\n<br />";
					echo "\n<ul>";
				}
				
				$prevunt = '';
			
			// Unit orglevel
			} else {
				$curunt = $orgRow[dept]; // Define for OVP units
				if(strpos($orgRow[dept], '-') !== FALSE ) $orgRow[fullname] = trim(strstr($orgRow[fullname], ' ')); // strip div-unt from full department name
				if(strpos($orgRow[dept], '-') !== FALSE ) list( , $curunt) = explode("-", $orgRow[dept]); // get unt from div-unt for UW group URL
				
				// Open new unit orglevel
				echo "\n<li class=\"unitlevel\">";
				echo "\n	<p>";
				if ($prevunt == '' || !$unt) echo "<br />";
				echo "<h3><a href=\"org.php?unt=$orgRow[dept]\"> $orgRow[fullname]</a></h3></p>";

				// Display all orglevels or just unit
				if (!$unt || $unt == $curunt) {
					echo "\n	<p><div class=\"task\">";
					if ($div) echo "(Div-Unt: $orgRow[dept]";
					if ($org) echo "(Org-Unt: $orgRow[dept]";
					echo " | UW Group: <a href=\"https://groups.uw.edu/group/uw_it_unit_".strtolower($curunt)."\">uw_it_unit_".strtolower($curunt)."</a> | Task: $orgRow[task_code])</div></p>";
					displaySupervisors ($db, $orgRow[dept], $orgRow[orglevel], $numStaff); 
				} else {
					echo "\n<ul>";
				}
				
				$prevunt = $curunt;

				// I removed this because it was breaking the flow if there was more than 1 supervisor position
				/*if ($numStaff > 1) {
					if (!$unt || $unt == $curunt) echo "\n<li class=\"close\">&nbsp;</li>";	
				}*/
				echo "\n</ul> <!--/unitlevel group-->";
				if (!$unt) echo "\n$topLink";
				echo "\n</li> <!--/unitlevel-->";
			}
		}

		// Close last unit orglevel
		echo "\n<li class=\"close\">&nbsp;</li>";
		
		// Close div or org orglevel
		echo "\n</ul> <!--/divlevel-->";
		echo "\n</li><!--/divlevel-->";
		echo "\n<li class=\"close\">&nbsp;</li><span data-heygirl></div>";
		// Close treeview and orgchart div
		echo "\n</ul><!--/treeview-->";
		echo "\n</div><!--/orgchart-->";
	
	// There are no parameters; display org structure
	} else {
		$supvname = "";
		$orgQuery = "SELECT * from depts WHERE orglevel <> 'unit' AND orglevel <> 'group' ORDER BY orglevel desc, lower(fullname)";
		$orgResult = runQuery($db, $orgQuery);
		echo "\n<div class=\"tree\">";
		echo "\n<ul>";
		
		while ($orgRow = pg_fetch_array($orgResult)) {
			getOrgHead ($db, $orgRow[dept], $orgRow[orglevel], $supvname);
			if ($orgRow[orglevel] == "org") {
				echo "\n<li class=\"org\"><a class=\"org\" href=\"org.php?org=$orgRow[dept]\">$orgRow[fullname]<p class=\"head slt\">$supvname*</p></a>";
				echo "\n<ul>";
				echo "\n<li class=\"div\">";
				echo "\n<ul>";
			} else {
				// Display div
				echo "\n<li class=\"div\"><a class=\"div\" href=\"org.php?div=$orgRow[dept]\">$orgRow[fullname]<p class=\"head slt\">$supvname*</p></a>";
				
				// Select units under div
				$untQuery = "SELECT * from depts WHERE dept ILIKE '$orgRow[dept]-%' AND orglevel = 'unit' ORDER BY lower(fullname)";
				$untResult = runQuery($db, $untQuery);
				$numUnts = pg_numrows($untResult);
				if ($numUnts > 0) {
					echo "\n<ul class=\"units\">";
					$orgdiv = "";
					displayUntHeads ($db, $untResult, $orgdiv);
					echo "\n</ul>";
				}
				echo "\n</li>";
			}
		}
		echo "\n</ul>";
		echo "\n</li>";
		
		// Select units under OVP
		$untQuery = "SELECT * from depts WHERE orglevel = 'unit' AND dept NOT ILIKE '%-%' AND orgchart <> '' ORDER BY lower(fullname)";
		$untResult = runQuery($db, $untQuery);
		$numOrgUnts = pg_numrows($untResult);
		if ($numOrgUnts > 0) {
			$orgdiv = "true";
			echo "\n<li class=\"orgdiv\">";
			echo "\n<ul class=\"units\">";
			displayUntHeads ($db, $untResult, $orgdiv);
			echo "\n</ul>";
			echo "\n</li>";
		}	
		echo "\n</ul>";
		echo "\n</li>";
		echo "\n</ul>";
		echo "\n</div> ";
		echo "<p class=\"head sltlink\" align=\"right\">*<a href=\"../seniorleadership/\">Senior Leadership</a></p>";
		echo "<p align=\"center\"><img src=\"../images/orglevels.png\" width=\"493\" height=\"50\" alt=\"Organization Levels: Org -> Div -> Unit -> Group -> Team\"></p>";
	}
	pg_close($db);	
								

function displayUntHeads ($db, $untResult, $orgdiv) {	
	while ($untRow = pg_fetch_array($untResult)) {
		getOrgHead ($db, $untRow[dept], $untRow[orglevel], $supvname);
		if(strpos($untRow[dept], '-') !== FALSE ) $untRow[fullname] = trim(strstr($untRow[fullname], ' ')); // strip div-unt from full department name
		echo "\n<li class=\"unt\">";
		if ($orgdiv) {
			echo "<a class=\"unt\" href=\"org.php?unt=$untRow[dept]\"> $untRow[fullname]<p class=\"head slt\">$supvname*</p></a></li>";
		} else {
			echo "<a class=\"unt\" href=\"org.php?unt=$untRow[dept]\"> $untRow[fullname]<p class=\"head\">$supvname</p></a></li>";
		}
	}
}

function compareByLastName($a, $b) {
  return strcmp($a["lname"], $b["lname"]);
}

function getOrgHead ($db, $dept, $orglevel, &$supvname) {	
	$supvname = "";
	if ($orglevel == "unit") {
		$supvQuery = "SELECT fname, lname FROM allcontacts WHERE dept = '$dept' AND supervisor > '30' AND supervisor < '40'";
	} else {
		$supvQuery = "SELECT fname, lname FROM allcontacts WHERE dept = '$dept' AND supervisor > '40'";
	}
	$supvResult = runQuery($db, $supvQuery);
	$num = pg_numrows($supvResult);

	if ($num > 1) {
                $supervisors = Array();
                for ($i = 0; $i < $num; $i++) {
                        $supervisors[$i] = pg_fetch_array($supvResult, $i);
                }
		// Sort by last name
		usort($supervisors, 'compareByLastName');
                foreach ($supervisors as $person) {
			$supvname .= $person[fname] . ' ' . $person[lname] . '<span style="display: block; height: 4px;"></span>';
		} 
	} else if ($num > 0) {
		$supervisor = pg_fetch_array($supvResult, 0);
                $supvname = "$supervisor[fname] $supervisor[lname]";
	}
	else {
		$supvname="TBD";
	}
}	

	
function displaySupervisors ($db, $dept, $orgLevel, &$numStaff) {
	
	// Build query to select supervisors in orglevel
	$supvQuery = "SELECT fname, lname, netid, department, subgroup, supervisor, title, task_code, comments FROM allcontacts WHERE dept = '$dept' AND ";
	if ($orgLevel == 'div' || $orgLevel == 'divhead' || $orgLevel == 'org') {
		$supvQuery .= "supervisor > '40' ";
	} elseif ($orgLevel == 'unit') {
		$supvQuery .= "supervisor > '30' AND supervisor < '40' ";
	}
	$supvQuery .= "ORDER BY supervisor DESC, lower(department), lower(subgroup), lower(lname), lower(fname)";
//					case when lower(subgroup) < lower(department) then lower(subgroup) else lower(department) end desc,
//					case when lower(department) < lower(subgroup) then lower(department) else lower(subgroup) end desc";
		
	
	// Find all supervisors in orglevel
	$supvResult = runQuery($db, $supvQuery);
	$numSupv = pg_numrows($supvResult);
	
	$group = '';
	$subgroup = '';
	
	// Check if there are supervisors at this org level
	if ($numSupv > 0) {
		while ($supvRow = pg_fetch_array($supvResult)) {
			if(strpos($supvRow[dept], '-') !== FALSE ) $supvRow[department] = trim(strstr($supvRow[department], ' ')); // strip div-unt from full department name
			if(strpos($supvRow[dept], '-') !== FALSE ) list( , $curunt) = explode("-", $supvRow[dept]); // get unt from div-unt for UW group URL

			// Check for photos and build supervisor photo link 
			$photo = "<a href=\"/intech/hr/people/contacts/view.php?lname=$supvRow[lname]&fname=$supvRow[fname]\"><img src=\"/intech/hr/people/thumbnails/";
			if ($supvRow[netid]&&file_exists("$_SERVER[DOCUMENT_ROOT]" . "/intech/hr/people/thumbnails/$supvRow[netid].jpg")){ 
				$photo .= "$supvRow[netid].jpg\" width=\"34\" height=\"40\" border=\"0\" alt=\"Photo of $supvRow[fname] $supvRow[lname]\"></a>";
			} else {
				$photo .= "nophoto.jpg\" width=\"34\" height=\"40\" border=\"0\" alt=\"Photo not available for $supvRow[fname] $supvRow[lname]\"></a>";
			} 
			
			// Build supervisor bizcard link
			$bizcard = "<i><a href=\"/intech/hr/people/contacts/view.php?lname=$supvRow[lname]&fname=$supvRow[fname]\">$supvRow[fname] $supvRow[lname], $supvRow[title]</a></i>";
			
			echo 		'<div class="card">';
			echo		'<span class="dots"></span>';
			echo "\n	$photo";
			echo "\n	$bizcard";
			echo 		'</div>'; 
			
			// Display all orglevels
			if ($orgLevel !== 'divhead') {
				if ($numStaff > 0 ){ //&& $numStaff != $numSupv) {
					displayDirectReports ($db, $dept, $supvRow[netid], $orgLevel, $group, $subgroup);
				}
			}
		}

                if ($numStaff == $numSupv || $numStaff == 0) {
			echo '<ul>';
                }  

	} else {
		$numStaff += 1;
		// Div does not have a supervisor; put placeholder TBD in place and select org head
		if ($orgLevel == 'div') {
			echo "\n<img src=\"/intech/hr/people/thumbnails/nophoto.jpg\" width=\"34\" height=\"40\" border=\"0\" alt=\"Photo not available for TBD\"> <i>TBD</i>";	
			$query = "SELECT netid FROM allcontacts WHERE dept = 'OVP' AND supervisor > '30' AND supervisor != '40' ORDER BY supervisor DESC";
		}
		
		// Unit does not have a supervisor; put placeholder TBD in place and select div head
		if ($orgLevel == 'unit') {
			echo "\n<img src=\"/intech/hr/people/thumbnails/nophoto.jpg\" width=\"34\" height=\"40\" border=\"0\" alt=\"Photo not available for TBD\"> <i>TBD</i>";
			if(strpos($dept, '-') !== FALSE ) list($curdiv, $curunt) = explode("-", $dept);		
			$query = "SELECT netid FROM allcontacts WHERE dept = '$curdiv' AND supervisor > '30' AND supervisor != '40' ORDER BY supervisor DESC";
		}
			
		$supv = runQuery($db, $query);
		$supvRow = pg_fetch_array($supv, 0);
		$prevunt = $curunt;
		if ($numStaff > 1) {
			displayDirectReports ($db, $dept, $supvRow[netid], $orgLevel, $group, $subgroup);
		} else { // nick added else
			echo '<ul>';
		}
	}
}


function displayDirectReports ($db, $dept, $supvnetid, $orgLevel, $group, $subgroup) {
	$curdiv = '';
	$curunt = '';
	$curgrp = '';

	// Don't include unit directReports if orglevel is div
	if ($orgLevel == 'div') {
		$peopleQuery = "SELECT fname, lname, netid, dept, department, subgroup, supervisor, task_code, title, comments FROM allcontacts WHERE dept = '$dept' AND supvnetid = '$supvnetid' ORDER BY lower(department), lower(subgroup), lower(lname), lower(fname)";
	} else {
		$peopleQuery = "SELECT fname, lname, netid, dept, department, subgroup, supervisor, task_code, title, comments FROM allcontacts WHERE dept ILIKE '$dept%' AND supvnetid = '$supvnetid' ORDER BY lower(department), lower(subgroup), lower(lname), lower(fname)";
	}
	
	$peopleResult = runQuery($db, $peopleQuery);
	$numPeople = pg_numrows($peopleResult);			
	
	// Check if there are direct reports at this orglevel 
	if ($numPeople > 0) {
		echo "\n<ul>";

		while ($peopleRow = pg_fetch_array($peopleResult)) {
			if(strpos($peopleRow[dept], '-') !== FALSE ) list($curdiv , $curunt, $curgrp) = explode("-", $peopleRow[dept]); // // Get div, unit, group from div-unt-grp for UW group URL

			if ($curgrp && $curgrp !== $group) {
				if(strpos($peopleRow[dept], '-') !== FALSE ) $peopleRow[department] = trim(strstr($peopleRow[department], ' ')); // strip div-unt from full department name
				if(strpos($peopleRow[department], '-') !== FALSE ) $peopleRow[department] = substr(strstr($peopleRow[department], '- '), 2); // strip div-unt-grp from full department name
				$anchorCurgrp = strtolower($curgrp);
				echo "\n<li class=\"grouplevel\">";
				echo "\n	<p><a name=\"$anchorCurgrp\"></a><h4>$peopleRow[department]</h4><div class=\"task\">(Div-Unt-Grp: $peopleRow[dept] | UW Group: <a href=\"https://groups.uw.edu/group/uw_it_group_".strtolower($curgrp)."\">uw_it_group_".strtolower($curgrp)."</a> | Task: $peopleRow[task_code])</div></p>";
				echo "\n</li>";
			}
			
			$peopleRow[subgroup] = trim($peopleRow[subgroup]);					
			
			// Display only first occurance of subgroup/team name 
			if ($peopleRow[subgroup] && ($peopleRow[subgroup] !== $subgroup)) {
				echo "\n<li class=\"teamlevel\">";
				echo "\n	<p><h5>$peopleRow[subgroup]</h5></p>";
				echo "\n</li>";
			}

			$group = $curgrp;
			if ($peopleRow[subgroup]) $subgroup = $peopleRow[subgroup];
			
			// Check for photos and build direct reports photo link 
			$photo = "<a href=\"/intech/hr/people/contacts/view.php?lname=$peopleRow[lname]&fname=$peopleRow[fname]\"><img src=\"/intech/hr/people/thumbnails/";
			
			if ($peopleRow[netid]&&file_exists("$_SERVER[DOCUMENT_ROOT]" . "/intech/hr/people/thumbnails/$peopleRow[netid].jpg")){ 
				$photo .= "$peopleRow[netid].jpg\" width=\"34\" height=\"40\" border=\"0\" alt=\"Photo of $peopleRow[fname] $peopleRow[lname]\"></a>";
			} else {
				$photo .= "nophoto.jpg\" width=\"34\" height=\"40\" border=\"0\" alt=\"Photo not available for $peopleRow[fname] $peopleRow[lname]\"></a>";
			}
			
			echo "\n<li> <!-- direct report-->";
			echo "\n	$photo";
			
			// Build direct reports bizcard link
			$bizcard = "<a href=\"/intech/hr/people/contacts/view.php?lname=$peopleRow[lname]&fname=$peopleRow[fname]\">$peopleRow[fname] $peopleRow[lname]";
			
			// Check for supervisors to add title to orgchart; exclude admins
			if ($peopleRow[supervisor] != '0' && $peopleRow[supervisor] != '30' && $peopleRow[supervisor] != '40') {
				echo "\n	<i>$bizcard";
				
				// Check for comments (status)
				if (!$peopleRow[comments]){
					echo ", $peopleRow[title]</a></i>";
				} else {
					echo "</a></i> ($peopleRow[comments])";
				}
				displayDirectReports ($db, $dept, $peopleRow[netid], $orgLevel, $group, $subgroup);
				echo "\n<li class=\"close\">&nbsp;</li>";
				echo "\n</ul>";
			
			} else {
				echo "\n	$bizcard</a>";
				
				// Check for comments (status)
				if ($peopleRow[comments]) echo " ($peopleRow[comments])";
			}
			echo "\n</li> <!-- /direct report-->";
		}
	} else {
		if ($orgLevel == 'div' || $orgLevel == 'org') echo "\n<ul>";
	}
}
?>	

