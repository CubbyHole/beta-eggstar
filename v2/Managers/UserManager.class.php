<?php
/**
 * Created by Notepad++.
 * User: Alban Truc
 * Date: 30/01/14
 * Time: 14:51
 */

/** @var string $projectRoot chemin du projet dans le système de fichier */
$projectRoot = $_SERVER['DOCUMENT_ROOT'].'/eggstar/v2';

require_once $projectRoot.'/Managers/AbstractManager.class.php';
require_once $projectRoot.'/Managers/AccountManager.class.php';
require_once $projectRoot.'/Managers/RefPlanManager.class.php';

require_once $projectRoot.'/Interfaces/UserManager.interface.php';

/**
 * Class UserManager
 * @author Alban Truc
 * @extends AbstractManager
 */
class UserManager extends AbstractManager implements UserManagerInterface{

    /** @var MongoCollection $userCollection collection user */
	protected $userCollection;

    /**
     * Constructeur:
     * - Apelle le constructeur de {@see AbstractManager::__construct} (gestion des accès de la BDD).
     * - Initialise la collection user.
     * @author Alban Truc
     * @since 01/2014
     */

    public function __construct()
    {
        parent::__construct('cubbyhole');
        $this->userCollection = $this->getCollection('user');
    }

    /**
     * Modifications de certaines données
     * @author Alban Truc
     * @param array $user
     * @since 30/04/2014
     * @return array
     */

    public function convert($user)
    {
        if(is_array($user))
        {
            if(isset($user['_id']))
                $user['_id'] = (string)$user['_id']; // MongoId => string

            if(isset($user['idCurrentAccount']))
                $user['idCurrentAccount'] = (string)$user['idCurrentAccount']; // MongoId => string
        }

        return $user;
    }

    /**
     * Conversion inverse de celle de la fonction ci-dessus
     * @author Alban Truc
     * @param array $user
     * @since 08/06/2014
     * @return array
     */

    public function reverseConvert($user)
    {
        if(is_array($user))
        {
            if(isset($user['_id']))
                $user['_id'] = new MongoId($user['_id']); // string => MongoId

            if(isset($user['idCurrentAccount']))
                $user['idCurrentAccount'] = new MongoId($user['idCurrentAccount']); // string => MongoId
        }

        return $user;
    }

    /**
     * Retrouver un User selon des critères donnés
     * @author Alban Truc
     * @param array $criteria critères de recherche
     * @param array $fieldsToReturn champs à récupérer
     * @since 31/03/2014
     * @return array
     */

    public function find($criteria, $fieldsToReturn = array())
    {
        $criteria = self::reverseConvert($criteria);

        $cursor = parent::__find('user', $criteria, $fieldsToReturn);

        if(!(is_array($cursor)) && !(array_key_exists('error', $cursor)))
        {
            $users = array();

            foreach($cursor as $user)
            {
                $user = self::convert($user);
                $users[] = $user;
            }

            if(empty($users))
                return array('error' => 'No match found.');
            else
                return $users;
        }
        else return $cursor; //message d'erreur
    }

    /**
     * Retourne le premier User correspondant au(x) critère(s) donné(s)
     * @author Alban Truc
     * @param array $criteria critère(s) de recherche
     * @param array $fieldsToReturn champs à retourner
     * @since 31/03/2014
     * @return array
     */

    public function findOne($criteria, $fieldsToReturn = array())
    {
        $criteria = self::reverseConvert($criteria);

        $result = parent::__findOne('user', $criteria, $fieldsToReturn);
        $result = self::convert($result);

        return $result;
    }

    /**
     * - Retrouver l'ensemble des User
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $fieldsToReturn champs à retourner
     * @since 11/03/2014
     * @return array tableau d'objets User
     */

    public function findAll($fieldsToReturn = array())
    {
        $cursor = parent::__find('user', $fieldsToReturn);

        if(!(is_array($cursor)) && !(array_key_exists('error', $cursor)))
        {
            $users = array();

            foreach($cursor as $user)
            {
                $user = self::convert($user);
                $users[] = $user;
            }
        }

        if(empty($users))
            return array('error' => 'No user found.');
        else
            return $users;
    }

