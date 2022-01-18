import logging
import json
import os
import sys
import configparser
import time
from signalrcore.hub_connection_builder import HubConnectionBuilder

libDir = os.path.realpath(os.path.dirname(os.path.abspath(__file__)) + '/../')
sys.path.append(libDir)

from account import account

class easee(account):

    def start_charger_listener(self,identifiant):
        url = self._url + '/hubs/chargers'
        options = {'access_token_factory': self.getToken}

        if identifiant in self.connections:
            return
        connection = HubConnectionBuilder().with_url(url,options)\
                .with_automatic_reconnect({
                    "type": "raw",
                    "keep_alive_interval": 10,
                    "reconnect_interval": 5,
                    "max_attempts": 5
                }).build()
        self.connections[identifiant] = connection
        connection.on_open(lambda: self.on_open(identifiant))
        connection.on_close(lambda: self.on_close(identifiant))
        connection.on('ProductUpdate', self.on_ProductUpdate)
        connection.on('ChargerUpdate', self.on_ChargeurUpdate)
        connection.on('CommandResponse', self.on_CommandResponse)
        connection.start()
        return

    def stop_charger_listener(self,serial):
        self.connections[serial].stopping = True
        self.connections[serial].stop()
        for i in range(10):
            if len(self.connections) == 0:
                break
        return

    def on_open(self,serial):
        self.log_debug("openning connection " + serial)
        self.connections[serial].send("SubscribeWithCurrentState", [serial, True])

    def on_close(self,serial):
        if serial in self.connections:
            if not hasattr(self.connections[serial],'stopping'):
                self.connections.pop(serial)
                time.sleep(1)
                self.start_charger_listener(serial)
                return
            else:
               self.connections.pop(serial)
        msg2Jeedom = {}
        msg2Jeedom['object'] = 'chargeur'
        msg2Jeedom['type'] = 'easee'
        msg2Jeedom['chargeur'] = serial
        msg2Jeedom['info'] = 'closed'
        self.log_debug("msg2Jeddom: " + str(msg2Jeedom))
        self._jeedom_com.send_change_immediate(msg2Jeedom)
        if serial in self.connections:
            del self.connections[serial]

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
        self.token = msg['token']
        self._url = msg['url']
        return

    def do_stop(self,message):
        for serial, connection in list(self.connections.items()):
            self.stop_charger_listener(serial)
        return

    def do_newToken(self,message):
        msg = json.loads(message)
        if not hasattr(self,'token') or self.token != msg['token']:
            logger.debug("Nouveau token reçu")
        else:
            logger.warn("Reception d'une commande 'newToken' sans modification du token")
        return

    def getToken(self):
        return self.token

    def do_start_charger_listener(self,message):
        msg = json.loads(message)
        if not 'identifiant' in msg:
            self.log_error(f"do_start_charger_listener(): identifiant is missing")
            return
        if not hasattr(self,'connections'):
            self.connections = {}
            self._cfg = configparser.ConfigParser()
            self._cfg.read('/var/www/html/plugins/chargeurVE/core/config/easee/chargeurVEd.ini')
        self.start_charger_listener(msg['identifiant'])
        
    def do_stop_charger_listener(self,message):
        msg = json.loads(message)
        if not 'identifiant' in msg:
            self.log_error(f"do_stop_charger_listener(): identifiant is missing")
            return
        if not hasattr(self,'connections'):
            return
        if not msg['identifiant'] in self.connections:
            return
        self.stop_charger_listener(msg['identifiant'])


