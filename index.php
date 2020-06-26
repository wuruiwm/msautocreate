<?php
require('common.php');
if(empty($_POST)){
	require('office.html');
	exit();
}

//echo json_encode(['stat'=>'username exists']);exit();
if($is_invitation_code){
	if(empty($_POST['invitation_code'])){
		response(1,'请输入邀请码');
	}
	$conn = mysql_conn();
	$code = $_POST['invitation_code'];
	$invitation_code = mysqli_fetch_assoc(mysqli_query($conn,"select * from invitation_code where `code`='$code'"));
	if(empty($invitation_code)){
		response(1,'邀请码不存在');
	}
	if($invitation_code['status'] != 0){
		response(1,'邀请码已被使用');
	}
}
$request = [
	'display_name'=>$_POST['display_name'],//显示名称
	'user_name'=>$_POST['user_name'],//邮箱用户名
];
$password = get_rand_string();
$token = get_ms_token($tenant_id,$client_id,$client_secret);
if(empty($token)){
	response(1,'获取token失败,请检查参数配置是否正确');
}
$email = create_user($request,$token,$_POST['domain'],$_POST['sku_id'],$password);
if($is_invitation_code){
	mysqli_query($conn,"UPDATE `invitation_code` SET `update_time` = ".time().", `status` = 1,`email`='$email' WHERE `code` = $code");
}
response(0,'申请账号成功',['email'=>$email,'password'=>$password]);