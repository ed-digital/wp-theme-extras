<?
	
	class EDInstagramPost {
		
		private $data;
		
		public function __construct($data) {
			
			$this->data = (object)$data;
			
		}
		
		public function __get($var) {
			
			return isset($this->data->$var) ? $this->data->$var : null;
			
		}
		
		public function getImageURL() {
			
			return $this->data->images->standard_resolution->url;
			
		}
		
		public function getHTML() {
			
			$tweet = $this->data;
			$text = $tweet->caption->text;

			$text = preg_replace("/(\@[^\ ]+)/", '<span class="inst-username">$1</span>', $text);
			$text = preg_replace("/(\#[^\ ]+)/", '<span class="inst-hashtag">$1</span>', $text);
			
			return $text;
			
		}
		
	}
	
	
	/*
		An example of using a child controller for more complex behaviour
	*/
	class ED_Instagram_API extends ED_API {
		
		private $_loaded = false;
		
		public function init() {
			$this->settings = ED()->getModuleSetting("instagram");
		}
		
		/*
			Gets latest posts by the specified user.
			- Supply either 'screen_name' or 'user_id'. user_id is preferred, since screen_name requires two HTTP requests!
			- For more info, see https://instagram.com/developer/endpoints/users/#get_users_media_recent
			- Also supply the optional `cache_time` argument in seconds, which defaults to 3600 (one hour)
			
		*/
		public function getRecent($args, $isAjax = false) {
			if($isAjax) throw new Exception("This method cannot be called via XHR.");
			
			if(@$args['screen_name'] && !@$args['user_id']) {
				$args['user_id'] = $this->getUserFromScreenName($args['screen_name'])->id;
			}
			
			$url = 'https://api.instagram.com/v1/users/'.$args['user_id'].'/media/recent?client_id='.$this->settings['tokens']['client_id'];
			$cacheKey = md5($url);
			
			if($cached = get_transient($cacheKey)) {
				
				return $cached;
				
			} else {
				
				$data = json_decode(file_get_contents($url));
				
				$data = @$data->data;
				
				if(!$data) {
					return array();
				}
				
				$data = array_map(function($item) {
					return new EDInstagramPost($item);
				}, $data);
				
				set_transient($cacheKey, $data, (int)@$args['cache_time'] ? $args['cache_time'] : 3600);
				
				return $data;
			}
			
		}
		
		public function getUserFromScreenName($name) {
			
			$url = 'https://api.instagram.com/v1/users/search?q='.urlencode($name).'&client_id='.$this->settings['tokens']['client_id'];
			$cacheKey = md5($url);
			
			if($cached = get_transient($cacheKey)) {
				
				return $cached;
				
			} else {
				
				$data = json_decode(file_get_contents($url));
				
				$info = @$data->data[0];
				
				if(!$info || strtolower($name) !== strtolower($info->username)) {
					throw new Exception("Could not locate Instagram user '".$name."'");
				}
				
				set_transient($cacheKey, $info, 100000);
				
				return $info;
			}
			

		}
		
	}
	
	ED()->API->addController("Instagram", new ED_Instagram_API());
	
?>