    /**
     * - Retrouver un user par son ID.
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $fieldsToReturn champs à retourner
     * @param string|MongoId $id Identifiant unique de l'user à trouver
     * @since 02/2014
     * @return array
     */

    public function findById($id, $fieldsToReturn = array())
    {
        $result = parent::__findOne('user', array('_id' => new MongoId($id)), $fieldsToReturn);
        $result = self::convert($result);

        return $result;
    }

    /**
     * - Retrouver un User selon certains critères et le modifier/supprimer
     * - Récupérer cet User ou sa version modifiée
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
        $searchQuery = self::reverseConvert($searchQuery);
        $updateCriteria = self::reverseConvert($updateCriteria);

        $result = parent::__findAndModify('user', $searchQuery, $updateCriteria, $fieldsToReturn, $options);
        $result = self::convert($result);

        return $result;
    }

    /**
     * - Insère un nouvel utilisateur en base.
     * - Gestion des exceptions et des erreurs.
     * @author Alban Truc
     * @param array $user
     * @param array $options
     * @since 31/03/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    public function create($user, $options = array('w' => 1))
    {
        $user = self::reverseConvert($user);

        $result = parent::__create('user', $user, $options);

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
        $criteria = self::reverseConvert($criteria);
        $update = self::reverseConvert($update);

        $result = parent::__update('user', $criteria, $update, $options);

        return $result;
    }

    /**
     * - Supprime un/des utilisateur(s) correspondant à des critères données
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $criteria ce qu'il faut supprimer
     * @param array $options
     * @since 31/03/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    public function remove($criteria, $options = array('w' => 1))
    {
        $criteria = self::reverseConvert($criteria);

        $result = parent::__remove('user', $criteria, $options);

        return $result;
    }

    /**
     * Authentifier un utilisateur:
     * - Récupère l'utilisateur inscrit avec l'e-mail indiquée. S'il y en a un:
     *  - Vérifie le mot de passe. S'il correspond:
     *      - Récupère son compte
     * @author Alban Truc
     * @param string $email
     * @param string $password
     * @since 02/2014
     * @return array contenant le message d'erreur
     */

    public function authenticate($email, $password)
    {
        //Récupère l'utilisateur inscrit avec l'e-mail indiquée.
        $query = array(
            'state' => (int)1,
            'email' => $email
        );

        $user = self::findOne($query);

        if(!(isset($user['error']))) //Si l'utilisateur existe
        {
            if($user['password'] == $password)
            {
                //On récupère le compte correspondant à l'utilisateur
                $accountCriteria = array(
                    '_id' => new MongoId($user['idCurrentAccount']),
                    'state' => (int)1
                );
                $accountManager = new AccountManager();
                $account = $accountManager->findOne($accountCriteria);

                if(!(isset($account['error']))) //Si le compte existe
                {
                    $refPlanManager = new RefPlanManager();
                    $refPlan = $refPlanManager->findById($account['idRefPlan']);

                    if(!(isset($refPlan['error'])))
                    {
                        unset($account['idRefPlan']);
                        unset($account['idUser']);

                        $account['refPlan'] = $refPlan;

                        unset($user['idCurrentAccount']);

                        $user['account'] = $account;

                        return $user;
                    }
                    else
                    {
                        $errorInfo = 'RefPlan with ID '.$account['idRefPlan'].' not found';
                        return array('error' => $errorInfo);
                    }
                }
                else
                {
                    $errorInfo = 'No active account with ID '.$user['idCurrentAccount'].' for user '.$user['_id'];
                    return array('error' => $errorInfo);
                }
            }
            else
            {
                $errorInfo = 'Password given ('.$password.') does not match with password in database.';
                return array('error' => $errorInfo);
            }
        }
        else
        {
            $errorInfo = 'No ACTIVE user found for the following e-mail: '.$email.' Maybe you didn\'t activate your account?';
            return array('error' => $errorInfo);
        }
    }
}