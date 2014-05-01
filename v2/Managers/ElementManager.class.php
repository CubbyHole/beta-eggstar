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

    public function find($criteria, $fieldsToReturn = array())
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

    public function findOne($criteria, $fieldsToReturn = array())
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

    public function findById($id, $fieldsToReturn = array())
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

    public function findAll($fieldsToReturn = array())
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

    public function findAndModify($searchQuery, $updateCriteria, $fieldsToReturn = NULL, $options = NULL)
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

    public function create($document, $options = array('w' => 1))
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

    public function update($criteria, $update, $options = array('w' => 1))
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

    public function remove($criteria, $options = array('w' => 1))
    {
        $result = parent::__remove('element', $criteria, $options);

        return $result;
    }

    /**
     * - Distingue deux cas: récupération des éléments d'un utilisateur et récupération des éléments partagés avec un utilisateur
     * - Dans le 1er cas (isOwner = 1), on retourne les infos de l'élément et du refElement
     * - Dans le second cas (isOwner = 0), on retourne le droit, le refRight, l'élément, le refElement et le propriétaire
     * - Gestion des erreurs
     * @author Alban Truc
     * @param string|MongoId $idUser
     * @param string $isOwner
     * @since 01/05/2014
     * @return array
     */

    public function returnElementsDetails($idUser, $isOwner)
    {
        if($isOwner == '1')
        {
            $criteria = array(
                'state' => (int)1,
                'idOwner' => new MongoId($idUser)
            );

            $elements = self::find($criteria);

            if(is_array($elements) && !(array_key_exists('error', $elements)))
            {
                //récupération des refElement pour chaque élément
                foreach($elements as $key => $element)
                {
                    unset($element['idOwner']);

                    $refElementManager = new RefElementManager();
                    $refElement = $refElementManager->findById($element['idRefElement']);

                    unset($element['idRefElement']);

                    if(is_array($refElement) && !(array_key_exists('error', $refElement)))
                    {
                        $element['refElement'] = $refElement;
                        $elements[$key] = $element;
                    }
                    else unset($elements[$key]);
                }

                if(empty($elements))
                    return array('error' => 'No match found.');
            }

            return $elements;
        }
        else if($isOwner == '0')
        {
            return self::returnSharedElementsDetails($idUser);
        }
        else return array('error' => 'Parameter isOwner must be 0 or 1');
    }

    /**
     * Retourne le droit, le refRight, l'élément, le refElement et le propriétaire
     * @author Alban Truc
     * @param string|MongoId $idUser
     * @since 01/05/2014
     * @return array
     */

    public function returnSharedElementsDetails($idUser)
    {
        $criteria = array(
            'state' => (int)1,
            'idUser' => new MongoId($idUser)
        );

        //récupération des droits sur les éléments
        $rightManager = new RightManager();
        $rights = $rightManager->find($criteria);

        $userManager = new UserManager();
        $refRightManager = new RefRightManager();
        $refElementManager = new RefElementManager();

        if(is_array($rights) && !(array_key_exists('error', $rights)))
        {
            foreach($rights as $key => $right)//pour chaque droit
            {
                $owner = NULL;
                $refRight = NULL;

                //Récupération de l'élément. On enlève le droit de la liste si l'élément n'est pas disponible
                $elementCriteria = array(
                    '_id' => new MongoId($right['idElement']),
                    'state' => (int)1
                );

                unset($right['idElement']);
                $element = self::findOne($elementCriteria);
                $idOwner = $element['idOwner'];
                unset($element['idOwner']);

                if(is_array($element) && !(array_key_exists('error', $element)))
                {
                    //récupération du refElement. On enlève le droit de la liste si le refElement n'est pas trouvé
                    $refElement = $refElementManager->findById($element['idRefElement']);
                    unset($element['idRefElement']);

                    if(is_array($refElement) && !(array_key_exists('error', $refElement)))
                    {
                        $element['refElement'] = $refElement;
                        $right['element'] = $element;
                    }
                    else
                    {
                        unset($rights[$key]);
                        break;
                    }
                }
                else
                {
                    unset($rights[$key]);
                    break;
                }

                /*
                 * Récupération de l'utilisateur propriétaire.
                 * Si son état n'est pas à 1, le droit n'aurait pas du être à 1; donc on enlève ce droit de la liste.
                 */
                $userCriteria = array(
                    '_id' => new MongoId($idOwner),
                    'state' => (int)1
                );

                $fieldsToReturn = array(
                    'firstName' => 1,
                    'lastName' => 1
                );

                unset($right['idUser']);

                $owner = $userManager->findOne($userCriteria, $fieldsToReturn);

                if(is_array($owner) && !(array_key_exists('error', $owner)))
                {
                    $right['owner'] = $owner;
                }
                else
                {
                    unset($rights[$key]);
                    break;
                }

                //Récupération du refRight. S'il n'existe pas on enlève ce droit de la liste.
                $refRight = $refRightManager->findById($right['idRefRight']);
                unset($right['idRefRight']);

                if(is_array($refRight) && !(array_key_exists('error', $refRight)))
                {
                    $right['refRight'] = $refRight;
                }
                else
                {
                    unset($rights[$key]);
                    break;
                }

                $rights[$key] = $right;
            }

            if(empty($rights))
                return array('error' => 'No match found.');
        }

        return $rights;
    }
}