<?php
/**
 * Created by PhpStorm.
 * User: Crocell
 * Date: 28/05/14
 * Time: 14:07
 */

$projectRoot = $_SERVER['DOCUMENT_ROOT'].'/eggstar/v2';
require_once $projectRoot.'/required.php';

function handleActions($request)
{
    if(is_array($request) && isset($request['action']))
    {
        switch ($request['action'])
        {
            case 'rename':
                break;
            case 'move':
                break;
            case 'disable':
                return disableHandler($request['idElement'], $request['idUser'], $request['returnImpactedElements']);
                break;
            case 'copy':
                return copyHandler($request['idElement'], $request['idUser'], $request['path'], $request['options']);
                break;
            case 'uplodad':
                break;
            case 'download':
                break;
        }
    }
    else return array('error' => 'Action parameter required, none found');
}

/**
 * vérifier si l'utilisateur a les droits nécessaires, c'est-à-dire si l'utilisateur est propriétaire
 *  ou le droit nécessaire est présent en base.
 * @author Alban Truc
 * @param MongoId|string $idElement
 * @param MongoId|string $idUser
 * @param array $refRightCodes
 * @since 31/05/2014
 * @return bool|array contenant un message d'erreur
 */

function actionAllowed($idElement, $idUser, $refRightCodes)
{
    $idElement = new MongoId($idElement);
    $idUser = new MongoId($idUser);

    $elementManager = new ElementManager();
    $element = $elementManager->findById($idElement);

    if(!array_key_exists('error', $element))
    {
        $isOwner = $hasRight = FALSE;

        if($element['idOwner'] == $idUser)
            $isOwner = TRUE;
        else
        {
            $rightManager = new RightManager();
            $hasRight = $rightManager->hasRightOnElement($idUser, $idElement, $refRightCodes);
        }

        if($isOwner || $hasRight)
            return TRUE;
        else return FALSE;
    }
    else return array('error' => 'Element does not exist');
}

/**
 * Prend en paramètre un id de refElement et retourne vrai si le refElement désigne un dossier,
 * faux sinon ou un tableau avec l'index error en cas d'erreur.
 * @author Alban Truc
 * @param MongoId|string $refElementId
 * @since 04/06/2014
 * @return array|bool
 * @todo employer cette fonction dans la fonction disableHandler
 */

function isFolder($refElementId)
{
    $refElementId = new MongoId($refElementId);

    //récupère le code du refElement de notre élément
    $refElementManager = new RefElementManager();
    $refElement = $refElementManager->findById($refElementId, array('code' => TRUE));

    if(!(array_key_exists('error', $refElement)))
    {
        //si le code commence par un 4 (les codes de dossier commencent par un 4)
        if(preg_match('/^4/', $refElement['code']))
            return TRUE;
        else
            return FALSE;
    }
    else return $refElement; //message d'erreur
}

/**
 * permet de modifier le statut d'un dossier à empty après un disable dans le-dit dossier s'il ne contient plus d'élément actif (=> state = 1)
 * permet également de modifier le statut d'un dossier à notempty après une copie dans le-dit dossier
 * @author Harry Bellod - adapté pour l'API par Alban Truc
 * @param string $serverPath (de l'élément disable ou du dossier où l'on copie)
 * @since 07/06/2014
 * @return bool true si update ok
 */

function updateFolderStatus($serverPath)
{
    $elementManager = new ElementManager();
    $refElementManager = new RefElementManager();

    if($serverPath != '/')
    {
        //on vérifie s'il reste des éléments actifs dans le dossier où l'on désactive l'élément
        $elements = $elementManager->find(array('serverPath' => $serverPath, 'state' => 1));

        //s'il n'y en a plus alors on passe le dossier courant à empty
        if(array_key_exists('error', $elements))
        {
            if($elements['error'] == 'No match found.')
                $refElement = $refElementManager->findOne(array('code' => '4002', 'state' => (int)1));
            else
                return $elements;
        }
        else
        {
            $refElement = $refElementManager->findOne(array('code' => '4003', 'state' => (int)1));
        }

        if(!(array_key_exists('error', $refElement)))
            $idRefElement = $refElement['_id'];
        else
            return $refElement;

        // on récupère le nom du dossier où l'on se trouve
        $explode = explode("/", $serverPath);
        $currentDirectory = $explode[sizeof($explode)-2];

        // on récupère son serverPath
        $pattern = "#".$currentDirectory."/#";
        $path = preg_replace($pattern, '', $serverPath,1);

        // on réalise une màj sur le dossier en question pour modifier son refElement (à Directory File Empty)
        $criteria = array('state' => (int) 1, 'name' => $currentDirectory, 'serverPath' => $path);

        $update = array(
            '$set' => array('idRefElement' => $idRefElement)
        );

        return $elementManager->update($criteria, $update);
    }
    return true; //rien à faire
}

