<?
	
	/*
		An example of using a child controller for more complex behaviour
	*/
	class ED_CampaignMonitor_API extends ED_API {
		
		private $_loaded = false;
		
		public function subscribeToList($args, $isAjax = true) {
			
			require_once("createsend-api/csrest_subscribers.php");
			
			$listID = isset($args['listID']) ? $args['listID'] : $this->getListID(isset($args['list']) ? $args['list'] : "main");
			
			$wrapper = new CS_REST_Subscribers($listID, array(
				"api_key" => ED()->getModuleSetting("campaignmonitor", "apiKey")
			));
			
			$result = $wrapper->add(array(
				"EmailAddress" => $args['email'],
				"Name" => isset($args['name']) ? $args['name'] : null,
				"CustomFields" => isset($args['CustomFields']) ? $args['CustomFields'] : null
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
	
	ED()->API->addController("CampaignMonitor", new ED_CampaignMonitor_API());
	
?>