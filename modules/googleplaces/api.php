<?
	
	class ED_GooglePlaces_API extends ED_API {
		
		private $_loaded = false;
		public $key = null;
		
		private function getKey() {
			if(!$this->key) {
				$this->key = ED()->getModuleSetting("googleplaces", "key");
			}
			return $this->key;
		}
		
		private function formatOpeningTime($val) {
			
			$hours = (int)substr($val,0, 2);
			$minutes = substr($val, 2, 2);
			$ampm = $hours > 12 ? 'pm' : 'am';
			
			if($hours > 13) {
				$hours -= 12;
			}
			
			if($minutes === "00") {
				return $hours.$ampm;
			} else {
				return $hours.":".$minutes.$ampm;
			}
			
		}

		public function getPlaceByID($placeID, $isXHR = false) {
			if($isXHR) throw new Exception("This method cannot be called via XHR.");
			
			$response = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/place/details/json?placeid=".urlencode($placeID)."&key=".$this->getKey()));
			
			if(!$response || $response->status !== "OK") {
				return null;
			}
			
			return $response->result;
			
		}
		
		public function getPlaceIDForKeyword($args, $isXHR = false) {
			
			if($isXHR) throw new Exception("This method cannot be called via XHR.");
			$response = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/place/autocomplete/json?input=".urlencode($args['keyword'])."&key=".$this->getKey()));
			
			if(!$response || !$response->predictions || count($response->predictions) === 0) {
				return null;
			}
			
			return @$response->predictions[0]->place_id;
			
		}
		
		/*
			Supply an associative array with either `placeID` argument or a 'name' argument (which will be autocompleted)
		*/
		public function getOpeningHoursForPlace($args, $isXHR = false) {
			
			// $cacheKey = md5("getOpeningHoursForPlace:".serialize(func_get_args()));
			// $cachedValue = get_transient($cacheKey);
			// 
			// if($cachedValue) {
			// 	return $cachedValue;
			// }
			
			if(isset($args['name']) && !isset($args['placeID'])) {
				$args['placeID'] = $this->getPlaceIDForKeyword(['keyword' => $args['name']]);
			}
			
			if(!isset($args['placeID'])) {
				return null;
			}
			
			$placeData = $this->getPlaceByID($args['placeID']);
			
			if(!$placeData->opening_hours || !$placeData->opening_hours->periods) {
				return null;
			}
			
			$outputValue = (object)[
				'isAlwaysOpen' => false,
				'openToday' => null,
				'openNow' => null,
				'openLabel' => null,
				'grouped' => null
			];
			
			if(count($placeData->opening_hours->periods) === 1 && @$placeData->opening_hours->periods[0]->open->time === '0000' && !@$placeData->opening_hours->periods[0]->close) {
				
				$outputValue->isAlwaysOpen = true;
				$outputValue->openLabel = 'Always Open';
				
			} else {
				
				// Index days
				$days = [];
				$daysIndex = [];
				
				foreach($placeData->opening_hours->periods as $item) {
					$dayNumber = ($item->open->day+6) % 7;
					@$daysIndex[$dayNumber] .= $item->open->time."/".$item->close->time;
					
					if(isset($days[$dayNumber])) {
						$days[$dayNumber] = [min($days[$dayNumber][0], $item->open->time), max($days[$dayNumber][1], $item->close->time)];
					} else {
						$days[$dayNumber] = [$item->open->time, $item->close->time];
					}
				}
				
				// Key sort, since they may be out of order
				ksort($daysIndex);
				ksort($days);
				
				// dump("Index", $daysIndex);
				// dump("Days", $days);
				
				// Compile list of shared days
				$output = [];
				$lastVal = null;
				$sinceDay = null;
				foreach($daysIndex as $day => $val) {
					if($lastVal && $lastVal->index === $val) {
						$lastVal->days[] = $day;
					} else {
						// No match
						if($lastVal) {
							// Add the previous value first
							$output[] = $lastVal;
						}
						$lastVal = (object)[
							'days' => [$day],
							'times' => $days[$day],
							'index' => $val
						];
					}
				}
				if($lastVal) {
					$output[] = $lastVal;
				}
				
				$daysLong = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
				$daysShort = ['Mon', 'Tue', 'Wed', 'Thur', 'Fri', 'Sat', 'Sun'];
				
				// Add labels
				foreach($output as $day => $item) {
					
					if(count($item->days) == 1) {
						$item->dayLabel = $daysLong[$item->days[0]];
						$item->dayLabelShort = $daysShort[$item->days[0]];
					} else {
						$item->dayLabel = $daysLong[$item->days[0]]." - ".$daysLong[end($item->days)];
						$item->dayLabelShort = $daysShort[$item->days[0]]."-".$daysShort[end($item->days)];
					}
					
					$item->timesLabel = $this->formatOpeningTime($item->times[0])." &mdash; ".$this->formatOpeningTime($item->times[1]);
					$item->timesLabelShort = str_replace(" ", "", $item->timesLabel);
					
				}
				
				// Determine if open now etc
				$isOpenNow = false;
				$isOpenToday = false;
				$openLabel = "Closed today";
				date_default_timezone_set("Australia/Canberra");
				$today = date("N")-1;
				$currentTime = date("Hi");
				
				foreach($output as $item) {
					
					if(in_array($today, $item->days)) {
						// Open today!
						$isOpenToday = true;
						// But when?
						if($currentTime >= $item->times[0] && $currentTime <= $item->times[1]) {
							// Open now!
							$isOpenNow = true;
							$openLabel = "Open till ".$this->formatOpeningTime($item->times[1])." today";
						} else if($currentTime < $item->times[0]) {
							$openLabel = "Opens later at ".$this->formatOpeningTime($item->times[0]);
						} else {
							$openLabel = "Closed now";
						}
					}
					
				}
				
				$outputValue->openToday = $isOpenToday;
				$outputValue->openNow = $isOpenNow;
				$outputValue->openLabel = $openLabel;
				$outputValue->grouped = $output;
			
			}
			
			set_transient($cacheKey, $outputValue, ED()->getModuleSetting("googleplaces", "cacheTime"));
			
			return $outputValue;
			
		}
		
	}
	
	ED()->API->addController("GooglePlaces", new ED_GooglePlaces_API());
	
?>