"""
Notify as soon as a country is not blocked anymore
from sending packages to with postal service Austria
due to COVID-19.
"""

import sys
import re
import urllib.request
import urllib.parse
import urllib.error

if len(sys.argv) < 2:
    print('Supply country name in German as first argument (case-insensitive).')
if len(sys.argv) < 3:
    print('Supply type (Brief|Paket) as second argument (case-sensitive).')
    quit()

country = sys.argv[1]
type = sys.argv[2]
web_url = 'https://www.post.at/p/c/liefereinschraenkungen-coronavirus'
user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.47 Safari/537.36'
csv_url_regex = r'(https://assets.post.at/-/media/Dokumente/Corona/([0-9]{8})-Annahmestopp-' + type + 'International.csv)'
csv_charset = 'cp1252'
email_text = None
matches = []

try:
    req = urllib.request.Request(web_url, data=None, headers={'User-Agent': user_agent})
    matches = re.findall(csv_url_regex, str(urllib.request.urlopen(req).read()))
except urllib.error.HTTPError as error:
    email_text = 'HTTP error "' + str(error.code) + '", reason: "' + error.reason + '". Could not fetch web page ' + web_url
    print(email_text)
except:
    email_text = 'Unknown error when trying to fetch web page ' + web_url
    print(email_text)

if len(matches) > 0:
    csv_url = matches[0][0]
    date_str_raw = matches[0][1]
    date_str = date_str_raw[0:4] + '-' + date_str_raw[4:6] + '-' + date_str_raw[6:]
    try:
        print('Downloading ' + csv_url)
        req = urllib.request.Request(csv_url, data=None, headers={'User-Agent': user_agent})
        csv_text = str(urllib.request.urlopen(req).read().decode(csv_charset))
        country_locked = country.lower() in csv_text.strip().lower()
        print('Country "' + country + '" locked: ' + str(country_locked))
        if not country_locked:
            email_text = 'You can send your package to "' + country + '" as of "' + date_str + '"\n\nCheck for yourself here: ' + web_url
    except urllib.error.HTTPError as error:
        print('Uh oh... could not fetch CSV file. code "' + str(error.code) + '", reason: "' + error.reason + '"')
        email_text = 'HTTP error "' + str(error.code) + '", reason: "' + error.reason + '". Could not fetch CSV file with country "' + country + '" for date ' + date_str + ' from URL: ' + csv_url + '\n\nPlease check ' + web_url
    except:
        print('Some weird error occurred.')
        email_text = 'Some weird error occurred when reading CSV file with country "' + country + '".'
else:
    email_text = 'Could not find a CSV download URL on the web page ' + web_url

if email_text is None:
    print('No email needed to be sent.')
else:
    import configparser
    config = configparser.ConfigParser()
    config.read('config.ini')
    data = urllib.parse.urlencode({
        'secret': config.get('mailer', 'secret'),
        'name': config.get('mailer', 'name'),
        'email': config.get('mailer', 'email'),
        'subject': 'corona-post',
        'message': email_text
    })
    try:
        urllib.request.urlopen(urllib.request.Request(config.get('mailer', 'api_url'), data=data.encode()))
        print('Email sent.')
    except:
        print('Email could NOT be sent.')

print('Script finished.')
