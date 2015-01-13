<?php

function getMostRecent($arr)
{
  $max=$arr[0]->created_time;
  $n=0;
  $cnt=count($arr);
  for ($i=1; $i<$cnt; $i++)
  {  
    if ($max<$arr[$i]->created_time)
    { 
      $max=$arr[$i]->created_time;
	  $n=$i;
	}
  }
  return $n;
}

function getPosters($arr)
{
  $cnt=count($arr);
  $names=array();
  $namecnt=0;
  for ($i=0; $i<$cnt; $i++) 
  {
    $nameflag=false;
    $buf=$arr[$i]->from->name;
    for ($j=0; $j<$namecnt; $j++)
      if ($buf==$names[$j])
	  {
	    $nameflag=true;
	    break;
	  }
    if ($nameflag==false)
    {
      $names[$namecnt]=$buf;
	  $namecnt++;
    }
  }
  return $names;  
}

//Replace with actual path to Facebook SDK
set_include_path ("Composer\\files\\facebook\php-sdk-v4\\facebook-facebook-php-sdk-v4-e2dc662");
include "autoload.php";
use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\GraphUser;
use Facebook\GraphObject;

error_reporting(E_ALL);
session_start();
FacebookSession::setDefaultApplication('777065655684035', '3648579cf4a413d1dfe490304456cd4c');
$session = new FacebookSession($_SESSION["token"]);
$request = new FacebookRequest($session, 'GET',
  "/".$_SESSION["group"]."/feed?since=".$_SESSION["fromdate"]."&until=".$_SESSION["todate"]);
try{$response = $request->execute();}
catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
$graphObject = $response->getGraphObject(GraphUser::className());
$outcome=$graphObject->getProperty('data');
$temp=[];
while ($outcome) 
{ 
  $temp=array_merge($temp,$graphObject->getProperty('data')->asArray());
  $next=$graphObject->getProperty('paging')->asArray();
  $request = new FacebookRequest(  $session,  'GET', substr($next["next"],31));
  $response=$request->execute();
  $graphObject = $response->getGraphObject(GraphUser::className());
  $outcome=$graphObject->getProperty('data');
  $j=count($temp);
  for ($i=$j-1; $i>=0; $i--)
    if ($temp[$i]->created_time<=$_SESSION["fromdate"])
	{
      $outcome=false;
	  $j=$i;
	}
   if ($outcome==false) $temp=array_slice($temp,0,$j);	
}
if ($_SESSION["members"]!="Everyone")
{
  $j=0;
  for ($i=0; $i<count($temp); $i++)
    if ($temp[$i]->from->name==$_SESSION["members"])
	{
	  $temp[$j]=$temp[$i];
	  $j++;
	}
  $temp=array_slice($temp,0,$j);
}
$cnt=count($temp);
$truecount=min($_SESSION["count"],$cnt);
$m=getMostRecent($temp);
echo "<i>Most Recently Created Post by:</i> <b>" .htmlentities($temp[$m]->from->name). "</b>";
echo "<i><br><br>Most Recent Post Created time:</i> <b>".date_format(date_create_from_format('Y-m-d\TH:i:sO', $temp[$m]->created_time), 'r'). "</b>";
echo "<i><br><br>Total Number of Posts:</i> <b> ".$cnt. "</b>";
echo "<i><br><br> Last ".$truecount." Posts: <br><br></i>";
//To get the max creation time and sorting the creation times in an array
$arr=array(array());
for ($i=0; $i<$cnt; $i++)
  {
    $arr[$i][0]=$temp[$i]->created_time;
	$arr[$i][1]=$i;
  }
sort($arr);
//To get the number of last posts that user wants to see
for ($k=$truecount-1; $k>=0; $k--)
{
  echo ($truecount-$k).". ".date_format(date_create_from_format('Y-m-d\TH:i:sO', $arr[$k+$cnt-$truecount][0]), 'r')."<br>" ;
  echo htmlentities($temp[$arr[$k+$cnt-$truecount][1]]->message)." ";
  if (property_exists ($temp[$arr[$k+$cnt-$truecount][1]], "likes"))
    echo "<b>(".count($temp[$arr[$k+$cnt-$truecount][1]]->likes->data)." <img src='like.png'>, ";
  else echo "<b>(0 <img src='like.png'>, ";
  if (property_exists ($temp[$arr[$k+$cnt-$truecount][1]], "comments"))
  {
    echo count($temp[$arr[$k+$cnt-$truecount][1]]->comments->data)." <img src='comment.png'>: ";
	foreach ($temp[$arr[$k+$cnt-$truecount][1]]->comments->data as $i)
	  echo htmlentities($i->from->name).", ";
	echo ")</b><br>";
  }
  else echo "0 <img src='comment.png'>)</b><br>";
  
}
$p=getPosters($temp);
$namecount=count($p);
echo "<i><br><br>Unique Users:</i> <b> ".$namecount." <br>(";
for ($i=0; $i<$namecount; $i++)
  if ($i==$namecount-1)
  {
   echo htmlentities($p[$i]).")</b>";
  }
  else
  {
    echo htmlentities($p[$i]).", ";
  }
//echo "<br/>Entire Feed Content <br/>";
//var_dump($temp);
?>