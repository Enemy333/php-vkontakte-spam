/*
 
 vkontakte.ru spammer script

 author: Andrey Sergienko
 reference: http://storinka.com.ua

 version 1 from 09.05.2009

*/

<?php

$fin = fopen("accs.txt","r");
$stime = 10;
$m_subject = "message subject";
$m_message = "message body";

//

if (!$fin) { echo "error when open accts.txt file\n"; exit; };

while($fline = fgets($fin))
{
    $fline = chop($fline);
    list($login,$passw) = explode(":",$fline);

    $browser = 'Mozilla 4.0';
    $id = 0;

    $pr = false;
    $pp = 0;

    $cookies = login($login, $passw);
    if ($cookies == "-1") continue;
    $friends = getfriends($cookies);

    foreach($friends as $i => $value)
    {
        global $cookies;
        echo "spamming by " . $login . " to " . $value['name'] . " " . $value['lastname'];
        $res = pmto($value,$cookies);
        echo " ...done\n";
        sleep($stime);
    };

};

fclose($fin);

echo "\nall things done\n";

function pmto($friend,$ck)
{
    global $pr, $pp, $browser, $id, $m_message, $m_subject;
    $fid = $friend['id'];
    $name = $friend['name'];
    $lastname = $friend['lastname'];
    
    $title = $name . ", " . $m_subject . " " . rand(1,512);
    $message = rand(123,65536) . " " . $m_message . " " . rand(345,32768);
    $act = "sent";
    $misc = 1;
    $to_id = $fid;
    $toFriends = 1;
    $to_ids = $fid;
    $to_reply = 0;
    
    $ret=socket_do("vkontakte.ru","","/mail.php?act=write&to=$fid",$browser,$ck,1,"POST",'http://vkontakte.ru',1,$pr,$pp);
    $secure = preg_replace("/.*\"secure\" value=\"/ms","",$ret);
    $secure = preg_replace("/\".*/ms","",$secure);
    
    $hash = preg_replace("/.*\"chas\" value=\"/ms","",$ret);
    $hash = preg_replace("/\".*/ms","",$hash);

    $ret=socket_do("vkontakte.ru","secure={$secure}&act={$act}&misc={$misc}&chas={$hash}&to_reply={$to_reply}&to_ids={$to_ids}&to_id={$to_id}&title={$title}&message={$message}&toFriends={$toFriends}","/mail.php",$browser,$ck,1,"POST",'http://vkontakte.ru/mail.php',1,$pr,$pp);
    return $ret;
};

function getfriends($ck)
{
    global $pr, $pp, $browser, $id;
    $ret=socket_do("vkontakte.ru","","/friend.php?$id",$browser,$ck,1,"POST",'http://vkontakte.ru/login.php',1,$pr,$pp);
    $ret = preg_replace("/.*list:\[/ms","",$ret);
    $ret = preg_replace("/\]\],.*/ms","",$ret);
    $ret = explode("\n",$ret);
    $ret_arr ='';
    foreach($ret as $i => $value)
    {
	$line = preg_replace("/^\[/","",$value);
	list($f_id, $f_name, $f_last) = explode(", ",$line);
	$f_name = preg_replace("/\{f:\'/","",$f_name);
	$f_name = preg_replace("/\'/","",$f_name);
	$f_last = preg_replace("/l:\'/","",$f_last);
	$f_last = preg_replace("/'.*/","",$f_last);
	$f_arr['id'] = $f_id;
	$f_arr['name'] = $f_name;
	$f_arr['lastname'] = $f_last;
	$ret_arr[] = $f_arr;
    };
    return $ret_arr;
};

function login($acc, $pass)
{
    global $browser, $login, $passw, $pr, $pp, $id;

    $acc=urlencode($acc);
    $pass=urlencode($pass);

    $ret=socket_do("vkontakte.ru","email={$acc}&pass={$pass}","/login.php",$browser,'',1,"POST",'http://vkontakte.ru',1,$pr,$pp);

    $ret=substr($ret,0,strpos($ret,"\r\n\r\n"));

    if(strpos($ret,"Location: /id")===false) return "-1";

    preg_match('#Location: \/id(.*)#',$ret,$id);
    $id = trim($id[1]);

    preg_match_all("/Set-Cookie: ([\s\S]+); expires=/isU",$ret,$m);
    $cook="Cookie: ";
    $tcnt=0;
    foreach($m[1] as $ck)
    {
	$tcnt++;
	$cook .= $ck . "; ";
    }
    $cook .= "\r\n";

    return $cook;
};

function socket_do($host,$vars,$service_uri,$browser,$cookies='',$sread=1,$method='POST',$ref='',$addheaders=  1,$proxy=false,$proxyport=0,$HTTP='1.0')
{
  $ret="";

  if($method=='GET' && $vars)
  {
    $service_uri.='?'.$vars;
    $vars='';
  }

  $header="Host: $host\r\n";
  $header.="User-Agent: $browser\r\n";

  if($addheaders==1)
  {
    $header.="Content-Type: application/x-www-form-urlencoded\r\n";
    $header.="Content-Length: ".strlen($vars)."\r\n";
  }
  else if($addheaders==0)
  {
    $header.="Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5\r\n";
    $header.="Accept-Language: ru-ru,ru;q=0.8,en-us;q=0.5,en;q=0.3\r\n";
    $header.="Accept-Encoding: \r\n";
    $header.="Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7\r\n";
  }
  else if($addheaders==2)
  {
    $header.="Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5\r\n";
    $header.="Accept-Language: ru-ru,ru;q=0.8,en-us;q=0.5,en;q=0.3\r\n";
    $header.="Accept-Encoding: \r\n";
    $header.="Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7\r\n";
    $header.="Pragma: no-cache\r\n";
    $header.="Cache-Control: no-cache\r\n";
    $header.="Content-Length: ".strlen($vars)."\r\n";
    $header.="Content-Type: application/x-www-form-urlencoded; charset=windows-1251\r\n";
  }
  else if($addheaders==3)
  {
    $header.="Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5\r\n";
    $header.="Accept-Language: ru-ru,ru;q=0.8,en-us;q=0.5,en;q=0.3\r\n";
    $header.="Accept-Encoding: gzip,deflate\r\n";
    $header.="Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7\r\n";
  }

  if($ref) $header.="Referer: $ref\r\n";

  $header.="Connection: close\r\n";

  if($cookies)
    $header.="$cookies\r\n";

  $header.="\r\n";

  if($proxy)
  {
    $addquery="$method http://$host$service_uri  HTTP/$HTTP\r\n";
    $fp=fsockopen("tcp://".$proxy,$proxyport,$errno,$errstr,30);
  }
  else
  {
    $addquery="$method $service_uri  HTTP/$HTTP\r\n";
    $fp=fsockopen("tcp://".$host,80,$errno,$errstr,30);
  }

  stream_set_timeout($fp,30);

  if(!$fp)
  {
    print "Socket error";
    exit();
  }

  fputs($fp,$addquery);
  fputs($fp,$header.$vars);

  if($sread)
  {
    while(!feof($fp))
    {
      $ret.=fgets($fp, 128);
    }
  }

  fclose($fp);

  return $ret;
}
?> 
