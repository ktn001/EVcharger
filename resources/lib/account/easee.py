from account.account import account
import json
import logging

logger = logging.getLogger(__name__)

class easee(account):

    def do_start(self,message):
        logger.info("============== EASEE ================")
        msg = json.loads(message)
        if not hasattr(self,'_token') or self._token != msg['token']:
            self._token = msg['token']
            logger.info ("le token est modifié")

    def do_newToken(self,message):
        msg = json.loads(message)
        if not hasattr(self,'_token') or self._token != msg['token']:
            logger.debug("Nouveau token reçu")
        else:
            logger.warn("Reception d'une commande 'newToken' sans modification du token")
