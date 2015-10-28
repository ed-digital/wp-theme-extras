<?
	
	/*
		An example of using a child controller for more complex behaviour
	*/
	class ED_CampaignMonitor_API extends ED_API {
		
		private $_loaded = false;
		public $twitter = null;
		
		public function subscribeToList($args, $isAjax = true) {
			
			require_once("createsend-api/csrest_subscribers.php");
			
			$listID = $this->getListID($args['list'] ? $args['list'] : "main");
			
			$wrapper = new CS_REST_Subscribers($listID, array(
				"api_key" => ED()->getModuleSetting("campaignmonitor", "apiKey")
			));
			
			$result = $wrapper->add(array(
				"EmailAddress" => $args['email'],
				"Name" => $args['name'],
				"CustomFields" => $args['CustomFields']
			));
			
			if(!$result->was_successful()) {
				throw new Exception($result->response->Message);
			}
			
			return true;
			
		}
		
		public function getListID($listName) {
			$lists = ED()->getModuleSetting("campaignmonitor", "listIDs");
			return $lists[$listName];
		}
		
	}
	
	ED()->API->addController("campaignmonitor", new ED_CampaignMonitor_API());
	
?>