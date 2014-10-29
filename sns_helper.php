<?php
	/**
	 * SNS 连接工厂类
	 */
	function get_sns_config(){
		$sns_config = array(
			'facebook'=>array(
				'appid' => '328199243934829',
				'secret' => '33c0746fd2cc7ab80444cc02d529bf79',
				'authorize_url' => 'https://www.facebook.com/dialog/oauth?client_id=%s&redirect_uri=%s&scope=email,create_note&state=%s',
				//'authorize_url' => 'https://www.facebook.com/dialog/oauth?client_id=%s&redirect_uri=%s&scope=email&state=%s',
				'accessToken_url' => 'https://graph.facebook.com/oauth/access_token?client_id=%s&client_secret=%s&code=%s&redirect_uri=%s'
			),
			'google'=>array(
				'appid' => '364643704492.apps.googleusercontent.com',
				'secret' => 'FpZTLm9VQf_T76SNXrL13oM1',
				'authorize_url' => 'https://accounts.google.com/o/oauth2/auth?response_type=code&client_id=%s&redirect_uri=%s&scope=https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email&state=%s',
				'accessToken_url' => 'https://accounts.google.com/o/oauth2/token'
			),
			'twitter'=>array(
				'appid' => 'NfUWPGNyW9I7LxQreFQFWQ',
				'secret' => 'k6wwZ7tDR6u1fcKzPYk6UETKi5SdnoxzqCYQRhlRf0',
				'authorize_url' => 'https://api.twitter.com/oauth/authorize',
				'accessToken_url' => '	https://api.twitter.com/oauth/access_token'
			),
			'sina'=>array(
				'appid' => '1554564287',
				'secret' => 'c0e699e1ff12e664f07cb3908b4ba31d',
				'authorize_url' => 'https://api.weibo.com/oauth2/authorize?client_id=%s&response_type=code&redirect_uri=%s&scope=email&state=%s',
				'accessToken_url' => 'https://api.weibo.com/oauth2/access_token?client_id=%s&client_secret=%s&grant_type=authorization_code&redirect_uri=%s&code=%s'
			),
			'renren'=>array(
				'app_id'=> '197937',
				'appid' => '4f7b6c9f5cb54d49a45b79e9e45e432c',
				'secret' => '9f8aaa4185ad4bbd8c4e2e147ed8bebf',
				'authorize_url' => 'https://graph.renren.com/oauth/authorize?client_id=%s&redirect_uri=%s&response_type=code&scope=publish_feed+read_user_notification+read_user_message&state=%s',
				'accessToken_url' => 'https://graph.renren.com/oauth/token?grant_type=authorization_code&client_id=%s&client_secret=%s&redirect_uri=%s&code=%s'
			),
			'kaixin'=>array(
				'app_id'=> '100029380',
				'appid' => '854069838398fd6189de150f352f6fe6',
				'secret' => 'eac37522d060c4b2bd7851653b85fb8e',
				'authorize_url' => 'http://api.kaixin001.com/oauth2/authorize?response_type=code&client_id=%s&redirect_uri=%s&scope=create_records&state=%s',
				'accessToken_url' => 'https://api.kaixin001.com/oauth2/access_token'
			)	
	   );
	   return $sns_config;
	}
	
	
	function getContainerFactory($apiName){	
		if($apiName == "facebook"){
			$container = new FacebookContainer;
		}
		if ($apiName == "google") {
			$container = new GoogleContainer;
		}
		if ($apiName == "sina") {
			$container = new SinaContainer;
		}
		if ($apiName == "renren") {
			$container = new RenrenContainer;
		}
		if ($apiName == "kaixin") {
			$container = new KaixinContainer;
		}
		return $container;
	}
	
	class KaixinContainer{
		function getAccessToken($accessToken_url,$appid,$secret,$code,$callback){
			$query = array(
						 'client_id'=>$appid,
						 'client_secret'=>$secret,
						 'grant_type'=>'authorization_code',
						 'redirect_uri'=>$callback,
						 'code'=>$code);
			//$accessToken_url = sprintf($accessToken_url,$appid,$secret,$callback,$code);
			$result = makerequest($accessToken_url, $query);
			if (strpos($result, 'error')) {
				return FALSE;
			}
			$result = std_class_object_to_array(json_decode($result));
			$access_token = $result['access_token'];
			return $access_token;
		}
		function getUserInfo($access_token,$params){
			$kaixin_user_url = "https://api.kaixin001.com/users/me.json?access_token=%s";
			$kaixin_user_url = sprintf($kaixin_user_url,$access_token);
			$userInfo = makerequest($kaixin_user_url,"");
			$userInfo = json_decode($userInfo);
			$userInfo = std_class_object_to_array($userInfo);
			$user = new User_sns();
			$user->setGuid($userInfo['uid']);
			$user->setUsername($userInfo['name']);
			$user->setIcon($userInfo['logo50']);
			$user->setAccesstoken($access_token);
			return $user;
		}
	}
	
	class RenrenContainer{
		function getAccessToken($accessToken_url,$appid,$secret,$code,$callback){
			$accessToken_url = sprintf($accessToken_url,$appid,$secret,$callback,$code);
			$result = makerequest($accessToken_url, "");
			if (strpos($result, 'error')) {
				return FALSE;
			}
			$result = std_class_object_to_array(json_decode($result));
			$access_token = $result['access_token'];
			return $access_token;
		}
		function getUserInfo($access_token,$params){
			$query = array('method'=>'users.getInfo',
						 'v'=>'1.0',
						 'access_token'=>$access_token,
						 'format'=>'JSON');
			$secret	= $params['secret'];
			$sig = renren_sig($query, $secret);
			$query['sig'] = $sig;
			$rr_user_url = "http://api.renren.com/restserver.do";
			$userInfo = makerequest($rr_user_url,$query);
			if (strpos($userInfo, 'error')) {
				return FALSE;
			}
			$userInfo = json_decode($userInfo);
			$userInfo = std_class_object_to_array($userInfo[0]);
			$user = new User_sns();
			$user->setGuid($userInfo['uid']);
			$user->setUsername($userInfo['name']);
			$user->setIcon($userInfo['tinyurl']);
			$user->setAccesstoken($access_token);
			return $user;
		}
	}
	class SinaContainer {
		function getAccessToken($accessToken_url,$appid,$secret,$code,$callback){
			$query = array('client_id'=>$appid,
						 'client_secret'=>$secret,
						 'grant_type'=>'authorization_code',
						 'redirect_uri'=>$callback,
						 'code'=>$code);
			$result = makerequest($accessToken_url, $query);
			if (strpos($result, 'error')) {
				return FALSE;
			}
			$result = std_class_object_to_array(json_decode($result));
			return $result;
		}
		function getUserInfo($access_token,$params){
			$userid = $params['uid'];
			if (!$userid) {
				$sina_url = "https://api.weibo.com/oauth2/get_token_info?access_token=".$access_token;
				$param['access_token'] = $access_token;
				$token_info = makerequest($sina_url,$param);
				$token_info = json_decode($token_info,true);
				$token_info = $token_info["expire_in"];
				if ($token_info > 0) {
					return TRUE;
				}else{
					return FALSE;
				}
			}
			$sina_url = "https://api.weibo.com/2/users/show.json?access_token=%s&uid=%s";
			$sina_url = sprintf($sina_url,$access_token,$userid);
			$userinfo = makerequest($sina_url,"");
			if (strpos($userinfo, 'error')) {
				return FALSE;
			}
			$userinfo = json_decode($userinfo,true);
			//$email = $this->getEmail($access_token);
			$user = new User_sns();
			$user->setGuid($userinfo['id']);
			$user->setUsername($userinfo['name']);
			$user->setIcon($userinfo['avatar_large']);
			$user->setGender($userinfo['gender']);
			$user->setAccesstoken($access_token);
			return $user;
		}
		function getEmail($access_token){
			$sina_url = "https://api.weibo.com/2/account/profile/email.json?access_token=%s";
			$sina_url = sprintf($sina_url,$access_token);
			$userinfo = makerequest($sina_url,"");
			$userinfo = json_decode($userinfo,true);
			return $userinfo["email"];
		}
		function feed($access_token,$param){
			$sina_url = "https://api.weibo.com/2/statuses/upload_url_text.json?access_token=%s";
			$sina_url = sprintf($sina_url,$access_token);
			$param['access_token'] = $access_token;
			$param['url'] = preg_replace('/^http:\/\/img[0-9]/','http://source',$param['url']);
			$userinfo = makerequest($sina_url,$param);
			if (strpos($userinfo, 'error')) {
				return FALSE;
			}
			$userinfo = json_decode($userinfo,true);
			return $userinfo;
		}
	}
	
	class GoogleContainer {
		function getAccessToken($accessToken_url,$appid,$secret,$code,$callback){
			$pama = array(
					'client_id' =>$appid,
					'client_secret' =>$secret,
					'redirect_uri'=>$callback,
					'grant_type'=>'authorization_code',
					'code'=>$code
				);
			$result = makerequest($accessToken_url, $pama);
			if (strpos($result, 'error')) {
				return FALSE;
			}
			$result = std_class_object_to_array(json_decode($result));
			$access_token = $result['access_token'];
			return $access_token;
		}
		function getUserId($access_token,$params){
			$google_url = "https://www.googleapis.com/oauth2/v1/userinfo?access_token=%s";
			$google_url = sprintf($google_url,$access_token);
			$userInfo = makerequest($google_url,"");
			$userInfo = json_decode($userInfo);
			$userInfo = std_class_object_to_array($userInfo);
			$uid = $userInfo['id'];
			return $uid;
		}
		function getUserInfo($access_token,$params){
			$google_url = "https://www.googleapis.com/oauth2/v1/userinfo?access_token=%s&fields=id,name,gender,locale,email,picture";
			$google_url = sprintf($google_url,$access_token);
			$userInfo_json = makerequest($google_url,"");
			$userInfo = json_decode($userInfo_json,TRUE);
			$user = new User_sns();
			if (strpos($userInfo_json, 'id')) {
				$user->setGuid($userInfo['id']);
			}
			if (strpos($userInfo_json, 'email')) {
				$user->setEmail($userInfo['email']);
			}
			if (strpos($userInfo_json, 'name')) {
				$user->setUsername($userInfo['name']);
			}
			if (strpos($userInfo_json, 'picture')) {
				$user->setIcon($userInfo['picture']);
			}
			$user->setAccesstoken($access_token);
			return $user;
		}
	}
	
	class FacebookContainer {
		function getAccessToken($accessToken_url,$appid,$secret,$code,$callback){
			$accessToken_url = sprintf($accessToken_url,$appid,$secret,$code,$callback);
			$result = makerequest($accessToken_url, "");
			if (strpos($result, 'error')) {
				return FALSE;
			}
			if($result != NULL){
				define("TOKEN_REGEX","/access_token=([^&]+)/");
				preg_match(TOKEN_REGEX, $result, $match);
			}
			return $match[1];
		}
		function getUserInfo($access_token,$params){
			$fb_user_url = "https://graph.facebook.com/me?access_token=%s&fields=id,name,gender,locale,email,picture";
			$fb_user_url = sprintf($fb_user_url,$access_token);
			$userInfo_json = makerequest($fb_user_url,"");
			if (strpos($userInfo_json, 'error') or empty($userInfo_json)) {
				return FALSE;
			}
			$userInfo = json_decode($userInfo_json);
			$userInfo = std_class_object_to_array($userInfo, 0);
			$user = new User_sns();
			if (strpos($userInfo_json, 'id')) {
				$user->setGuid($userInfo['id']);
			}
			if (strpos($userInfo_json, 'email')) {
				$user->setEmail($userInfo['email']);
			}
			if (strpos($userInfo_json, 'name')) {
				$user->setUsername($userInfo['name']);
			}
			if (strpos($userInfo_json, 'gender')) {
				$user->setGender($userInfo['gender']);
			}
			if (strpos($userInfo_json, 'picture')) {
				$icon = $userInfo['picture']['data']['url'];
				if (strpos($icon, '_q.jpg')) {
					$icon = str_replace ( "_q.jpg", "_n.jpg", $icon );
				}
				$user->setIcon($icon);
			}
			$user->setAccesstoken($access_token);
			return $user;
		}
		function getUserId($access_token,$params){
			$fb_user_url = "https://graph.facebook.com/me?access_token=%s";
			$fb_user_url = sprintf($fb_user_url,$access_token);
			$userInfo = makerequest($fb_user_url,"");
			if (strpos($userInfo, 'error')) {
				return FALSE;
			}
			$userInfo = json_decode($userInfo);
			$userInfo = std_class_object_to_array($userInfo, 0);
			$uid = $userInfo['id'];
			return $uid;
		}
		function feed($access_token,$params_array){
			$fb_feed_url = "https://graph.facebook.com/me/notes";
			$params["subject"] = $params_array["title"];
			$params["message"] = $params_array["body"];
			$params["access_token"] = $access_token;
			$userFeed = makerequest($fb_feed_url,$params);
			if (strpos($userFeed, 'error')) {
				return FALSE;
			}
			return $userFeed;
			
		}
		function notifications($access_token,$params){
			$notifications_url = "https://graph.facebook.com/me/notifications?include_read=true&access_token=%s";
			$notifications_url = sprintf($notifications_url,$access_token);
			$notifications = makerequest($notifications_url,"");
			return $notifications;
		}
		function threads($access_token,$params){
			$threads_url = "https://graph.facebook.com/me/inbox?access_token=%s";
			$threads_url = sprintf($threads_url,$access_token);
			$threads = makerequest($threads_url,"");
			return $threads;
		}
		function friendsrequest($access_token,$params){
			$sns_type = $params['sns_type'];
			$method = $params['method'];
			$id = $params['id'];
			$redirect_uri = urlencode(callback."?mode=XCui&sns_type=".$sns_type."&method=".$method) ;		
			$friends_url = "http://www.facebook.com/dialog/friends/?id=%s&app_id=%s&redirect_uri=%s";
			$friends_url = sprintf($friends_url,$id,appid,$redirect_uri);
			return $friends_url;
		}
		function login($params) {
			return 'http://my.337.com/liyuhang/test.php?media_appid=jojotest&sns_type=Facebook';
		}
		function get_loginstatus($access_token, $param) {
			$uid = $param['original_uid'];
			if ($this->getUserId($access_token,"") == $uid) {
				return true;
			}
			return false;
		}
	}
	
	function std_class_object_to_array($stdclassobject){
		$_array = is_object($stdclassobject) ? get_object_vars($stdclassobject) : $stdclassobject;
		foreach ($_array as $key => $value) {
			$value = (is_array($value) || is_object($value)) ? std_class_object_to_array($value) : $value;
			$array[$key] = $value;
		}
		return $array;
	}
	
	function renren_sig($params, $secrect){
		ksort($params);
		$base_string = "";
		foreach ($params as $key => $value) {
			$base_string .= $key . "=" . $value;
		}
		$base_string .= $secrect;
		return md5($base_string);
	}
	
