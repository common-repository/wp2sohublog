<?php
/*
Plugin Name: WP2SohuBlog
Plugin URI: http://starhai.net/2010/284.htm
Description: 同步发表 WordPress 博客日志到 百度空间,初次安装必须设置后才能使用。
Version: 1.0.0
Author: Starhai
Author URI: http://starhai.net/
*/
/*  Copyright 2010  Starhai   (email : i@starhai.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2.

*/
class sohublog{
	public  $useragent="Nokia"; //定义要模拟的浏览器名称
	private $token="";
	private $ch;	//CURL对象句柄
	private $cookie;	//保存Cookie的临时文件
	private $data;	//临时数据保存地址
	public $sblog_class;
public function login($user,$pass)
	{

		$d = tempnam('../tmp/', 'cookie.txt');  //创建随机临时文件保存cookie.
		$this->cookie=$d;
	    $ch = curl_init("http://blog.sohu.com");
	    $this->ch=$ch;
	    curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie);
	    curl_setopt($ch, CURLOPT_HEADER, 1);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	    curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent);
	    curl_exec($ch);
	    curl_close($ch);
	    unset($this->ch);


	    $ch = curl_init($this->ch);
		$str1=microtime(true)*100;
		$s= $str1."2";
		$posturl="http://passport.sohu.com/sso/login.jsp";
		$post="userid=".$user."&password=".$pass."&appid=1019&persistentcookie=0&s=".$s."&b=1&w=1024&pwdtype=1&v=26";

	    curl_setopt($ch, CURLOPT_REFERER, "http://blog.sohu.com");
	    curl_setopt($ch, CURLOPT_URL, $posturl);
		curl_setopt($ch, CURLOPT_POST, 1); // how many parameters to post
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	    curl_setopt($ch, CURLOPT_HEADER, 1);
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	    curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie);
	    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
	   	curl_exec($ch);

 		curl_close($ch);



		unset($this->ch);

	    $ch = curl_init($this->ch);
 		$creaturl="http://blog.sohu.com/manage/entry.do?m=add&t=shortcut";
 		$reff="http://blog.sohu.com/manage/profile.do";
	    curl_setopt($ch, CURLOPT_URL, $creaturl);
	    curl_setopt($ch, CURLOPT_REFERER,$reff);
	    curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_HEADER, 1);
	    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
	   	$data= curl_exec($ch);
		curl_close($ch);
	   	preg_match_all( "/name=\"aid\" value=\"(.*?)\"\>/s",$data, $tokens );
	   	$this->token=$tokens[1][0];
		//echo $this->token; //测试代码，获得的token
		unset($this->ch);
	}


public function send($title,$tags,$content,$x_allowComment)
	{



		$posturl="http://blog.sohu.com/manage/entry.do";
		$post="oper=art_ok&m=save&aid=".urlencode($this->token)."&shortcutFlag=true&contrChId=&contrCataId=&entrytitle=".$title."&keywords=".$tags."&categoryId=0&newGategory=&entrycontent=".$content."&excerpt=&allowComment=".$x_allowComment."&perm=0";
		$ch = curl_init($this->ch);
   		curl_setopt($ch, CURLOPT_URL, $posturl);
		curl_setopt($ch, CURLOPT_POST, 1); // how many parameters to post
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_REFERER, "http://blog.sohu.com/manage/entry.do?m=add&t=shortcut");
		curl_setopt($ch, CURLOPT_COOKIEJAR,  $this->cookie);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_exec($ch);
		curl_close($ch);
		unset($this->ch);
	}

	public function logoff()
	{
		unset($this->ch);
		unlink($this->cookie);
	}

}
?>
<?php
// Hook for adding admin menus
add_action('admin_menu', 'mt_add_sohu_pages');
add_action('publish_post', 'publish_post_2_sohublog');
add_action('xmlrpc_public_post', 'publish_post_2_sohublog');
// action function for above hook
function mt_add_sohu_pages() {
    //call register settings function
	add_action( 'admin_init', 'register_wpsohu_settings' );
	// Add a new submenu under Options:
    add_options_page('WP2Sohu Options', 'WP2SohuBlog', 'administrator', 'wpsohu', 'mt_wpsohu_page');



}

function register_wpsohu_settings() {
	//register our settings
	register_setting( 'WP2Sohu-settings-group', 'wp2sohuuser' );
	register_setting( 'WP2Sohu-settings-group', 'wp2sohupass' );
	register_setting( 'WP2Sohu-settings-group', 'sohu_sdurl' );
	register_setting( 'WP2Sohu-settings-group', 'sohu_pinglun' );
	register_setting( 'WP2Sohu-settings-group', 'sohu_xrank' );


}


