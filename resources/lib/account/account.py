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
#

import sys
import threading
import time
from queue import Queue
from jeedom import *
import logging
import json

class account():
    """Class de base pour les differents types d'account"""

    def __init__(self, id, type, queue, jeedom_com):
        self._id = id
        self._type = type
        self._jeedomQueue = queue
        self._jeedom_com = jeedom_com

    def log_debug(self,txt):
        logging.debug(f'[account][{self._type}][{self._id}] {txt}')

    def log_info(self,txt):
        logging.info(f'[account][{self._type}][{self._id}] {txt}')

    def log_warning(self,txt):
        logging.warning(f'[account][{self._type}][{self._id}] {txt}')

    def log_error(self,txt):
        logging.error(f'[account][{self._type}][{self._id}] {txt}')

    def read_jeedom_queue(self):
        if not self._jeedomQueue.empty():
            message = self._jeedomQueue.get()
            self.log_debug(f'message reçu: {message}')
            msg = json.loads(message)
            if not 'cmd' in msg:
                self.log_error(f'le message "{message}" n\'a pas de commande')
                return
            commande = 'do_' + msg['cmd']
            if (hasattr(self, commande)):
                function = eval(f"self.{commande}")
                if callable(function):
                    function(message)

    def listen_jeedom(self):
        self._stop = False
        while 1:
            time.sleep(0.5)
            if self._stop:
                self.log_info(f'arrêt de thread')
                return
            self.read_jeedom_queue()

    def run(self):
        self.log_debug(f'Lancement de thread')
        thread = threading.Thread(target=self.listen_jeedom, args=()).start()
        self.log_info(f'Thread lancé')
        return thread

    def do_stop(self,msg):
        self._stop = True
