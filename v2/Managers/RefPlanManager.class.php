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
require_once $projectRoot.'/Interfaces/RefPlanManager.interface.php';

/**
 * Class RefPlanManager
 * @author Alban Truc
 * @extends AbstractManager
 */
class RefPlanManager extends AbstractManager implements RefPlanManagerInterface{

    /** @var MongoCollection $refPlanCollection collection refPlan */
	protected $refPlanCollection;

    /**
     * Constructeur:
     * - Appelle le constructeur de {@see AbstractManager::__construct} (gestion des accès de la BDD).
     * - Initialise la collection refplan.
     * @author Alban Truc
     * @since 01/2014
     */

	public function __construct()
	{
        parent::__construct();
        $this->refPlanCollection = $this->getCollection('refplan');
	}

    /**
     * Modifications de certaines données
     * @author Alban Truc
     * @param array $refPlan
     * @since 30/04/2014
     * @return array
     */

    public function convert($refPlan)
    {
        if(is_array($refPlan))
        {
            if(isset($refPlan['_id']))
                $refPlan['_id'] = (string)$refPlan['_id']; // MongoId => string
        }

        return $refPlan;
    }

    /**
     * Retrouver un refPlan selon des critères donnés
     * @author Alban Truc
     * @param array $criteria critères de recherche
     * @param array $fieldsToReturn champs à récupérer
     * @since 29/03/2014
     * @return array
     */

    public function find($criteria, $fieldsToReturn = array())
    {
        $cursor = parent::__find('refplan', $criteria, $fieldsToReturn);

        if(!(is_array($cursor)) && !(array_key_exists('error', $cursor)))
        {
            $refPlans = array();

            foreach($cursor as $refPlan)
            {
                $refPlan = self::convert($refPlan);
                $refPlans[] = $refPlan;
            }

            if(empty($refPlans))
                return array('error' => 'No match found.');
            else
                return $refPlans;
        }
        else return $cursor; //message d'erreur
    }

    /**
     * Retourne le premier refPlan correspondant au(x) critère(s) donné(s)
     * @author Alban Truc
     * @param array $criteria critère(s) de recherche
     * @param array $fieldsToReturn champs à retourner
     * @since 29/03/2014
     * @return array
     */

    public function findOne($criteria, $fieldsToReturn = array())
    {
        $result = parent::__findOne('refplan', $criteria, $fieldsToReturn);
        $result = self::convert($result);

        return $result;
    }

    /**
     * - Retrouver un refPlan par son ID.
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param string|MongoId $id Identifiant unique du refPlan à trouver
     * @param array $fieldsToReturn champs à retourner
     * @since 02/2014
     * @return array
     */

    public function findById($id, $fieldsToReturn = array())
	{
        $result = parent::__findOne('refplan', array('_id' => new MongoId($id)), $fieldsToReturn);
        $result = self::convert($result);

        return $result;
	}


    /**
     * - Retrouver le(s) refPlan gratuit(s), soit par son nom (free) soit par son prix de 0
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $fieldsToReturn champs à retourner
     * @since 11/03/2014
     * @return array
     */

    public function findFreePlans($fieldsToReturn = array())
    {
        $criteria = array(
            '$or' => array(
                array('name' => 'free'),
                array('price' => (int)0)
            )
        );

        $cursor = parent::__find('refplan', $criteria, $fieldsToReturn);

        if(!(is_array($cursor)) && !(array_key_exists('error', $cursor)))
        {
            $freePlans = array();

            foreach($cursor as $refPlan)
            {
                $refPlan = self::convert($refPlan);
                $freePlans[] = $refPlan;
            }

            if(empty($freePlans))
                return array('error' => 'No free plan found.');
            else
                return $freePlans;
        }
        else return $cursor; //message d'erreur
    }

    /**
     * - Retrouver le(s) refPlan payants, à savoir ceux dont le prix est > à 0
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $fieldsToReturn champs à retourner
     * @since 11/03/2014
     * @return array
     */

    public function findPremiumPlans($fieldsToReturn = array())
    {
        $criteria = array(
            'price' => array('$gt' => (int)0)
        );

        $cursor = parent::__find('refplan', $criteria, $fieldsToReturn);

        if(!(is_array($cursor)) && !(array_key_exists('error', $cursor)))
        {
            $premiumPlans = array();

            foreach($cursor as $refPlan)
            {
                $refPlan = self::convert($refPlan);
                $premiumPlans[] = $refPlan;
            }

            if(empty($premiumPlans))
                return array('error' => 'No premium plan found.');
            else
                return $premiumPlans;
        }
        else return $cursor; //message d'erreur
    }

    /**
     * - Retrouver l'ensemble des refPlan
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $fieldsToReturn champs à retourner
     * @since 11/03/2014
     * @return array
     */

    public function findAll($fieldsToReturn = array())
    {
        $cursor = parent::__find('refplan', $fieldsToReturn);

        if(!(is_array($cursor)) && !(array_key_exists('error', $cursor)))
        {
            $plans = array();

            foreach($cursor as $refPlan)
            {
                $refPlan = self::convert($refPlan);
                $plans[] = $refPlan;
            }
        }

        if(empty($plans))
            return array('error' => 'No plan found.');
        else
            return $plans;
    }

    /**
     * - Retrouver un RefPlan selon certains critères et le modifier/supprimer
     * - Récupérer ce RefPlan ou sa version modifiée
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
        $result = parent::__findAndModify('refplan', $searchQuery, $updateCriteria, $fieldsToReturn, $options);
        $result = self::convert($result);

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
        $result = parent::__update('refplan', $criteria, $update, $options);

        return $result;
    }

    /**
     * - Supprime un/des plan(s) correspondant à des critères données
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $criteria ce qu'il faut supprimer
     * @param array $options
     * @since 11/03/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    public function remove($criteria, $options = array('w' => 1))
    {
        $result = parent::__remove('refplan', $criteria, $options);

        return $result;
    }

    /**
     * - Ajoute un refPlan en base de données
     * - Gestion des exceptions et des erreurs
     * @author Alban Truc
     * @param array $document
     * @param array $options
     * @since 12/03/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    public function create($document, $options = array('w' => 1))
    {
        $result = parent::__create('refplan', $document, $options);

        return $result;
    }
}

?>