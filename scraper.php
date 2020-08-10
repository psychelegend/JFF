<?php
//0 is the CVRNumber Page
//1 is the Pnumber Page
//2 is the Rest
$existCount=0;
function getId($shNumm,$rw){
	if($shNumm<=1){
		return trim($rw[0]);
	}
	return trim($rw[4]);
}
function rJunk($strr){
	return str_replace("'","",$strr);
}

function getFormattedCol($shNumm,$rw,$catId){
	$value="";
	if($shNumm==0){
		if(empty(trim($rw[0])) || !empty(trim($rw[2])))
			return $value;
		$value="('".rJunk($rw[0])."'";//cvrNumber
		$value.=",'".rJunk($rw[3])."'";//name
		$value.=",'".rJunk($rw[4])."'";//address
		$value.=",'".rJunk($rw[5])."'";//postal
		$value.=",'".rJunk($rw[6])."'";//byname
		$value.=",'".rJunk($rw[3])."'";//companyForm
		$value.=",'".rJunk($rw[9])."'";//telephone
		$value.=",'".rJunk($rw[10])."'";//email
		$value.=",'0','pending','0.00','".rJunk($rw[9])."','".$catId."','Denmark','0')";
	}else if($shNumm==1){
		if(empty(trim($rw[0])) || !empty(trim($rw[2])))
			return $value;
		$value="('".rJunk($rw[0])."'";//pNumber
		$value.=",'".rJunk($rw[3])."'";//name
		$value.=",'".rJunk($rw[4])."'";//address
		$value.=",'".rJunk($rw[5])."'";//postal
		$value.=",'".rJunk($rw[6])."'";//byname
		$value.=",'".rJunk($rw[3])."'";//companyForm
		$value.=",'".rJunk($rw[8])."'";//telephone
		$value.=",'".rJunk($rw[9])."'";//email
		$value.=",'0','pending','0.00','".rJunk($rw[8])."','".$catId."','Denmark','0')";
	}else if($shNumm==2){
		if(empty(trim($rw[4])))
			return $value;
		$value="('".rJunk($rw[4])."'";//pNumber
		$value.=",'".rJunk($rw[0])."'";//name
		$value.=",'".rJunk($rw[1])."'";//address
		$value.=",'".rJunk($rw[2])."'";//postal
		$value.=",'".rJunk($rw[3])."'";//byname
		$value.=",'".rJunk($rw[0])."'";//companyForm
		$value.=",'',''";
		$value.=",'0','pending','0.00','','".$catId."','Denmark','0')";
	}
	return $value;
}

function insertToDb($shNum,$xls,$catId){
	$hasRecords=0;
	$records="";
	$cnt=0;
	$arr=[];
	include "connection.php";
	$tobeDeleted="";
	$mullai="select cvr_number from service_finder_providers";
	$durdur=mysqli_query($con, $mullai);
	foreach($durdur as $err){
			$araw[]=$err['cvr_number'];
	}
	
	for ( $i=1;$i<count($xls->rows($shNum));$i++ ) {
		if($shNum==0){
			if(!empty(trim($xls->rows($shNum)[$i][2]))){
				$tobeDeleted=$tobeDeleted."'".$xls->rows($shNum)[$i][0]."',";
			}
			if(in_array($xls->rows($shNum)[$i][0],$araw)){
				$existCount=$existCount+count($araw);
				continue;
			}					
		}
		$output=getFormattedCol($shNum,$xls->rows($shNum)[$i],$catId);
		if(empty($output))
			continue;
		array_push($arr,getId($shNum,$xls->rows($shNum)[$i]));
		$cnt+=1;
		if($hasRecords==1)
			$records.=",";
		$records.=$output;
		$hasRecords=1;
	}
	$dodo=substr($tobeDeleted,0,strlen($tobeDeleted)-1);
	//echo $dodo;
	if(!empty($dodo)){
		if(mysqli_query($con, "delete from service_finder_providers where cvr_number in (".$dodo.")")){
			echo "";
		}
	}

	$sql="insert into service_finder_providers(cvr_number,full_name,Address,zipcode,city,company_name,phone,email,avatar_id,admin_moderation,rating,mobile,category_id,Country,featured) values ".$records;
	//echo $sql;
	$wpuseridsame="update service_finder_providers set wp_user_id=id";
	mysqli_query($con, $wpuseridsame);
	if(mysqli_query($con, $sql)){
		echo "";		
		//header('Refresh: 0; url=firstpgm.php');
	} else{
		echo "";
		//echo "ERROR: Could not able to execute $sql. " . mysqli_error($con);
	}
	mysqli_close($con);
	return $arr;
}

function getCatId($catname){
	$sqq="select term_id from www_terms where name='".$catname."'";
	$ans=0;
	include "connection.php";
	$answer=mysqli_query($con, $sqq);
	foreach ($answer as $categor){
		$ans=$categor['term_id'];
	}
	mysqli_close($con);
	return $ans;
}

require_once "simple_html_dom.php";
$searchWord=$_POST["category"];
//$searchWord="homelandservice";
$urlToRequest = "https://datacvr.virk.dk/data/exportexcel?soeg=".$searchWord."&oprettet=null&ophoert=null&branche=&type=undefined&language=da";
if(file_put_contents( "scrapeData.xls",getPage($urlToRequest))) { 
	include_once "SimpleXLS.php";
	if ( $xls = SimpleXLS::parse('scrapeData.xls') ) {
		$searchRecordCount=0;
		$arr=[];
		$catId=getCatId($searchWord);
		for ($shNo=0; $shNo<count($xls->sheets)-1;$shNo++) {
			if($shNo==0 || $shNo==2){
				$arr=array_merge($arr,insertToDb($shNo,$xls,$catId));
				$searchRecordCount=$searchRecordCount+count($xls->rows($shNo))-1;
			}
		}
		print("Number of records exist : ".$existCount);
		//echo nl2br("Total search Result count : ".$searchRecordCount."\nTotal active count 		: ".count(array_unique($arr)));
		echo "Total search Result count : ".$searchRecordCount."\nTotal active count 		: ".count(array_unique($arr));
	} else {
		echo SimpleXLS::parseError();
	}
}else { 
    echo "File downloading failed."; 
} 
?>
