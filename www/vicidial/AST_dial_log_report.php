<?php 
# AST_dial_log_report.php
# 
# Copyright (C) 2014  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 130709-1346 - First build
# 130831-0927 - Changed to mysqli PHP functions
# 140108-0744 - Added webserver and hostname to report logging
# 141114-0844 - Finalized adding QXZ translation to all admin files
# 141230-1510 - Added code for on-the-fly language translations display
#
# describe vicidial_dial_log
# +-------------------+-----------------------+------+-----+---------+-------+
# | Field             | Type                  | Null | Key | Default | Extra |
# +-------------------+-----------------------+------+-----+---------+-------+
# | caller_code       | varchar(30)           | NO   | MUL | NULL    |       |
# | lead_id           | int(9) unsigned       | YES  | MUL | 0       |       |
# | server_ip         | varchar(15)           | YES  |     | NULL    |       |
# | call_date         | datetime              | YES  | MUL | NULL    |       |
# | extension         | varchar(100)          | YES  |     |         |       |
# | channel           | varchar(100)          | YES  |     |         |       |
# | context           | varchar(100)          | YES  |     |         |       |
# | timeout           | mediumint(7) unsigned | YES  |     | 0       |       |
# | outbound_cid      | varchar(100)          | YES  |     |         |       |
# | sip_hangup_cause  | smallint(4) unsigned  | YES  |     | 0       |       |
# | uniqueid          | varchar(20)           | YES  |     |         |       |
# | sip_hangup_reason | varchar(50)           | YES  |     |         |       |
# +-------------------+-----------------------+------+-----+---------+-------+
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$report_name='Dial Log Report';

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["query_date_D"]))			{$query_date_D=$_GET["query_date_D"];}
	elseif (isset($_POST["query_date_D"]))	{$query_date_D=$_POST["query_date_D"];}
if (isset($_GET["query_date_T"]))			{$query_date_T=$_GET["query_date_T"];}
	elseif (isset($_POST["query_date_T"]))	{$query_date_T=$_POST["query_date_T"];}
if (isset($_GET["server_ip"]))				{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))		{$server_ip=$_POST["server_ip"];}
if (isset($_GET["hangup_cause"]))					{$hangup_cause=$_GET["hangup_cause"];}
	elseif (isset($_POST["hangup_cause"]))			{$hangup_cause=$_POST["hangup_cause"];}
if (isset($_GET["sip_hangup_cause"]))					{$sip_hangup_cause=$_GET["sip_hangup_cause"];}
	elseif (isset($_POST["sip_hangup_cause"]))			{$sip_hangup_cause=$_POST["sip_hangup_cause"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["lower_limit"]))			{$lower_limit=$_GET["lower_limit"];}
	elseif (isset($_POST["lower_limit"]))	{$lower_limit=$_POST["lower_limit"];}
if (isset($_GET["upper_limit"]))			{$upper_limit=$_GET["upper_limit"];}
	elseif (isset($_POST["upper_limit"]))	{$upper_limit=$_POST["upper_limit"];}
