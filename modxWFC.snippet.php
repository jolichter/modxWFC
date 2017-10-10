<?php
# German MODX Weather Forecast V 17.10.008
# Weather information for any location from yahoo.com
# Documentation (old): https://developer.yahoo.com/weather/documentation.html
#
# Mit PHP direkt YAHOO json-Dateien verarbeiten (via YQL-Query)
# Benötigt ein aktiviertes allow_url_fopen oder ein installiertes cURL-Modul
#
# Testlink YQL-Abfrage für Bitburg:
# https://query.yahooapis.com/v1/public/yql?q=select * from weather.forecast where woeid=639303 AND u="c"
# Eindeutige WOEID [WHERE-ON-EARTH-ID] kann z.B. über http://woeid.rosselliot.co.nz gefunden werden
#
# oder per geo.places Abfrage (evtl. nicht so sicher wie mit WOEID)
# https://query.yahooapis.com/v1/public/yql?q=select * from weather.forecast where woeid in (select woeid from geo.places(1) where text="Bitburg, de") AND u="c"
#
# Beispiel Snippet Aufruf für Bitburg:
# [[!modxWFC? &WOEID=`639303` &ICONS=`64`]] oder [[!modxWFC? &CITY=`Bitburg, de` &ICONS=`64`]]
#
#
# Variablen -Start------------------->
   $intWOEID = $modx->getOption('WOEID',$scriptProperties,'');
   $strCITY = $modx->getOption('CITY',$scriptProperties,'');
   # welche Icons benutzt werden sollen (64 oder 128)
   $strIcons = $modx->getOption('ICONS',$scriptProperties,'64');
   # Icons Bilder Pfad
   $strURLIconHome = $modx->config['base_url'].'assets/yimg/'.$strIcons.'/';

   # URL query.yahooapis.com mit WOEID
   $BASE_URL1 = 'http://query.yahooapis.com/v1/public/yql';
   $query1 = 'select * from weather.forecast where woeid='.$intWOEID.' AND u="c"';
   $strTargetURL1 = $BASE_URL1.'?q='.urlencode($query1).'&format=json';
   
   # URL query.yahooapis.com mit CITY (geo.places)
   $BASE_URL2 = 'http://query.yahooapis.com/v1/public/yql';
   $query2 = 'select * from weather.forecast where woeid in (select woeid from geo.places(1) where text="'.$strCITY.'") AND u="c"';
   $strTargetURL2 = $BASE_URL2.'?q='.urlencode($query2).'&format=json';
# Variablen -Ende-------------------<



if (empty($intWOEID) and empty($strCITY)) {
  return "missing WOEID or CITY";
}
if (!empty($intWOEID) and !empty($strCITY)) {
  return "WOEID and CITY is not possible";
}

if (!empty($intWOEID)) {
  $strTargetURL = $strTargetURL1;
} else {
  $strTargetURL = $strTargetURL2;
}


# Methode zum abholen der Daten wählen -Start------------------->
if (function_exists('curl_version')) {
   # mit cURL Daten holen
   $strArray = array('Accept-Language: '.$_SERVER["HTTP_ACCEPT_LANGUAGE"]);
   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, $strTargetURL);
   curl_setopt($ch, CURLOPT_HTTPHEADER, $strArray);
   curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
   $json = curl_exec($ch);
   $intReturnCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
   curl_close($ch);
   # prüfe ob die Seite erreichbar ist!
   if ($intReturnCode != 200 && $intReturnCode != 302 && $intReturnCode != 304) {return 'ERROR: Page not available!';};
}
else if (file_get_contents(__FILE__) && ini_get('allow_url_fopen')) {
   # mit file_get_contents Daten holen
   $json = file_get_contents($strTargetURL);
}
else
{
   return 'ERROR: cURL extension and allow_url_fopen is not available!';
}
# Methode zum abholen der Daten wählen -Ende-------------------<


# konvertiert in UTF-8
$json = utf8_encode($json); 


# PHP Objekt aus JSON konvertieren
$phpObj = json_decode($json);

# Check for valid JSON
$jsonCheck = (json_last_error() == JSON_ERROR_NONE) ? ($return_data ? $phpObj : TRUE) : FALSE;
   if($jsonCheck == FALSE)
   return false;


