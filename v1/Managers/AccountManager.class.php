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
     * - Apelle le constructeur de AbstractManager (gestion des accès de la BDD).
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
     * Retrouver un account par son ID. Inclut le refPlan de l'account dans le retour.
     * @author Alban Truc
     * @param $id String|MongoId Identifiant unique de l'account à trouver
     * @since 02/2014
     * @return array si résultat, bool FALSE sinon
     */

    public function findById($id)
	{
        /**
         * Doc du findOne: http://www.php.net/manual/en/mongo.tutorial.findone.php
         * Utilisé lorsqu'on attend un résultat unique (notre cas) ou si l'on ne veut que le 1er résultat.
         * Les ID dans Mongo sont des objets MongoId: http://de3.php.net/manual/en/class.mongoid.php
         */
        $result = $this->accountCollection->findOne(array('_id' => new MongoId($id)));

        //Si un compte est trouvé
		if($result)
		{
            //Cast le MongoId en string
			$result['_id'] = (string) $result['_id'];

			//On récupère le refPlan correspondant au compte
			$refPlan = $this->refPlanManager->findById($result['idRefPlan']);

            //Si un refPlan est trouvé
			if($refPlan)
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
     * - Gestion des exceptions MongoCursor: http://de3.php.net/manual/en/class.mongocursorexception.php
     * - On n'insert pas de nouveau refPlan, ceux-ci sont déjà définis en base.
     * @author Alban Truc
     * @param $account array
     * @since 02/2014
     * @return bool TRUE si l'insertion a réussi, FALSE sinon
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

		else return array('error');
	}
	
}

?>