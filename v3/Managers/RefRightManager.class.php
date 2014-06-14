<?php
/**
 * Created by PhpStorm.
 * User: Crocell
 * Date: 25/04/14
 * Time: 15:18
 */

/** @var string $projectRoot chemin du projet dans le système de fichier */
$projectRoot = $_SERVER['DOCUMENT_ROOT'].'/eggstar/v3';

require_once $projectRoot.'/required.php';

/**
 * Class RefRightManager
 * @author Alban Truc
 */
class RefRightManager extends AbstractManager implements RefRightManagerInterface{

    /** @var MongoCollection $refRightCollection collection refRight */
    protected $refRightCollection;

    /**
     * Constructeur:
     * - Appelle le constructeur de {@see AbstractManager::__construct} (gestion des accès de la BDD).
     * - Initialise la collection refRight.
     * @author Alban Truc
     * @since 01/2014
     */

    public function __construct()
    {
        parent::__construct();
        $this->refRightCollection = $this->getCollection('refright');
    }

    /**
     * Modifications de certaines données
     * @author Alban Truc
     * @param array $refRight
     * @since 30/04/2014
     * @return array
     */

    public function convert($refRight)
    {
        if(is_array($refRight))
        {
            if(isset($refRight['_id']))
                $refRight['_id'] = (string)$refRight['_id']; // MongoId => string
        }

        return $refRight;
    }

    /**
     * Conversion inverse de celle de la fonction ci-dessus
     * @author Alban Truc
     * @param array $refRight
     * @since 08/06/2014
     * @return array
     */

    public function reverseConvert($refRight)
    {
        if(is_array($refRight))
        {
            if(isset($refRight['_id']))
                $refRight['_id'] = new MongoId($refRight['_id']); // string => MongoId
        }

        return $refRight;
    }

    /**
     * Retrouver un refRight selon des critères donnés
     * @author Alban Truc
     * @param array $criteria critères de recherche
     * @param array $fieldsToReturn champs à récupérer
     * @since 29/03/2014
     * @return array
     */

    public function find($criteria, $fieldsToReturn = array())
    {
        $criteria = self::reverseConvert($criteria);

        $cursor = parent::__find('refright', $criteria, $fieldsToReturn);

        if(!(is_array($cursor)) && !(array_key_exists('error', $cursor)))
        {
            $refRights = array();

            foreach($cursor as $refRight)
            {
                $refRight = self::convert($refRight);
                $refRights[] = $refRight;
            }

            if(empty($refRights))
                return array('error' => 'No match found.');
            else
                return $refRights;
        }
        else return $cursor; //message d'erreur
    }

    /**
     * Retourne le premier refRight correspondant au(x) critère(s) donné(s)
     * @author Alban Truc
     * @param array $criteria critère(s) de recherche
     * @param array $fieldsToReturn champs à retourner
     * @since 29/03/2014
     * @return array
     */

    public function findOne($criteria, $fieldsToReturn = array())
    {
        $criteria = self::reverseConvert($criteria);

        $result = parent::__findOne('refright', $criteria, $fieldsToReturn);
        $result = self::convert($result);

        return $result;
    }

    /**
     * - Retrouver un refRight par son ID.
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param string|MongoId $id Identifiant unique de l'refRight à trouver
     * @param array $fieldsToReturn champs à retourner
     * @since 02/2014
     * @return array contenant le message d'erreur
     */

    public function findById($id, $fieldsToReturn = array())
    {
        $result = parent::__findOne('refright', array('_id' => new MongoId($id)), $fieldsToReturn);
        $result = self::convert($result);

        return $result;
    }

    /**
     * - Retrouver l'ensemble des refRights
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $fieldsToReturn champs à retourner
     * @since 11/03/2014
     * @return array tableau d'objets RefRight
     */

    public function findAll($fieldsToReturn = array())
    {
        $cursor = parent::__find('refright', $fieldsToReturn);

        if(!(is_array($cursor)) && !(array_key_exists('error', $cursor)))
        {
            $refRights = array();

            foreach($cursor as $refRight)
            {
                $refRight = self::convert($refRight);
                $refRights[] = $refRight;
            }
        }

        if(empty($refRights))
            return array('error' => 'No refRight found.');
        else
            return $refRights;
    }

    /**
     * - Retrouver un refRight selon certains critères et le modifier/supprimer
     * - Récupérer cet refRight ou sa version modifiée
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

        $result = parent::__findAndModify('refright', $searchQuery, $updateCriteria, $fieldsToReturn, $options);
        $result = self::convert($result);

        return $result;
    }

    /**
     * - Ajoute un refRight en base de données
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $document
     * @param array $options
     * @since 12/03/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    public function create($document, $options = array('w' => 1))
    {
        $document = self::reverseConvert($document);

        $result = parent::__create('refright', $document, $options);

        return $result;
    }

    /**
     * Fonction d'update utilisant celle de {@see AbstractManager}
     * @author Alban Truc
     * @param array $criteria description des entrées à modifier
     * @param array $update nouvelles valeurs
     * @param array|NULL $options
     * @since 11/03/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    public function update($criteria, $update, $options = array('w' => 1))
    {
        $criteria = self::reverseConvert($criteria);
        $update = self::reverseConvert($update);

        $result = parent::__update('refright', $criteria, $update, $options);

        return $result;
    }

    /**
     * - Supprime un/des refRight(s) correspondant à des critères données
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $criteria ce qu'il faut supprimer
     * @param array $options
     * @since 11/03/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    public function remove($criteria, $options = array('w' => 1))
    {
        $criteria = self::reverseConvert($criteria);

        $result = parent::__remove('refright', $criteria, $options);

        return $result;
    }
}