if (isset($_GET["lead_id"]))			{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))	{$lead_id=$_POST["lead_id"];}
if (isset($_GET["extension"]))			{$extension=$_GET["extension"];}
	elseif (isset($_POST["extension"]))	{$extension=$_POST["extension"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}

#### SIP response code directory
$sip_response_directory = array(
	0 => "",
	100 => _QXZ("Trying"),
	180 => _QXZ("Ringing"),
	181 => _QXZ("Call is Being Forwarded"),
	182 => _QXZ("Queued"),
	183 => _QXZ("Session in Progress"),
	199 => _QXZ("Early Dialog Terminated"),
	200 => _QXZ("OK"),
	202 => _QXZ("Accepted"),
	204 => _QXZ("No Notification"),
	300 => _QXZ("Multiple Choices"),
	301 => _QXZ("Moved Permanently"),
	301 => _QXZ("Moved Temporarily"),
	302 => _QXZ("Moved Temporarily"),
	305 => _QXZ("Use Proxy"),
	380 => _QXZ("Alternative Service"),
	400 => _QXZ("Bad Request"),
	401 => _QXZ("Unauthorized"),
	402 => _QXZ("Payment Required"),
	403 => _QXZ("Forbidden"),
	404 => _QXZ("Not Found"),
	405 => _QXZ("Method Not Allowed"),
	406 => _QXZ("Not Acceptable"),
	407 => _QXZ("Proxy Authentication Required"),
	408 => _QXZ("Request Timeout"),
	409 => _QXZ("Conflict"),
	410 => _QXZ("Gone"),
	411 => _QXZ("Length Required"),
	412 => _QXZ("Conditional Request Failed"),
	413 => _QXZ("Request Entity Too Large"),
	414 => _QXZ("Request-URI Too Long"),
	415 => _QXZ("Unsupported Media Type"),
	416 => _QXZ("Unsupported URI Scheme"),
	417 => _QXZ("Unknown Resource-Priority"),
	420 => _QXZ("Bad Extension"),
	421 => _QXZ("Extension Required"),
	422 => _QXZ("Session Interval Too Small"),
	423 => _QXZ("Interval Too Brief"),
	424 => _QXZ("Bad Location Information"),
	428 => _QXZ("Use Identity Header"),
	429 => _QXZ("Provide Referrer Identity"),
	430 => _QXZ("Flow Failed"),
	433 => _QXZ("Anonymity Disallowed"),
	436 => _QXZ("Bad Identity-Info"),
	437 => _QXZ("Unsupported Certificate"),
	438 => _QXZ("Invalid Identity Header"),
	439 => _QXZ("First Hop Lacks Outbound Support"),
	470 => _QXZ("Consent Needed"),
	480 => _QXZ("Temporarily Unavailable"),
	481 => _QXZ("Call/Transaction Does Not Exist"),
	482 => _QXZ("Loop Detected."),
	483 => _QXZ("Too Many Hops"),
	484 => _QXZ("Address Incomplete"),
	485 => _QXZ("Ambiguous"),
	486 => _QXZ("Busy Here"),
	487 => _QXZ("Request Terminated"),
	488 => _QXZ("Not Acceptable Here"),
	489 => _QXZ("Bad Event"),
	491 => _QXZ("Request Pending"),
	493 => _QXZ("Undecipherable"),
	494 => _QXZ("Security Agreement Required"),
	500 => _QXZ("Server Internal Error"),
	501 => _QXZ("Not Implemented"),
	502 => _QXZ("Bad Gateway"),
	503 => _QXZ("Service Unavailable"),
	504 => _QXZ("Server Time-out"),
	505 => _QXZ("Version Not Supported"),
	513 => _QXZ("Message Too Large"),
	580 => _QXZ("Precondition Failure"),
	600 => _QXZ("Busy Everywhere"),
	603 => _QXZ("Decline"),
	604 => _QXZ("Does Not Exist Anywhere"),
	606 => _QXZ("Not Acceptable"),
);

$master_sip_response_directory=array();
$master_sip_response_verbiage_directory=array();
$i=0;
while (list($key, $val)=each($sip_response_directory)) 
	{
	$master_sip_response_directory[$i]=$key;
	$master_sip_response_verbiage_directory[$i]=$val;
	$i++;
	}
$sip_responses_to_print=count($master_sip_response_directory);

$NOW_DATE = date("Y-m-d");

if (strlen($query_date_D) < 6) {$query_date_D = "00:00:00";}
if (strlen($query_date_T) < 6) {$query_date_T = "23:59:59";}
if (!isset($query_date)) {$query_date = $NOW_DATE;}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$outbound_autodial_active =		$row[1];
	$slave_db_server =				$row[2];
	$reports_use_slave_db =			$row[3];
	$SSenable_languages =			$row[4];
	$SSlanguage_method =			$row[5];
	}
##### END SETTINGS LOOKUP #####
###########################################

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	}
else
	{
	$PHP_AUTH_PW = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_PW);
	$PHP_AUTH_USER = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_USER);
	}

$NOW_DATE = date("Y-m-d");

$stmt="SELECT selected_language from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	}

$auth=0;
$reports_auth=0;
$admin_auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'REPORTS',1);
if ($auth_message == 'GOOD')
	{$auth=1;}

