<?php
/**
 * Created by Notepad++.
 * User: Alban Truc
 * Date: 31/01/14
 * Time: 12:53
 */

require_once 'AbstractManager.class.php';
require_once 'RefPlanManager.class.php';
require_once '.\Interfaces\AccountManager.interface.php';

class AccountManager extends AbstractManager implements AccountManagerInterface
{
	protected $accountCollection;
	protected $refPlanManager;

    /**
     * Constructeur:
     * - Appelle le constructeur de AbstractManager (gestion des accès de la BDD).
     * - Initialise la collection account.
     * - Crée un objet RefPlanManager (l'account a une clé étrangère de refPlan).
     * @author Alban Truc
     * @since 01/2014
     */

    public function __construct()
	{
		parent::__construct();
		$this->accountCollection = $this->getCollection('account');
		$this->refPlanManager = new RefPlanManager();
	}

    /**
     * - Retrouver un account par son ID. Inclut le refPlan de l'account dans le retour.
     * - Gestion des erreurs
     * @author Alban Truc
     * @param $id String|MongoId Identifiant unique de l'account à trouver
     * @since 02/2014
     * @return FALSE|array contenant le résultat de la requête
     */

    public function findById($id)
	{
        /**
         * Doc du findOne: http://www.php.net/manual/en/mongo.tutorial.findone.php
         * Utilisé lorsqu'on attend un résultat unique (notre cas) ou si l'on ne veut que le 1er résultat.
         * Les ID dans Mongo sont des objets MongoId: http://www.php.net/manual/en/class.mongoid.php
         */
        $result = $this->accountCollection->findOne(array('_id' => new MongoId($id)));

        //Si un compte est trouvé
		if($result !== NULL)
		{
            //Cast le MongoId en string
			$result['_id'] = (string) $result['_id'];

            /**
             * format de date actuel: 2014-Feb-23
             * format de time actuel: 18:11:00
             */
            $startDateTimestamp = $result['startDate']->sec;
            $startDate = date('Y-M-d', $startDateTimestamp);
            $startDateTime = date('H:i:s', $startDateTimestamp);

            $result['startDate'] = array
            (
                'timestamp' => $startDateTimestamp,
                'date' => $startDate,
                'time' => $startDateTime
            );

            /**
             * $endDateTimestamp = $result['endDate']->sec;
             * $endDate = date('Y-M-d', $endDateTimestamp);
             * $endDateTime = date('H:i:s', $endDateTimestamp);
             *
             * $result['endDate'] = array(
             *                              'timestamp' => $endDateTimestamp,
             *                              'date' => $endDate,
             *                              'time' => $endDateTime
             *                            );
             */

            //On récupère le refPlan correspondant au compte
            $refPlan = $this->refPlanManager->findById($result['idRefPlan']);

            //Si un refPlan est trouvé
			if(!(array_key_exists("error", $refPlan)))
			{
                //On retourne toutes les infos du RefPlan plutôt que (seulement) son ID
				unset($result['idRefPlan']);
				$result['refPlan'] = $refPlan;

				return $result;
			}
			else return $refPlan; //Message d'erreur approprié
		}
		else return array('error' => 'Account with ID '.$id.' not found');
	}

    /**
     * - Insère un nouveau compte en base.
     * - Gestion des exceptions MongoCursor: http://www.php.net/manual/en/class.mongocursorexception.php
     * - Gestion des erreurs
     * - On n'insert pas de nouveau refPlan, ceux-ci sont déjà définis en base.
     * @author Alban Truc
     * @param $account array
     * @since 02/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    public function createAccount($account)
	{
		try 
		{
            /**
             * w = 1 est optionnel, il est déjà à 1 par défaut. Cela permet d'avoir un retour du status de l'insertion.
             * Plus d'informations sur toutes les options, voir chapitre "Parameters":
             * http://www.php.net/manual/en/mongocollection.insert.php
             */
            $info = $this->accountCollection->insert($account, array('w' => 1));

		}
		catch(MongoCursorException $e)
        {
            return array('error' => $e->getMessage());
		}

        /**
         * Gestion de ce qui est retourné grâce à l'option w.
         * Plus d'informations sur les retours, voir chapitre "Return Values":
         * http://www.php.net/manual/en/mongocollection.insert.php
         */
		if(!(empty($info)) && $info['ok'] == '1' && $info['err'] === NULL) return TRUE;

		else return array('error' => $info['err']);
	}

    /**
     * - Supprime un compte à partir de son ID
     * - Gestion des exceptions MongoCursor: http://www.php.net/manual/en/class.mongocursorexception.php
     * - Gestion des erreurs
     * @author Alban Truc
     * @param MongoID|String $id
     * @since 23/02/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    public function removeAccount($id)
    {
        //On cherche le compte à supprimer à partir de son MongoId.
        $criteria = array('_id' => new MongoId($id));

        try
        {
            /**
             * w = 1 est optionnel, il est déjà à 1 par défaut.
             * Cela permet d'avoir un retour du status de la suppression.
             * justOne = TRUE est également optionnel.
             * Cela permet de ne supprimer qu'un enregistrement correspondant aux critères.
             * Les IDs étant uniques, on pourrait se passer de cette option.
             * Documentation du remove: http://www.php.net/manual/en/mongocollection.remove.php
             */

            $info = $this->accountCollection->remove($criteria, array('w' => 1, 'justOne' => TRUE));

        }
        catch(MongoCursorException $e)
        {
            return array('error' => $e->getMessage());
        }

        /**
         * Gestion de ce qui est retourné grâce à l'option w.
         * Si on essaye de supprimer un document qui n'existe pas, remove() ne renvoie pas d'exception.
         * Dans ce cas, $info['n'] contiendra 0. Nous devons donc vérifer que ce n est différent de 0.
         * Plus d'informations sur les retours, voir chapitre "Return Values":
         * http://www.php.net/manual/en/mongocollection.insert.php
         */

        if(!(empty($info)) && $info['ok'] == '1' && $info['err'] === NULL)
        {
            if($info['n'] != '0') return TRUE;

            else return array('error' => 'Cannot remove account with ID '.$id.', no account found for this ID.');
        }
        else return array('error' => $info['err']);
    }
}

?>