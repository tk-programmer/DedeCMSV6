<?php

/**
 * @version        $Id: reg_new.php 1 8:38 2010年7月9日Z tianya $
 * @package        DedeCMS.Member
 * @copyright      Copyright (c) 2020, DedeBIZ.COM
 * @license        https://www.dedebiz.com/license
 * @link           https://www.dedebiz.com
 */
require_once(dirname(__FILE__) . "/config.php");

if ($cfg_mb_allowreg == 'N') {
    ShowMsg('系统关闭了新用户注册！', 'index.php');
    exit();
}

if (!isset($dopost)) $dopost = '';
$step = empty($step) ? 1 : intval($step);

if ($step == 1) {
    if ($cfg_ml->IsLogin()) {
        ShowMsg('你已经登录系统，无需重新注册！', 'index.php');
        exit();
    }
    if ($dopost == 'regbase') {
        $svali = GetCkVdValue();
        if (preg_match("/1/", $safe_gdopen)) {
            if (strtolower($vdcode) != $svali || $svali == '') {
                ResetVdValue();
                ShowMsg('验证码错误！', '-1');
                exit();
            }
        }

        // $faqkey = isset($faqkey) && is_numeric($faqkey) ? $faqkey : 0;
        // if($safe_faq_reg == '1')
        // {
        //     if($safefaqs[$faqkey]['answer'] != $rsafeanswer || $rsafeanswer=='')
        //     {
        //         ShowMsg('验证问题答案错误', '-1');
        //         exit();
        //     }
        // }

        $userid = $uname = trim($userid);
        $pwd = trim($userpwd);
        $pwdc = trim($userpwdok);
        $rs = CheckUserID($userid, '用户名');
        if ($rs != 'ok') {
            ShowMsg($rs, '-1');
            exit();
        }
        if (strlen($userid) > 20 || strlen($uname) > 36) {
            ShowMsg('你的用户名或用户笔名过长，不允许注册！', '-1');
            exit();
        }
        if (strlen($userid) < $cfg_mb_idmin || strlen($pwd) < $cfg_mb_pwdmin) {
            ShowMsg("你的用户名或密码过短，不允许注册！", "-1");
            exit();
        }
        if ($pwdc != $pwd) {
            ShowMsg('你两次输入的密码不一致！', '-1');
            exit();
        }

        $uname = HtmlReplace($uname, 1);
        // //用户笔名重复检测
        // if($cfg_mb_wnameone=='N')
        // {
        //     $row = $dsql->GetOne("SELECT * FROM `#@__member` WHERE uname LIKE '$uname' ");
        //     if(is_array($row))
        //     {
        //         ShowMsg('用户笔名或公司名称不能重复！', '-1');
        //         exit();
        //     }
        // }
        // if(!CheckEmail($email))
        // {
        //     ShowMsg('Email格式不正确！', '-1');
        //     exit();
        // }

        // if($cfg_md_mailtest=='Y')
        // {
        //     $row = $dsql->GetOne("SELECT mid FROM `#@__member` WHERE email LIKE '$email' ");
        //     if(is_array($row))
        //     {
        //         ShowMsg('你使用的Email已经被另一帐号注册，请使其它帐号！', '-1');
        //         exit();
        //     }
        // }

        //检测用户名是否存在
        $row = $dsql->GetOne("SELECT mid FROM `#@__member` WHERE userid LIKE '$userid' ");
        if (is_array($row)) {
            ShowMsg("你指定的用户名 {$userid} 已存在，请使用别的用户名！", "-1");
            exit();
        }
        // if($safequestion==0)
        // {
        //     $safeanswer = '';
        // }
        // else
        // {
        //     if(strlen($safeanswer)>30)
        //     {
        //         ShowMsg('你的新安全问题的答案太长了，请控制在30字节以内！', '-1');
        //         exit();
        //     }
        // }

        //会员的默认金币
        $dfscores = 0;
        $dfmoney = 0;
        $dfrank = $dsql->GetOne("SELECT money,scores FROM `#@__arcrank` WHERE rank='10' ");
        if (is_array($dfrank)) {
            $dfmoney = $dfrank['money'];
            $dfscores = $dfrank['scores'];
        }
        $jointime = time();
        $logintime = time();
        $joinip = GetIP();
        $loginip = GetIP();
        $pwd = md5($userpwd);
        $mtype = '个人';

        $spaceSta = ($cfg_mb_spacesta < 0 ? $cfg_mb_spacesta : 0);

        $inQuery = "INSERT INTO `#@__member` (`mtype` ,`userid` ,`pwd` ,`uname` ,`sex` ,`rank` ,`money` ,`email` ,`scores` ,
        `matt`, `spacesta` ,`face`,`safequestion`,`safeanswer` ,`jointime` ,`joinip` ,`logintime` ,`loginip` )
       VALUES ('$mtype','$userid','$pwd','$uname','','10','$dfmoney','','$dfscores',
       '0','$spaceSta','','','','$jointime','$joinip','$logintime','$loginip'); ";
        if ($dsql->ExecuteNoneQuery($inQuery)) {
            $mid = $dsql->GetLastID();

            //写入默认会员详细资料
            if ($mtype == '个人') {
                $space = 'person';
            } else if ($mtype == '企业') {
                $space = 'company';
            } else {
                $space = 'person';
            }

            //写入默认统计数据
            $membertjquery = "INSERT INTO `#@__member_tj` (`mid`,`article`,`album`,`archives`,`homecount`,`pagecount`,`feedback`,`friend`,`stow`)
                   VALUES ('$mid','0','0','0','0','0','0','0','0'); ";
            $dsql->ExecuteNoneQuery($membertjquery);

            //写入默认空间配置数据
            $spacequery = "INSERT INTO `#@__member_space`(`mid` ,`pagesize` ,`matt` ,`spacename` ,`spacelogo` ,`spacestyle`, `sign` ,`spacenews`)
                    VALUES('{$mid}','10','0','{$uname}的空间','','$space','',''); ";
            $dsql->ExecuteNoneQuery($spacequery);

            //写入其它默认数据
            $dsql->ExecuteNoneQuery("INSERT INTO `#@__member_flink`(mid,title,url) VALUES('$mid','织梦内容管理系统','http://www.dedecms.com'); ");

            //----------------------------------------------
            //模拟登录
            //---------------------------
            $cfg_ml = new MemberLogin(7 * 3600);
            $rs = $cfg_ml->CheckUser($userid, $userpwd);


            // //邮件验证
            // if($cfg_mb_spacesta==-10)
            // {
            //     $userhash = md5($cfg_cookie_encode.'--'.$mid.'--'.$email);
            //     $url = $cfg_basehost.(empty($cfg_cmspath) ? '/' : $cfg_cmspath)."/member/index_do.php?fmdo=checkMail&mid={$mid}&userhash={$userhash}&do=1";
            //     $url = preg_replace("#http:\/\/#i", '', $url);
            //     $url = 'http://'.preg_replace("#\/\/#", '/', $url);
            //     $mailtitle = "{$cfg_webname}--会员邮件验证通知";
            //     $mailbody = '';
            //     $mailbody .= "尊敬的用户[{$uname}]，您好：\r\n";
            //     $mailbody .= "欢迎注册成为[{$cfg_webname}]的会员。\r\n";
            //     $mailbody .= "要通过注册，还必须进行最后一步操作，请点击或复制下面链接到地址栏访问这地址：\r\n\r\n";
            //     $mailbody .= "{$url}\r\n\r\n";
            //     $mailbody .= "Power by http://www.dedecms.com 织梦内容管理系统！\r\n";

            //     $headers = "From: ".$cfg_adminemail."\r\nReply-To: ".$cfg_adminemail;
            //     if($cfg_sendmail_bysmtp == 'Y' && !empty($cfg_smtp_server))
            //     {        
            //         $mailtype = 'TXT';
            //         require_once(DEDEINC.'/mail.class.php');
            //         $smtp = new smtp($cfg_smtp_server,$cfg_smtp_port,true,$cfg_smtp_usermail,$cfg_smtp_password);
            //         $smtp->debug = false;
            //         $smtp->sendmail($email,$cfg_webname,$cfg_smtp_usermail, $mailtitle, $mailbody, $mailtype);
            //     }
            //     else
            //     {
            //         @mail($email, $mailtitle, $mailbody, $headers);
            //     }
            // }//End 邮件验证
            ShowMsg('你已经登录系统，无需重新注册！', 'index.php');
            exit;
        } else {
            ShowMsg("注册失败，请检查资料是否有误或与管理员联系！", "-1");
            exit();
        }
    }
    require_once(DEDEMEMBER . "/templets/reg-new.htm");
} else {
    if (!$cfg_ml->IsLogin()) {
        ShowMsg("尚未完成基本信息的注册,请返回重新填写！", "index_do.php?fmdo=user&dopost=regnew");
        exit;
    } else {
        ShowMsg('你已经登录系统，无需重新注册！', 'index.php');
        exit;
    }
}
