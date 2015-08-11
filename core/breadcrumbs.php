<?
	
	class EDBreadcrumbs {
		
		static $defaultOptions = array(
			"showHome" => true
		);
		
		static function getBreadcrumbs($options = array(), $targetPost = null) {
			
			$options = array_merge(self::$defaultOptions, $options);
			
			global $post;
			if(!$targetPost) {
				$targetPost = $post;
			}
			
			// Items
			$items = array();
			
			if($options['showHome']) {
				$items[] = (object)array(
					"title" => "Home",
					"url" => "/",
					"isCurrent" => false 
				);
			}
			
			// Grab the current URL as an array of path segments
			$urlParts = explode("/", preg_replace("/(^\/|\/?\?.*$|\/$)/", "", $_SERVER['REQUEST_URI']));
			
			// Add each item
			foreach($urlParts as $index => $part) {
				$testURL = implode("/", array_slice($urlParts, 0, $index+1));
				$postID = url_to_postid($testURL);
				
				if($postID) {
					$item = $items[] = get_post($postID);;
					$item->title = $item->post_title;
					$item->url = get_permalink($item->ID);
					$item->isCurrent = false;
				}
				
			}
			
			if(count($items)) {
				$lastItem = end($items);
				$lastItem->isCurrent = true;
			}
			
			return $items;
			
		}
		
		static function printBreadcrumbs($options = array(), $targetPost = null) {
			
			$breadcrumbs = self::getBreadcrumbs($options, $targetPost);
			
			echo "<ul class='breadcrumb-list'>";
			foreach($breadcrumbs as $item) {
				echo "<li class='breadcrumb-item ".($item->isCurrent?'is-current':'is-ancestor')."'><a href='".$item->url."'>".$item->title."</a></li>";
			}
			echo "</ul>";
			
		}
		
	}
	
?>