/**
 * Fait la somme des size. Accepte une liste d'objets Element ou une liste de tableaux.
 * @author Alban Truc
 * @param array $elementList
 * @since 05/06/2014
 * @return int
 */

function sumSize($elementList)
{
    $totalSize = 0;

    foreach($elementList as $element)
    {
        if(array_key_exists('size', $element))
            $totalSize += $element['size'];
    }

    return $totalSize;
}

/**
 * Copie les droits appliqués aux éléments d'une liste source pour les éléments d'une autre liste.
 * Attention: les deux listes doivent être de même taille et l'ordre des éléments car une association des id des éléments
 * sources et ceux de la seconde liste sont associés dans leur ordre de rangement.
 * @author Alban Truc
 * @param array $sourceElementList
 * @param array $pastedElementList
 * @since 07/06/05
 */

function copyRights($sourceElementList, $pastedElementList)
{
    $rightManager = new RightManager();

    $rightCriteria = array(
        'state' => (int) 1,
    );

    $sourceRightList = array();

    $associateIds = array();
    $count = 0;

    foreach($sourceElementList as $sourceElement)
    {
        $associateIds[(string)$sourceElement['_id']] = (string)$pastedElementList[$count]['_id'];
        $count++;
        $rightCriteria['idElement'] = $sourceElement['_id'];
        $rights = $rightManager->find($rightCriteria);

        if(!(array_key_exists('error', $rights)))
            $sourceRightList = array_merge_recursive($sourceRightList, $rights);
    }

    //si on voulait log
    //$rightsToPaste = array();
    //$pastedRights = array();
    //$failedToPaste = array();
    //$count = 0

    foreach($sourceRightList as $right)
    {
        $rightCopy = $right;
        $rightCopy['_id'] = new MongoId();
        $rightCopy['idElement'] = new MongoId($associateIds[(string)$right['idElement']]);

        //$rightsToPaste[] = $rightCopy;
        $insertResult = $rightManager->create($rightCopy);

        //si on voulait log
        /*
        if(!(is_bool($insertResult))) //erreur
        {
            $failedToPaste[$count]['rightToCopy'] = $right;
            $failedToPaste[$count]['rightCopy'] = $rightCopy;
            $failedToPaste[$count]['error'] = $insertResult['error'];
            $count++;
        }
        */
    }
}

/**
 * Renomme de la même manière que le ferait un OS Windows pour éviter les collisions de nom.
 * Remarque: la version actuelle de cette fonction ne prend pas en compte l'extension du fichier (si l'élément est
 * effectivement un fichier). On ne peut donc pas avoir dans un même emplacement un fichier test.flac et test.mp3.
 * @author Alban Truc
 * @param string $path
 * @param array $element
 * @since 07/06/2014
 * @return array|Element[]|string
 */

