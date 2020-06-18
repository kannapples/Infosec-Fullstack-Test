<?php
use PHPUnit\Framework\TestCase;

require_once('./webroot/api/CountryData.php');

class CountryDataTest extends TestCase
{
    // expect ApiURL to return specific data based on input
    function testValidApiURL() {
        $this->testEmptyInput(); //if empty string is submitted
        $this->testGoodNameInput(); //if name is submitted that returns data
        $this->testBadNameInput(); //if name is submitted that returns no data
        $this->testGoodCodeInput(); //if code is submitted that returns data
        $this->testBadCodeInput(); //if code is submitted that returns no data
    }
    // expect all sort functions to return results descending by population or count
    function testSortFunctions() {
        $this->testCountrySort();
        $this->testRegionSort();
        $this->testSubregionSort();
    }
    // expect altSpelling to be populated for instances where search term matched alt spelling instead of name
    function testAltSpelling() {
        $countrydata = New CountryData();
        $countryjson = $this->buildDataContents('name', 'united', $countrydata);
        $countrydataarr = $countrydata->countryJsonToArray($countryjson);
        $post = buildPostArr();
        $altSpelling = $countrydata->getAltSpelling($countrydataarr[5],$post);
        $this->assertEquals($altSpelling, "United Mexican States");
    }

    ////////////////////////////////////////////////////
    //////////////  HELPER FUNCTIONS ///////////////////
    ////////////////////////////////////////////////////

    function buildDataContents($searchType, $countryInput, $countrydata = null) {
        if ($countrydata === null) {
            $countrydata = New CountryData();
        }
        
        $post = array('search-type' => $searchType, 'country-input' => $countryInput);
        $apiurl = $countrydata->createApiUrl($post);
        return @file_get_contents($apiurl);
    }

    // helper functions for testValidApiURL
    function testEmptyInput() {
        $countryjson = $this->buildDataContents('name', '');
        $this->assertEquals($false, $countryjson);
    }
 
    function testGoodNameInput() {
        $countryjson = $this->buildDataContents('name', 'united');
        $this->assertJsonStringEqualsJsonFile(__DIR__ . '/JsonRetVal_Name_United.json', $countryjson);
    }

    function testBadNameInput() {
        $countryjson = $this->buildDataContents('name', 'xxxxx');
        $this->assertEquals($false, $countryjson);
    }

    function testGoodCodeInput() {
        $countryjson = $this->buildDataContents('code', 'AW');
        $this->assertJsonStringEqualsJsonFile(__DIR__ . '/JsonRetVal_Code_AW.json', $countryjson);
    }

    function testBadCodeInput() {
        $countryjson = $this->buildDataContents('code', 'xx');
        $this->assertEquals($false, $countryjson);
    }

    function testCountrySort() {
        $countrydata = New CountryData();
        $countryjson = $this->buildDataContents('name', 'united', $countrydata);
        $countrydataarr = $countrydata->countryJsonToArray($countryjson); //turn data into an array of objects
        $sortedCountryDataArr = $countrydata->sort_countries($countrydataarr); //sort countries by population
        $this->assertEquals($sortedCountryDataArr[0]->name,"United States of America");
        $this->assertEquals($sortedCountryDataArr[5]->name,"United States Minor Outlying Islands");
    }

    function testRegionSort() {
        $countrydata = New CountryData();
        $countryjson = $this->buildDataContents('name', 'united', $countrydata);
        $countrydataarr = $countrydata->countryJsonToArray($countryjson);
        $post = buildPostArr();
        $analyticsdata = $countrydata->buildAnalyticsData($countrydataarr, 6, $post);
        $sortedRegionDataArr = $countrydata->sort_regions($analyticsdata["analytics"]["region"]);
        $this->assertEquals(reset($sortedRegionDataArr)['name'], "Americas");
        $this->assertEquals(reset($sortedRegionDataArr)['count'], 3);
    }

    function testSubregionSort() {
        $countrydata = New CountryData();
        $countryjson = $this->buildDataContents('name', 'united', $countrydata);
        $countrydataarr = $countrydata->countryJsonToArray($countryjson);
        $post = buildPostArr();
        $analyticsdata = $countrydata->buildAnalyticsData($countrydataarr, 6, $post);
        $sortedSubregionDataArr = $countrydata->sort_subregions($analyticsdata["analytics"]["region"]["Americas"]["subregions"]);
        $this->assertEquals(reset($sortedSubregionDataArr)['name'], "Northern America");
        $this->assertEquals(reset($sortedSubregionDataArr)['count'], 2);
    }
    // build post array to mimic $_POST array from XML Http Request
    function buildPostArr() {
        $post['search-type'] = 'name';
        $post['country-input'] = 'united';
        return $post;
    }
}

