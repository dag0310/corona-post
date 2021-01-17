"""
Notify as soon as a country is not blocked anymore due to COVID-19
from sending packages to with postal service Austria.
"""

import os
import sys
import re
import csv
import configparser
import urllib.request
import urllib.parse
import urllib.error
import smtplib
import ssl
from email.message import EmailMessage

environment = sys.argv[1] if len(sys.argv) > 1 else 'production'

config = configparser.ConfigParser()
config.read(os.path.join(os.path.dirname(__file__), 'config.ini'))

types = ['Brief', 'Paket']
web_url = 'https://www.post.at/p/c/liefereinschraenkungen-coronavirus'
user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.47 Safari/537.36'
csv_charset = 'cp1252'


def send_email(to_email, subject, message):
    msg = EmailMessage()
    msg['Subject'] = subject
    msg['From'] = config['email']['from_name'] + " <" + config['email']['from_email'] + ">"
    msg['To'] = to_email
    msg.set_content(message)
    try:
        context = ssl.create_default_context()
        with smtplib.SMTP(config['email']['smtp_host'], config['email']['smtp_port']) as server:
            server.ehlo()  # Can be omitted
            server.starttls(context=context)
            server.ehlo()  # Can be omitted
            server.login(config['email']['smtp_user'], config['email']['smtp_pass'])
            server.send_message(msg)
            print('Email sent to "' + to_email + '"')
            return True
    except Exception as error:
        print(error)
        print('Email could NOT be sent to "' + to_email + '"')
    return False


def send_admin_email(message):
    print('Sending admin email with message "' + message + '"')
    return send_email(config['email']['admin_email'], "Bericht für den Admin", message)


def notify_receivers(receivers, type, csv_url_regex):
    matches = []
    try:
        req = urllib.request.Request(web_url, data=None, headers={'User-Agent': user_agent})
        matches = re.findall(csv_url_regex, str(urllib.request.urlopen(req).read()))
    except urllib.error.HTTPError as error:
        print(error)
        send_admin_email('HTTP error "' + str(error.code) + '", reason: "' + error.reason + '". Could not fetch web page ' + web_url)
        return
    except Exception as error:
        print(error)
        send_admin_email('Unknown error when trying to fetch web page ' + web_url)
        return

    if len(matches) <= 0:
        send_admin_email('Could not find a CSV download URL on the web page ' + web_url)
        return

    csv_url = matches[0][0]
    date_str_raw = matches[0][1]
    date_str = date_str_raw[0:4] + '-' + date_str_raw[4:6] + '-' + date_str_raw[6:]
    try:
        print('Downloading ' + csv_url)
        req = urllib.request.Request(csv_url, data=None, headers={'User-Agent': user_agent})
        csv_text = str(urllib.request.urlopen(req).read().decode(csv_charset))
        for receiver in receivers:
            if receiver['type'] != type:
                continue
            country_blocked = False
            for csv_line in csv_text.strip().split('\r\n'):
                if receiver['country'].strip().lower() == csv_line.strip().lower():
                    country_blocked = True
                    break
            if environment != 'production':
                print('Country "' + receiver['country'] + '" blocked: ' + str(country_blocked))
            if country_blocked:
                continue
            message = 'Dein ' + type + ' nach ' + receiver['country'] + ' kann verschickt werden seit ' + date_str + ' laut ' + web_url + '\n\nGeschickt von https://apps.geymayer.com/corona-post'
            if send_email(receiver['email'], "Dein " + type + " kann wieder verschickt werden!", message):
                with open(config[environment]['receivers_path'], "r+") as file:
                    lines = file.readlines()
                    file.seek(0)
                    file.truncate()
                    for line in lines:
                        if line.strip("\n") != "\t".join([receiver['email'], receiver['country'], receiver['type']]):
                            file.write(line)
    except urllib.error.HTTPError as error:
        print(error)
        send_admin_email('HTTP error "' + str(error.code) + '", reason: "' + error.reason + '". Could not fetch CSV file for date ' + date_str + ' from URL: ' + csv_url + '\n\nPlease check ' + web_url)
        return
    except Exception as error:
        print(error)
        send_admin_email('Some weird error occurred when reading CSV file ' + csv_url)
        return


def main():
    receivers = []
    with open(config[environment]['receivers_path'], newline='\n') as receivers_file:
        for row in csv.reader(receivers_file, delimiter='\t'):
            receivers.append({'email': row[0], 'country': row[1], 'type': row[2]})

    for type in types:
        notify_receivers(receivers, type, r'(https://assets.post.at/-/media/Dokumente/Corona/([0-9]{8})-Annahmestopp-' + type + 'International.csv)')

    print('Script finished.')
    return 0


if __name__ == "__main__":
    main()