if ($auth > 0)
	{
	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 7;";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$admin_auth=$row[0];

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 6 and view_reports > 0;";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$reports_auth=$row[0];

	if ($reports_auth < 1)
		{
		$VDdisplayMESSAGE = _QXZ("You are not allowed to view reports");
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	if ( ($reports_auth > 0) and ($admin_auth < 1) )
		{
		$ADD=999999;
		$reports_only_user=1;
		}
	}
else
	{
	$VDdisplayMESSAGE = _QXZ("Login incorrect, please try again");
	if ($auth_message == 'LOCK')
		{
		$VDdisplayMESSAGE = _QXZ("Too many login attempts, try again in 15 minutes");
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
	Header("HTTP/1.0 401 Unauthorized");
	echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$PHP_AUTH_PW|$auth_message|\n";
	exit;
	}


##### BEGIN log visit to the vicidial_report_log table #####
$LOGip = getenv("REMOTE_ADDR");
$LOGbrowser = getenv("HTTP_USER_AGENT");
$LOGscript_name = getenv("SCRIPT_NAME");
$LOGserver_name = getenv("SERVER_NAME");
$LOGserver_port = getenv("SERVER_PORT");
$LOGrequest_uri = getenv("REQUEST_URI");
$LOGhttp_referer = getenv("HTTP_REFERER");
if (preg_match("/443/i",$LOGserver_port)) {$HTTPprotocol = 'https://';}
  else {$HTTPprotocol = 'http://';}
if (($LOGserver_port == '80') or ($LOGserver_port == '443') ) {$LOGserver_port='';}
else {$LOGserver_port = ":$LOGserver_port";}
$LOGfull_url = "$HTTPprotocol$LOGserver_name$LOGserver_port$LOGrequest_uri";

$LOGhostname = php_uname('n');
if (strlen($LOGhostname)<1) {$LOGhostname='X';}
if (strlen($LOGserver_name)<1) {$LOGserver_name='X';}

$stmt="SELECT webserver_id FROM vicidial_webservers where webserver='$LOGserver_name' and hostname='$LOGhostname' LIMIT 1;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$webserver_id_ct = mysqli_num_rows($rslt);
if ($webserver_id_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$webserver_id = $row[0];
	}
else
	{
	##### insert webserver entry
	$stmt="INSERT INTO vicidial_webservers (webserver,hostname) values('$LOGserver_name','$LOGhostname');";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$affected_rows = mysqli_affected_rows($link);
	$webserver_id = mysqli_insert_id($link);
	}

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$query_date, $end_date, $lower_limit, $upper_limit, $file_download, $report_display_type|', url='$LOGfull_url', webserver='$webserver_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$report_log_id = mysqli_insert_id($link);
##### END log visit to the vicidial_report_log table #####


if ( (strlen($slave_db_server)>5) and (preg_match("/$report_name/",$reports_use_slave_db)) )
	{
	mysqli_close($link);
	$use_slave_server=1;
	$db_source = 'S';
	require("dbconnect_mysqli.php");
	$MAIN.="<!-- Using slave server $slave_db_server $db_source -->\n";
	}

$server_ip_string='|';
$server_ip_ct = count($server_ip);
$i=0;
while($i < $server_ip_ct)
	{
	$server_ip_string .= "$server_ip[$i]|";
	$i++;
	}

$server_stmt="select server_ip,server_description from servers where active_asterisk_server='Y' order by server_ip asc;";
$server_rslt=mysql_to_mysqli($server_stmt, $link);
$servers_to_print=mysqli_num_rows($server_rslt);
$i=0;
while ($i < $servers_to_print)
	{
	$row=mysqli_fetch_row($server_rslt);
	$LISTserverIPs[$i] =		$row[0];
	$LISTserver_names[$i] =	$row[1];
	if (preg_match('/\-ALL/',$server_ip_string) )
		{
		$server_ip[$i] = $LISTserverIPs[$i];
		}
	$i++;
	}

$i=0;
$server_ips_string='|';
$server_ip_ct = count($server_ip);
while($i < $server_ip_ct)
	{
	if ( (strlen($server_ip[$i]) > 0) and (preg_match("/\|$server_ip[$i]\|/",$server_ip_string)) )
		{
		$server_ips_string .= "$server_ip[$i]|";
		$server_ip_SQL .= "'$server_ip[$i]',";
		$server_ipQS .= "&server_ip[]=$server_ip[$i]";
		}
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$server_ip_string) ) or ($server_ip_ct < 1) )
	{
	$server_ip_SQL = "";
	$server_rpt_string="- "._QXZ("ALL servers")." ";
	if (preg_match('/\-\-ALL\-\-/',$server_ip_string)) {$server_ipQS="&server_ip[]=--ALL--";}
	}
