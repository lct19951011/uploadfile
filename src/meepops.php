<?php
/**
 * websocket一对一聊天
 * Created by Lane
 * User: ChaoTanLiu
 * Date: 18/12/4
 * Time: 下午14：44
 * E-mail: 981232310@qq.com
 * WebSite: https://www.cnblogs.com/yizhiqingtan/
 */

//引入MeepoPS
require_once 'MeepoPS/index.php';


//使用WebSocket协议传输的Api类
$webSocket = new \MeepoPS\Api\Websocket('0.0.0.0', '3100');
//启动的子进程数量. 通常为CPU核心数
$webSocket->childProcessCount = 1;
//设置MeepoPS实例名称
$webSocket->instanceName = 'MeepoPS-WebSocket';

//回调方法
$webSocket->callbackNewData = 'callbackNewData';
$webSocket->callbackConnectClose = 'callbackConnectClose';


//使用HTTP协议传输的Api类
// $http = new \MeepoPS\Api\Http('0.0.0.0', '3101');
// $http->setDocument('localhost:3101', './Example/Chat_Robot/Web');

//启动MeepoPS
\MeepoPS\runMeepoPS();

//收到新数据时
function callbackNewData($connect, $data)
{   
    $dbhost = 'vivioning.mysql.rds.aliyuncs.com:3225';  // mysql服务器主机地址
    $dbuser = 'root';            // mysql用户名
    $dbpass = 'ERPerp20180000';          // mysql用户名密码
    $conn = mysqli_connect($dbhost, $dbuser, $dbpass,"west_education");
    $data = json_decode($data,true);
    //需要获取到的数据
    // $data = array(
    //                 'content'=>$data,
    //                 'my_user_id'=>8,
    //                 'send_user_id'=>2,
    // );  
    $my_user_id = $data['my_user_id'];
    $send_user_id = $data['send_user_id'];
    $content = $data['content'];
    // $group_id = isset($data['group_id'])?$data['group_id']:1;
    $group_id = $data['group_id'];
    $my_message = array(
        'msg' => 'OK',   
        'data' => array(
            'content' => $content,
            'type' => 0,
            'create_time' => date('Y-m-d H:i:s'),
        ),
    );
    $get_message = array(
        'msg' => 'OK',   
        'data' => array(
            'content' => $content,
            'type' => 1,
            'create_time' => date('Y-m-d H:i:s'),
        ),
    );

    $time = time();

    if(empty($group_id)&&!empty($send_user_id)&&empty($content))
    {
        $sql = "INSERT INTO `west_education`.`w_meepops`(`status`, `web_id`, `user_id`,`create_time`) VALUES (1, $connect->id,$my_user_id,$time )";
        if(! $conn )
        {
            die('Could not connect: ' . mysqli_error());
        }
        echo '接收到消息！';
        mysqli_query($conn,$sql);
        

    }
 
    if(empty($group_id)&&!empty($send_user_id)){

        $sql = "SELECT * FROM `west_education`.`w_meepops` WHERE `user_id` = $send_user_id  AND `status` = 1  order by id desc LIMIT 0,1";
        if(! $conn )
        {
            die('Could not connect: ' . mysqli_error());
        }
        $result = mysqli_query($conn,$sql);
        $result = $result->fetch_array();
        $send_id = $result['web_id'];
    
        var_dump("发送给了web_id：".$send_id);
        foreach ($connect->instance->clientList as $client) {
            if ($send_id == $client->id) {
                $client->send(json_encode($get_message));
            }
        }
        $connect->send(json_encode($my_message));
    }

    if(!empty($group_id) && empty($content))
    {
        $sql = "INSERT INTO `west_education`.`w_meepops_group_list`(`status`, `group_id`, `user_id`,`web_id`,`create_time`) VALUES (1, $group_id,$my_user_id,$connect->id,$time )";
        if(! $conn )
        {
            die('Could not connect: ' . mysqli_error());
        }
        echo '接收到消息！';
        mysqli_query($conn,$sql);
    }

    if(!empty($group_id)&&empty($send_user_id)){
        $sql = "SELECT * FROM `west_education`.`w_meepops_group` WHERE `id` = $group_id  AND `status` = 1  order by id desc LIMIT 0,1";
        if(! $conn )
        {
            die('Could not connect: ' . mysqli_error());
        }
        mysqli_query($conn,$sql);
        $result = mysqli_query($conn,$sql);
        $result = $result->fetch_array();
        $group = $result['user_id'];
        $group = explode(',',$group);

        foreach($group as $client_id)
        {
            $sql = "SELECT * FROM `west_education`.`w_meepops_group_list` WHERE `user_id` = $client_id  AND `status` = 1 AND `group_id` =$group_id order by id desc LIMIT 0,1";
            if(! $conn )
            {
                die('Could not connect: ' . mysqli_error());
            }
            echo '接收到消息！';
            $result = mysqli_query($conn,$sql);
            $result = $result->fetch_array();
            $send_id = $result['web_id'];
            foreach ($connect->instance->clientList as $client) {
                if ($send_id == $client->id && $client->id != $connect->id) {
                    $client->send(json_encode($get_message));
                }
            }   
        }
    
        $connect->send(json_encode($my_message));
    } 

    mysqli_close($conn);
}


//链接关闭时
function callbackConnectClose($connect)
{
    $dbhost = 'vivioning.mysql.rds.aliyuncs.com:3225';  // mysql服务器主机地址
    $dbuser = 'root';            // mysql用户名
    $dbpass = 'ERPerp20180000';          // mysql用户名密码
    $conn = mysqli_connect($dbhost, $dbuser, $dbpass,"west_education");
    $sql = "UPDATE `west_education`.`w_meepops` SET `status` = 0 WHERE `web_id` = $connect->id";

    if(! $conn )
    {
        die('Could not connect: ' . mysqli_error());
    }
    echo '链接关闭';
    mysqli_query($conn,$sql);
    mysqli_close($conn);
}

