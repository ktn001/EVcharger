from account.account import account
import json
import logging

logger = logging.getLogger(__name__)

class easee(account):

    def do_start(self,message):
        logger.info("============== EASEE ================")
        msg = json.loads(message)
        if not '_token' in self or self._token != msg['token']:
            self._token = msg['token']
            loggerinfo ("le token est modifié")
