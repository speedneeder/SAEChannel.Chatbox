<?php

$channel = new SaeChannel();
//聊天记录保存在mysql之中
$mysql = new SaeMysql();

//uid的生成和用户列表的保存直接抄钝刀的老王大大的代码
$uid = $_COOKIE['uid'];
if (empty($uid))
{
    $uid = mt_rand();
    setcookie('uid', $uid, time()+3000, '/', 'chatbox.sinaapp.com', FALSE, TRUE);
}

//用户列表保存在kvdb之中
$kv = new SaeKV();
$kv->init();
$user_list = $kv->get('ChatingUserList');

if (empty($user_list))
{
    $user_list = array();
}

$act = $_REQUEST["action"];

//创建channel
if($act == 'create_channel')
{
    $uname = $_REQUEST["username"];
    $user_list[$uid] = $uname;    
    
    // channel 名称
    $channel_name = 'chatbox.'. $uid;
    // 过期时间，默认为3600秒
    $duration = 10;
    //创建一个channel，返回一个地址
    $channel_url = $channel->createChannel($channel_name, $duration);

    $kv->set('ChatingUserList', $user_list);
    
    exit($channel_url);
}
//发送消息
else if($act == 'sendmsg')
{
    $msg = $_REQUEST["message"];
    $uname = $_REQUEST["username"];
    $msg = date('Y-m-d H:m:s') . ' <span style="color: white; background-color: blue">' . $uname . '</span> 说: ' . $msg;
    $sql = "INSERT  INTO `msg_list` (msg, uname, send_time) VALUES ('$msg', '$uname', NOW());";
    $mysql->runSql( $sql );

    
    
    // 往名为$channel_name的channel里发送一条消息
    foreach ($user_list as $uid => $uname) {
        $channel_name = 'chatbox.'.$uid;
        $send = $channel->sendMessage($channel_name, $msg);
        if ($send !== TRUE){
            unset($user_list[$uid]);
        }       
    }
    
    //向小贱鸡请求消息
    $url= 'http://xiaofengrobot.sinaapp.com/api.php?text=' . $_REQUEST["message"];
    $answer= file_get_contents($url);
    $msg = date('Y-m-d H:m:s') . ' <span style="color: white; background-color: blue">小风</span> 说: ' . $answer;
    
    // 往名为$channel_name的channel里发送一条消息
    foreach ($user_list as $uid => $uname) {
        $channel_name = 'chatbox.'.$uid;
        $send = $channel->sendMessage($channel_name, $msg);
        if ($send !== TRUE){
            unset($user_list[$uid]);
        }       
    }
    
    $mysql->closeDb();
}
//获取当前用户列表
else if($act == 'getuserlist')
{
    $tmp_user_list = array();
    $i = 0;
    foreach($user_list as $uid => $uname)
    {
        $tmp_user_list[$i] = $uname;
        $i++;
    }
    $ret = json_encode($tmp_user_list);
    exit($ret);
}
//获取最后30条聊天记录
else if($act == 'getmsglist')
{
    $sql = "SELECT * FROM `msg_list` WHERE id > (SELECT MAX(id) FROM `msg_list`) - 30 ORDER BY id;";
    $data = $mysql->getData($sql);
    if($data == null) {
        $mysql->closeDb();
        exit(0);
    }
    $i = 0;
    foreach($data as $line){
        //对"做转义处理
        $msg_sql = str_replace('"', '\"', $line['msg']);
        //因为中文会出现乱码，所以要处理
        $msg_list[$i] = urlencode($msg_sql);
        $i++;
    }
    
    $mysql->closeDb();
    $ret = urldecode(json_encode($msg_list));
    exit($ret);
}
else if($act == 'connected')
{
    $uname = $_REQUEST["username"];
    $msg = date('Y-m-d H:m:s') . ' <span style="color: white; background-color: green">' . $uname . ' 进入聊天室</span>';

    $sql = "INSERT  INTO `msg_list` (msg, uname, send_time) VALUES ('$msg', '$uname', NOW());";
    $mysql->runSql( $sql );
    $mysql->closeDb();
    //向列表之中的所有用户发送消息
    foreach ($user_list as $uid => $uname) {
        $channel_name = 'chatbox.'.$uid;
        $send = $channel->sendMessage($channel_name, $msg);
        if ($send !== TRUE){
            unset($user_list[$uid]);
        }       
    }  
    $kv->set('ChatingUserList', $user_list);    
}
else if($act == 'disconnected')
{    
    unset($user_list[$uid]);
    $uname = $_REQUEST["username"];
    $msg = date('Y-m-d H:m:s') . ' <span style="color: white; background-color: red">' . $uname . ' 退出聊天室</span>';
    $sql = "INSERT  INTO `msg_list` (msg, uname, send_time) VALUES ('$msg', '$uname', NOW());";
    $mysql->runSql( $sql );
    $mysql->closeDb();
    //向列表之中的所有用户发送消息
    foreach ($user_list as $uid => $uname) {
        $channel_name = 'chatbox.'.$uid;
        $send = $channel->sendMessage($channel_name, $msg);
        if ($send !== TRUE){
            unset($user_list[$uid]);
        }       
    } 
    $kv->set('ChatingUserList', $user_list);
}


