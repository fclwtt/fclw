<?php
/**
 *
 * 微信登录
 *
 * @time           2017年10月8日14:45:58
 * @link           https://www.dedemao.com
 */
require_once(dirname(__FILE__)."/../include/common.inc.php");
$code = $_GET['code'];
if(!$code){
    ShowMsg('获取code失败!','-1');
    exit();
}
$res = wxGetToken($code);
if(!$res['access_token'] || !$res['openid']){
    ShowMsg('获取access_token失败!','-1');
    exit();
}
$userinfo = wxGetUserinfo($res['access_token'],$res['openid']);
if(!$userinfo['openid'] || !$userinfo['unionid']){
    ShowMsg('获取用户资料失败!','-1');
    exit();
}
$maintable = '#@__member';
$row = $dsql->GetOne("SELECT * FROM `$maintable` WHERE unionid='{$userinfo['unionid']}' ");
$time = time();
$ip = GetIP();
$userid = 'wx-'.substr($userinfo['unionid'],-4);

include_once(DEDEINC.'/memberlogin.class.php');
$cfg_ml = new MemberLogin();
$pwd = $cfg_ml->GetEncodePwd($userinfo['unionid']);
if(empty($row)){
    $sex = $userinfo['sex']==1 ? '男' : '女';
    $query = "INSERT INTO `{$maintable}`(userid,pwd,uname,sex,face,jointime,joinip,logintime,loginip,openid,unionid)  VALUES ('{$userid}','{$pwd}','{$userinfo['nickname']}','{$sex}','{$userinfo['headimgurl']}','{$time}','{$ip}','{$time}','{$ip}','{$userinfo['openid']}','{$userinfo['unionid']}')";
    $dsql->ExecuteNoneQuery($query);
}else{
    $dsql->ExecuteNoneQuery("UPDATE `$maintable` SET face = '{$userinfo['headimgurl']}',logintime='{$time}',loginip = '{$ip}' WHERE id='{$row['mid']}'");
}
//检查帐号
$rs = $cfg_ml->CheckUser($userid,$userinfo['unionid']);
if(!$wxkf_jump){
    $memberPath = DEDEMEMBER;
    $pos = strripos($memberPath,'/');
    $wxkf_jump = substr($memberPath,$pos);
}
if($rs==0)
{
    ResetVdValue();
    ShowMsg("用户名不存在！", $wxkf_jump, 0, 2000);
    exit();
}
else if($rs==-1) {
    ResetVdValue();
    ShowMsg("密码错误！", $wxkf_jump, 0, 2000);
    exit();
}
else if($rs==-2) {
    ResetVdValue();
    ShowMsg("管理员帐号不允许从前台登录！", $wxkf_jump, 0, 2000);
    exit();
}
else
{
    // 清除会员缓存
    $cfg_ml->DelCache($cfg_ml->M_ID);
    if(empty($gourl) || preg_match("#action|_do#i", $gourl))
    {
        ShowMsg("成功登录，5秒钟后转向系统主页...",$wxkf_jump,0,2000);
    }
    else
    {
        $gourl = str_replace('^','&',$gourl);
        ShowMsg("成功登录，现在转向指定页面...",$gourl,0,2000);
    }
    exit();
}
exit();

//获取微信access_token
function wxGetToken($code) {
    global $wxkf_appid,$wxkf_appsecret;
    $AppID = $wxkf_appid;//AppID(应用ID)
    $AppSecret = $wxkf_appsecret;//AppSecret(应用密钥)
    $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$AppID.'&secret='.$AppSecret.'&code='.$code.'&grant_type=authorization_code';
    $res = getCurlContents($url);
    $res = json_decode($res, true);
    return $res;
}

function wxGetUserinfo($access_token,$openid)
{
    $url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid;
    $res = getCurlContents($url);
    $res = json_decode($res, true);
    return $res;
}

function getCurlContents($url, $method ='GET', $data = array()) {
    if ($method == 'POST') {
        //使用crul模拟
        $ch = curl_init();
        //禁用https
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        //允许请求以文件流的形式返回
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch); //执行发送
        curl_close($ch);
    }else {
        if (ini_get('allow_fopen_url') == '1') {
            $result = file_get_contents($url);
        }else {
            //使用crul模拟
            $ch = curl_init();
            //允许请求以文件流的形式返回
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            //禁用https
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_URL, $url);
            $result = curl_exec($ch); //执行发送
            curl_close($ch);
        }
    }
    return $result;
}