else
	{
	$server_ip_SQL = preg_replace('/,$/i', '',$server_ip_SQL);
	$server_ip_SQL = "and server_ip IN($server_ip_SQL)";
	$server_rpt_string="- "._QXZ("server(s)")." ".preg_replace('/\|/', ", ", substr($server_ip_string, 1, -1));
	}
if (strlen($server_ip_SQL)<3) {$server_ip_SQL="";}

$sip_hangup_cause_string='|';
$sip_hangup_cause_ct = count($sip_hangup_cause);
$i=0;
while($i < $sip_hangup_cause_ct)
	{
	$sip_hangup_cause_string .= "$sip_hangup_cause[$i]|";
	$i++;
	}

$sip_hangup_causes_string='|';

$i=0; 
$sip_hangup_cause_SQL="";
while($i < $sip_hangup_cause_ct)
	{
	if ( (strlen($sip_hangup_cause[$i]) > 0) and (preg_match("/\|$sip_hangup_cause[$i]\|/",$sip_hangup_cause_string)) ) 
		{
		$sip_hangup_causes_string .= "$sip_hangup_cause[$i]|";
		$sip_hangup_causeQS .= "&sip_hangup_cause[]=$sip_hangup_cause[$i]";
		$sip_hangup_cause_SQL.="$sip_hangup_cause[$i],";
		}
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$sip_hangup_cause_string) ) or ($sip_hangup_cause_ct < 1) )
	{
	$HC_rpt_string="- "._QXZ("ALL SIP hangup causes")." ";
	if (preg_match('/\-\-ALL\-\-/',$sip_hangup_cause_string)) 
		{
		$sip_hangup_causeQS="&sip_hangup_cause[]=--ALL--";
		$sip_hangup_cause_SQL="";
		}
	}
else
	{
	$sip_hangup_causes_string=preg_replace('/\!/', "-", $sip_hangup_causes_string);
	$HC_rpt_string=_QXZ("AND SIP hangup cause(s)")." ".preg_replace('/\|/', ", ", substr($sip_hangup_causes_string, 1, -1));
	}
$sip_hangup_cause_SQL = preg_replace('/,$/i', '',$sip_hangup_cause_SQL);
if (strlen($sip_hangup_cause_SQL)>0) {$sip_hangup_cause_SQL="and sip_hangup_cause in ($sip_hangup_cause_SQL)";}

$HEADER.="<HTML>\n";
$HEADER.="<HEAD>\n";
$HEADER.="<STYLE type=\"text/css\">\n";
$HEADER.="<!--\n";
$HEADER.="   .green {color: white; background-color: green}\n";
$HEADER.="   .red {color: white; background-color: red}\n";
$HEADER.="   .blue {color: white; background-color: blue}\n";
$HEADER.="   .purple {color: white; background-color: purple}\n";
$HEADER.="-->\n";
$HEADER.=" </STYLE>\n";
$HEADER.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HEADER.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HEADER.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
$HEADER.="<link rel=\"stylesheet\" href=\"verticalbargraph.css\">\n";
$HEADER.="<script language=\"JavaScript\" src=\"wz_jsgraphics.js\"></script>\n";
$HEADER.="<script language=\"JavaScript\" src=\"line.js\"></script>\n";
$HEADER.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HEADER.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

$short_header=1;

$MAIN.="<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";
$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$MAIN.="<TABLE BORDER=0 cellspacing=5 cellpadding=5><TR><TD VALIGN=TOP align=center>\n";
$MAIN.="<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">\n";
$MAIN.=_QXZ("Date").":\n";
$MAIN.="<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";
$MAIN.="<script language=\"JavaScript\">\n";
$MAIN.="var o_cal = new tcal ({\n";
$MAIN.="	// form name\n";
$MAIN.="	'formname': 'vicidial_report',\n";
$MAIN.="	// input name\n";
$MAIN.="	'controlname': 'query_date'\n";
$MAIN.="});\n";
$MAIN.="o_cal.a_tpl.yearscroll = false;\n";
$MAIN.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$MAIN.="</script>\n";

