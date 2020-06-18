function getCountries() {
    resetHtml(); // empty out html so no leftover information is displayed
    const country_input = document.getElementById('country-input').value;
    let search_type = '';
    if (document.getElementById('search-type-name').classList.contains('active')){
         search_type = 'name';
    } else {
         search_type = 'code';
    }
    
    const xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) { // http request completed successfully
            document.getElementById("loading").innerHTML = ''; // Hide the image after the response from the server
            handleResponse(this.responseText); 
        }
    }
    document.getElementById("loading").innerHTML = '<img src="/assets/spinning_globe.gif" />'; // show loading icon before sending request
    xhr.open("POST", "http://localhost:8765/api/CountryData.php");
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.send("func=getCountryData&country-input="+country_input+"&search-type="+search_type); //send form information to php to access api
}

function handleResponse(serverResponse) {
     //error handling
     if (serverResponse === 'nodata') { 
        document.getElementById('error-text').style.display = 'block';
        document.getElementById('error-text').innerHTML = 'No Results. Please double check your spelling or country code and try again.'
    } else if (serverResponse === 'noinput') {
        document.getElementById('error-text').style.display = 'block';
        document.getElementById('error-text').innerHTML = 'No input given. Please enter a country name or country code to search.'
    } else {
        // console.log(serverResponse);
        const json_response_data = JSON.parse(serverResponse); //convert server response from JSON to Javascript object
        parseCountryData(json_response_data['data'][0], json_response_data['altsp'][0]);
        parseCountryAnalytics(json_response_data['analytics'][0]);
    }
}

function parseCountryData(country_data, altsp_data) {
    const country_display = document.getElementById('country-display'); //location in html where country data will be displayed 
    let countries_info_html = { data: "<div class='row justify-content-center'>"}; 
    country_data.forEach(country => { //loop through array and display in html
        var country_altsp = altsp_data[country['name']]; //get alt spelling that triggered search result for country
        countryHTML(country, countries_info_html, country_altsp); //build HTML to display countries
    });
    countries_info_html.data += "</div>";

    country_display.innerHTML = countries_info_html.data;
}

function parseCountryAnalytics(analytics_data) {
    const region_analytics = analytics_data['region'];
    const num_results = analytics_data['num_results'];
    const search_analytics = document.getElementById('search-analytics'); //location in html where search data will be displayed
    let search_analytics_html = { data: "<div class='analytics-container'>"}; 
    // build html to display analytics
    analyticsHTML(region_analytics, search_analytics_html, num_results);
    search_analytics_html.data += "</div>"; //end analytics-container
    search_analytics.innerHTML = search_analytics_html.data;
}

function countryHTML(root, countries_info_html, country_altsp) {
    countries_info_html.data += "<div class='country-container col-lg-6'>";
    countries_info_html.data += "<div class='title-container d-flex flex-column justify-content-center'>";
    countries_info_html.data += "<span class='title '>"+root["name"]+"</span>";
    if (country_altsp != null) {
        countries_info_html.data += "<span class='country-altsp'>("+country_altsp+")</span>";
    }
    countries_info_html.data += "</div>"; //end title-container
    countries_info_html.data += "<div class='country-info-container row'>";
    countries_info_html.data += "<div class='col-sm-8'>";
    countries_info_html.data += "<div class='row'>";
    countries_info_html.data += "<div class='col-xl-6 col-lg-12 col-md-6 country-info'>";
    countries_info_html.data += "<div class='country-info-label'>Alpha Codes: </div>";
    countries_info_html.data += "<div class='country-info-text '>"+root["alpha2Code"]+" / "+root["alpha3Code"]+"</div></div>"; //end country-info
    countries_info_html.data += "<div class='col-xl-6 col-lg-12 col-md-6 country-info'>";
    countries_info_html.data += "<div class='country-info-label'>Population: </div>";
    countries_info_html.data += "<div class='country-info-text'>"+root["population"].toLocaleString()+"</div></div>"; //end country-info
    countries_info_html.data += "<div class='col-xl-6 col-lg-12 col-md-6 country-info'>";
    countries_info_html.data += "<div class='country-info-label'>Region: </div>";
    countries_info_html.data += "<div class='country-info-text'>"+root["region"]+"</div></div>"; //end country-info
    countries_info_html.data += "<div class='col-xl-6 col-lg-12 col-md-6 country-info'>";
    countries_info_html.data += "<div class='country-info-label'>Subregion: </div>";
    countries_info_html.data += "<div class='country-info-text'>"+root["subregion"]+"</div></div>"; //end country-info
    countries_info_html.data += "<div class='col-xl-6 col-lg-12 col-md-6 country-info no-bot-border'>";
    countries_info_html.data += "<div class='country-info-label'>Language(s): </div>";
    countries_info_html.data += "<div class='country-info-text'>";
    const lang_arr = Object.entries(root["languages"]);
    lang_arr.forEach(index => {
        countries_info_html.data += "<div class='lang-info'>"+index[1]["name"]+" ("+index[1]["nativeName"]+")</div>";
    })
    countries_info_html.data += "</div></div></div></div>" //end country-info-text, country-info, row, col-sm-8, country-info-container
    countries_info_html.data += "<div class='col-sm-4 country-flag'>"; 
    countries_info_html.data += "<img src="+root["flag"]+">";

    countries_info_html.data += "</div></div></div>"; //end country-flag, country-info-container ,country-container
}

