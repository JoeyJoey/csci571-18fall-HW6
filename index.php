<?php
include 'geoHash.php';
$apikey = "7BIMOVCbJ8y0yMADW7qRN81akEUoxpmG";
if($_SERVER['REQUEST_METHOD']=="POST") {
    if ($_POST['from'] == 'here') {
        $lon_data= $_POST['longitude'];
        $lat_data = $_POST['latitude'];
    }
    if($_POST['from'] == 'on'){
        $locationTxt = $_POST['locationTxt'];
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=$locationTxt&key=AIzaSyDyF9IJniFyk2pi6C9_eCUOtc255458hJo";
        $url = preg_replace("/ /", "%20", $url);
        $responseGeo = file_get_contents($url);
        if ($responseGeo != false) {
            $geoArray = json_decode($responseGeo, true);
            if ($geoArray['status'] == 'OK') {
                $lat_data= $geoArray['results'][0]['geometry']['location']['lat'];
                $lon_data = $geoArray['results'][0]['geometry']['location']['lng'];
            }
        }
    }
    $geoPoint = encode($lat_data, $lon_data);
    if (isset($_POST['distance']) && $_POST['distance'] != "") {
        $radius = $_POST['distance'];
    } else {
        $radius = 10;
    }
    $category = $_POST['category'];
    switch ($category) {
        case 'Default':
            $segmentId = "";
            break;
        case 'Music':
            $segmentId = "KZFzniwnSyZfZ7v7nJ";
            break;
        case 'Sports':
            $segmentId = "KZFzniwnSyZfZ7v7nE";
            break;
        case 'Arts & Theatre':
            $segmentId = "KZFzniwnSyZfZ7v7na";
            break;
        case 'Film':
            $segmentId = "KZFzniwnSyZfZ7v7nn";
            break;
        case 'Miscellaneous':
            $segmentId = "KZFzniwnSyZfZ7v7n1";
            break;
    }
    $keyword = $_POST['keyWord'];

    $url = "https://app.ticketmaster.com/discovery/v2/events.json?apikey=$apikey&keyword=$keyword&segmentId=$segmentId&radius=$radius&unit=miles&geoPoint=$geoPoint";
    $url = preg_replace("/ /", "%20", $url);

    $response_ticket = file_get_contents($url);
    $eventArray = null;
    if($response_ticket != false){
        $jsonArray = json_decode($response_ticket, true);
        if (isset($jsonArray['_embedded'])) {
            $eventArray = $jsonArray['_embedded']['events'];
            $latlng = ["lat" => $lat_data, "lng" => $lon_data];
            array_push($eventArray,$latlng);
        }
    }
    echo json_encode($eventArray);
}
else if(isset($_GET["eventID"])){
        $eventID = $_GET['eventID'];
        $url = "https://app.ticketmaster.com/discovery/v2/events/$eventID.json?apikey=$apikey";
        $response_detail = file_get_contents($url);
        echo $response_detail;
}
else if(isset($_GET['venueName'])){
    $venueName = $_GET['venueName'];
    $url = "https://app.ticketmaster.com/discovery/v2/venues?apikey=$apikey&keyword=$venueName";
    $url = preg_replace("/ /", "%20", $url);
    $response_venue = file_get_contents($url);
    echo $response_venue;
}
else{
?>

<!DOCTYPE html>
<html>
<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <script async defer
            src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDyF9IJniFyk2pi6C9_eCUOtc255458hJo">
    </script>
    <script type="text/javascript" >
        show = false;
        function clearAll() {
            document.getElementById('searchForm').reset();
            cleanResult('searchResult');
            document.getElementById('mapDiv').style.display = "none";

        }

        function cleanResult(eleName){
          var ele = document.getElementById(eleName);
          while(ele.hasChildNodes()){
              ele.removeChild(ele.firstChild);
          }
        }

        function getGeo() {
            var url =  "http://ip-api.com/json";

            var ipRequest = new XMLHttpRequest();
            try{
                ipRequest.open("GET",url,false);
                ipRequest.send();
            }catch(e){
                alert(e);
            }
            var lat,lon;
            if(ipRequest.readyState === 4 && ipRequest.status === 200){
                var geoJson = ipRequest.responseText;
                try{
                    var jsonObject = JSON.parse(geoJson);
                }catch(e){
                    alert(e);
                }
                lat = jsonObject.lat;
                lon = jsonObject.lon;
            }
            else{
                 lat = 34.0266;
                 lon = -118.2831;
            }
            var searchButton = document.getElementsByName('searchButton')[0];
            searchButton.removeAttribute('disabled');

            var oriForm = document.getElementById('searchForm');
            var newInput1 = document.createElement("input");
            newInput1.setAttribute("type","hidden");
            newInput1.setAttribute("name","longitude");
            newInput1.setAttribute("value",lon);
            oriForm.appendChild(newInput1);

            var newInput2 = document.createElement("input");
            newInput2.setAttribute("type","hidden");
            newInput2.setAttribute("name","latitude");
            newInput2.setAttribute("value",lat);
            oriForm.appendChild(newInput2);
        }

        function sendData(paraForm) {
            var url = paraForm.action;
            var paramData = "";
            var formData = new FormData(paraForm);
            for (const element of formData){
             paramData += element[0] + "=" + encodeURIComponent(element[1]) + "&";
            }
            paramData = paramData.slice(0,-1);

            var sendRequest = new XMLHttpRequest();
            sendRequest.open("POST",url,false);
            sendRequest.setRequestHeader("Content-type","application/x-www-form-urlencoded");
            sendRequest.send(paramData);

            if(sendRequest.readyState === 4 && sendRequest.status === 200){
                try{
                    var eventArray = JSON.parse(sendRequest.responseText);
                }catch(e){
                    alert(e);
                }
                if(eventArray !== null){
                    startPoint = eventArray[eventArray.length-1];
                }
                showSearchTable(eventArray);
            }
        }

        function showSearchTable(eventArray) {

            cleanResult('searchResult');
            var table = document.createElement("table");
            table.id = "searchTable";
            var th = table.insertRow();
            th.style.fontWeight="bold";
            if(eventArray == null){
                var th_td =th.insertCell();
                th_td.style.width = "700px";
                th_td.style.backgroundColor = "RGB(241,241,241)";
                th_td.innerHTML = "No Records has been found";

            }
            else{
                var th_td1 = th.insertCell();
                var th_td2 = th.insertCell();
                var th_td3 = th.insertCell();
                var th_td4 = th.insertCell();
                var th_td5 = th.insertCell();
                th_td1.innerHTML ="Date";
                th_td2.innerHTML ="Icon";
                th_td3.innerHTML ="Event";
                th_td4.innerHTML ="Genre";
                th_td5.innerHTML ="Venue";
                for( var i=0;i< eventArray.length-1;i++ ) {
                    var singleEvent = eventArray[i];
                    var tr = table.insertRow();
                    var td1 = tr.insertCell();
                    var tmpStr = "N/A";
                    if(singleEvent.hasOwnProperty('dates') && singleEvent.dates.hasOwnProperty('start')){
                        if(singleEvent.dates.start.hasOwnProperty('localDate') && singleEvent.dates.start.localDate!=="" && singleEvent.dates.start.localDate!=="Undefined" ){
                            tmpStr = singleEvent.dates.start.localDate;
                        }
                        if(singleEvent.dates.start.hasOwnProperty('localTime')){
                            if(singleEvent.dates.start.localTime !== 'Undefined' && singleEvent.dates.start.localTime !== ""){
                                tmpStr += " " + singleEvent.dates.start.localTime;
                            }
                        }
                    }
                    td1.innerHTML = tmpStr;

                    var td2 = tr.insertCell();
                    if(singleEvent.hasOwnProperty('images') && singleEvent.images.length !== 0){
                        var img = document.createElement("img");
                        img.id = "resultCellImg";
                        img.src = singleEvent.images[0].url;
                        td2.appendChild(img);
                    }
                    else{
                        td2.innerHTML = "";
                    }

                    var td3 = tr.insertCell();
                    td3.id = i;
                    td3.innerHTML = "N/A";
                    if(singleEvent.hasOwnProperty('name') && singleEvent.name !== ""){
                        td3.innerHTML = singleEvent.name;
                        td3.className = "changeCell";
                    }

                    var td4 = tr.insertCell();
                    td4.innerHTML = "N/A";
                    if(singleEvent.hasOwnProperty('classifications') && singleEvent.classifications.length !==0){
                        if(singleEvent.classifications[0].hasOwnProperty('segment') && singleEvent.classifications[0].segment.hasOwnProperty('name')){
                            td4.innerHTML = singleEvent.classifications[0].segment.name;
                        }
                    }

                    var td5 = tr.insertCell();
                    td5.innerHTML = "N/A";
                    if(singleEvent.hasOwnProperty('_embedded') && singleEvent._embedded.length !== 0){
                        if(singleEvent._embedded.hasOwnProperty('venues') && singleEvent._embedded.venues.length !== 0){
                           if(singleEvent._embedded.venues[0].hasOwnProperty('name') && singleEvent._embedded.venues[0].name !== ""){
                               td5.innerHTML = singleEvent._embedded.venues[0].name;
                               td5.id = i+100;
                               td5.className = "changeCell";
                           }
                        }
                    }
                }
            }
            document.getElementById('searchResult').appendChild(table);
            if(eventArray !== null){
                for(i=1;i<table.rows.length;i++){
                    var currentRow = table.rows.item(i);
                    var eventCell = currentRow.cells.item(2);
                    var venueCell  = currentRow.cells.item(4);
                    if(eventCell.innerHTML !== "N/A"){
                        eventCell.addEventListener('click', function(){
                            callDetail(eventArray[this.id].id);
                        });
                    }
                    if(venueCell.innerHTML !== "N/A"){
                        var lat = eventArray[venueCell.id-100]._embedded.venues[0].location.latitude;
                        var lon = eventArray[venueCell.id-100]._embedded.venues[0].location.longitude;
                        venueCell.addEventListener('click',function(){
                            venueCellAct(lat,lon,this.id);
                        });
                    }
                }
            }
        }
        function venueCellAct(lat,lon,cellID){
            if(show === false){
                popMap(lat,lon,cellID);
            }else{
                document.getElementById('mapDiv').style.display ='none';
                show = false;
            }
        }

        function callDetail(eventID) {
            var url = document.getElementById('searchForm').action+"?eventID="+eventID;
            var req = new XMLHttpRequest();
            req.open("GET",url,false);
            req.send();
            if(req.readyState === 4 && req.status === 200){
                try{
                    var detailObj = JSON.parse(req.responseText);
                }catch(e){
                    alert(e);
                }
                showDetail(detailObj);
            }
        }

        function showDetail(detailObj){
            cleanResult('searchResult');
            var flag = false;
            var searchResult = document.getElementById("searchResult");
            var name = document.createElement('h2');
            name.innerHTML = detailObj.name;
            searchResult.appendChild(name);

            var table = document.createElement('table');
            table.style.margin = "auto";
            table.style.borderWidth = "0px";
            var row1 = table.insertRow();

            var wordArea = document.createElement('div');
            wordArea.id = "word";

            var dateStr = "";
            if(detailObj.hasOwnProperty('dates') && detailObj.dates.hasOwnProperty('start')){
                if(detailObj.dates.start.hasOwnProperty('localDate') && detailObj.dates.start.localDate!=="" && detailObj.dates.start.localDate!=="Undefined" ){
                    dateStr = detailObj.dates.start.localDate;
                }
                if(detailObj.dates.start.hasOwnProperty('localTime')){
                    if(detailObj.dates.start.localTime !== 'Undefined' && detailObj.dates.start.localTime !== ""){
                        dateStr += " " + detailObj.dates.start.localTime;
                    }
                }
            }
            if(dateStr !== ""){
                var dateTitle = document.createElement('p');
                dateTitle.className = "title";
                dateTitle.innerHTML ="Date"+"\n";
                var dateDes = document.createElement('p');
                dateDes.className = "content";
                dateDes.innerHTML = dateStr;
                wordArea.appendChild(dateTitle);
                wordArea.appendChild(dateDes);
            }

            if(detailObj._embedded.hasOwnProperty('attractions') && detailObj._embedded.attractions.length !== 0){
                var artistTitle = document.createElement('p');
                artistTitle.className = "title";
                artistTitle.innerHTML = "Artist/Team";
                var artistDes = document.createElement('p');
                artistDes.className = "content";
                for(var i = 0;i<detailObj._embedded.attractions.length;i++){
                    tmpObj = detailObj._embedded.attractions[i];
                    if(tmpObj.hasOwnProperty('name') && tmpObj.name !== 'Undefined'){
                        var a = document.createElement('A');
                        a.innerHTML = tmpObj.name;
                        a.href = tmpObj.url;
                        a.target = "_blank";
                        artistDes.appendChild(a);
                    }
                    if(i !== detailObj._embedded.attractions.length -1){
                        artistDes.innerHTML +="|";
                    }
                }
                wordArea.appendChild(artistTitle);
                wordArea.appendChild(artistDes);
            }

            if(detailObj.hasOwnProperty('_embedded')){
                if(detailObj._embedded.hasOwnProperty('venues') && detailObj._embedded.venues.length !== 0){
                    if(detailObj._embedded.venues[0].hasOwnProperty('name') && detailObj._embedded.venues[0].name !== 'Undefined'){
                        flag = true;
                        var venueTitle = document.createElement('p');
                        venueTitle.className = "title";
                        venueTitle.innerHTML = "Venue";
                        var venueDes = document.createElement('p');
                        venueDes.className = "content";
                        venueDes.innerHTML = detailObj._embedded.venues[0].name;
                        wordArea.appendChild(venueTitle);
                        wordArea.appendChild(venueDes);
                    }
                }
            }

            if(detailObj.hasOwnProperty('classifications')&& detailObj.classifications.length !== 0){
                var genresTitle = document.createElement('p');
                genresTitle.className = "title";
                genresTitle.innerHTML = "Genres";

                var genresObj = detailObj.classifications[0];
                var genresDes = document.createElement('p');
                genresDes.className = "content";
                if(genresObj.hasOwnProperty('segment') && genresObj.segment.name !== 'Undefined'){
                    genresDes.innerHTML += genresObj.segment.name;
                }
                if(genresObj.hasOwnProperty('genre') && genresObj.genre.name !== 'Undefined'){
                    genresDes.innerHTML += " | "+genresObj.genre.name;
                }
                if(genresObj.hasOwnProperty('subGenre') && genresObj.subGenre.name !== 'Undefined'){
                    genresDes.innerHTML += " | "+genresObj.subGenre.name;
                }
                if(genresObj.hasOwnProperty('type') && genresObj.type.name !== 'Undefined'){
                    genresDes.innerHTML += " | "+genresObj.type.name;
                }
                if(genresObj.hasOwnProperty('subType')&& genresObj.subType.name !== 'Undefined'){
                    genresDes.innerHTML += " | "+genresObj.subType.name;
                }
                wordArea.appendChild(genresTitle);
                wordArea.appendChild(genresDes);
            }

            if(detailObj.hasOwnProperty('priceRanges')&& detailObj.priceRanges.length !== 0 ){
                var priceStr ="";
                if(detailObj.priceRanges[0].hasOwnProperty('min')&& detailObj.priceRanges[0].min !== 'Undefined' ){
                    priceStr += detailObj.priceRanges[0].min;
                }
                if(detailObj.priceRanges[0].hasOwnProperty('max')&& detailObj.priceRanges[0].max !== 'Undefined'){
                    if(priceStr !== ""){
                        priceStr += " - "+detailObj.priceRanges[0].max;
                    }else{
                        priceStr +=detailObj.priceRanges[0].max;
                    }
                }
                if(detailObj.priceRanges[0].hasOwnProperty('currency')&& detailObj.priceRanges[0].currency !== 'Undefined'){
                    if(priceStr !== ""){
                        priceStr += "  "+detailObj.priceRanges[0].currency;
                    }
                }
                if(priceStr !== ""){
                    var priceTitle = document.createElement('p');
                    priceTitle.className = "title";
                    priceTitle.innerHTML = "Price Range";
                    var priceDes = document.createElement('p');
                    priceDes.className = "content";
                    priceDes.innerHTML = priceStr;
                }
                wordArea.appendChild(priceTitle);
                wordArea.appendChild(priceDes);
            }
            if(detailObj.dates.hasOwnProperty('status')){
                if(detailObj.dates.status.hasOwnProperty('code')&& detailObj.dates.status.code !== 'Undefined'){
                    var statusTitle = document.createElement('p');
                    statusTitle.className = "title";
                    statusTitle.innerHTML = "Ticket Status";
                    var statusDes = document.createElement('p');
                    statusDes.className = "content";
                    statusDes.innerHTML = detailObj.dates.status.code;
                    wordArea.appendChild(statusTitle);
                    wordArea.appendChild(statusDes);
                }
            }
            if(detailObj.hasOwnProperty('url')){
                if(detailObj.url !== 'Undefined'){
                    var buyTitle = document.createElement('p');
                    buyTitle.className = "title";
                    buyTitle.innerHTML = "Buy Ticket At";
                    var buyDes = document.createElement('p');
                    buyDes.className = "content";
                    var a = document.createElement('A');
                    a.innerHTML = "Ticketmaster";
                    a.href = detailObj.url;
                    a.target = "_blank";
                    buyDes.appendChild(a);
                    wordArea.appendChild(buyTitle);
                    wordArea.appendChild(buyDes);
                }
            }
            var cell1 = row1.insertCell();
            cell1.style.borderWidth = "0px";
            cell1.appendChild(wordArea) ;

            if(detailObj.hasOwnProperty('seatmap') && detailObj.seatmap.hasOwnProperty('staticUrl')){
                if(detailObj.seatmap.staticUrl !== 'Undefined' && detailObj.seatmap.staticUrl !== "" ){
                    var seatImg = document.createElement('img');
                    seatImg.src = detailObj.seatmap.staticUrl;
                    seatImg.id = "seatCellImg";
                    var cell2 = row1.insertCell();
                    cell2.style.borderWidth ="0px";
                    cell2.appendChild(seatImg);
                }
            }
            searchResult.appendChild(table);

            if(flag === true){
                var venueName = detailObj._embedded.venues[0].name;
                venueDetail(venueName);
            }else{
                venueDetail(null);
            }
        }

        function venueDetail(venueName) {
            venueReq = new XMLHttpRequest();
            url = document.getElementById('searchForm').action + "?venueName=" + venueName;
            venueReq.open("GET", url, false);
            venueReq.send();
            if (venueReq.readyState === 4 && venueReq.status === 200) {

                var venueObj = JSON.parse(venueReq.responseText);
                var venueInfo = venueObj._embedded.venues[0];

                var arrowMapTitle = document.createElement('p');
                arrowMapTitle.innerHTML = "click to show venue info";
                arrowMapTitle.className = "arrow_p";
                var arrowMap = document.createElement('DIV');
                arrowMap.className = "arrow_down";

                var arrowPhotoTitle = document.createElement('p');
                arrowPhotoTitle.innerHTML = "click to show venue photos";
                arrowPhotoTitle.className = "arrow_p";
                var arrowPhoto = document.createElement('DIV');
                arrowPhoto.className = "arrow_down";
                document.getElementById('searchResult').appendChild(arrowMapTitle);
                document.getElementById('searchResult').appendChild(arrowMap);

                var venueTable = createVenueTable(venueInfo);
                document.getElementById('searchResult').appendChild(venueTable);
                if(venueInfo !== null && venueInfo.hasOwnProperty('location')){
                    initMap(venueInfo.location.latitude,venueInfo.location.longitude,'detailMap');
                }
                document.getElementById('searchResult').appendChild(arrowPhotoTitle);
                document.getElementById('searchResult').appendChild(arrowPhoto);
                var photoTable = createPhotoTable(venueInfo);
                document.getElementById('searchResult').appendChild(photoTable);
                
                arrowMap.onclick = function () {
                    if(venueTable.style.display === "none"){
                        if(photoTable.style.display === 'block'){
                            arrowPhoto.className = "arrow_down";
                            photoTable.style.display = "none";
                            arrowPhotoTitle.innerHTML = "click to show venue photos";
                        }
                        arrowMap.className = "arrow_up";
                        venueTable.style.display = "block";
                        arrowMapTitle.innerHTML = "click to hide venue info";
                    }else{
                        arrowMap.className = "arrow_down";
                        venueTable.style.display = "none";
                        arrowMapTitle.innerHTML = "click to show venue info";
                    }
                }
                arrowPhoto.onclick = function () {
                    if(photoTable.style.display === "none"){
                        if(venueTable.style.display === 'block'){
                            arrowMap.className = "arrow_down";
                            venueTable.style.display = "none";
                            arrowMapTitle.innerHTML = "click to show venue info";
                        }
                        arrowPhoto.className = "arrow_up";
                        photoTable.style.display = "block";
                        arrowPhotoTitle.innerHTML = "click to hide venue photos";
                    }else{
                        arrowPhoto.className = "arrow_down";
                        photoTable.style.display = "none";
                        arrowPhotoTitle.innerHTML = "click to show venue photos";
                    }
                }
            }
        }
        function createVenueTable(venueInfo){
            var venueTable = document.createElement('table');
            if(venueInfo === null || venueInfo.length === 0){
                venueTable.style.width = "800px";
                var tr = venueTable.insertRow();
                var td = tr.insertCell();
                td.style.fontWeight = "bold";
                td.innerHTML = "No Venue Info Found";
                td.style.width = "800px";

            }else{
                venueTable.style.maxWidth = "800px";
               // venueTable.style.margin = "auto";
                var tr1 = venueTable.insertRow();
                var tr1_td1 = tr1.insertCell();
                tr1_td1.style.fontWeight = "bold";
                tr1_td1.innerHTML = "Name";
                var tr1_td2 = tr1.insertCell();
                tr1_td2.innerHTML = venueInfo.name;

                var tr2 = venueTable.insertRow();
                var tr2_td1 = tr2.insertCell();
                tr2_td1.innerHTML = "Map";
                tr2_td1.style.fontWeight = "bold";
                var tr2_td2 = tr2.insertCell();
                tr2_td2.className = "mapCell";
                if(venueInfo.hasOwnProperty('location')){
                    if(venueInfo.location.hasOwnProperty('latitude') && venueInfo.location.hasOwnProperty('longitude')){
                        var mapDiv = document.createElement('DIV');
                        mapDiv.className = "map";
                        mapDiv.style.display = "block";
                        mapDiv.id = 'detailMap';
                        mapDiv.style.left = "150px";
                        mapDiv.style.top ="10px";
                        tr2_td2.appendChild(mapDiv);

                        var walkButton =document.createElement("Button");
                        walkButton.className ="mapButton";
                        walkButton.innerText = "Walk There";
                        walkButton.style.top ="40px";
                        walkButton.style.left = "30px";

                        var bikeButton =document.createElement("Button");
                        bikeButton.className ="mapButton";
                        bikeButton.innerText = "Bike There";
                        bikeButton.style.top ="60px";
                        bikeButton.style.left = "30px";

                        var driveButton =document.createElement("Button");
                        driveButton.className ="mapButton";
                        driveButton.innerText = "Drive There";
                        driveButton.style.top = "80px";
                        driveButton.style.left = "30px";

                        tr2_td2.appendChild(walkButton);
                        tr2_td2.appendChild(bikeButton);
                        tr2_td2.appendChild(driveButton);
                    }else {
                        tr2_td2.innerHTML = "N/A";
                    }
                }else{
                    tr2_td2.innerHTML = "N/A";
                }

                var tr3 = venueTable.insertRow();
                var tr3_td1 = tr3.insertCell();
                tr3_td1.innerHTML = "Address";
                tr3_td1.style.fontWeight = "bold";
                var tr3_td2 = tr3.insertCell();
                if(venueInfo.hasOwnProperty('address')){
                    if(venueInfo.address.hasOwnProperty('line1') && venueInfo.address.line1 !=="Undefined" && venueInfo.address.line1 !==""){
                        tr3_td2.innerHTML =venueInfo.address.line1;
                    }else{
                        tr3_td2.innerHTML = "N/A";
                    }
                }else {
                    tr3_td2.innerHTML = "N/A";
                }

                var tr4 = venueTable.insertRow();
                var tr4_td1 = tr4.insertCell();
                tr4_td1.innerHTML = "City";
                tr4_td1.style.fontWeight = "bold";
                var tr4_td2 = tr4.insertCell();
                var tmpStr = "";
                if(venueInfo.hasOwnProperty('city')){
                    if(venueInfo.city.hasOwnProperty('name') && venueInfo.city.name !=="Undefined" && venueInfo.city.name !==""){
                        tmpStr += venueInfo.city.name;
                    }
                }
                if(venueInfo.hasOwnProperty('state')){
                    if(venueInfo.state.hasOwnProperty('stateCode') && venueInfo.state.stateCode !=="Undefined" && venueInfo.state.stateCode!==""){
                        if(tmpStr !== ""){
                            tmpStr += ", ";
                        }
                        tmpStr += venueInfo.state.stateCode;
                    }
                }
                if(tmpStr !== ""){
                    tr4_td2.innerHTML = tmpStr;
                }else {
                    tr4_td2.innerHTML = "N/A";
                }

                var tr5 = venueTable.insertRow();
                var tr5_td1 = tr5.insertCell();
                tr5_td1.innerHTML = "Postal Code";
                tr5_td1.style.fontWeight = "bold";
                var tr5_td2 = tr5.insertCell();
                if(venueInfo.hasOwnProperty('postalCode') && venueInfo.postalCode !== "" && venueInfo.postalCode !== "Undefined"){
                    tr5_td2.innerHTML = venueInfo.postalCode;
                }else {
                    tr5_td2.innerHTML = "N/A";
                }

                var tr6 = venueTable.insertRow();
                var tr6_td1 = tr6.insertCell();
                tr6_td1.innerHTML = "Upcoming Events";
                tr6_td1.style.fontWeight = "bold";
                var tr6_td2 = tr6.insertCell();
                if(venueInfo.hasOwnProperty('url') && venueInfo.url !=="" && venueInfo.url !== ""){
                    var a = document.createElement('A');
                    a.href = venueInfo.url;
                    a.innerHTML = venueInfo.name +" Tickets";
                    a.target = "_blank";
                    tr6_td2.appendChild(a);
                }else{
                    tr6_td2 = "N/A";
                }
            }
            venueTable.style.display = "none";
            return venueTable;
        }
        function createPhotoTable(venueInfo){
            var photoTable = document.createElement("table");
            photoTable.style.width = "1010px";
            if(venueInfo !== null && venueInfo.hasOwnProperty('images') && venueInfo.images.length !== 0){
                var imageArray = venueInfo.images;
                for (var i = 0; i < imageArray.length; i++) {
                    var tr = photoTable.insertRow();
                    var td = tr.insertCell();
                    var img = document.createElement("img");
                    img.src = imageArray[i].url;
                    img.style.maxWidth = "1000px";
                    td.appendChild(img);
                }
            }else{
                var tr = photoTable.insertRow();
                var td = tr.insertCell();
                td.innerHTML = "No Venue Photos Found";
                td.style.fontWeight = "bold";
                td.style.width = "1000px";
            }
            photoTable.style.display = "none";
            return photoTable;
        }

        function initMap(latitude,longitude,mapID){
            var directionsService = new google.maps.DirectionsService();
            var directionsDisplay = new google.maps.DirectionsRenderer();

            var latData = Number(latitude);
            var lonData = Number(longitude);
            var site = {lat:latData, lng:lonData};
            var mapDiv = document.getElementById(mapID);

            var siteMap = new google.maps.Map(mapDiv, {zoom:13,center:site} );
            marker = new google.maps.Marker({position: site, map: siteMap});
            directionsDisplay.setMap(siteMap);

            document.getElementsByClassName('mapButton')[0].onclick = function () {
                marker.setMap(null);
                renderRoute(directionsService,directionsDisplay,site,'WALKING');
            };
            document.getElementsByClassName('mapButton')[1].onclick = function () {
                marker.setMap(null);
                renderRoute(directionsService,directionsDisplay,site,'BICYCLING');
            };
            document.getElementsByClassName('mapButton')[2].onclick = function () {
                marker.setMap(null);
                renderRoute(directionsService,directionsDisplay,site,'DRIVING');
            };
        }
        function renderRoute(directionsService,directionsDisplay,site,buttonName){
            var start = new google.maps.LatLng(startPoint.lat,startPoint.lng);
            if(document.getElementsByName('from'))
            var request = {
                    origin:start,
                    destination:site,
                    travelMode : buttonName
                };
            directionsService.route(request,function(result,status){
                if(status === 'OK'){
                    directionsDisplay.setDirections(result);
                }
            });
        }

        function popMap(latitude,longitude,cellID){
            var cell = document.getElementById(cellID);
            var rect = cell.getBoundingClientRect();
            var mapDiv = document.getElementById('mapDiv');
            mapDiv.style.top = rect.top+window.pageYOffset+50+"px";
            mapDiv.style.left = rect.left-50+"px";
            mapDiv.style.position = "absolute";
            initMap(latitude,longitude,'resultMap');
            mapDiv.style.display ="block";
            show = true;
        }

        function activeLocation(){
            var locationInput = document.getElementsByName('locationTxt')[0];
            locationInput.removeAttribute('disabled');
        }
        function disableLocation(){
            var locationInput = document.getElementsByName('locationTxt')[0];
            locationInput.disabled = true;
        }

    </script>

    <style type="text/css">
        body{
            margin:auto;
        }
        #searchArea{
            margin:auto;
            background-color:rgb(249,249,249);
            margin-top: 50px;
            height:200px;
            width:500px;
            border-width:3px;
            border-style:solid;
            border-color:rgb(215,215,215);
            padding:5px;
        }
        #searchArea hr{
            border:0px;
            height:3px;
            background: rgb(215,215,215);
        }
        #searchArea h2{
            text-align: center;
            font-size: 24px;
            font-style:italic;
            font-weight: bold;
            margin:0;
            padding:0;
        }
        #searchForm{
            padding:10px;
        }
        #searchForm label{
            font-weight:bold;
        }
        #searchResult{
            margin:auto;
            margin-top: 20px;
            text-align: center;
            width:1200px;
        }
        #searchResult table{
            border-collapse:collapse;
            border: solid rgb(219,219,219);
            border-width:1px 0px 0px 1px;

            margin:auto;
            margin-top:5px;
            max-width: 1100px;
        }
        #searchResult table td{
            padding:5px;
            text-align: center;
            border:solid rgb(219,219,219);
            border-width: 0px 1px 1px 0px;

        }
        #searchResult table th {
            font-weight: bold;
        }
        #resultCellImg{
            width:100px;
            height:60px;
        }
        #searchResult table .changeCell:hover{
            color:rgb(195,195,195);
        }
        #searchResult h2{
            font-weight:bold;
        }
        #word{
            max-width:400px;
            text-align: left;
            line-height: 10px;
        }
        #word .title{
            font-weight: 800;
        }
        #word .content{
            font-weight:500;
            font-size: 14px;
        }
        a{
            text-decoration: none;
            color:black;
        }
        a:hover{
           color: rgb(195,195,195);
        }
        #seatCellImg{
            max-width: 450px;
            padding-left: 20px;
        }
        .arrow_p{
            margin: auto;
            font-size: 14px;
            color:rgb(195,195,195);
        }
        .arrow_up{
            height: 32px;
            width: 50px;
            margin:auto;
            background-image: url(http://csci571.com/hw/hw6/images/arrow_up.png);
            background-repeat: no-repeat;
            background-size: 100%;
            padding-bottom: 10px;

        }
        .arrow_down{
            height: 32px;
            width: 50px;
            margin:auto;
            background-image: url("http://csci571.com/hw/hw6/images/arrow_down.png");
            background-repeat: no-repeat;
            background-size: 100%;
            padding-bottom: 10px;
        }
        #mapDiv{
            height: 300px;
            width:400px;
            z-index:1;
            display: none;
        }
        .buttonList{
            z-index:5;
            top:0px;
            left:0px;
            position:absolute;
        }
        .map{
            height: 300px;
            width:400px;
            z-index:2;
            top:0px;
            left:0px;
        }
        .mapButton{
            position:absolute;
            background-color: rgb(232,232,232);
            font-size: 16px;
            height:20px;
            width:90px;
            margin: 0;
            padding: 0;
            outline:none;
            border:none;
        }
        .mapButton:hover{
            color: rgb(143,143,143);
        }
        .mapCell{
            position:relative;
            height:320px;
            width:660px;
        }
    </style>
