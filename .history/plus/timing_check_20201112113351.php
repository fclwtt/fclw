<?php
require_once (dirname(__FILE__) . "/../include/common.inc.php");
//require_once("../member/config.php");
define('DEDEADMIN', DEDEROOT.'/fclw'); //dede修改为你后台的文件夹名称
require_once(DEDEINC.'/userlogin.class.php');
require_once(DEDEADMIN.'/../include/common.inc.php');
require_once(DEDEINC.'/typelink.class.php');
require_once(DEDEINC.'/datalistcp.class.php');
require_once(DEDEADMIN.'/inc/inc_list_functions.php');
require_once(DEDEADMIN.'/inc/inc_batchup.php');
require_once(DEDEADMIN.'/inc/inc_archives_functions.php');
require_once(DEDEINC.'/typelink.class.php');
require_once(DEDEINC.'/arc.archives.class.php');
require_once(DEDEADMIN."/inc/inc_archives_functions.php");
require_once(DEDEINC."/arc.partview.class.php");
echo("程序开始运行……");
//$dsql1 = new DedeSql(false);
$query = "select * from `#@__check_time`";
//$dsql1->Execute('me',$query);
echo("程序开始运行2……");
$dsql->SetQuery($query);//将SQL查询语句格式化
$dsql->Execute();//执行SQL操作
$makeall=0;
echo("程序开始运行3……");
while($row1 = $dsql->GetArray())
{
    echo("程序开始运行4……");
    $now = time();
	$date = getdate($now);
	$hours = $date[hours];
  if($hours>=$row1['start_time'] and $hours<$row1['end_time'])
  {
  echo("程序开始运行5……");
   
    $interval = ($row1['end_time'] - $row1['start_time']) * 3600;
	if($now - $row1['check_time'] > $interval)
	{
	    echo("程序开始运行6……");
		$amount = $row1['amount'];
		
		$addsql = "";
		$qstr = "";
		//$dsql2 = new DedeSql(false);
		
		if($row1['lmorzs'] == 0 and $row1['typeid'] == 0 and $amount) //按照栏目更新
		{
		  //  ShowMsg("1");
		    echo("按照栏目更新开始运行……");
			$query = "select id,typeid from `#@__archives` where arcrank=-1 order by typeid,id";
			$dsql->SetQuery($query);//将SQL查询语句格式化
            $dsql->Execute();//执行SQL操作
			$typeid_now = -2;
			while($row2 = $dsql->GetArray())
			{
			  if($typeid_now !=  $row2['typeid'])
			  {
				 $typeid_now = $row2['typeid'];
				 $count = 1;
			  }
			  else $count++;
			  if($count <= $amount)
			  {
				  if($qstr == "") $qstr = $row2['id']; 
		          else $qstr = $qstr."`".$row2['id'];
			  }
			}
		}
		elseif(!$amount) //更新所有的文档
		{
		// ShowMsg("1"); 
		 echo("更新所有的文档开始运行……");
		 $makeall=1;
		}
		else //按照总数更新
		{
		    echo("按照总数更新开始运行……");
			if($row1['typeid']) $addsql = " and typeid=".$row1['typeid'];
			$query = "select id from `#@__archives` where arcrank=-1 $addsql limit 0,$amount";
			$dsql->SetQuery($query);//将SQL查询语句格式化
            $dsql->Execute();//执行SQL操作
		
			while($row2 = $dsql->GetArray())
			{
			  if(!$qstr) $qstr = $row2['id']; 
			  else $qstr = $qstr."`".$row2['id'];
			}
			
		}
		
	if($qstr != "" or $makeall)
	{
	//ShowMsg("$qstr");//测试
		//审核更新文档开始
		echo("审核更新文档开始运行……");
		$is_check = 1;
		$maxpagesize = $row1['maxpagesize'];
		$arcids = ereg_replace('[^0-9,]','',ereg_replace('`',',',$qstr));
		if($makeall){
		  $query = "Select arc.id,arc.typeid,ch.issystem,ch.maintable,ch.addtable From `#@__arctiny` arc
				left join `#@__arctype` tp on tp.id=arc.typeid
				left join `#@__channeltype` ch on ch.id=tp.channeltype where arc.arcrank=0";
				//ShowMsg("1"); 
	    }
	    else
		{
		 $query = "Select arc.id,arc.typeid,ch.issystem,ch.maintable,ch.addtable From `#@__arctiny` arc
				left join `#@__arctype` tp on tp.id=arc.typeid
				left join `#@__channeltype` ch on ch.id=tp.channeltype
				where arc.id in($arcids) ";
	    }
		$dsql->SetQuery($query);
		$dsql->Execute('ckall');
		$typediarr = array();
	
		while($row = $dsql->GetArray('ckall'))
		{
			if (!in_array($row['typeid'],$typediarr))
			{
			   array_push($typediarr,$row['typeid']);
			   $query = "Select reid,topid From `#@__arctype` where id=".$row['typeid'];
			   $row3 = $dsql->GetOne($query);
			   if (!in_array($row3['reid'],$typediarr) and $row3['reid']!=0) array_push($typediarr,$row3['reid']);
			   if (!in_array($row3['topid'],$typediarr) and $row3['topid']!=0) array_push($typediarr,$row3['topid']);
			}
			$aid = $row['id'];
			$maintable = ( trim($row['maintable'])=='' ? '#@__archives' : trim($row['maintable']) );
			$dsql->ExecuteNoneQuery("Update `#@__arctiny` set arcrank='0' where id='$aid' ");
			if($row['issystem']==-1)
			{
				$dsql->ExecuteNoneQuery("Update `".trim($row['addtable'])."` set arcrank='0' where aid='$aid' ");
			}
			else
			{
				$dsql->ExecuteNoneQuery("Update `$maintable` set arcrank='0',pubdate='$now' where id='$aid' ");
			}
			$pageurl = MakeArt($aid,false);
		}//while
		//审核更新文档结束
		echo("审核更新文档结束运行……");
		//更新主页
		echo("更新主页运行……");
		$GLOBALS['_arclistEnv'] = 'index';
		$row = $dsql->GetOne("Select * From `#@__homepageset`");
		$row['templet'] = MfTemplet($row['templet']);
		$pv = new PartView();
		$pv->SetTemplet($cfg_basedir . $cfg_templets_dir . "/" . $row['templet']);
		$pv->SaveToHtml(DEDEROOT.'/index.html');
		//更新主页
		
		$query = "Update `#@__check_time` set check_time=$now where id='".$row1['id']."' "; //更新审核时间
		echo("更新审核时间……");
	    $dsql->ExecuteNoneQuery($query);
	//ShowMsg("1"); 
		
	}//if
	

	}//if
  }//if
}//while
//更新栏目开始
echo("更新栏目开始……");
if($is_check == 1)
{
    require_once(DEDEROOT."/data/cache/inc_catalog_base.inc");
    require_once(DEDEINC."/channelunit.func.php");
	require_once(DEDEINC."/arc.listview.class.php");
	foreach($typediarr as $typeid)
	{
	    $lv = new ListView($typeid);
        //$reurl = $lv->MakeHtml();
		$reurl = $lv->MakeHtml(1,$maxpagesize);
	}

}	
//更新栏目结束
echo("更新栏目结束……");
  AjaxHead();
  
  

?>