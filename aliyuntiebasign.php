<?php
function handler($event, $context) {
    $logger = $GLOBALS['fcLogger'];
    $logger->info($event);
    // $logger->info($context);
    // print_r($event);print_r($context);
    $data = json_decode($event,true);
    $bduss=$data['payload'];

    /*定义变量*/
    $tieba_header = array(
            'Content-Type: application/x-www-form-urlencoded',
            'Charset: UTF-8',
            'net: 3',
            'User-Agent: bdtb for Android 8.4.0.1',
            'Connection: Keep-Alive',
            'Accept-Encoding: gzip',
            'Host: c.tieba.baidu.com',
            );
    $firefox_header = array(
            'Host: tieba.baidu.com',
            'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:50.0) Firefox/50.0',
            'Accept: */*',
            'Accept-Language: zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Referer: http://tieba.baidu.com/',
            'Connection: keep-alive',
        );


	$logger->info('hello world');
    $tbs = gettbs($bduss,$firefox_header);
    if ($tbs == false){
        return '登录失败';
    }
    $log = signtb($bduss,$tieba_header,$tbs);
	$logger->info($log);
    return $log;
}

function xCurl($url,$cookie=null,$postdata=null,$header=array()) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	if (!is_null($postdata)) curl_setopt($ch, CURLOPT_POSTFIELDS,$postdata);
	if (!is_null($cookie)) curl_setopt($ch, CURLOPT_COOKIE,$cookie);
	if (!empty($header)) curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 50);
	$re = curl_exec($ch);
	curl_close($ch);
	return $re;
}
function gettbs($bduss,$firefox_header){
    
    $logger = $GLOBALS['fcLogger'];
    $re=json_decode(xCurl('http://tieba.baidu.com/dc/common/tbs','BDUSS=' . $bduss,null,$firefox_header),true);
    if (! $re['is_login']) {
        $log = '<a>登录失败，点此</a><a href="'.substr($_SERVER['PHP_SELF'],strrpos($_SERVER['PHP_SELF'],'/')+1).'">返回</a>';
        $logger->info($log);
        return false;
    }
    $tbs = $re['tbs'];
    return $tbs;
}

function signtb($bduss,$tieba_header,$tbs){
    $logger = $GLOBALS['fcLogger'];
    $postdata = array ('BDUSS='.$bduss,'tbs=' . $tbs);
    $postdata = implode('&', $postdata).'&sign='.md5(implode('', $postdata).'tiebaclient!!!');
    $log="";
    for ($pageno = 1; 1 ; $pageno ++) {
        $postdata='BDUSS='.urlencode($bduss).'&_client_version=8.1.0.4'.'&page_no=' . $pageno.'&page_size=100'.'&sign='.md5('BDUSS='.$bduss.'_client_version=8.1.0.4'.'page_no='.$pageno.'page_size=100'.'tiebaclient!!!');
        $re = json_decode(gzdecode(xCurl('http://c.tieba.baidu.com/c/f/forum/like','ca=open',$postdata,$tieba_header)),true);
        foreach ($re['forum_list']['non-gconforum'] as $list) {
            $log .= '尝试签到“' . $list['name'].'吧”:';

            $re_o = json_decode(gzdecode(xCurl('http://c.tieba.baidu.com/c/c/forum/sign','ca=open','BDUSS='.urlencode($bduss).'&fid='.$list['id'].'&kw='.urlencode($list['name']).'&sign='.md5('BDUSS='.$bduss.'fid='.$list['id'].'kw='.$list['name'].'tbs='.$tbs.'tiebaclient!!!').'&tbs='.$tbs,$tieba_header)),true);
            if ($re_o['error_code'] == '0'){
                            $log .= '签到完成，经验值加' . $re_o['user_info']['sign_bonus_point'] . '，你是今天第' . $re_o['user_info']['user_sign_rank'] . '个签到的。'.PHP_EOL; 

                            }else{
                            $log .= $re_o['error_msg'] . PHP_EOL;

                            }
        }
        
        if ($re['has_more'] == '0')
                    break;
    }
    return $log;
}
?>
