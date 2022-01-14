# This file is part of Jeedom.
#
# Jeedom is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Jeedom is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Jeedom. If not, see <http://www.gnu.org/licenses/>.


import string
import sys
import os
import time
import traceback
import re
from optparse import OptionParser
import json
import argparse
import importlib
#import asyncio

libDir = os.path.realpath(os.path.dirname(os.path.abspath(__file__)) + '/../lib')
sys.path.append (libDir)

from jeedom import *
import account

_logLevel = "error"
_socketPort = -1
_socketHost = 'localhost'
_pidfile = '/tmp/jeedom/chargeurVE/daemond.pid'
_apiKey = ''
_callback = ''
accounts = {}

#===============================================================================
# Options
#...............................................................................
# Prise en compte des options de la ligne de commande
#===============================================================================
def options():
    global _logLevel
    global _callback
    global _apiKey
    global _pidfile
    global _socketPort

    parser = argparse.ArgumentParser( description='chargeurVE Daemon for Jeedom plugin')
    parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
    parser.add_argument("--callback", help="Callback", type=str)
    parser.add_argument("--apikey", help="Apikey", type=str)
    parser.add_argument("--pid", help="Pid file", type=str)
    parser.add_argument("--socketport", help="Port pour réception des commandes du plugin", type=int)
    args = parser.parse_args()

    if args.loglevel:
        _logLevel = args.loglevel
    if args.callback:
        _callback = args.callback
    if args.apikey:
        _apiKey = args.apikey
    if args.pid:
        _pidfile = args.pid
    if args.socketport:
        _socketPort = int(args.socketport)


    jeedom_utils.set_logLevel(_logLevel)

    logging.info('Start demond')
    logging.info('Log level : '+str(_logLevel))
    logging.debug('Apikey : '+str(_apiKey))
    logging.info('Socket port : '+str(_socketPort))
    logging.info('Socket host : '+str(_socketHost))
    logging.info('PID file : '+str(_pidfile))

def start_account(accountType, accountId):
    global accounts
    logging.debug(f'start: id={accountId} type: {accountType}')

    if accountId in accounts:
        logging.debug("L'account est déjà demarré")
        return

    logging.info(f"Création d'un account <{accountType}> id:{accountId}")
    queue = Queue()
    account = eval("account." + accountType)(accountId, accountType, queue)
    accounts[accountId] = {
            'type' : accountType,
            'queue' : queue,
            'account' : account
            }
    accounts[accountId]['account'].run()

    # On informe Jeedon du démarrage
    jeedom_com.send_change_immediate({
        'object' : 'account_deamon',
        'info' : 'started',
        'account_id' : accountId
    })
            
    return

# -------- Lecture du socket ------------------------------------------------

def read_socket():
    global JEEDOM_SOCKET_MESSAGE
    global accounts

    if not JEEDOM_SOCKET_MESSAGE.empty():
        # jeedom_socket a reçu un message qu'il a mis en queue que l'on récupère ici
        logging.debug("Message received in socket JEEDOM_SOCKET_MESSAGE")
        payload = json.loads(JEEDOM_SOCKET_MESSAGE.get().decode())

        # Vérification de la clé API
        if payload['apikey'] != _apiKey:
            logging.error("Invalid apikey from socket : " + str(payload))
            return

        logging.debug(str(payload))
        if 'accountType' in payload:
            accountType = payload['type']

            if not 'id' in payload:
                logging.error(f"Message reçu avec un accountType ({accountType}) mais pas d'accountId")
                return
            accountId = payload['id']

            if not 'message' in payload:
                logging.error(f"Message reçu avec un accountType ({accountType}) et un id ({accountId}) mais sans 'message'")
            message = payload['message']

            if 'cmd' in message and message['cmd'] == 'start':
                start_account(accountType, accountId);

            # Envoi du message dans la queue de traitement de l'account
            accounts[accountId]['queue'].put(json.dumps(message))

            # Si la commande était l'arrêt de l'account...
            if 'cmd' in message and message['cmd'] == 'stop':
                # on retire l'account de la liste
                del accounts[accountId]

def listen_jeedom():
    try:
        while 1:
            time.sleep(0.5)
            read_socket()
    except KeyboardInterrupt:
        shutdown()


# ----------- procédures d'arrêt -------------------------------------------

def handler(signum=None, frame=None):
    logging.debug("Signal %i caught, exiting..." % int(signum))
    shutdown()

def shutdown():
    logging.debug("Shutdown")
    logging.debug("Removing PID file " + str(_pidfile))
    try:
        os.remove(_pidfile)
    except:
        pass
    try:
        jeedom_socket.close()
    except:
        pass
    logging.debug("Exit 0")
    sys.stdout.flush()
    os._exit(0)

  ###########################
 #                           #
#  #    #    ##    #  #    #  #
#  ##  ##   #  #   #  ##   #  #
#  # ## #  #    #  #  # #  #  #
#  #    #  ######  #  #  # #  #
#  #    #  #    #  #  #   ##  #
#  #    #  #    #  #  #    #  #
 #                           #
  ###########################

options()

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)

try:
    jeedom_utils.write_pid(str(_pidfile))
    jeedom_com = jeedom_com(apikey = _apiKey, url=_callback)
    if (not jeedom_com.test()):
        logging.error('Network communication issue. Please fixe your Jeedom network configuration.')
        shutdown()
    jeedom_socket = jeedom_socket(port=_socketPort,address=_socketHost)
    jeedom_socket.open()
    jeedom_com.send_change_immediate({
        'object' : 'deamon',
        'info'   : 'started'
    })
    listen_jeedom()
except Exception as e:
    logging.error('Fatal error : '+str(e))
    logging.info(traceback.format_exc())
    shutdown()