function avoidNameCollision($path, $element)
{
    if(is_array($element))
    {
        $elementManager = new ElementManager();

        //un élément avec le même nom n'est-il pas déjà présent?
        $seekForNameDuplicate = array(
            'state' => (int)1,
            'serverPath' => $path,
            'name' => $element['name']
        );

        $elementsWithSameName = $elementManager->find($seekForNameDuplicate);
        //var_dump($elementsWithSameName);

        if(array_key_exists('error', $elementsWithSameName))
        {
            //cas no match found => pas d'élément avec le même nom à l'emplacement de destination
            if($elementsWithSameName['error'] == 'No match found.')
            {
                $elementNameInDestination = $element['name'];
            }
            else return $elementsWithSameName;
        }
        else //nom déjà utilisé
        {
            //existe-t-il déjà des copies?
            $seekForCopies = array(
                'state' => (int)1,
                'serverPath' => $path,
                'name' => new MongoRegex("/^".$element['name']." - Copy/i")
            );

            $duplicate = $elementManager->find($seekForCopies, array('name' => TRUE, '_id' => FALSE));
            //var_dump($duplicate);

            if(array_key_exists('error', $duplicate))
            {
                //cas où il n'y a pas de copie
                if($duplicate['error'] == 'No match found.')
                {
                    $elementNameInDestination = $element['name'].' - Copy';
                }
                else return $duplicate;
            }
            else //une ou plusieurs copies ont été trouvées
            {
                /**
                 * actuellement nous avons un tableau de tableaux contenant les noms des duplicats.
                 * Exemple: array ( [0] => array ( ['name'] => 'duplicaName' ) )
                 * La manipulation suivante sert à enlever un "étage" pour obtenir par exemple
                 * array ( [0] => 'duplicataName' ).
                 * Nos environnements de développement ne disposant pas de PHP 5.5.0, nous ne pouvons
                 * utiliser pour cela la fonction array_column. En remplacement, nous appliquons une
                 * fonction via array_map.
                 * @see http://www.php.net/manual/en/function.array-column.php
                 * @see http://www.php.net/manual/en/function.array-map.php
                 */

                $f = function($array){return $array['name'];};
                $duplicate = array_map($f, $duplicate);
                //var_dump($duplicate);
                //@see http://www.php.net/manual/en/function.in-array.php
                if(!(in_array($element['name'].' - Copy', $duplicate)))
                    $elementNameInDestination = $element['name'].' - Copy';
                else
                {
                    /**
                     * @see http://www.php.net/manual/en/function.unset.php
                     * @see http://www.php.net/manual/en/function.array-search.php
                     * Supprime dans le tableau la valeur correspondant à
                     * $element->getName().' - Copy' pour simplifier les opérations suivantes
                     */
                    unset($duplicate[array_search($element['name'].' - Copy', $duplicate)]);

                    //@see http://www.php.net/manual/en/function.sort.php cf. exemple #2
                    sort($duplicate, SORT_NATURAL | SORT_FLAG_CASE);
                    //var_dump($duplicate);

                    /*
                     * déterminer quel nom du type elementName - Copy (number) est disponible,
                     * avec number le plus proche possible de 0
                     */

                    //Le "number" dont il était question plus haut commence à 2
                    $copyNumberIndex = 2;
                    //var_dump($duplicate); exit();
                    if(!(empty($duplicate))) //Plus d'une copie
                    {
                        $count = 0;
                        while(isset($duplicate[$count]))
                        {
                            if($duplicate[$count]==$element['name'].' - Copy ('.$copyNumberIndex.')')
                            {
                                $copyNumberIndex++;
                            }
                            $count++;
                        }
                    }
//                    var_dump($copyNumberIndex);
                    $elementNameInDestination = $element['name'].' - Copy ('.$copyNumberIndex.')';
//                    var_dump($elementNameInDestination); exit();
                }
            }
        }
        return $elementNameInDestination;
    }
    else
        return array('error' => 'array required');
}

/**
 * Prépare le retour de la fonction copyHandler
 * @author Alban Truc
 * @param array $options
 * @param bool $operationSuccess
 * @param array $error
 * @param array $elementsImpacted
 * @param array $pastedElements
 * @param array $failedToPaste
 * @since 06/06/2014
 * @return array
 */

function prepareCopyReturn($options, $operationSuccess, $error, $elementsImpacted, $pastedElements, $failedToPaste)
{
    $return = array();

    $return['operationSuccess'] = $operationSuccess;

    if(is_array($error) && array_key_exists('error', $error))
        $return['error'] = $error['error'];
    else $return['error'] = $error;

    if(is_array($options))
    {
        if(array_key_exists('returnImpactedElements', $options) && $options['returnImpactedElements'] == 'TRUE')
        {
            if(empty($elementsImpacted))
                $return['elementsImpacted'] = 'No impacted element or the function had an error before the element(s) got retrieved.';
            else
                $return['elementsImpacted'] = $elementsImpacted;
        }

        if(array_key_exists('returnPastedElements', $options) && $options['returnPastedElements'] == 'TRUE')
        {
            if(empty($pastedElements))
                $return['pastedElements'] = 'No pasted element or the function had an error before trying to.';
            else
                $return['pastedElements'] = $pastedElements;

            if(empty($failedToPaste))
                $return['failedToPaste'] = 'No fail or the function had an error before trying to.';
            else
                $return['failedToPaste'] = $failedToPaste;
        }
    }
    return $return;
}

