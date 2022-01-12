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
import asyncio

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
    if not accountId in accounts:
        logging.info(f"Création d'un account <{accountType}> id:{accountId}")
        queue = Queue()
        account = eval("account." + accountType)(accountId, accountType, queue)
        accounts[accountId] = {
                'type' : accountType,
                'queue' : queue,
                'account' : account
                }
        accounts[accountId]['account'].run()
    return

# -------- Lecture du socket ------------------------------------------------

def read_socket():
    global JEEDOM_SOCKET_MESSAGE
    global accounts

    if not JEEDOM_SOCKET_MESSAGE.empty():
        logging.debug("Message received in socket JEEDOM_SOCKET_MESSAGE")
        payload = json.loads(JEEDOM_SOCKET_MESSAGE.get().decode())
        if payload['apikey'] != _apiKey:
            logging.error("Invalid apikey from socket : " + str(payload))
            return
        try:
            accountType = payload['type']
            accountId = payload['id']
            if 'message' in payload:
                message = payload['message']
                if message['cmd'] == 'start':
                    return start_account(accountType, accountId);
                accounts[accountId]['queue'].put(json.dumps(message))
                if message['cmd'] == 'stop':
                    del accounts[accountId]

        except Exception as e:
            logging.error('Send command to demon error : '+str(e))

async def listen_jeedom():
    jeedom_socket.open()
    try:
        while 1:
            time.sleep(0.5)
            read_socket()
    except KeyboardInterrupt:
        shutdown()

async def start_chargeurVE():
    asyncio.create_task(listen_jeedom())

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
    jeedom_socket = jeedom_socket(port=_socketPort,address=_socketHost)
    asyncio.run(start_chargeurVE(), debug=True)
except Exception as e:
    logging.error('Fatal error : '+str(e))
    logging.info(traceback.format_exc())
    shutdown()