function analyticsHTML(region_analytics, search_analytics_html, num_results) {
    search_analytics_html.data += "<div id='search-analytics-header'>";
    search_analytics_html.data += "<span id='search-analytics-title'>Region Analysis</span><span id='search-analytics-subtitle'>Displays regions and subregions and number of occurrences in the search.</span>"
    search_analytics_html.data += "<div id='num-results'>Countries Returned: "+num_results+"</div></div>"; //end num-results, search-analytics-header
    search_analytics_html.data += "<div id='analytics-data' class='row justify-content-center'>";
    for (var region in region_analytics) {
        search_analytics_html.data += "<div class='region col-lg-3 col-md-4 col-sm-6 col-xs-12'>";     
        search_analytics_html.data += "<div class='region-info d-flex'><div class='region-label'>"+region_analytics[region]['name']+":</div>";
        search_analytics_html.data += "<div class='region-text'>"+region_analytics[region]['count']+"</div></div>"; //end region-info
        search_analytics_html.data += "<div class='subregion-container d-flex flex-column'>";
        let subregion_counter = 1; // track index of subregion loop, start at 1 to match count
        let subregion_length = Object.keys(region_analytics[region]['subregions']).length;
        for (var subregion_num in region_analytics[region]['subregions']) {
            let subregion = region_analytics[region]['subregions'][subregion_num];
            if ((subregion != 'count') && (subregion != 'name')) { //exclude 'count' node
                if (subregion_counter == subregion_length) { // if this is the last entry, don't display the bottom border
                    search_analytics_html.data += "<div class='subregion-info no-bot-border d-flex flex-row'>";
                } else {
                    search_analytics_html.data += "<div class='subregion-info d-flex flex-row'>";
                }
                search_analytics_html.data += "<div class='subregion-label'>"+subregion['name']+":</div>";
                search_analytics_html.data += "<div class='subregion-text'>"+subregion['count']+"</div>"
                search_analytics_html.data += "</div>"; //end subregion-info
                subregion_counter ++;
            }            
        }
        search_analytics_html.data += "</div></div>" //end subregion-container, region
    };
    search_analytics_html.data += "</div>"; //end analytics-data
}

// empty out html so no leftover information is displayed
function resetHtml() {
    document.getElementById('error-text').innerHTML = ''; //reset error text
    document.getElementById('error-text').style.display = 'none'; //hide error text div
    document.getElementById('country-display').innerHTML = ''; //reset country info
    document.getElementById('search-analytics').innerHTML = ''; //reset search analytic info
}

// turn search type selection divs into responsive 'buttons'
function searchTypeButtons() {
    var search_type_btns = document.getElementById("search-type-btns");
    var btns = search_type_btns.getElementsByClassName("search-type-btn");
    for (var i = 0; i < btns.length; i++) {
        //when div is clicked, move active class to div and remove it from other div
        btns[i].addEventListener("click", function() { 
            var current = document.getElementsByClassName("active");
            current[0].className = current[0].className.replace(" active", "");
            this.className += " active";
        });
    }
}
// submit the form and kick off search process without reloading
function submitCountrySearchForm() {
    $('#country-search-form').submit(function(e) {
        getCountries();
        e.preventDefault(); //prevent page from reloading
    }); 
}

//event listeners
$( document ).ready(function() {
    submitCountrySearchForm(); //submit form and call getCountries
    searchTypeButtons(); //respond to clicks on 'name/code' divs with active class
});