/**
 * Rend un élément inaccessible (en BDD, ne comporte pas d'action sur le serveur de fichier).
 * @author Alban Truc
 * @param MongoId|string $idElement
 * @param MongoId|string $idUser
 * @param string $returnImpactedElements si 'TRUE', retourne les éléments impactés par l'action
 * @since 31/05/2014
 * @return array|Element
 * @todo appel de la fonction qui fait diverses tâches (cf. documentation) sur le serveur de fichier
 */

function disableHandler($idElement, $idUser, $returnImpactedElements = 'false')
{
    $idElement = new MongoId($idElement);
    $idUser = new MongoId($idUser);

    // 11 correspond au droit de lecture et écriture
    $hasRight = actionAllowed($idElement, $idUser, array('11'));

    if(!(is_array($hasRight)))
    {
        if($hasRight === TRUE)
        {
            //récupère l'élément
            $elementManager = new ElementManager();
            $element = $elementManager->findById($idElement);

            if(!(array_key_exists('error', $element)))
            {
                if($element['state'] == 1)
                {
                    //récupère le code du refElement de notre élément
                    $refElementManager = new RefElementManager();
                    $refElement = $refElementManager->findById($element['idRefElement'], array('code' => TRUE));

                    if(!(array_key_exists('error', $refElement)))
                    {
                        //si le code commence par un 4 (les codes de dossier commencent par un 4)
                        if(preg_match('/^4/', $refElement['code']))
                        {
                            $serverPath = $element['serverPath'].$element['name'].'/';

                            //notre criteria inclut tous les éléments se trouvant dans le dossier et ses dossiers enfants
                            $elementCriteria = array(
                                'state' => (int) 1,
                                '$or' => array(
                                    array('_id' => $idElement),
                                    array('serverPath' => new MongoRegex("/^$serverPath/i"))
                                )
                            );
                        }
                        else //un fichier
                        {
                            $elementCriteria = array(
                                '_id' => $idElement,
                                'state' => (int)1,
                            );
                        }

                        //pour mettre à jour tous les documents et pas uniquement le premier répondant au critère
                        $options = array(
                            'multiple' => TRUE
                        );

                        //désactivation de l'élément et suppression du lien de téléchargement
                        $elementUpdate = array(
                            '$set' => array(
                                'state' => (int)0,
                                'downloadLink' => ''
                            )
                        );

                        /*
                         * obligatoirement à récupérer avant la mise à jour, sinon aucun document ne devrait être trouvé
                         * en cas de réussite de cette mise à jour. On ne peut pas non plus faire le même critère avec à la
                         * place un état de 0 parce qu'il se peut qu'il y ait déjà des éléments désactivés en base.
                         * L'id est récupérer pour le critère de mise à jour des droits et le size pour la déduction du
                         * stockage occupé par les éléments (mise à jour du storage du compte utilisateur).
                         */
                        $impactedElements = $elementManager->find($elementCriteria, array('_id' => TRUE, 'size' => TRUE));

                        $elementUpdateResult = $elementManager->update($elementCriteria, $elementUpdate, $options);

                        if(!(is_array($elementUpdateResult)))
                        {
                            if($elementUpdateResult === TRUE)
                            {
                                $updateFolderStatus = updateFolderStatus($element['serverPath']);

                                if(is_bool($updateFolderStatus) && $updateFolderStatus === TRUE)
                                {
                                    //séparation en deux tableaux
                                    $idImpactedElements = array();
                                    $sizeImpactedElements = array();

                                    foreach($impactedElements as $impactedElement)
                                    {
                                        //création d'un tableau contenant uniquement les id des éléments impactés
                                        $idImpactedElements[]['idElementImpacted'] = $impactedElement['_id'];

                                        //création d'un tableau contenant uniquement la taille de chaque élément impacté
                                        if(isset($impactedElement['size']))
                                            $sizeImpactedElements[] = $impactedElement['size'];
                                    }
                                    //var_dump($sizeImpactedElements);
                                    //désactivation des droits sur ces éléments
                                    $rightUpdate = array(
                                        '$set' => array(
                                            'state' => (int)0
                                        )
                                    );

                                    $rightManager = new RightManager();

                                    foreach($idImpactedElements as $id)
                                    {
                                        $rightCriteria = array(
                                            'state' => (int)1,
                                            'idElement' => $id
                                        );

                                        //l'opération n'étant pas bloquante, on ne se soucie pour l'instant pas d'un potentiel échec
                                        $rightManager->update($rightCriteria, $rightUpdate, $options);
                                    }

                                    //déduction du stockage occupé par ces éléments dans la collection account
                                    $totalSize = array_sum($sizeImpactedElements); //http://www.php.net/manual/function.array-sum.php
                                    //var_dump($totalSize); exit();
                                    $accountManager = new AccountManager();

                                    $accountCriteria = array(
                                        'state' => (int)1,
                                        'idUser' => $idUser
                                    );

                                    $accountUpdate = array(
                                        '$inc' => array('storage' => -$totalSize)
                                    );

                                    $accountUpdateResult = $accountManager->update($accountCriteria, $accountUpdate);

                                    if(!(is_array($accountUpdateResult)))
                                    {
                                        if($accountUpdateResult === TRUE)
                                        {
                                            if($returnImpactedElements == 'true')
                                                return $idImpactedElements;
                                            else return TRUE;
                                        }
                                        else return array('error' => 'Did not manage to update the storage value.');
                                    }
                                    else return $accountUpdateResult;
                                }
                                else return $updateFolderStatus;
                            }
                            else return array(
                                'error' => 'Did not manage to update all elements.
                                The elements that we couldn\'t updates are in this array at the index \'notUpdated\'',
                                /*
                                 * logiquement, les éléments non mis à jour sont ceux pour lequel l'état est toujours à 1,
                                 * d'où la réutilisation du critère précédent
                                 */
                                'notUpdated' => $elementManager->find($elementCriteria, array('_id' => TRUE))
                            );
                        }
                        else return $elementUpdateResult; //message d'erreur
                    }
                    else return $refElement; //message d'erreur
                }
                else return array('error' => 'Element already inactivated');
            }
            else return $element; //message d'erreur
        }
        else return array('error' => 'Access denied');
    }
    else return $hasRight;
}

