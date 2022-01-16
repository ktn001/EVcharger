import logging
import json
import os
import sys

libDir = os.path.realpath(os.path.dirname(os.path.abspath(__file__)) + '/../')
sys.path.append(libDir)

from account import account
from signalrcore.hub_connection_builder import HubConnectionBuilder

class easee(account):

    def on_open(self,serial):
        self.log_debug("openning connection " + serial)
        self.log_debug(self.connections)
        self.connections[serial].send("SubscribeWithCurrentState", [serial, True])

    def getToken(self):
        self.log_debug("====== " + self._token + " =======")
        return self._token

    def product_update(self,messages):
        for message in messages:
            self.log_debug("PRODUCT: " + str(message))

    def charger_update(self,messages):
        for message in messages:
            self.log_debug("CHARGER: " + str(message))

    def do_start(self,message):
        msg = json.loads(message)
        if not 'token' in msg:
            self.log_error ('do_start(): token is missing')
            return
        if not 'url' in msg:
            self.log_error("do_start(): url is missing")
            return
        self._token = msg['token']
        self._url = msg['url']
        return

    def do_newToken(self,message):
        msg = json.loads(message)
        if not hasattr(self,'_token') or self._token != msg['token']:
            logger.debug("Nouveau token reçu")
        else:
            logger.warn("Reception d'une commande 'newToken' sans modification du token")
        return

    def do_start_charger_listener(self,message):
        msg = json.loads(message)

        if not 'identifiant' in msg:
            self.log_error(f"do_start_charger_listener(): identifiant is missing")
            return
        if not hasattr(self,'connections'):
            self.connections = {}
        url = self._url + '/hubs/chargers'
        options = {'access_token_factory': self.getToken}
                #.configure_logging(logging.DEBUG)\
        connection = HubConnectionBuilder().with_url(url,options)\
                .with_automatic_reconnect({
                    "type": "raw",
                    "keep_alive_interval": 10,
                    "reconnect_interval": 5,
                    "max_attempts": 5
                }).build()
        self.connections[msg['identifiant']] = connection
        connection.on_open(lambda: self.on_open(msg['identifiant']))
        connection.on('ProductUpdate', self.product_update)
        connection.on('ChargerUpdate', self.charger_update)
        connection.start()
        return
        
