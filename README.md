# 2019-01-03 Update: Yahoo Wetter API ist nicht mehr ohne Authentifizierung verwendbar
"Important EOL Notice: As of Thursday, Jan. 3, 2019, the weather.yahooapis.com and query.yahooapis.com for Yahoo Weather API will be retired."
-> daher funktioniert dieses snippet nicht mehr!


# modxWFC is expired! Please use [dwdWeather](https://github.com/jolichter/dwdWeather)

- Is not so tragic, the Yahoo data was inaccurate or missing. The best alternative what I have found, is the charge-free weather information on the Open Data server of Deutschen Wetterdienst (dwd.de).

- Nichts tragisches, Daten waren nicht präzise und teilweise sogar fehlerhaft. Die beste Alternative die ich gefunden habe, sind die entgeltfreie Wetterinformationen auf dem Open Data-Server vom Deutschen Wetterdienst (dwd.de).

---

### modxWFC
GERMAN Yahoo-Weather forecast by ysql-Query for MODX

### Example Call:

Get the weather for Bitburg with WOEID

```[[!modxWFC? &WOEID=`639303` &ICONS=`128`]]```

or with CITY name

```[[!modxWFC? &CITY=`Bitburg, de` &ICONS=`128`]]```