</head>

<body onload="getGeo()">

<div id="searchArea" >
    <h2>Events Search</h2><hr>
    <form id="searchForm" method="post" action="index.php" onsubmit="sendData(this);event.preventDefault();event.stopPropagation()">
        <label>KeyWord  <input type="text" name="keyWord" required /></label><br/>
        <label>Category  <select name="category">
                <option value="Default" selected>Default</option>
                <option value="Music">Music</option>
                <option value="Sports">Sports</option>
                <option value="Arts&Theatre">Arts&Theatre</option>
                <option value="Film">Film</option>
                <option value="Miscellanenous">Miscellaneous</option>
            </select>
        </label> <br/>
        <label>Distance(miles) <input type="text" name="distance" placeholder=10 oninput = "value = value.replace(/[^\d]/g,'')"/>
            from <input type="radio" checked="checked" name="from" value="here" onclick="disableLocation()"/>Here<br/>
            <input style="margin-left:270px" type="radio" name="from" value="on" onclick="activeLocation()"/><input type="text" name="locationTxt" placeholder="location" disabled required />
        </label><br/>
        <label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <input type="submit" name="searchButton" value="Search" disabled="disabled"/>
            <input type="button" name="clear" value="Clear" onclick="clearAll()" /></label>
    </form>
</div>

<div id="searchResult">
</div>
<div id = 'mapDiv'>
    <div class = 'buttonList'>
        <button class = 'mapButton' style="top:0px">Walk There</button>
        <button class = 'mapButton' style="top:20px">Bike There</button>
        <button class = 'mapButton' style="top:40px">Drive There</button>
    </div>
    <div class ='map' id ='resultMap'></div>
</div>


</body>
</html>
<?php
}
?>