# Funktion Windrichtung
if (!function_exists('getWindDirection')) {
function getWindDirection($degree = 0) {
$direction = array('N', 'NNO', 'NO', 'ONO', 'O', 'OSO', 'SO', 'SSO', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW');
$step = 360 / (count($direction));
$b = floor(($degree + ($step/2)) / $step);
return $direction[$b % count($direction)];
}
}


# Assoziatives Array (Hashs) mit deutschen Wochentagen
$strWeekday_de = array('Mon' => 'Montag', 'Tue' => 'Dienstag', 'Wed' => 'Mittwoch',
'Thu' => 'Donnerstag', 'Fri' => 'Freitag', 'Sat' => 'Samstag', 'Sun' => 'Sonntag');


# Hashs mit deutschen Konditionen (Code/Description)
$strConditions_de = array(
 '0'  => 'Tornado',
 '1'  => 'Tropensturm',
 '2'  => 'Hurrikan',
 '3'  => 'schwere Gewitter',
 '4'  => 'Gewitter',
 '5'  => 'Mischregen und Schnee',
 '6'  => 'gemischter Regen und Schneeregen',
 '7'  => 'gemischter Schnee und Schneeregen',
 '8'  => 'gefrierender Nieselregen',
 '9'  => 'Nieselregen',
 '10' => 'gefrierender Regen',
 '11' => 'leichter Regen',
 '12' => 'Regen',
 '13' => 'Schneegestöber',
 '14' => 'leichte Schneeschauer',
 '15' => 'Schneeverwehungen',
 '16' => 'Schnee',
 '17' => 'Hagel',
 '18' => 'Schneeregen',
 '19' => 'Staub',
 '20' => 'neblig',
 '21' => 'Dunst',
 '22' => 'dunstig',
 '23' => 'stürmisch',
 '24' => 'windig',
 '25' => 'sehr kalt',
 '26' => 'bewölkt',
 '27' => 'überwiegend bewölkt',
 '28' => 'überwiegend bewölkt',
 '29' => 'teilweise wolkig',
 '30' => 'teilweise wolkig',
 '31' => 'klar',
 '32' => 'sonnig',
 '33' => 'heiter',
 '34' => 'heiter',
 '35' => 'Regen und Hagel',
 '36' => 'sehr heiß',
 '37' => 'Sonne und vereinzelt Gewitter',
 '38' => 'vereinzelt Gewitter',
 '39' => 'Sonne und vereinzelt Regenschauer',
 '40' => 'vereinzelt Regenschauer',
 '41' => 'starker Schnee',
 '42' => 'vereinzelt Schneefall',
 '43' => 'Schnee und Wind',
 '44' => 'teilweise wolkig',
 '45' => 'Gewitterregen',
 '46' => 'Schneeschauer',
 '47' => 'vereinzelt Gewitterschauer',
 '3200' => 'not available'
);


if($json != "") {
  $json = $phpObj;

# Platzhalter -Start------------------->
  $location = $json->query->results->channel->location;
  $modx->setPlaceholder('location', $location->city);

  $pubDate = $json->query->results->channel->item->pubDate;
  $modx->setPlaceholder('pubDate', DATE('Y-m-d G:i', STRTOTIME($pubDate)));

  $wind = $json->query->results->channel->wind;
  $modx->setPlaceholder('wind', round(floatval($wind->speed)).' km/h');
  $modx->setPlaceholder('direction', getWindDirection($wind->direction));


  $atmosphere = $json->query->results->channel->atmosphere;
  $modx->setPlaceholder('humidity', $atmosphere->humidity.' %');
  $modx->setPlaceholder('visibility', round(floatval($atmosphere->visibility)).' km');

      # Evtl. muss der Luftdruck umgerechnet werden (yahoo bug?)
      if ($atmosphere->pressure > 20000) {
         $mb = $atmosphere->pressure/32.33;
         $modx->setPlaceholder('pressure', round(floatval($mb)).' mb');
      }
      else {
         $modx->setPlaceholder('pressure', round(floatval($atmosphere->pressure)).' mb');
      }
      # Zustand des Luftdrucks (Ganzzahl: 0, 1, 2)
      if ($atmosphere->rising == 0) {
         $modx->setPlaceholder('pressTrend', 'konstant');
      } elseif ($atmosphere->rising == 1) {
         $modx->setPlaceholder('pressTrend', 'steigend');
      } else {
         $modx->setPlaceholder('pressTrend', 'fallend');
      }


  $astronomy = $json->query->results->channel->astronomy;
  $modx->setPlaceholder('sunrise', DATE('H:i', STRTOTIME($astronomy->sunrise)));
  $modx->setPlaceholder('sunset', DATE('H:i', STRTOTIME($astronomy->sunset)));


  $condition = $json->query->results->channel->item->condition;
  $modx->setPlaceholder('cond_png', $strURLIconHome.$condition->code.'.png');
  $modx->setPlaceholder('cond_time', DATE('Y-m-d G:i', STRTOTIME($condition->date)));
  $modx->setPlaceholder('cond_temp', $condition->temp.' &deg;C');

  # Wettertext Deutsch
  if (array_key_exists(trim($condition->code),$strConditions_de)) {
     $modx->setPlaceholder('cond_text', $strConditions_de[trim($condition->code)]);
  }
  else {
     $modx->setPlaceholder('cond_text', '???');
  }

  # Wettertext original (englisch)
  #$modx->setPlaceholder('cond_text', trim($condition->text));


  $forecast = $json->query->results->channel->item->forecast;
  # Vorhersage 0-9 (morgen, übermorgen ...)
  for($i=0; $i <= 9; $i++) {
      $modx->setPlaceholder('fc_png'.$i, $strURLIconHome.$forecast[$i]->code.'.png');
      $modx->setPlaceholder('fc_date'.$i, DATE('Y-m-d', STRTOTIME($forecast[$i]->date)));
      $modx->setPlaceholder('fc_day'.$i, $strWeekday_de[$forecast[$i]->day]);
      $modx->setPlaceholder('fc_temp_high'.$i, $forecast[$i]->high.' &deg;C');
      $modx->setPlaceholder('fc_temp_low'.$i, $forecast[$i]->low.' &deg;C');

      # Wettertext Deutsch
      if (array_key_exists(trim($forecast[$i]->code),$strConditions_de)) {
         $modx->setPlaceholder('fc_text'.$i, $strConditions_de[trim($forecast[$i]->code)]);
      }
      else {
         $modx->setPlaceholder('fc_text'.$i, '???');
      }

      # Wettertext original (englisch)
      #$modx->setPlaceholder('fc_text'.$i, trim($forecast[$i]->text));
  }
# Platzhalter -Ende-------------------<

} else {
  return false;
}

return;
