<?
	
	class EDTweet {
		
		private $data;
		
		public function __construct($data) {
			
			$this->data = (object)$data;
			
		}
		
		public function __get($var) {
			
			return isset($this->data->$var) ? $this->data->$var : null;
			
		}
		
		public function getHTML($links = true, $users = true, $hashtags = true, $mediaLinks = true) {
			
			$tweet = $this->data;
			$text = $tweet->full_text ? $tweet->full_text : $tweet->text;

			$entities = array();
			
			if($links && is_array(@$tweet->entities->urls)) {
				foreach($tweet->entities->urls as $e) {
					$temp["start"] = $e->indices[0];
					$temp["end"] = $e->indices[1];
					$temp["replacement"] = "<a href='".$e->expanded_url."' class='t-url' target='_blank'>".$e->display_url."</a>";
					$entities[] = $temp;
				}
			}
			
			if($mediaLinks && is_array(@$tweet->entities->media)) {
				foreach($tweet->entities->media as $e) {
					$temp["start"] = $e->indices[0];
					$temp["end"] = $e->indices[1];
					if($mediaLinks == "hide") {
						$temp["replacement"] = "";
					} else {
						$temp["replacement"] = "<a href='".$e->expanded_url."' class='t-media' target='_blank'>".$e->display_url."</a>";
					}
					$entities[] = $temp;
				}
			}
			
			if($users && is_array(@$tweet->entities->user_mentions)) {
				foreach($tweet->entities->user_mentions as $e) {
					$temp["start"] = $e->indices[0];
					$temp["end"] = $e->indices[1];
					$temp["replacement"] = "<a href='https://twitter.com/".$e->screen_name."' class='t-user' target='_blank'>@".$e->screen_name."</a>";
					$entities[] = $temp;
				}
			}
			
			if($hashtags && is_array(@$tweet->entities->hashtags)) {
				foreach($tweet->entities->hashtags as $e) {
					$temp["start"] = $e->indices[0];
					$temp["end"] = $e->indices[1];
					$temp["replacement"] = "<a href='https://twitter.com/hashtag/".$e->text."?src=hash' class='t-hashtag' target='_blank'>#".$e->text."</a>";
					$entities[] = $temp;
				}
			}
			
			usort($entities, function($a,$b){ return($b["start"]-$a["start"]); });
			
			foreach($entities as $item) {
				$text = utf8_substr_replace($text, $item["replacement"], $item["start"], $item["end"] - $item["start"]);
			}
			
			return $text;
		}
		
		public function getURL() {
			
			return $this->getUserURL()."/status/".$this->data->id_str;
			
		}
		
		public function getUserURL() {
			
			return "http://twitter.com/".$this->data->user->screen_name;
			
		}
		
	}
	
	
	/*
		An example of using a child controller for more complex behaviour
	*/
	class ED_Twitter_API extends ED_API {
		
		private $_loaded = false;
		public $twitter = null;
		
		private function ensureLoaded() {
			if($this->_loaded) return;
			include_once("twitter-api/TwitterAPIExchange.php");
			$tokens = ED()->getModuleSetting("twitter", "tokens");
			$this->twitter = new TwitterAPIExchange($tokens);
		}
		
		public function getTweetsFromSearch ($args, $isAjax = false) {
			if($isAjax) throw new Exception("This method cannot be called via XHR.");
			$this->ensureLoaded();
			
			$url = 'https://api.twitter.com/1.1/search/tweets.json';
			$query = [];
			foreach ($args as $k => $v) {
				$query[] = $k.'='.urlencode((string)$v);
			}
			$qs = "?".implode("&", $query);
			
			$cacheKey = md5($url.$qs);
			
			if($cached = get_transient($cacheKey) && !$args['dontCache']) {
				
				return $cached;
				
			} else {
				
				$response = $this->twitter->setGetfield($qs)
					->buildOauth($url, 'GET')
					->performRequest();
				
				$data = json_decode($response)->statuses;
				
				$data = array_map(function($item) {
					return new EDTweet($item);
				}, $data);
				
				set_transient($cacheKey, $data, (int)@$args['cache_time'] ? $args['cache_time'] : 3600);
				
				return $data;
			}
		}
		
		/*
			Gets latest tweets by the specified user.
			- Basics are `screen_name` or `user_id`, `count`, `exclude_replies`, `include_rts`
			- For more info, see https://dev.twitter.com/rest/reference/get/statuses/user_timeline
			- Also supply the optional `cache_time` argument in seconds, which defaults to 3600 (one hour)
			
		*/
		public function getUserTimeline($args, $isAjax = false) {
			if($isAjax) throw new Exception("This method cannot be called via XHR.");
			$this->ensureLoaded();
			
			$url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
			$query = array();
			foreach($args as $k => $v) {
				$query[] = $k."=".urlencode((string)$v);
			}
			$qs = "?".implode("&", $query);
			
			$cacheKey = md5($url.$qs);
			
			if($cached = get_transient($cacheKey)) {
				
				return $cached;
				
			} else {
				
				$response = $this->twitter->setGetfield($qs)
					->buildOauth($url, 'GET')
					->performRequest();
				
				$data = json_decode($response);
				
				$data = array_map(function($item) {
					return new EDTweet($item);
				}, $data);
				
				set_transient($cacheKey, $data, (int)@$args['cache_time'] ? $args['cache_time'] : 3600);
				
				return $data;
			}
			
		}
		
	}
	
	ED()->API->addController("Twitter", new ED_Twitter_API());
	
?>