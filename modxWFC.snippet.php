<?php
# German MODX Weather Forecast V 17.10.007
#
# Mit PHP direkt YAHOO json-Dateien verarbeiten (ysql Abfrage)
# Benötigt ein aktiviertes allow_url_fopen oder ein installiertes cURL-Modul
#
# Testlink ysql-Abfrage für Bitburg:
# https://query.yahooapis.com/v1/public/yql?q=select * from weather.forecast where woeid=639303 AND u="c"
# Eindeutige WOEID kann z.B. über http://woeid.rosselliot.co.nz gefunden werden
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


# Hashs mit deutschen Konditionen
$strConditions_de = array( 'AM Clouds/PM Sun' => 'vormittags bewölkt / nachmittags sonnig',
'AM Drizzle' => 'vormittags Nieselregen',
'AM Drizzle/Wind' => 'vormittags Nieselregen / Wind',
'AM Fog/PM Clouds' => 'vormittags Nebel / nachmittags bewölkt',
'AM Fog/PM Sun' => 'vormittags Nebel, nachmittags sonnig',
'AM Ice' => 'vormittags Eis',
'AM Light Rain' => 'vormittags leichter Regen',
'AM Light Rain/Wind' => 'vormittags leichter Regen / Wind',
'AM Light Snow' => 'vormittags leichter Schneefall',
'AM Rain' => 'vormittags Regen',
'AM Rain/Snow Showers' => 'vormittags Regen / Schneeschauer',
'AM Rain/Snow Showers/Wind' => 'vormittags Regen / Schneeschauer / Wind',
'AM Rain/Snow' => 'vormittags Regen / Schnee',
'AM Rain/Snow/Wind' => 'vormittags Regen / Schnee / Wind',
'AM Rain/Wind' => 'vormittags Regen / Wind',
'AM Showers' => 'vormittags Schauer',
'AM Showers/Wind' => 'vormittags Schauer / Wind',
'AM Snow Showers' => 'vormittags Schneeschauer',
'AM Snow' => 'vormittags Schnee',
'AM Thundershowers' => 'vormittags Gewitterschauer',
'Blowing Snow' => 'Schneetreiben',
'Breezy' => 'föniges Wetter',
'Clear' => 'klar',
'Clear/Windy' => 'klar / windig',
'Clouds Early/Clearing Late' => 'früh Wolken / später klar',
'Cloudy' => 'bewölkt',
'Cloudy/Wind' => 'bewölkt / Wind',
'Cloudy/Windy' => 'wolkig / windig',
'Drifting Snow' => 'Schneetreiben',
'Drifting Snow/Windy' => 'Schneetreiben / windig',
'Drizzle Early' => 'früh Nieselregen',
'Drizzle Late' => 'später Nieselregen',
'Drizzle' => 'Nieselregen',
'Drizzle/Fog' => 'Nieselregen / Nebel',
'Drizzle/Wind' => 'Nieselregen / Wind',
'Drizzle/Windy' => 'Nieselregen / windig',
'Fair' => 'heiter',
'Fair/Windy' => 'heiter / windig',
'Few Showers' => 'vereinzelte Schauer',
'Few Showers/Wind' => 'vereinzelte Schauer / Wind',
'Few Snow Showers' => 'vereinzelt Schneeschauer',
'Fog Early/Clouds Late' => 'früh Nebel, später Wolken',
'Fog Late' => 'später neblig',
'Fog' => 'Nebel',
'Fog/Windy' => 'Nebel / windig',
'Foggy' => 'neblig',
'Freezing Drizzle' => 'gefrierender Nieselregen',
'Freezing Drizzle/Windy' => 'gefrierender Nieselregen / windig',
'Freezing Rain' => 'gefrierender Regen',
'Haze' => 'Dunst',
'Heavy Drizzle' => 'starker Nieselregen',
'Heavy Rain Shower' => 'starker Regenschauer',
'Heavy Rain' => 'starker Regen',
'Heavy Rain/Wind' => 'starker Regen / Wind',
'Heavy Rain/Windy' => 'starker Regen / windig',
'Heavy Snow Shower' => 'starker Schneeschauer',
'Heavy Snow' => 'starker Schneefall',
'Heavy Snow/Wind' => 'starker Schneefall / Wind',
'Heavy Thunderstorm' => 'schweres Gewitter',
'Heavy Thunderstorm/Windy' => 'schweres Gewitter / windig',
'Ice Crystals' => 'Eiskristalle',
'Ice Late' => 'später Eis',
'Isolated T-storms' => 'vereinzelte Gewitter',
'Isolated Thunderstorms' => 'vereinzelte Gewitter',
'Light Drizzle' => 'leichter Nieselregen',
'Light Freezing Drizzle' => 'leichter gefrierender Nieselregen',
'Light Freezing Rain' => 'leichter gefrierender Regen',
'Light Freezing Rain/Fog' => 'leichter gefrierender Regen / Nebel',
'Light Rain Early' => 'anfangs leichter Regen',
'Light Rain' => 'leichter Regen',
'Light Rain Late' => 'später leichter Regen',
'Light Rain Shower' => 'leichter Regenschauer',
'Light Rain Shower/Fog' => 'leichter Regenschauer / Nebel',
'Light Rain Shower/Windy' => 'leichter Regenschauer / windig',
'Light Rain with Thunder' => 'leichter Regen mit Gewitter',
'Light Rain/Fog' => 'leichter Regen / Nebel',
'Light Rain/Freezing Rain' => 'leichter Regen / gefrierender Regen',
'Light Rain/Wind Early' => 'früh leichter Regen / Wind',
'Light Rain/Wind Late' => 'später leichter Regen / Wind',
'Light Rain/Wind' => 'leichter Regen / Wind',
'Light Rain/Windy' => 'leichter Regen / windig',
'Light Sleet' => 'leichter Schneeregen',
'Light Snow Early' => 'früher leichter Schneefall',
'Light Snow Grains' => 'leichter Schneegriesel',
'Light Snow Late' => 'später leichter Schneefall',
'Light Snow Shower' => 'leichter Schneeschauer',
'Light Snow Shower/Fog' => 'leichter Schneeschauer / Nebel',
'Light Snow with Thunder' => 'leichter Schneefall mit Gewitter',
'Light Snow' => 'leichter Schneefall',
'Light Snow/Fog' => 'leichter Schneefall / Nebel',
'Light Snow/Freezing Rain' => 'leichter Schneefall / gefrierender Regen',
'Light Snow/Wind' => 'leichter Schneefall / Wind',
'Light Snow/Windy' => 'leichter Schneeschauer / windig',
'Light Snow/Windy/Fog' => 'leichter Schneefall / windig / Nebel',
'Mist' => 'Nebel',
'Mostly Clear' => 'überwiegend klar',
'Mostly Cloudy' => 'überwiegend bewölkt',
'Mostly Cloudy/Wind' => 'meist bewölkt / Wind',
'Mostly Cloudy/Windy' => 'meist bewölkt / windig',
'Mostly Sunny' => 'überwiegend sonnig',
'Partial Fog' => 'teilweise Nebel',
'Partly Cloudy' => 'teilweise bewölkt',
'Partly Cloudy/Wind' => 'teilweise bewölkt / Wind',
'Partly Cloudy/Windy' => 'teilweise bewölkt / windig',
'Patches of Fog' => 'Nebelfelder',
'Patches of Fog/Windy' => 'Nebelfelder / windig',
'PM Drizzle' => 'nachmittags Nieselregen',
'PM Fog' => 'nachmittags Nebel',
'PM Light Snow' => 'nachmittags leichter Schneefall',
'PM Light Rain' => 'nachmittags leichter Regen',
'PM Light Rain/Wind' => 'nachmittagsleichter Regen / Wind',
'PM Light Snow/Wind' => 'nachmittags leichter Schneefall / Wind',
'PM Rain' => 'nachmittags Regen',
'PM Rain/Snow Showers' => 'nachmittags Regen / Schneeschauer',
'PM Rain/Snow Showers/Wind' => 'nachmittags Regen / Schneeschauer / Wind',
'PM Rain/Snow/Wind' => 'nachmittags Regen / Schnee / Wind',
'PM Rain/Snow' => 'nachmittags Regen / Schnee',
'PM Rain/Wind' => 'nachmittags Regen / Wind',
'PM Showers' => 'nachmittags Schauer',
'PM Showers/Wind' => 'nachmittags Schauer / Wind',
'PM Snow Showers' => 'nachmittags Schneeschauer',
'PM Snow Showers/Wind' => 'nachmittags Schneeschauer / Wind',
'PM Snow' => 'nachmittags Schnee',
'PM T-storms' => 'nachmittags Gewitter',
'PM Thundershowers' => 'nachmittags Gewitterschauer',
'PM Thunderstorms' => 'nachmittags Gewitter',
'Rain and Snow' => 'Schneeregen',
'Rain and Snow/Windy' => 'Regen und Schnee / windig',
'Rain/Snow Showers/Wind' => 'Regen/Schneeschauer / Wind',
'Rain Early' => 'früh Regen',
'Rain Late' => 'später Regen',
'Rain Shower' => 'Regenschauer',
'Rain Shower/Windy' => 'Regenschauer / windig',
'Rain to Snow' => 'Regen in Schnee übergehend',
'Rain' => 'Regen',
'Rain/Snow Early' => 'früh Regen / Schnee',
'Rain/Snow Late' => 'später Regen / Schnee',
'Rain/Snow Showers Early' => 'früh Regen- Schneeschauer',
'Rain/Snow Showers Late' => 'später Regen- Schneeschnauer',
'Rain/Snow Showers' => 'Regen / Schneeschauer',
'Rain/Snow' => 'Regen / Schnee',
'Rain/Snow/Wind' => 'Regen / Schnee / Wind',
'Rain/Thunder' => 'Regen / Gewitter',
'Rain/Thunder/Wind' => 'Regen / Gewitter / Wind',
'Rain/Wind Early' => 'früh Regen / Wind',
'Rain/Wind Late' => 'später Regen / Wind',
'Rain/Wind' => 'Regen / Wind',
'Rain/Windy' => 'Regen / windig',
'Scattered Showers' => 'vereinzelte Schauer',
'Scattered Showers/Wind' => 'vereinzelte Schauer / Wind',
'Scattered Flurries' => 'vereinzelte Schneefälle',
'Scattered Snow Showers' => 'vereinzelte Schneeschauer',
'Scattered Snow Showers/Wind' => 'vereinzelte Schneeschauer / Wind',
'Scattered T-storms' => 'vereinzelte Gewitter',
'Scattered Thunderstorms' => 'vereinzelte Gewitter',
'Shallow Fog' => 'flacher Nebel',
'Showers' => 'Schauer',
'Showers Early' => 'früh Schauer',
'Showers Late' => 'später Schauer',
'Showers in the Vicinity' => 'Regenfälle in der Nähe',
'Showers/Wind' => 'Schauer / Wind',
'Showers/Wind Late' => 'Schauer / später Wind',
'Sleet and Freezing Rain' => 'Schneeregen und gefrierender Regen',
'Sleet/Windy' => 'Schneeregen / windig',
'Snow Grains' => 'Schneegriesel',
'Snow to Wintry Mix' => 'frostig und Schnee',
'Snow Late' => 'später Schnee',
'Snow Shower' => 'Schneeschauer',
'Snow Showers Early' => 'früh Schneeschauer',
'Snow Showers Late' => 'später Schneeschauer',
'Snow Showers' => 'Schneeschauer',
'Snow Showers/Wind' => 'Schneeschauer / Wind',
'Snow to Rain' => 'Schneeregen',
'Snow' => 'Schneefall',
'Snow/Wind' => 'Schneefall / Wind',
'Snow/Windy' => 'Schnee / windig',
'Squalls' => 'Böen',
'Sunny' => 'sonnig',
'Sunny/Wind' => 'sonnig / Wind',
'Sunny/Windy' => 'sonnig / windig',
'T-showers' => 'Gewitterschauer',
'Thunder in the Vicinity' => 'Gewitter in der Umgebung',
'Thunder' => 'Gewitter',
'Thundershowers Early' => 'früh Gewitterschauer',
'Thundershowers' => 'Gewitterschauer',
'Thunderstorm' => 'Gewitter',
'Thunderstorm/Windy' => 'Gewitter / windig',
'Thunderstorms Early' => 'früh Gewitter',
'Thunderstorms Late' => 'später Gewitter',
'Thunderstorms' => 'Gewitter',
'Unknown Precipitation' => 'Niederschlag',
'Unknown' => 'unbekannt',
'Wintry Mix' => 'winterlicher Mix' );


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
  if (array_key_exists(trim($condition->text),$strConditions_de)) {
     $modx->setPlaceholder('cond_text', $strConditions_de[trim($condition->text)]);
  }
  else {
     $modx->setPlaceholder('cond_text', trim($condition->text));
  }


  $forecast = $json->query->results->channel->item->forecast;
  # Vorhersage 0-9 (morgen, übermorgen ...)
  for($i=0; $i <= 9; $i++) {
      $modx->setPlaceholder('fc_png'.$i, $strURLIconHome.$forecast[$i]->code.'.png');
      $modx->setPlaceholder('fc_date'.$i, DATE('Y-m-d', STRTOTIME($forecast[$i]->date)));
      $modx->setPlaceholder('fc_day'.$i, $strWeekday_de[$forecast[$i]->day]);
      $modx->setPlaceholder('fc_temp_high'.$i, $forecast[$i]->high.' &deg;C');
      $modx->setPlaceholder('fc_temp_low'.$i, $forecast[$i]->low.' &deg;C');

      if (array_key_exists(trim($forecast[$i]->text),$strConditions_de)) {
         $modx->setPlaceholder('fc_text'.$i, $strConditions_de[trim($forecast[$i]->text)]);
      }
      else {
         $modx->setPlaceholder('fc_text'.$i, trim($forecast[$i]->text));
      }
  }
# Platzhalter -Ende-------------------<

} else {
  return false;
}

return;