class User_sns {
	private $username;
	private $uid;
	private $guid;
	private $pf;
	private $gender;
	private $appid;
	private $creatdate;
	private $lastlogintime;
	private $email;
	private $icon;
	private $accesstoken;
	private $refreshtoken;
	function _construct(){
		
	}
	public function getPf() {
		return $this->pf;
	}
	public function getAppid() {
		return $this->appid;
	}
	public function getCreatdate() {
		return $this->creatdate;
	}
	public function getLastlogintime() {
		return $this->lastlogintime;
	}
	public function setPf($pf) {
		$this->pf = $pf;
	}
	public function setGender($gender){
		$this->gender = $gender;
	}
	public function getGender(){
		return $this->gender;
	}
	public function setAppid($appid) {
		$this->appid = $appid;
	}
	public function setCreatdate($creatdate) {
		$this->creatdate = $creatdate;
	}
	public function setLastlogintime($lastlogintime) {
		$this->lastlogintime = $lastlogintime;
	}
	public function getGuid() {
		return $this->guid;
	}
	public function setGuid($guid) {
		$this->guid = $guid;
	}
	public function getUsername() {
		return $this->username;
	}
	public function getUid() {
		return $this->uid;
	}
	public function getEmail() {
		return $this->email;
	}
	public function getIcon() {
		return $this->icon;
	}
	public function getAccesstoken() {
		return $this->accesstoken;
	}
	public function getRefreshtoken() {
		return $this->refreshtoken;
	}
	public function setUsername($username) {
		$this->username = $username;
	}
	function setUid($uid) {
		$this->uid = $uid;
	}
	public function setEmail($email) {
		$this->email = $email;
	}
	public function setIcon($icon) {
		$this->icon = $icon;
	}
	public function setAccesstoken($accesstoken) {
		$this->accesstoken = $accesstoken;
	}
	public function setRefreshtoken($refreshtoken) {
		$this->refreshtoken = $refreshtoken;
	}
}
	
	
