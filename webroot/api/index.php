<?php

 // initial function
 header('Content-Type: application/json');

if(!empty($_POST['country-input'])){ //check if an input was given
    
    $restcountriesurl = createApiUrl($_POST); // create api endpoint

    if (!$countrydata = @file_get_contents($restcountriesurl)) { //suppress warning message to handle errors within system
        echo 'nodata'; //return error to app.js
    } else { 
        $countrydataarr = json_decode($countrydata); //turn data into an array of objects
        //acode endpoint returns a single object, convert this into array for consistency
        if ((gettype($countrydataarr) == 'object')) { 
            $countrydataarr = array($countrydataarr);
        }
        // don't call usort on single-result name data
        $num_results = sizeof($countrydataarr);
         if ($num_results > 1){
            usort($countrydataarr, "sort_countries"); //sort countries by population, descending
        }
        // build analytics object for footer and get alt spelling information
        $countryanalytics = get_analytics($countrydataarr, $num_results, $_POST);
        // return results to app.js
        echo json_encode(['data' => [$countrydataarr], 'analytics' => [$countryanalytics['analytics']], 'altsp' => [$countryanalytics['altsp']]]); 
    }
} else {
    echo 'noinput'; //return error to app.js
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
function get_analytics($countrydataarr,$num_results,$country_input) {
    $region_data['analytics']['num_results'] = $num_results;
    foreach($countrydataarr as $country) {
        // if alt spelling name triggered country inclusion, also display that name    
        $altSp[$country->name] = get_alt_spelling($country, $country_input);
        $region_data['altsp'] = $altSp;
        //handle cases where no region is given
        if ($country->region === "") { 
            $country->region = 'None';
        } 
        $region_data['analytics']['region'][$country->region]['count'] += 1; //use hash to count instances
        $region_data['analytics']['region'][$country->region]['name'] = $country->region; //need separate name node for sorting by count
        //handle cases where no subregion is given
        if ($country->subregion === "") { 
            $country->subregion = 'None';
        }
        $region_data['analytics']['region'][$country->region]['subregions'][$country->subregion]['count'] += 1; //use hash to count instances
        $region_data['analytics']['region'][$country->region]['subregions'][$country->subregion]['name'] = $country->subregion; //need separate name node for sorting by count
    }
    uasort($region_data["analytics"]["region"], "sort_regions");
    foreach($region_data["analytics"]["region"] as $region) {
        $region_name = $region['name'];
        usort($region_data['analytics']['region'][$region_name]['subregions'], "sort_subregions");
    }
    return $region_data;
}
// identify alt spellings that caused country to be returned if search string is not in main country name
function get_alt_spelling($country, $post) {
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
function sort_countries($a, $b) {
    return $a->population > $b->population ? -1 : 1; 
}
// sort regions based on count
function sort_regions($a, $b) {
    return $a["count"] > $b["count"] ? -1 : 1;
}
// sort subregions based on count
function sort_subregions($a, $b) {
    return $a["count"] > $b["count"] ? -1 : 1;
}