$MAIN.="<BR><BR><INPUT TYPE=TEXT NAME=query_date_D SIZE=9 MAXLENGTH=8 VALUE=\"$query_date_D\">";

$MAIN.="<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=query_date_T SIZE=9 MAXLENGTH=8 VALUE=\"$query_date_T\">";

$MAIN.="</TD><TD ROWSPAN=2 VALIGN=TOP>"._QXZ("Server IP").":<BR/>\n";
$MAIN.="<SELECT SIZE=5 NAME=server_ip[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$server_ip_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL SERVERS")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL SERVERS")." --</option>\n";}
$o=0;
while ($servers_to_print > $o)
	{
	if (preg_match("/\|$LISTserverIPs[$o]\|/",$server_ip_string)) 
		{$MAIN.="<option selected value=\"$LISTserverIPs[$o]\">$LISTserverIPs[$o] - $LISTserver_names[$o]</option>\n";}
	else
		{$MAIN.="<option value=\"$LISTserverIPs[$o]\">$LISTserverIPs[$o] - $LISTserver_names[$o]</option>\n";}
	$o++;
	}
$MAIN.="</SELECT></TD>";

$MAIN.="<TD ROWSPAN=2 VALIGN=top align=center>"._QXZ("SIP Response").":<BR/>";
$MAIN.="<SELECT SIZE=5 NAME=sip_hangup_cause[] multiple>\n";
if  (preg_match('/--ALL--/',$sip_hangup_causes_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL SIP CAUSES")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL SIP CAUSES")." --</option>\n";}

$o=0;
while ($sip_responses_to_print > $o)
	{
	if (preg_match("/\|$master_sip_response_directory[$o]\|/",$sip_hangup_causes_string)) 
		{$MAIN.="<option selected value=\"$master_sip_response_directory[$o]\">$master_sip_response_directory[$o] - $master_sip_response_verbiage_directory[$o]</option>\n";}
	else
		{$MAIN.="<option value=\"$master_sip_response_directory[$o]\">$master_sip_response_directory[$o] - $master_sip_response_verbiage_directory[$o]</option>\n";}
	$o++;
	}
$MAIN.="</SELECT>";
$MAIN.="</TD>";

$MAIN.="<TD ROWSPAN=2 VALIGN=middle align=center>\n";
$MAIN.="<INPUT TYPE=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'><BR/><BR/>\n";
$MAIN.="</TD></TR></TABLE>\n";
$MAIN.="<PRE><font size=2>\n";

