<?php
require_once(dirname(__FILE__).'/config.php');
require_once(DEDEADMIN."/inc/inc_catalog_options.php");
require_once(DEDEINC."/typelink.class.php");
CheckPurview('member_Type');
if(empty($dopost))
{
	$dopost = "";
}

//保存更改
//echo("正常运行开始");
if($dopost=="save")
{
	$startID = 1;
	$endID = $idend;
	for(;$startID<=$endID;$startID++)
	{
		$query = '';
		$id = ${'ID_'.$startID};
		$start_time =   ${'stime_'.$startID};
		$end_time =    ${'etime_'.$startID};
		$amount =   ${'amount_'.$startID};
		$lmorzs = ${'lmorzs_'.$startID};
		$maxpagesize = ${'maxpagesize_'.$startID};
		$typeid = ${'typeid_'.$startID};
		if(isset(${'check_'.$startID}))
		{
			//if($start_time!='' and $end_time!='')
			//{
				$query = "update #@__check_time set  
				          start_time='$start_time',
						  end_time='$end_time',
						  amount='$amount', 
						  lmorzs='$lmorzs', 
						  maxpagesize='$maxpagesize',
						  typeid='$typeid' 
						  where id='$id'";
				$dsql->ExecuteNoneQuery($query);
			//}
		}
		else
		{
			$query = "Delete From #@__check_time where id='$id' ";
			$dsql->ExecuteNoneQuery($query);
		}
	}

	//增加新记录
	if(isset($check_new) && $stime_new!='' && $etime_new!='')
	{
		if($stime_new<0 or $stime_new>23)
		{
		  	ShowMsg('开始时间的范围为：0-23！','times_man.php',0,4000);
	        exit();
		}
		if($etime_new<0 or $etime_new>24)
		{
		  	ShowMsg('结束时间的范围为：0-23！','times_man.php',0,4000);
	        exit();
		}
		if($stime_new>=$etime_new)
		{
		  	ShowMsg('开始时间必须大于结束时间！','times_man.php',0,4000);
	        exit();
		}
		$query = "Insert Into #@__check_time(start_time,end_time,amount,check_time,lmorzs,maxpagesize,typeid) 
		          Values('{$stime_new}','{$etime_new}','{$amount_new}',0,'{$lmorzs_new}','{$maxpagesize_new}','{$typeid_new}');";
		$dsql->ExecuteNoneQuery($query);
	}
	header("Content-Type: text/html; charset={$cfg_soft_lang}");
	echo "<script> alert('成功更新点审核时间段！'); </script>";
}

require_once(DEDEADMIN."/templets/times_man.htm");
//echo("正常运行结束");
?>