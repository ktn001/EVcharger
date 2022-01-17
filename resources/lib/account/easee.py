import logging
import json
import os
import sys
import configparser

libDir = os.path.realpath(os.path.dirname(os.path.abspath(__file__)) + '/../')
sys.path.append(libDir)

from account import account
from signalrcore.hub_connection_builder import HubConnectionBuilder

class easee(account):


    def on_open(self,serial):
        self.log_debug("openning connection " + serial)
        self.log_debug(self.connections)
        self.connections[serial].send("SubscribeWithCurrentState", [serial, True])

    def on_close(self,serial):
        if serial in self.connections:
            self.connections.pop(serial)
        msg2Jeedom = {}
        msg2Jeedom['object'] = 'chargeur'
        msg2Jeedom['type'] = 'easee'
        msg2Jeedom['chargeur'] = serial
        msg2Jeedom['info'] = 'closed'
        self.log_debug("msg2Jeddom: " + str(msg2Jeedom))
        self._jeedom_com.send_change_immediate(msg2Jeedom)

    def getToken(self):
        return self._token

    def on_ProductUpdate(self,messages):
        for message in messages:
            cmd_id = str(message['id'])
            if not cmd_id in self._cfg['signalR_id']:
                continue
            logicalId = self._cfg['signalR_id'][cmd_id]
            msg2Jeedom = {}
            msg2Jeedom['object'] = 'cmd'
            msg2Jeedom['type'] = 'easee'
            msg2Jeedom['chargeur'] = message['mid']
            msg2Jeedom['logicalId'] = logicalId
            msg2Jeedom['value'] = message['value']
            self.log_debug("msg2Jeddom: " + str(msg2Jeedom))
            self._jeedom_com.send_change_immediate(msg2Jeedom)

    def on_ChargeurUpdate(self,messages):
        for message in messages:
            cmd_id = str(message['id'])
            if not cmd_id in self._cfg['signalR_id']:
                continue
            logicalId = self._cfg['signalR_id'][cmd_id]
            msg2Jeedom = {}
            msg2Jeedom['object'] = 'cmd'
            msg2Jeedom['type'] = 'easee'
            msg2Jeedom['chargeur'] = message['mid']
            msg2Jeedom['logicalId'] = logicalId
            msg2Jeedom['value'] = message['value']
            self.log_debug("msg2Jeddom: " + str(msg2Jeedom))
            self._jeedom_com.send_change_immediate(msg2Jeedom)

    def on_CommandResponse(self,messages):
        pass

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
            self._cfg = configparser.ConfigParser()
            self._cfg.read('/var/www/html/plugins/chargeurVE/core/config/easee/chargeurVEd.ini')
        url = self._url + '/hubs/chargers'
        options = {'access_token_factory': self.getToken}

        if msg['identifiant'] in self.connections:
            return
        connection = HubConnectionBuilder().with_url(url,options)\
                .with_automatic_reconnect({
                    "type": "raw",
                    "keep_alive_interval": 10,
                    "reconnect_interval": 5,
                    "max_attempts": 5
                }).build()
        self.connections[msg['identifiant']] = connection
        connection.on_open(lambda: self.on_open(msg['identifiant']))
        connection.on_close(lambda: self.on_close(msg['identifiant']))
        connection.on('ProductUpdate', self.on_ProductUpdate)
        connection.on('ChargerUpdate', self.on_ChargeurUpdate)
        connection.on('CommandResponse', self.on_CommandResponse)
        connection.start()
        return
        