// mt_options_page() displays the page content for the Test Options submenu
function mt_wpsohu_page() {

 if (!function_exists("curl_init"))
 {
?>

<div class="wrap">
<h2>您的服务器不支持cURL库，插件WP2Sohublog无法工作，请禁用该插件。</h2><br />
</div>

<?php
 }
 else
 {

?>
<div class="wrap">
<h2>WP2SohuBlog 选项</h2>
设置仅适用于搜狐博客，不支持Wordpress中<b>private</b>属性的文章发布到搜狐博客。

<br/><br/>
<form method="post" action="options.php">

  <?php settings_fields( 'WP2Sohu-settings-group' ); ?>
   <table class="form-table">
   		<tr valign="top">
        <th scope="row">搜狐的登录名</th>
        <td>
			<input name="wp2sohuuser" type="text" id="wp2sohuuser" value="<?php form_option('wp2sohuuser'); ?>" class="regular-text" />

		</td>
		</tr>
		<tr valign="top">
        <th scope="row">搜狐的登录密码(请填写32位小写MD5值)</th>
        <td>
			<input name="wp2sohupass" type="password" id="wp2sohuuser" value="<?php form_option('wp2sohupass'); ?>" class="regular-text" />

		</td>

		</tr>


		 <tr valign="top">
        <th scope="row">评论权限设置</th>
        <td>

			<input name="sohu_pinglun"  value="1" <?php checked(1, get_option('sohu_pinglun')); ?> id="commentRadio1" type="radio">
			<label for="commentRadio1">禁止所有人</label>
			<input name="sohu_pinglun" value="2" <?php checked(2, get_option('sohu_pinglun')); ?> id="commentRadio2" type="radio">
			<label for="commentRadio2">只有登录用户</label>
			<input name="sohu_pinglun" value="3" <?php checked(3, get_option('sohu_pinglun')); ?> id="commentRadio3" type="radio">
			<label for="commentRadio3">只有好友</label>
		</td>
		</tr>


		 <tr valign="top">
        <th scope="row">原文链接设置</th>
        <td>

			<input name="sohu_sdurl"  value="0" <?php checked(0, get_option('sohu_sdurl')); ?> id="cwp2baidusdurl1" type="radio">
			<label for="cwp2baidusdurl1">不发送</label>
			<input name="sohu_sdurl" value="1" <?php checked(1, get_option('sohu_sdurl')); ?> id="cwp2baidusdurl2" type="radio">
			<label for="cwp2baidusdurl2">发送（链接在文章头部)</label>
			<input name="sohu_sdurl" value="2" <?php checked(2, get_option('sohu_sdurl')); ?> id="cwp2baidusdurl3" type="radio">
			<label for="cwp2baidusdurl3">发送（链接在文章尾部)</label>
		</td>
		</tr>

    </table>

  <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>
</form>

<br/><br/><B>特别提示：如何获得密码的32位MD5值</B>（见<A HREF="http://starhai.net/2010/287.htm" TARGET="_blank">http://starhai.net/2010/287.htm</A>)

</div>
<?php
 }
}



function publish_post_2_sohublog($post_ID){

	$post=get_post($post_ID);
	$status=$post->post_status;
	if($post->post_date==$post->post_modified)
	{
		if($post->post_type =="post")
		{


				$title=$post->post_title;
				if (strlen($title)==0)
					{$title="无题  ";}
				$content=$post->post_content;
				$sendurl=get_option('sohu_sdurl');
				if ($sendurl==1)
				{
					$content="查看原文：<a href=".get_permalink($post_ID).">".get_permalink($post_ID)."</a><br/>".$content;
				}
				elseif($sendurl==2)
				{
					$content.="<br/>查看原文：<a href=".get_permalink($post_ID).">".get_permalink($post_ID)."</a>";
				}
				else
				{

					if (strlen($content)==0)
					{$content="a blank ";}
				}


				$x_cms_flag=get_option('sohu_pinglun');

			$posttags = get_the_tags($post_ID);
				if ($posttags)
				{
				foreach($posttags as $tags) {
					$wptags.=$tags->name . ',';
				}
				}
				$wptags.="wp2sohublog";


				$wp2sohuuser=get_option('wp2sohuuser');
				$wp2sohupass=get_option('wp2sohupass');
				if (strlen($wp2sohuuser)>1)
				{
					if (strlen($wp2sohupass)>3)
					{
							if(!function_exists('iconv'))
							{
								require_once(dirname(__FILE__).'/iconv.php');
							}

					$user=urlencode(iconv('utf-8', 'GBK', $wp2sohuuser));
					$pass=urlencode(iconv('utf-8', 'GBK', $wp2sohupass));
					$title=urlencode(iconv('utf-8', 'GBK', $title));
					$wptags=urlencode(iconv('utf-8', 'GBK', $wptags));
					$content=urlencode(iconv('utf-8', 'GBK', $content));
					$x_cms_flag=urlencode($x_cms_flag);

					$blog=new sohublog();
					$blog->login($user,$pass);
					$blog->send($title,$wptags,$content,$x_cms_flag);
					$blog->logoff();
					}
				}

		}
	}
}
?>