if ($SUBMIT && $query_date) {
		$stmt="SELECT * From vicidial_dial_log where call_date>='$query_date $query_date_D' and call_date<='$query_date $query_date_T' $server_ip_SQL $sip_hangup_cause_SQL order by call_date asc";
		$rslt=mysql_to_mysqli($stmt, $link);

		if (!$lower_limit) {$lower_limit=1;}
		if ($lower_limit+999>=mysqli_num_rows($rslt)) {$upper_limit=($lower_limit+mysqli_num_rows($rslt)%1000)-1;} else {$upper_limit=$lower_limit+999;}
		$MAIN.="--- "._QXZ("DIAL LOG RECORDS FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string, $HC_rpt_string\n --- "._QXZ("RECORDS")." #$lower_limit-$upper_limit               <a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$sip_hangup_causeQS&lower_limit=$lower_limit&upper_limit=$upper_limit&file_download=1\">["._QXZ("DOWNLOAD")."]</a>\n";
		$CSV_text="\""._QXZ("CALLER CODE")."\",\""._QXZ("LEAD ID")."\",\""._QXZ("SERVER IP")."\",\""._QXZ("CALL DATE")."\",\""._QXZ("EXTENSION")."\",\""._QXZ("CHANNEL")."\",\""._QXZ("CONTEXT")."\",\""._QXZ("TIMEOUT")."\",\""._QXZ("OUTBOUND CID")."\",\""._QXZ("SIP HANGUP CAUSE")."\",\""._QXZ("UNIQUE ID")."\",\""._QXZ("SIP HANGUP REASON")."\"\n";

		$dial_log_rpt ="+----------------------+-----------+-----------------+---------------------+----------------------+----------------------------------------------------+----------------------+---------+------------------------------------------+--------+----------------------+----------------------------------------------------+\n";
		$dial_log_rpt.="|                      |           |                 |                     |                      |                                                    |                      |         |                                          | "._QXZ("SIP",6)." |                      |                                                    |\n";
		$dial_log_rpt.="|                      |           |                 |                     |                      |                                                    |                      |         |                                          | "._QXZ("HANGUP",6)." |                      |                                                    |\n";
		$dial_log_rpt.="| "._QXZ("CALLER CODE",20)." | "._QXZ("LEAD ID",9)." | "._QXZ("SERVER IP",15)." | "._QXZ("CALL DATE",19)." | "._QXZ("EXTENSION",20)." | "._QXZ("CHANNEL",50)." | "._QXZ("CONTEXT",20)." | "._QXZ("TIMEOUT",7)." | "._QXZ("OUTBOUND CID",40)." | "._QXZ("CAUSE",6)." | "._QXZ("UNIQUE ID",20)." | "._QXZ("SIP HANGUP REASON",50)." |\n";
		$dial_log_rpt.="+----------------------+-----------+-----------------+---------------------+----------------------+----------------------------------------------------+----------------------+---------+------------------------------------------+--------+----------------------+----------------------------------------------------+\n";
		if ($DB) {$dial_log_rpt.=$stmt."\n";}

		if(mysqli_num_rows($rslt)>0) {
			$i=0;
			while($row=mysqli_fetch_array($rslt)) {
				$i++;
				if (strlen($row["extension"])>20) {$row["extension"]=substr($row["extension"], 0, 17)."...";}
				if (strlen($row["caller_code"])>20) {$row["caller_code"]=substr($row["caller_code"], 0, 17)."...";}
				if (strlen($row["context"])>20) {$row["context"]=substr($row["context"], 0, 17)."...";}
				if (strlen($row["outbound_cid"])>40) {$row["outbound_cid"]=substr($row["outbound_cid"], 0, 37)."...";}
				if ($i>=$lower_limit && $i<=$upper_limit) {
					if (strlen($row["channel"])>50) {
						$dial_log_rpt.="| ";
						$dial_log_rpt.=sprintf("%-20s",substr($row["caller_code"], 0, 20))." | ";
						$dial_log_rpt.=sprintf("%-9s",$row["lead_id"])." | ";
						$dial_log_rpt.=sprintf("%-15s",$row["server_ip"])." | ";
						$dial_log_rpt.=sprintf("%-19s",$row["call_date"])." | ";
						$dial_log_rpt.=sprintf("%-20s", substr($row["extension"], 0, 20))." | ";
						$dial_log_rpt.=sprintf("%-50s", substr($row["channel"], 0, 50))." | ";
						$dial_log_rpt.=sprintf("%-20s", substr($row["context"], 0, 20))." | ";
						$dial_log_rpt.=sprintf("%-7s",$row["timeout"])." | ";
						$dial_log_rpt.=sprintf("%-40s", substr($row["outbound_cid"], 0, 40))." | ";
						$dial_log_rpt.=sprintf("%-6s",$row["sip_hangup_cause"])." | ";
						$dial_log_rpt.=sprintf("%-20s",$row["uniqueid"])." | ";
						$dial_log_rpt.=sprintf("%-50s",$row["sip_hangup_reason"])." |\n";

						$dial_log_rpt.="| ";
						$dial_log_rpt.=sprintf("%-20s","")." | ";
						$dial_log_rpt.=sprintf("%-9s","")." | ";
						$dial_log_rpt.=sprintf("%-15s","")." | ";
						$dial_log_rpt.=sprintf("%-19s","")." | ";
						$dial_log_rpt.=sprintf("%-20s", "")." | ";
						$dial_log_rpt.=sprintf("%-50s", substr($row["channel"], 50))." | ";
						$dial_log_rpt.=sprintf("%-20s", "")." | ";
						$dial_log_rpt.=sprintf("%-7s","")." | ";
						$dial_log_rpt.=sprintf("%-40s", "")." | ";
						$dial_log_rpt.=sprintf("%-6s","")." | ";
						$dial_log_rpt.=sprintf("%-20s","")." | ";
						$dial_log_rpt.=sprintf("%-50s","")." |\n";
					} else {
						$dial_log_rpt.="| ";
						$dial_log_rpt.=sprintf("%-20s",substr($row["caller_code"], 0, 20))." | ";
						$dial_log_rpt.=sprintf("%-9s",$row["lead_id"])." | ";
						$dial_log_rpt.=sprintf("%-15s",$row["server_ip"])." | ";
						$dial_log_rpt.=sprintf("%-19s",$row["call_date"])." | ";
						$dial_log_rpt.=sprintf("%-20s", substr($row["extension"], 0, 20))." | ";
						$dial_log_rpt.=sprintf("%-50s", substr($row["channel"], 0, 50))." | ";
						$dial_log_rpt.=sprintf("%-20s", substr($row["context"], 0, 20))." | ";
						$dial_log_rpt.=sprintf("%-7s",$row["timeout"])." | ";
						$dial_log_rpt.=sprintf("%-40s", substr($row["outbound_cid"], 0, 40))." | ";
						$dial_log_rpt.=sprintf("%-6s",$row["sip_hangup_cause"])." | ";
						$dial_log_rpt.=sprintf("%-20s",$row["uniqueid"])." | ";
						$dial_log_rpt.=sprintf("%-50s",$row["sip_hangup_reason"])." |\n";
					}
				}
				$CSV_text.="\"$row[caller_code]\",\"$row[lead_id]\",\"$row[server_ip]\",\"$row[call_date]\",\"$row[extension]\",\"$row[channel]\",\"$row[context]\",\"$row[timeout]\",\"$row[outbound_cid]\",\"$row[sip_hangup_cause]\",\"$row[uniqueid]\",\"$row[sip_hangup_reason]\"\n";
			}
		} else {
			$dial_log_rpt.="*** "._QXZ("NO RECORDS FOUND")." ***\n";
		}
		$dial_log_rpt.="+----------------------+-----------+-----------------+---------------------+----------------------+----------------------------------------------------+----------------------+---------+------------------------------------------+--------+----------------------+----------------------------------------------------+\n";

		$dial_log_rpt_hf="";
		$ll=$lower_limit-1000;
		if ($ll>=1) {
			$dial_log_rpt_hf.="<a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$sip_hangup_causeQS&lower_limit=$ll\">[<<< "._QXZ("PREV")." 1000 "._QXZ("records")."]</a>";
		} else {
			$dial_log_rpt_hf.=sprintf("%-23s", " ");
		}
		$dial_log_rpt_hf.=sprintf("%-145s", " ");

		if (($lower_limit+1000)<mysqli_num_rows($rslt)) {
			if ($upper_limit+1000>=mysqli_num_rows($rslt)) {$max_limit=mysqli_num_rows($rslt)-$upper_limit;} else {$max_limit=1000;}
			$dial_log_rpt_hf.="<a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$sip_hangup_causeQS&lower_limit=".($lower_limit+1000)."\">["._QXZ("NEXT")." $max_limit "._QXZ("records")." >>>]</a>";
		} else {
			$dial_log_rpt_hf.=sprintf("%23s", " ");
		}
		$dial_log_rpt_hf.="\n";
		$MAIN.=$dial_log_rpt_hf.$dial_log_rpt.$dial_log_rpt_hf;
		
		$MAIN.="</PRE>\n";

}
	if ($file_download>0) {
		$FILE_TIME = date("Ymd-His");
		$CSVfilename = "AST_dial_log_report_$US$FILE_TIME.csv";
		$CSV_text=preg_replace('/ +\"/', '"', $CSV_text);
		$CSV_text=preg_replace('/\" +/', '"', $CSV_text);
		// We'll be outputting a TXT file
		header('Content-type: application/octet-stream');

		// It will be called LIST_101_20090209-121212.txt
		header("Content-Disposition: attachment; filename=\"$CSVfilename\"");
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		ob_clean();
		flush();

		echo "$CSV_text";

	} else {
		echo $HEADER;
		require("admin_header.php");
		echo $MAIN;
	}

$endMS = microtime();
$startMSary = explode(" ",$startMS);
$endMSary = explode(" ",$endMS);
$runS = ($endMSary[0] - $startMSary[0]);
$runM = ($endMSary[1] - $startMSary[1]);
$TOTALrun = ($runS + $runM);

$END_TIME=date("U");

#print "Total run time: ".($END_TIME-$START_TIME);

$stmt="UPDATE vicidial_report_log set run_time='$TOTALrun' where report_log_id='$report_log_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);

?>
