<?php
/**
 * @desc libphpTwitter
 * @version 1.1
 * @author Johnathan Proffer, jproffer@gmail.com
 * @license GNU
 * 
 * @example
 * 		$twitter = new twitter('myusername','mypasword');
 * 		$twitter->update('hello, world!');
 * 		$data = $twitter->GetTimeline(1024,'public');
 * 		$twitter->DirectMessage('gnutel0','this rocks!');
 *
 */
	class twitter {
		
		private $uid;
		private $pwd;
		private $ch;
		public $error;
		private $of = array("user","friends","public");
		
		private $headers = array (
									'X-Twitter-Client: libphpTwitter', 
									'X-Twitter-Client-Version: 1.5', 
									'X-Twitter-Client-URL: http://www.johnproffer.com'
							);
		
		/* API paths */
		private $api = array(
								"update" 			=> 'http://twitter.com/statuses/update.xml',
								"public_timeline"	=> 'http://twitter.com/statuses/public_timeline.xml',
								"followers"			=> 'http://twitter.com/statuses/followers.xml',
								"featured"			=> 'http://twitter.com/statuses/featured.xml',
								"friends_timeline"	=> 'http://twitter.com/statuses/friends_timeline', 		// do not add '.xml' or '/' to these api urls - 
								"user_timeline"		=> 'http://twitter.com/statuses/user_timeline', 		// they are auto postpended in individual functions.
								"show"				=> 'http://twitter.com/statuses/show',
								"users"				=> 'http://twitter.com/users',
								"friends"			=> 'http://twitter.com/statuses/friends',
								"messages"			=> 'http://twitter.com/direct_messages'
						);
		
		/**
		 * Constructor
		 * @param string $uid
		 * @param string $pwd
		 * @param string $user_agent
		 */
		public function __construct($uid,$pwd, $user_agent='libphptwitter') {
			$this->uid=$uid;
			$this->pwd=$pwd;
			$this->ch = curl_init ();
			curl_setopt ( $this->ch, CURLOPT_VERBOSE, 1);
			curl_setopt ( $this->ch, CURLOPT_USERAGENT, $user_agent);
			curl_setopt ( $this->ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt ( $this->ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ( $this->ch, CURLOPT_HTTPHEADER, $this->headers);
		}
		
/** =====================================================================================
 * update/send functions  
 ** ===================================================================================== */
		/**
		 * Update user status
		 * @param string $status
		 * @return xml array, or FALSE on error
		 */
		function update($status) {
			$status=urlencode($status);
			$postargs = "status=$status";
			return $this->process($this->api['update'], $postargs);
		}
		
		/**
		 * Send direct message to a user
		 * @param string $user
		 * @param string $text
		 * @return xml array, or FALSE on error
		 */
		function DirectMessage($user, $text) {
			$user=urlencode($user); 
			$text=urlencode($text);
			$postargs="user=$user&text=$text";
			return $this->process("{$this->api['messages']}/new.xml", $postargs );
		}		
/** =====================================================================================
 * retrieval functions  
 ** ===================================================================================== */
		/**
		 * Retrieve Direct Messages received
		 * @param GMDATE_TIME $since (e.g. 'Wed, 09 Feb 1994 22:23:32 GMT')
		 * @return xml array, or FALSE on error
		 */
		function GetDirectMessages($since = FALSE) {
			if ($since) { $since=urlencode($since); }
			$request="{$this->api['messages']}.xml";
			$request.=($since)?"?since=$since":"";
			return $this->process($request);
		}
		
		/**
		 * Get detailed information on a followed user
		 * @param string or int $id
		 * @return xml array, or FALSE on error
		 */
		function GetUserInfo($id) {
			$id=urlencode($id);
			return $this->process("{$this->api['users']}/show/$id.xml");
		}
		
		/**
		 * Get a list of featured users and their status.
		 * @return xml array, or FALSE on error
		 */
		function GetFeaturedUsers() {
			return $this->process($this->api['featured']);
		}

		/**
		 * Get all followers of currently logged in user
		 * @return xml array, or FALSE on error
		 */
		function GetFollowers() {
			return $this->process($this->api['followers']);
		}

		/**
		 * Get friends of currently authenticated user
		 * If $id is provided, then retrieves friends of $id		
		 * @param string or int $id
		 * @return xml array, or FALSE on error
		 */
		function GetFriends($id = FALSE) {
			if ($id) {
				$id=urlencode($id);
				$ext="/$id.xml";
			} else { $ext=".xml"; }
			return $this->process("{$this->api['friends']}$ext");
		}
		
		/**
		 * Get status of a user
		 *
		 * @param string or int $id
		 * @return xml array, or FALSE on error
		 */
		function GetStatus($id) {
			$id=urlencode($id);
			return $this->process ("{$this->api['show']}/$id.xml");
		}


		/**
		 * Get authenticated user's timeline, or timeline of another user
		 *
		 * @param string or int $id
		 * @param string $of [ user | friends | public ]
		 * @param int $count
		 * @param GMDATE_TIME $since
		 * @return xml array, or FALSE on error
		 */
		function GetTimeline($id=FALSE, $of="user", $count = 20, $since = FALSE) {
			$id=urlencode($id); $since=urlencode($since); $count=intval($count);
			$count=($count==0)?20:$count;
			if (!in_array($of,$this->of)) { $this->error="\$of can only be of type 'user', 'friends', or 'public'."; return FALSE; }
			
			$url=$this->api["{$of}_timeline"];
			
			if ($of!='public') 	{ 	$url.=($id)?"/$id.xml":".xml"; 		}
			if ($since) 		{	$url.="?since=".urlencode($since); 	}
			
			return $this->process($url);
		}

		/**
		 * @desc post and process twitter api calls
		 * @param string $url
		 * @param array $postargs
		 * @return xml array, or FALSE on error
		 */
		function process($url, $postargs = FALSE) {
			echo "url: $url";
			if ($postargs) {
				curl_setopt ( $this->ch, CURLOPT_POST, TRUE);
				curl_setopt ( $this->ch, CURLOPT_POSTFIELDS, $postargs );
			} else { curl_setopt($this->ch, CURLOPT_POST, FALSE); }
			
			curl_setopt($this->ch, CURLOPT_URL, $url);
			curl_setopt($this->ch, CURLOPT_USERPWD, "{$this->uid}:{$this->pwd}");
			
			$res = curl_exec ($this->ch);
			$xml = new SimpleXMLElement ($res);
			if (isset($xml->error)) { $this->error = $xml->error; return FALSE; }
			return $xml;
		}
	}
	
?>