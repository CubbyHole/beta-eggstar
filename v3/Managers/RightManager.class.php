<?php
/**
 * Created by PhpStorm.
 * User: Crocell
 * Date: 25/04/14
 * Time: 15:09
 */

/** @var string $projectRoot chemin du projet dans le système de fichier */
$projectRoot = $_SERVER['DOCUMENT_ROOT'].'/eggstar/v3';

require_once $projectRoot.'/required.php';

/**
 * Class RightManager
 * @author Alban Truc
 */
class RightManager extends AbstractManager implements RightManagerInterface{

    /** @var MongoCollection $rightCollection collection right */
    protected $rightCollection;

    /**
     * Constructeur:
     * - Appelle le constructeur de {@see AbstractManager::__construct} (gestion des accès de la BDD).
     * - Initialise la collection right.
     * @author Alban Truc
     * @since 01/2014
     */

    public function __construct()
    {
        parent::__construct();
        $this->rightCollection = $this->getCollection('right');
    }

    /**
     * Modifications de certaines données
     * @author Alban Truc
     * @param array $right
     * @since 30/04/2014
     * @return array
     */

    public function convert($right)
    {
        if(is_array($right))
        {
            if(isset($right['_id']))
                $right['_id'] = (string)$right['_id']; // MongoId => string

            if(isset($right['idUser']))
                $right['idUser'] = (string)$right['idUser']; // MongoId => string

            if(isset($right['idElement']))
                $right['idElement'] = (string)$right['idElement']; // MongoId => string

            if(isset($right['idRefRight']))
                $right['idRefRight'] = (string)$right['idRefRight']; // MongoId => string
        }

        return $right;
    }

    /**
     * Conversion inverse de celle de la fonction ci-dessus
     * @author Alban Truc
     * @param array $right
     * @since 08/06/2014
     * @return array
     */

    public function reverseConvert($right)
    {
        if(is_array($right))
        {
            if(isset($right['_id']))
                $right['_id'] = new MongoId($right['_id']); // string => MongoId

            if(isset($right['idUser']))
                $right['idUser'] = new MongoId($right['idUser']); // string => MongoId

            if(isset($right['idElement']))
                $right['idElement'] = new MongoId($right['idElement']); // string => MongoId

            if(isset($right['idRefRight']))
                $right['idRefRight'] = new MongoId($right['idRefRight']); // string => MongoId
        }

        return $right;
    }

    /**
     * Retrouver un droit selon des critères donnés
     * @author Alban Truc
     * @param array $criteria critères de recherche
     * @param array $fieldsToReturn champs à récupérer
     * @since 29/03/2014
     * @return array
     */

    public function find($criteria, $fieldsToReturn = array())
    {
        $criteria = self::reverseConvert($criteria);

        $cursor = parent::__find('right', $criteria, $fieldsToReturn);

        if(!(is_array($cursor)) && !(array_key_exists('error', $cursor)))
        {
            $rights = array();

            foreach($cursor as $right)
            {
                $right = self::convert($right);
                $rights[] = $right;
            }

            if(empty($rights))
                return array('error' => 'No match found.');
            else
                return $rights;
        }
        else return $cursor; //message d'erreur
    }

    /**
     * Retourne le premier droit correspondant au(x) critère(s) donné(s)
     * @author Alban Truc
     * @param array $criteria critère(s) de recherche
     * @param array $fieldsToReturn champs à retourner
     * @since 29/03/2014
     * @return array
     */

    public function findOne($criteria, $fieldsToReturn = array())
    {
        $criteria = self::reverseConvert($criteria);

        $result = parent::__findOne('right', $criteria, $fieldsToReturn);
        $result = self::convert($result);

        return $result;
    }

    /**
     * - Retrouver un droit par son ID.
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param string|MongoId $id Identifiant unique du droit à trouver
     * @param array $fieldsToReturn champs à retourner
     * @since 02/2014
     * @return array contenant le message d'erreur
     */

    public function findById($id, $fieldsToReturn = array())
    {
        $result = parent::__findOne('right', array('_id' => new MongoId($id)), $fieldsToReturn);
        $result = self::convert($result);

        return $result;
    }

    /**
     * - Retrouver l'ensemble des droits
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $fieldsToReturn champs à retourner
     * @since 11/03/2014
     * @return array tableau d'objets Right
     */

    public function findAll($fieldsToReturn = array())
    {
        $cursor = parent::__find('right', $fieldsToReturn);

        if(!(is_array($cursor)) && !(array_key_exists('error', $cursor)))
        {
            $rights = array();

            foreach($cursor as $right)
            {
                $right = self::convert($right);
                $rights[] = $right;
            }
        }

        if(empty($rights))
            return array('error' => 'No right found.');
        else
            return $rights;
    }

    /**
     * - Retrouver un droit selon certains critères et le modifier/supprimer
     * - Récupérer ce droit ou sa version modifiée
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

        $result = parent::__findAndModify('right', $searchQuery, $updateCriteria, $fieldsToReturn, $options);
        $result = self::convert($result);

        return $result;
    }

    /**
     * - Ajoute un droit en base de données
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

        $result = parent::__create('right', $document, $options);

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

        $result = parent::__update('right', $criteria, $update, $options);

        return $result;
    }

    /**
     * - Supprime un/des droit(s) correspondant à des critères données
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

        $result = parent::__remove('right', $criteria, $options);

        return $result;
    }

    /**
     * Indique si l'utilisateur donné a les droits voulus sur l'élément donné
     * @author Alban Truc
     * @param MongoId|string $idUser
     * @param MongoId|string $idElement
     * @param string $refRightCode
     * @param string $returnRights
     * @since 15/05/2014
     * @return bool
     */

    public function hasRightOnElement($idUser, $idElement, $refRightCode, $returnRights = 'FALSE')
    {
        //récupérer l'id du refRight à partir du code
        $refRightManager = new RefRightManager();

        $refRightCriteria = array(
            'state' => (int)1,
            'code' => (string)$refRightCode
        );

        $refRight = $refRightManager->findOne($refRightCriteria);

        //récupérer le droit
        $rightCriteria = array(
            'state' => (int)1,
            'idUser' => new MongoId($idUser),
            'idElement' => new MongoId($idElement),
            'idRefRigt' => $refRight['_id']
        );

        $right = self::find($rightCriteria);

        if(!(array_key_exists('error', $right)))
            return TRUE;
        else return FALSE;
    }
}