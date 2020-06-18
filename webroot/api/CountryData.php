<?php
header('Content-Type: application/json');

$functionName = $_POST["func"]; // func parameter should be sent in AJAX, determines which function to run
if (method_exists('CountryData',$functionName)) { // check if function exists
	$countrydata = New CountryData(); //create new instance of class
	$countrydata->$functionName(); // run function
}


class CountryData {

	function getCountryData() {

		//should 'is input given' be a function so it can be unit-tested?
		if(!empty($_POST['country-input'])){ //check if an input was given
				
			$restcountriesurl = $this->createApiUrl($_POST); // create api endpoint

			if (!$countrydata = @file_get_contents($restcountriesurl)) { //suppress warning message to handle errors within system
				echo 'nodata'; //return error to app.js
			} else { 
				$countrydataarr = $this->countryJsonToArray($countrydata); // turn json string into array of objects
	
				// don't call usort on single-result name data
				$num_results = sizeof($countrydataarr);
				if ($num_results > 1){
					$sortedCountryDataArr = $this->sort_countries($countrydataarr);
				}
				// build analytics object for footer and get alt spelling information
				$analytics_data = $this->buildAnalyticsData($sortedCountryDataArr, $num_results, $_POST);
				// sort analytics object 
				// var_dump($analytics_data);
				$countryanalytics = $this->sortAnalytics($analytics_data);
				// return results to app.js
				echo json_encode(['data' => [$sortedCountryDataArr], 'analytics' => [$countryanalytics['analytics']], 'altsp' => [$countryanalytics['altsp']]]); 
			}
		} else {
			echo 'noinput'; //return error to app.js
		}
	}
	
	// turn json string into array of objects
	function countryJsonToArray($countrydata) {
		$countrydataarr = json_decode($countrydata); //turn data into an array of objects
		//acode endpoint returns a single object, convert this into array for consistency
		if ((gettype($countrydataarr) == 'object')) { 
			$countrydataarr = array($countrydataarr);
		}

		return $countrydataarr;
	}

	// build api endpoint
	function createApiUrl($post){
		$baseurl = 'https://restcountries.eu/rest/v2/';
		$filterlist = 'name;alpha2Code;alpha3Code;flag;region;subregion;population;languages;altSpellings'; //limit the data being returned by the server request
		
		//create api url based on search type
		if ($post['search-type'] == 'name') {
			$restcountriesurl = $baseurl . 'name/' . $post['country-input'] . '?fields=' . $filterlist;
		} else {
			$restcountriesurl = $baseurl . 'alpha/' . $post['country-input'] . '?fields=' . $filterlist;
		}
		return $restcountriesurl;
	}
	// calculate region, subregion counts and alt spelling results
	private function sortAnalytics($analytics_data) {
		// $regions = $analytics_data["analytics"]["region"];
		// sort regions based on count
		$analytics_data["analytics"]["region"] = $this->sort_regions($analytics_data["analytics"]["region"]);
		$regions = $analytics_data["analytics"]["region"];
		foreach($regions as $region) {
			
			$region_name = $region['name'];
			//sort subregions based on count
			$analytics_data["analytics"]["region"][$region_name]["subregions"] = $this->sort_subregions($region['subregions']);
		}
		return $analytics_data;
	}
	
	function buildAnalyticsData($countrydataarr, $num_results, $post) {
		$analytics_data['analytics']['num_results'] = $num_results;
		foreach($countrydataarr as $country) {
			// if alt spelling name triggered country inclusion, also display that name    
			$altSp[$country->name] = $this->getAltSpelling($country, $post);
			$analytics_data['altsp'] = $altSp;
			//handle cases where no region is given
			if ($country->region === "") { 
				$country->region = 'No Region';
			} 
			$analytics_data['analytics']['region'][$country->region]['count'] += 1; //use hash to count instances
			$analytics_data['analytics']['region'][$country->region]['name'] = $country->region; //need separate name node for sorting by count
			//handle cases where no subregion is given
			if ($country->subregion === "") { 
				$country->subregion = 'No Subregion';
			}
			$analytics_data['analytics']['region'][$country->region]['subregions'][$country->subregion]['count'] += 1; //use hash to count instances
			$analytics_data['analytics']['region'][$country->region]['subregions'][$country->subregion]['name'] = $country->subregion; //need separate name node for sorting by count
		}

		return $analytics_data;
	}
	// identify alt spellings that caused country to be returned if search string is not in main country name
	function getAltSpelling($country, $post) {
		// this only applies to name searches
		if ($post['search-type'] == 'name') {
			$country_input = $post['country-input'];
			foreach($country->altSpellings as $altSp) {
				// lowercase strings for matching
				$lower_altsp = strtolower($altSp);
				$lower_ctry_input = strtolower($country_input);
				$lower_ctry_name = strtolower($country->name);
				// if alt spelling contains search string and the country name does not contain search string, store alt spelling
				if (!(strpos($lower_altsp, $lower_ctry_input) === false) && (strpos($lower_ctry_name, $lower_ctry_input) === false)) {
					return $altSp;
				}
			}
		}
	}
	// sort country objects based on population
	function sort_countries($countrydataarr) {
		usort($countrydataarr, function ($a, $b) { //sort countries by population, descending
			return $a->population > $b->population ? -1 : 1; 
		}); 
		return $countrydataarr;
	}
	// sort regions based on count
 	function sort_regions($regions) {
		uasort($regions, function($a, $b) {
			return $a["count"] > $b["count"] ? -1 : 1;
		});
		return $regions;
	}
	// sort subregions based on count
	function sort_subregions($subregions) {
		usort($subregions, function($a, $b){
			return $a["count"] > $b["count"] ? -1 : 1;
		});
		// var_dump($subregions);
		return $subregions;
	}
}