/**
 * Copie l'élément (et ce qu'il contient dans le cas d'un dossier) dans la destination indiquée.
 * $options est un tableau de booléens avec comme indexes possibles:
 * - returnImpactedElements pour retourner les éléments à copier
 * - returnPastedElements pour retourner les éléments copiés (ceux présent dans la destination), échec et succès
 * - keepRights pour également copier les droits des éléments sources
 * On peut se retrouver avec cette structure:
 *  array(
 *          'error' => 'message d'erreur',
 *          'impactedElements' => array(),
 *          'pastedElements' => array(),
 *          'failedToPaste' => array())
 *       )
 * @author Alban Truc & Harry Bellod
 * @param string|MongoId $idElement
 * @param string|MongoId $idUser
 * @param string $path
 * @param array $options
 * @since 07/06/2014
 * @return array
 * @todo optimisation: découpage en plusieurs fonctions de moins de 80 lignes
 * @todo meilleure prise en charge des conflits de noms: actuellement l'extension n'est pas prise en compte
 */

function copyHandler($idElement, $idUser, $path, $options = array())
{
    $idElement = new MongoId($idElement);
    $idUser = new MongoId($idUser);

    $impactedElements = array();
    $impactedElements = array();
    $pastedElements = array();
    $failedToPaste = array();

    $operationSuccess = FALSE;

    /*
     * 11 correspond au droit de lecture et écriture.
     * Si on souhaite accepter la copie avec des droits de plus bas niveau, il suffit d'ajouter les codes correspondant
     * au tableau en 3e paramètre ci-dessous.
     */

    $hasRight = actionAllowed($idElement, $idUser, array('11'));

    if(!(is_array($hasRight)))
    {
        if($hasRight === TRUE)
        {
            //récupère l'élément
            $elementManager = new ElementManager();
            $element = $elementManager->findById($idElement);

            if(!(array_key_exists('error', $element)))
            {
                if($element['state'] == 1)
                {
                    if($path != $element['serverPath'])
                    {
                        $elementCriteria = array(
                            'state' => (int)1,
                        );

                        /*
                         * extraction de l'emplacement du dossier de destination à partir de $path
                         * @see http://www.php.net/manual/en/function.implode.php
                         * @see http://www.php.net/manual/en/function.explode.php
                         */
                        $destinationFolderPath = implode('/', explode('/', $path, -2)).'/';
                        $elementCriteria['serverPath'] = $destinationFolderPath;

                        /**
                         * la racine n'ayant pas d'enregistrement pour elle même, on a un serverPath "/" mais de nom.
                         * il faut donc distinguer les cas de copies d'un élément dans la racine des autres cas.
                         */
                        if($path != "/")
                        {
                            /*
                         * extraction du nom du dossier de destination à partir du $path
                         * @see http://www.php.net/manual/en/function.array-slice.php
                         */
                            $destinationFolderName = implode(array_slice(explode('/', $path), -2, 1));
                            $elementCriteria['name'] = $destinationFolderName;
                        }

                        //récupération de l'id de l'élément en base correspondant au dossier de destination
                        $idDestinationFolder = $elementManager->findOne($elementCriteria, array('_id' => TRUE));

                        if((array_key_exists('error', $idDestinationFolder)))
                            return prepareCopyReturn($options, $operationSuccess, $idDestinationFolder, $impactedElements, $pastedElements, $failedToPaste);
                        else
                        {
                            //vérification des droits dans la destination
                            $hasRightOnDestination = actionAllowed($idDestinationFolder['_id'], $idUser, array('11'));

                            if(is_array($hasRightOnDestination) && array_key_exists('error', $hasRightOnDestination))
                                return prepareCopyReturn($options, $operationSuccess, $hasRightOnDestination, $impactedElements, $pastedElements, $failedToPaste);
                            elseif($hasRightOnDestination == FALSE)
                                return prepareCopyReturn($options, $operationSuccess, array('error' => 'Access denied in destination'), $impactedElements, $pastedElements, $failedToPaste);

                        }
                    }

                    $elementNameInDestination = avoidNameCollision($path, $element);

                    if(is_string($elementNameInDestination))
                    {
                        $isElementAFolder = isFolder($element['idRefElement']);

                        if(!(is_array($isElementAFolder))) //pas d'erreur
                        {
                            //récupérer la valeur de storage de l'utilisateur
                            $accountManager = new AccountManager();

                            $accountCriteria = array(
                                'state' => (int)1,
                                'idUser' => $idUser
                            );

                            $fieldsToReturn = array(
                                'storage' => TRUE,
                                'idRefPlan' => TRUE
                            );

                            $account = $accountManager->findOne($accountCriteria, $fieldsToReturn);

                            if(!(array_key_exists('error', $account)))
                            {
                                $currentUserStorage = $account['storage'];

                                //récupérer le stockage maximum autorisé par le plan de l'utilisateur
                                $refPlanManager = new RefPlanManager();

                                $refPlan = $refPlanManager->findById($account['idRefPlan'], array('maxStorage' => TRUE));

                                if(!(array_key_exists('error', $refPlan)))
                                    $maxStorageAllowed = $refPlan['maxStorage'];
                                else
                                    return prepareCopyReturn($options, $operationSuccess, $refPlan, $impactedElements, $pastedElements, $failedToPaste);
                            }
                            else return prepareCopyReturn($options, $operationSuccess, $account, $impactedElements, $pastedElements, $failedToPaste);

                            if($isElementAFolder == TRUE) //l'élément est un dossier
                            {
                                $serverPath = $element['serverPath'].$element['name'].'/';

                                //récupération des éléments contenus dans le dossier
                                $seekElementsInFolder = array(
                                    'state' => (int)1,
                                    'serverPath' => new MongoRegex("/^$serverPath/i")
                                );

                                $elementsInFolder = $elementManager->find($seekElementsInFolder);
                            }

                            if(isset($elementsInFolder) && !(array_key_exists('error', $elementsInFolder)))
                                $impactedElements = $elementsInFolder;

                            $impactedElements[] = $element;

                            $totalSize = sumSize($impactedElements); //calcul de la taille du contenu

                            if($currentUserStorage + $totalSize <= $maxStorageAllowed) //copie autorisée
                            {
                                $count = 0;

                                foreach($impactedElements as $key => $impactedElement)
                                {
                                    //préparation de la copie
                                    $elementCopy = $impactedElement;
                                    $elementCopy['_id'] = new MongoId();

                                    if(count($impactedElements) != $key+1)
                                    {
                                        $explode = explode($serverPath, $elementCopy['serverPath']);

                                        if(isset($explode[1]) && $explode[1] != '')
                                        {
                                            $elementPath = $path.$elementNameInDestination.'/'.$explode[1];
                                            $elementCopy['serverPath'] = $elementPath;
                                        }
                                        else
                                            $elementCopy['serverPath'] = $path.$elementNameInDestination.'/';
                                    }
                                    else
                                    {
                                        $elementCopy['name'] = $elementNameInDestination;
                                        $elementCopy['serverPath'] = $path;
                                    }

                                    $elementCopy['downloadLink'] = '';

                                    //insertion de la copie
                                    $copyResult = $elementManager->create($elementCopy);

                                    //gestion des erreurs
                                    if(!(is_bool($copyResult))) //erreur
                                    {
                                        $failedToPaste[$count]['elementToCopy'] = $impactedElement;
                                        $failedToPaste[$count]['elementCopy'] = $elementCopy;
                                        $failedToPaste[$count]['error'] = $copyResult['error'];
                                        $count++;
                                    }
                                    elseif($copyResult == TRUE)
                                        $pastedElements[] = $elementCopy;
                                }

                                if($totalSize > 0)
                                {
                                    $updateCriteria = array(
                                        '_id' => new MongoId($account['_id']),
                                        'state' => (int)1
                                    );
                                    $storageUpdate = array('$inc' => array('storage' => $totalSize));
                                    $accountUpdate = $accountManager->update($updateCriteria, $storageUpdate);

                                    if(is_array($accountUpdate) && array_key_exists('error', $accountUpdate))
                                    {
                                        $errorMessage = 'Error when trying to add '.$totalSize.' to user account';
                                        return prepareCopyReturn($options, $operationSuccess, $errorMessage, $impactedElements, $pastedElements, $failedToPaste);
                                    }
                                }

                                // Lors de copie dans un dossier, on vérifie si le dossier était empty. Au quel cas on le passe à NotEmpty
                                updateFolderStatus($path);

                                if(array_key_exists('keepRights', $options) && $options['keepRights'] == 'TRUE')
                                    copyRights($impactedElements, $pastedElements);

                                //@todo copie sur le serveur de fichier

                                $operationSuccess = TRUE;

                                return prepareCopyReturn($options, $operationSuccess, array(), $impactedElements, $pastedElements, $failedToPaste);

                            } //pas assez d'espace
                            else
                            {
                                $errorMessage = 'Not enough space available for your account to proceed action';
                                return prepareCopyReturn($options, $operationSuccess, $errorMessage, $impactedElements, $pastedElements, $failedToPaste);
                            }
                        }
                        else return prepareCopyReturn($options, $operationSuccess, $isElementAFolder, $impactedElements, $pastedElements, $failedToPaste);
                    }
                    else return prepareCopyReturn($options, $operationSuccess, $elementNameInDestination, $impactedElements, $pastedElements, $failedToPaste);
                }
                else return prepareCopyReturn($options, $operationSuccess, array('error' => 'Element inactivated, nothing to do'), $impactedElements, $pastedElements, $failedToPaste);
            }
            else return prepareCopyReturn($options, $operationSuccess, $element, $impactedElements, $pastedElements, $failedToPaste);
        }
        else return prepareCopyReturn($options, $operationSuccess, array('error' => 'Access denied'), $impactedElements, $pastedElements, $failedToPaste);
    }
    else return prepareCopyReturn($options, $operationSuccess, $hasRight, $impactedElements, $pastedElements, $failedToPaste);
}

function renameHandler($idElement, $idUser, $name)
{
    $idElement = new MongoId($idElement);
    $idUser = new MongoId($idUser);

    // 11 correspond au droit de lecture et écriture
    $hasRight = actionAllowed($idElement, $idUser, '11');

    if(!(is_array($hasRight)))
    {
        if($hasRight === TRUE)
        {

        }
        else return array('error' => 'Access denied');
    }
    else return $hasRight;
}

function moveHandler($idElement, $idUser, $path)
{
    $idElement = new MongoId($idElement);
    $idUser = new MongoId($idUser);

    // 11 correspond au droit de lecture et écriture
    $hasRight = actionAllowed($idElement, $idUser, '11');

    if(!(is_array($hasRight)))
    {
        if($hasRight === TRUE)
        {

        }
        else return array('error' => 'Access denied');
    }
    else return $hasRight;
}