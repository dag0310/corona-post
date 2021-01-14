"""
Postal service Austria Corona-locked checker

Check if country is locked for sending packages with postal service Austria.
"""

import sys
import datetime
import urllib.request
import urllib.parse
import urllib.error

if len(sys.argv) < 2:
    print('Supply country name as first argument.')
    quit()

country = sys.argv[1]
yesterday = datetime.date.today() - datetime.timedelta(days=1)
date_str = yesterday.strftime("%Y%m%d")
type = 'Brief' # Brief | Paket
charset = 'cp1252'
web_url = 'https://www.post.at/p/c/liefereinschraenkungen-coronavirus'
csv_url = 'https://assets.post.at/-/media/Dokumente/Corona/' + date_str + '-Annahmestopp-' + type + 'International.csv'

print('Downloading ' + csv_url)

email_text = None
try:
    req = urllib.request.Request(
    csv_url,
    data=None,
    headers={
        'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.47 Safari/537.36'
    })
    csv_text = str(urllib.request.urlopen(req).read().decode(charset))
    country_locked = country.lower() in csv_text.strip().lower()
    print('Country "' + country + '" locked: ' + str(country_locked))
    if not country_locked:
        email_text = 'You can send your package to "' + country + '" as of "' + yesterday.strftime("%Y-%m-%d") + '"\n\nCheck for yourself here: ' + web_url
except urllib.error.HTTPError as error:
    print('Uh oh... could not fetch CSV file. code "' + str(error.code) + '", reason: "' + error.reason + '"')
    email_text = 'HTTP error "' + str(error.code) + '", reason: "' + error.reason + '". Could not fetch CSV file with country "' + country + '" for date ' + yesterday.strftime("%Y-%m-%d") + ' from URL: ' + csv_url + '\n\nPlease check ' + web_url
except:
    print('Some weird error occurred.')
    email_text = 'Some weird error occurred when reading CSV file with country "' + country + '".'

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
        'subject': 'You can send your package to ' + country + ' now!',
        'message': email_text
    })
    try:
        urllib.request.urlopen(urllib.request.Request(config.get('mailer', 'api_url'), data=data.encode()))
        print('Email sent.')
    except:
        print('Email could NOT be sent.')

print('Script finished.')
