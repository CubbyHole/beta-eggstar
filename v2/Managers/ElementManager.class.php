<?php
/**
 * Created by PhpStorm.
 * User: Crocell
 * Date: 25/04/14
 * Time: 14:39
 */

/** @var string $projectRoot chemin du projet dans le système de fichier */
$projectRoot = $_SERVER['DOCUMENT_ROOT'].'/eggstar/v2';

require_once $projectRoot.'/required.php';

/**
 * Class ElementManager
 * @author Alban Truc
 */
class ElementManager extends AbstractManager implements ElementManagerInterface{

    /** @var MongoCollection $elementCollection collection element */
    protected $elementCollection;

    /**
     * Constructeur:
     * - Appelle le constructeur de {@see AbstractManager::__construct} (gestion des accès de la BDD).
     * - Initialise la collection element.
     * @author Alban Truc
     * @since 01/2014
     */

    public function __construct()
    {
        parent::__construct();
        $this->elementCollection = $this->getCollection('element');
    }

    /**
     * Modifications de certaines données
     * @author Alban Truc
     * @param array $element
     * @since 30/04/2014
     * @return array
     */

    public function convert($element)
    {
        if(is_array($element))
        {
            if(isset($element['_id']))
                $element['_id'] = (string)$element['_id']; // MongoId => string

            if(isset($element['idOwner']))
                $element['idOwner'] = (string)$element['idOwner']; // MongoId => string

            if(isset($element['idRefElement']))
                $element['idRefElement'] = (string)$element['idRefElement']; // MongoId => string
        }

        return $element;
    }

    /**
     * Retrouver un élément selon des critères donnés
     * @author Alban Truc
     * @param array $criteria critères de recherche
     * @param array $fieldsToReturn champs à récupérer
     * @since 29/03/2014
     * @return array
     */

    function find($criteria, $fieldsToReturn = array())
    {
        $cursor = parent::__find('element', $criteria, $fieldsToReturn);

        if(!(is_array($cursor)) && !(array_key_exists('error', $cursor)))
        {
            $elements = array();

            foreach($cursor as $element)
            {
                $element = self::convert($element);
                $elements[] = $element;
            }

            if(empty($elements))
                return array('error' => 'No match found.');
            else
                return $elements;
        }
        else return $cursor; //message d'erreur
    }

    /**
     * Retourne le premier élément correspondant au(x) critère(s) donné(s)
     * @author Alban Truc
     * @param array $criteria critère(s) de recherche
     * @param array $fieldsToReturn champs à retourner
     * @since 29/03/2014
     * @return array
     */

    function findOne($criteria, $fieldsToReturn = array())
    {
        $result = parent::__findOne('element', $criteria, $fieldsToReturn);
        $result = self::convert($result);

        return $result;
    }

    /**
     * - Retrouver un élément par son ID.
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param string|MongoId $id Identifiant unique de l'élément à trouver
     * @param array $fieldsToReturn champs à retourner
     * @since 02/2014
     * @return array contenant le message d'erreur
     */

    function findById($id, $fieldsToReturn = array())
    {
        $result = parent::__findOne('element', array('_id' => new MongoId($id)));
        $result = self::convert($result);

        return $result;
    }

    /**
     * - Retrouver l'ensemble des éléments
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $fieldsToReturn champs à retourner
     * @since 11/03/2014
     * @return array tableau d'objets Element
     */

    function findAll($fieldsToReturn = array())
    {
        $cursor = parent::__find('element', array());

        if(!(is_array($cursor)) && !(array_key_exists('error', $cursor)))
        {
            $elements = array();

            foreach($cursor as $element)
            {
                $element = self::convert($element);
                $elements[] = $element;
            }
        }

        if(empty($elements))
            return array('error' => 'No element found.');
        else
            return $elements;
    }

    /**
     * - Retrouver un élément selon certains critères et le modifier/supprimer
     * - Récupérer cet élément ou sa version modifiée
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $searchQuery critères de recherche
     * @param array $updateCriteria les modifications à réaliser
     * @param array|NULL $fieldsToReturn pour ne récupérer que certains champs
     * @param array|NULL $options
     * @since 11/03/2014
     * @return array
     */

    function findAndModify($searchQuery, $updateCriteria, $fieldsToReturn = NULL, $options = NULL)
    {
        $result = parent::__findAndModify('element', $searchQuery, $updateCriteria, $fieldsToReturn, $options);
        $result = self::convert($result);

        return $result;
    }

    /**
     * - Ajoute un élément en base de données
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $document
     * @param array $options
     * @since 12/03/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    function create($document, $options = array('w' => 1))
    {
        $result = parent::__create('element', $document, $options);

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

    function update($criteria, $update, $options = array('w' => 1))
    {
        $result = parent::__update('element', $criteria, $update, $options);

        return $result;
    }

    /**
     * - Supprime un/des élément(s) correspondant à des critères données
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $criteria ce qu'il faut supprimer
     * @param array $options
     * @since 11/03/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    function remove($criteria, $options = array('w' => 1))
    {
        $result = parent::__remove('element', $criteria, $options);

        return $result;
    }
}