function $(id){return document.getElementById(id)}
var body=document.body;
var input=$("input");
var topbar=$("topbar");
var txtin=$("in");
var inleft=$("inleft");
var inright=$("inright");
var output=$("output");
var phpeval=$("phpeval");
var phpevalin=$("phpevalin");
var current_pre;

var cmd_his = [];
var cmd_hisi = 0;

function XHR_Create() {
    var A=null; 
    try {  	A=new XMLHttpRequest(); } catch(e) { 
		try { A=new ActiveXObject("Msxml2.XMLHTTP"); } catch(e1) { 
			try { A=new ActiveXObject("Microsoft.XMLHTTP"); } catch(e2) { 
				A=null;
			}
		} 
    }
    return A; 
}
var the_o = {};
var http_request;
function cb1() {
	$('remoteerr').textContent ='';
	if ( http_request.readyState == 4) {
		inright.className = "";
		inright.disabled = false;
		inright.value = "Enter";
		if (http_request.status == 200 ) {
			try {
				the_o = JSON.parse( http_request.responseText );
				setleft(the_o.left);
				$('cwd').textContent=the_o.cwd;
				if (the_o.output!=null && the_o.output.length>0) {
					print(the_o.output);
				} else {
					current_pre.textContent += the_o.superaddition;
				}
			}catch(ex){
				$('remoteerr').textContent = "ERROR:PHP \n" + http_request.responseText;
			}
		}
	}
};
function send(){
	var cmd=txtin.value;
	if (cmd_his[cmd_his.length-1]!=cmd) cmd_his.push(cmd);
	cmd_hisi = cmd_his.length;
	txtin.value='';
	if (cmd=='cls') {
		output.innerHTML = '';
		input.className = "";
		$("spacer").style.height = '0px';
	} else {
		print(inleft.textContent + cmd);
		http_request = XHR_Create();
		http_request.open("POST","?execute", true);
		http_request.onreadystatechange = cb1;
		http_request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		http_request.send("cwd=" + encodeURIComponent($('cwd').textContent) + "&cmd=" + encodeURIComponent(cmd));
		inright.className = "waiting";
		inright.disabled = true;
		inright.value = "Working...";
	}
}
function setleft(txt){
	txtin.style.width="99%";
	inleft.textContent=txt;
	txtin.style.paddingLeft=inleft.offsetWidth+'px';
	txtin.style.width="100%";
}
function posfix(){
	if (input.className != "fixedBottom")  {
		if ((input.offsetTop+input.offsetHeight)>document.body.clientHeight) {
			input.className = "fixedBottom";
			$("spacer").style.height = input.offsetHeight+'px';
		}
	} else {
		if (($("spacer").offsetTop+input.offsetHeight)<document.body.clientHeight) {
			input.className = "";
			$("spacer").style.height = '0px';
		}
	}
}
window.addEventListener("resize",posfix,false);
function print(txt){
	x = document.createElement("pre");
	x.textContent=txt;
	output.appendChild(x);
	current_pre = x;
	window.scrollBy(0,x.offsetHeight);
	posfix();
	window.scrollBy(0,x.offsetHeight);
	txtin.focus();
}

function keydown1(e){
	if (e.keyCode==38) { //UP
		if (cmd_hisi>0) cmd_hisi--;
		txtin.value = cmd_his[cmd_hisi];
		return false;
	} else if (e.keyCode==40) { //down
		if (cmd_hisi<(cmd_his.length-1)) cmd_hisi++;
		txtin.value = cmd_his[cmd_hisi];
		return false;
	} else {
		cmd_hisi = cmd_his.length;
		return true;
	}
}
function phpevalshow(){phpeval.style.display='';phpevalin.value='';phpevalin.focus()}
function phpevalhide(){phpeval.style.display='none';}
function phpevalrun(){
	print(" ");
    print(inleft.textContent + "Execute PHP Script!\n<?php \n"+phpevalin.value+"\n?>");
    http_request = XHR_Create();
    http_request.open("POST","?execute", true);
    http_request.onreadystatechange = cb1;
    http_request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    http_request.send("cmd=_SPECIAL_RUNPHP&cwd=" + encodeURIComponent($('cwd').textContent) + "&php=" + encodeURIComponent(phpevalin.value));
    inright.className = "waiting";
    inright.disabled = true;
    inright.value = "Working...";
}
function prockill(){
    print(inleft.textContent + "_SPECIAL_KILL_CURRENT_PROC");
    http_request = XHR_Create();
    http_request.open("POST","?execute", true);
    http_request.onreadystatechange = cb1;
    http_request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    http_request.send("cmd=_SPECIAL_KILL_CURRENT_PROC&cwd=" + encodeURIComponent($('cwd').textContent));
    inright.className = "waiting";
    inright.disabled = true;
    inright.value = "Killing...";
}
txtin.style.paddingRight=inright.offsetWidth+'px';
setleft('>>');
phpevalhide();
print('Loaded System!');