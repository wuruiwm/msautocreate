<?php
require('common.php');

if(empty($_GET['a'])){
	if(check_login()){
		header('location:./admin.php?a=invitation_code');
		exit();
	}else{
		require('login.html');
		exit();
	}
}


if($_GET['a'] == 'login'){
	$username = !empty($_POST['username']) ? $_POST['username'] : '';
	$password = !empty($_POST['password']) ? $_POST['password'] : '';
	if($username == $admin['username'] && md5($password) == $admin['password']){
		$_SESSION['token'] = md5($admin['username'] . $admin['password']);
		header('location:./admin.php?a=invitation_code');
		exit();
	}else{
		exit("登录失败");
	}
}

if($_GET['a'] == 'invitation_code'){
	if(!check_login()){
		require('login.html');
		exit();
	}
	require('invitation_code.html');
	exit();
}

if($_GET['a'] == 'invitation_code_list'){
	if(!check_login()){
		response(1,"登录已失效");
	}
	$page = !empty($_GET['page']) ? intval($_GET['page']) : 0;
	$limit = !empty($_GET['limit']) ? intval($_GET['limit']) : 0;
	$status = !empty($_GET['status']) ? intval($_GET['status']) : 0;//0全部 1已使用 2未使用
	$keyword = !empty($_GET['keyword']) ? intval($_GET['keyword']) : '';
	if($page <= 0){
		response(1,"页码必须大于0");
	}
	if($limit <= 0){
		response(1,"每页条数必须大于0");
	}
	$where = '1';
	if(!empty($status)){
		if($status == 1){
			$where .= ' and status=1';
		}
		if($status == 2){
			$where .= ' and status=0';
		}
	}
	if(!empty($keyword)){
		$where .= " and (`code` like '%$keyword%' or `email` like '%$keyword%')";
	}
	$conn = mysql_conn();
	$result = mysqli_query($conn,"select * from invitation_code where $where limit ". ($page-1)*$limit . ',' . $limit);
	$data = array();
	while($row = mysqli_fetch_assoc($result)){
		$data[] = $row;
	}
    if(!empty(implode(',',array_column($data,'email')))){
        $token = get_ms_token($tenant_id,$client_id,$client_secret);
    }
	foreach($data as $k =>$v){
		$data[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
		$data[$k]['update_time'] = date('Y-m-d H:i:s',$v['update_time']);
        if(!empty($v['email'])){
            !empty(account_status($v['email'],$token)) ?  $data[$k]['account_status'] = 0 : $data[$k]['account_status'] = -1;
        }
	}
	$count = mysqli_fetch_assoc(mysqli_query($conn,"select count(*) as `count` from invitation_code where $where"));
	response(0,"获取邀请码列表成功",$data,$count['count']);
}

if($_GET['a'] == 'invitation_code_create'){
	if(!check_login()){
		response(1,"登录已失效");
	}
	$num = !empty($_POST['num']) ? intval($_POST['num']) : 0;
	if($num <= 0){
		response(1,"数量必须大于0");
	}
	$conn = mysql_conn();
	$i = 0;
	$total = $num;
	$success = 0;
	$error = 0;
	while($i < $num){
		$code = get_rand_number($admin['invitation_code_num']);
		$time = time();
		$result = mysqli_query($conn,"INSERT INTO `invitation_code`(`code`, `create_time`, `update_time`, `status`) VALUES ('$code',$time,$time, 0)");
		if(!empty($result)){
			$success++;
		}else{
			$error++;
		}
		$i++;
	}
	$data = [
		'total'=>$total,
		'success'=>$success,
		'error'=>$error,
	];
	response(0,'生成成功',$data);
}

if($_GET['a'] == 'invitation_code_delete'){
    if(!check_login()){
        response(1,"登录已失效");
    }
    $token = get_ms_token($tenant_id,$client_id,$client_secret);
    $user_email = !empty($_POST['email']) ? $_POST['email'] : 0;
    if($user_email){
        $resultaccount = account_delete($user_email,$token);
    }else{
        $resultaccount = false;
    }
    $id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
    $conn = mysql_conn();
    $resultsql = mysqli_query($conn,"DELETE FROM `invitation_code` WHERE `id` = $id");
    if(!empty($resultsql) && $resultaccount){
        response(0,"邀请码删除成功,用户账户删除成功");
    }else if(!empty($resultsql)){
        response(0,"邀请码删除成功,无用户或无权限删除账户");
    }else{
        response(1,"删除失败");
    }
}

if($_GET['a'] == 'invitation_code_account_enable'){
    if(!check_login()){
        response(1,"登录已失效");
    }
    $token = get_ms_token($tenant_id,$client_id,$client_secret);
    $user_email = !empty($_POST['email']) ? $_POST['email'] : 0;
    $result=account_enable($user_email,$token);
    if(!empty($result)){
        response(0,$user_email."允许失败");
    }else{
        response(1,$user_email."允许成功");
    }
}

if($_GET['a'] == 'invitation_code_account_disable'){
    if(!check_login()){
        response(1,"登录已失效");
    }
    $token = get_ms_token($tenant_id,$client_id,$client_secret);
    $user_email = !empty($_POST['email']) ? $_POST['email'] : 0;
    $result=accountin_disable($user_email,$token);
    if(!empty($result)){
        response(0,$user_email."禁用失败");
    }else{
        response(1,$user_email."成功禁用");
    }
}
