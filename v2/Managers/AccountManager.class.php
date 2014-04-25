<?php
/**
 * Created by Notepad++.
 * User: Alban Truc
 * Date: 31/01/14
 * Time: 12:53
 */

/** @var string $projectRoot chemin du projet dans le système de fichier */
$projectRoot = $_SERVER['DOCUMENT_ROOT'].'/eggstar/v2';

require_once $projectRoot.'/Managers/AbstractManager.class.php';
require_once $projectRoot.'/Managers/UserManager.class.php';
require_once $projectRoot.'/Managers/RefPlanManager.class.php';

require_once $projectRoot.'/Interfaces/AccountManager.interface.php';

/**
 * Class AccountManager
 * @author Alban Truc
 * @extends AbstractManager
 */
class AccountManager extends AbstractManager implements AccountManagerInterface
{
    /** @var MongoCollection $accountCollection collection account */
    protected $accountCollection;

    /** @var  UserManager $userManager instance de cette classe */
    protected $userManager;

    /** @var RefPlanManager $refPlanManager instance de cette classe */
    protected $refPlanManager;

    /**
     * Constructeur:
     * - Appelle le constructeur de {@see AbstractManager::__construct} (gestion des accès de la BDD).
     * - Initialise la collection account.
     * - Crée un objet RefPlanManager (l'account a une clé étrangère de refPlan).
     * - Crée un objet UserManager (l'account a une clé étrangère de user).
     * @author Alban Truc
     * @since 01/2014
     */

    public function __construct()
    {
        parent::__construct();
        $this->accountCollection = $this->getCollection('account');
        /**
         * Le UserManager nécessite l'AccountManager qui nécessite le UserManager...
         * Pour éviter un appel infini entre ces deux constructeurs:
         * - je passe ici l'instance actuelle d'AccountManager au constructeur de UserManager
         * - le constructeur UserManager se chargera ensuite de distinguer s'il doit créer une nouvelle instance
         * d'AccountManager ou utiliser une référence. {@see UserManager::__construct}
         */
        $this->userManager = new UserManager($this);
        $this->refPlanManager = new RefPlanManager();
    }

    /**
     * Retrouver un Account selon des critères donnés
     * @author Alban Truc
     * @param array $criteria critères de recherche
     * @param array $fieldsToReturn champs à récupérer
     * @since 29/03/2014
     * @return array
     */

    public function find($criteria, $fieldsToReturn = array())
    {
        $cursor = parent::__find('account', $criteria, $fieldsToReturn);

        if(!(is_array($cursor)) && !(array_key_exists('error', $cursor)))
        {
            $accounts = array();

            foreach($cursor as $account)
            {
                $accounts[] = $account;
            }

            if(empty($accounts))
                return array('error' => 'No match found.');
            else
                return $accounts;
        }
        else return $cursor; //message d'erreur
    }

    /**
     * Retourne le premier Account correspondant au(x) critère(s) donné(s)
     * @author Alban Truc
     * @param array $criteria critère(s) de recherche
     * @param array $fieldsToReturn champs à retourner
     * @since 29/03/2014
     * @return array
     */

    public function findOne($criteria, $fieldsToReturn = array())
    {
        $result = parent::__findOne('account', $criteria, $fieldsToReturn);

        return $result;
    }

    /**
     * - Retrouver l'ensemble des Account
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $fieldsToReturn champs à retourner
     * @since 11/03/2014
     * @return array
     */

    public function findAll($fieldsToReturn = array())
    {
        $cursor = parent::__find('account', array());

        if(!(is_array($cursor)) && !(array_key_exists('error', $cursor)))
        {
            $accounts = array();

            foreach($cursor as $account)
            {
                $accounts[] = $account;
            }
        }

        if(empty($accounts))
            return array('error' => 'No account found.');
        else
            return $accounts;
    }

    /**
     * - Retrouver un account par son ID.
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param string|MongoId $id Identifiant unique de l'account à trouver
     * @param array $fieldsToReturn champs à retourner
     * @since 02/2014
     * @return array
     */

    public function findById($id, $fieldsToReturn = array())
    {
        $result = parent::__findOne('account', array('_id' => new MongoId($id)));

        return $result;
    }

    /**
     * - Retrouver un Account selon certains critères et le modifier/supprimer
     * - Récupérer cet Account ou sa version modifiée
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $searchQuery critères de recherche
     * @param array $updateCriteria les modifications à réaliser
     * @param array|NULL $fieldsToReturn pour ne récupérer que certains champs
     * @param array|NULL $options
     * @since 11/03/2014
     * @return array
     */

    public function findAndModify($searchQuery, $updateCriteria, $fieldsToReturn = NULL, $options = NULL)
    {
        $result = parent::__findAndModify('account', $searchQuery, $updateCriteria, $fieldsToReturn, $options);

        return $result;
    }

    /**
     * - Insère un nouveau compte en base.
     * - Gestion des exceptions et des erreurs
     * - On n'insert pas de nouveau refPlan, ceux-ci sont déjà définis en base.
     * @author Alban Truc
     * @param array $account
     * @param array $options
     * @since 31/03/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    public function create($account, $options = array('w' => 1))
    {
        $result = parent::__create('account', $account, $options);

        return $result;
    }

    /**
     * Fonction d'update utilisant celle de {@see AbstractManager}
     * @author Alban Truc
     * @param array $criteria description des entrées à modifier
     * @param array $update nouvelles valeurs
     * @param array|NULL $options
     * @since 31/03/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    public function update($criteria, $update, $options = array('w' => 1))
    {
        $result = parent::__update('account', $criteria, $update, $options);

        return $result;
    }

    /**
     * - Supprime un/des compte(s) correspondant à des critères données
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $criteria ce qu'il faut supprimer
     * @param array $options
     * @since 31/03/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    public function remove($criteria, $options = array('w' => 1))
    {
        $result = parent::__remove('account', $criteria, $options);

        return $result;
    }
}

?>