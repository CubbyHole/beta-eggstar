<?php
/**
 * Created by PhpStorm.
 * User: Crocell
 * Date: 25/04/14
 * Time: 15:02
 */

/** @var string $projectRoot chemin du projet dans le système de fichier */
$projectRoot = $_SERVER['DOCUMENT_ROOT'].'/eggstar/v2';

require_once $projectRoot.'/required.php';

/**
 * Class RefElementManager
 * @author Alban Truc
 */
class RefElementManager extends AbstractManager implements RefElementManagerInterface{

    /** @var MongoCollection $refElementCollection collection refElement */
    protected $refElementCollection;

    /**
     * Constructeur:
     * - Appelle le constructeur de {@see AbstractManager::__construct} (gestion des accès de la BDD).
     * - Initialise la collection refElement.
     * @author Alban Truc
     * @since 01/2014
     */

    public function __construct()
    {
        parent::__construct();
        $this->refElementCollection = $this->getCollection('refelement');
    }

    /**
     * Modifications de certaines données
     * @author Alban Truc
     * @param array $refElement
     * @since 30/04/2014
     * @return array
     */

    public function convert($refElement)
    {
        if(is_array($refElement))
        {
            if(isset($refElement['_id']))
                $refElement['_id'] = (string)$refElement['_id']; // MongoId => string
        }

        return $refElement;
    }

    /**
     * Retrouver un refElement selon des critères donnés
     * @author Alban Truc
     * @param array $criteria critères de recherche
     * @param array $fieldsToReturn champs à récupérer
     * @since 29/03/2014
     * @return array
     */

    public function find($criteria, $fieldsToReturn = array())
    {
        $cursor = parent::__find('refelement', $criteria, $fieldsToReturn);

        if(!(is_array($cursor)) && !(array_key_exists('error', $cursor)))
        {
            $refElements = array();

            foreach($cursor as $refElement)
            {
                $refElement = self::convert($refElement);
                $refElements[] = $refElement;
            }

            if(empty($refElements))
                return array('error' => 'No match found.');
            else
                return $refElements;
        }
        else return $cursor; //message d'erreur
    }

    /**
     * Retourne le premier refElement correspondant au(x) critère(s) donné(s)
     * @author Alban Truc
     * @param array $criteria critère(s) de recherche
     * @param array $fieldsToReturn champs à retourner
     * @since 29/03/2014
     * @return array
     */

    public function findOne($criteria, $fieldsToReturn = array())
    {
        $result = parent::__findOne('refelement', $criteria, $fieldsToReturn);
        $result = self::convert($result);

        return $result;
    }

    /**
     * - Retrouver un refElement par son ID.
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param string|MongoId $id Identifiant unique de l'refElement à trouver
     * @param array $fieldsToReturn champs à retourner
     * @since 02/2014
     * @return array contenant le message d'erreur
     */

    public function findById($id, $fieldsToReturn = array())
    {
        $result = parent::__findOne('refelement', array('_id' => new MongoId($id)), $fieldsToReturn);
        $result = self::convert($result);

        return $result;
    }

    /**
     * - Retrouver l'ensemble des refElements
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $fieldsToReturn champs à retourner
     * @since 11/03/2014
     * @return array tableau d'objets RefElement
     */

    public function findAll($fieldsToReturn = array())
    {
        $cursor = parent::__find('refelement', $fieldsToReturn);

        if(!(is_array($cursor)) && !(array_key_exists('error', $cursor)))
        {
            $refElements = array();

            foreach($cursor as $refElement)
            {
                $refElement = self::convert($refElement);
                $refElements[] = $refElement;
            }
        }

        if(empty($refElements))
            return array('error' => 'No refElement found.');
        else
            return $refElements;
    }

    /**
     * - Retrouver un refElement selon certains critères et le modifier/supprimer
     * - Récupérer cet refElement ou sa version modifiée
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
        $result = parent::__findAndModify('refelement', $searchQuery, $updateCriteria, $fieldsToReturn, $options);
        $result = self::convert($result);

        return $result;
    }

    /**
     * - Ajoute un refElement en base de données
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $document
     * @param array $options
     * @since 12/03/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    public function create($document, $options = array('w' => 1))
    {
        $result = parent::__create('refelement', $document, $options);

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
        $result = parent::__update('refelement', $criteria, $update, $options);

        return $result;
    }

    /**
     * - Supprime un/des refElement(s) correspondant à des critères données
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $criteria ce qu'il faut supprimer
     * @param array $options
     * @since 11/03/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    public function remove($criteria, $options = array('w' => 1))
    {
        $result = parent::__remove('refelement', $criteria, $options);

        return $result;
    }
}