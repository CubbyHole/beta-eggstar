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
 * Rend un élément inaccessible (en BDD, ne comporte pas d'action sur le serveur de fichier).
 * @author Alban Truc
 * @param MongoId|string $idElement
 * @param MongoId|string $idUser
 * @param bool $returnImpactedElements si TRUE, retourne les éléments impactés par l'action
 * @since 31/05/2014
 * @return array|Element
 * @todo appel de la fonction qui fait diverses tâches (cf. documentation) sur le serveur de fichier
 */

function disableHandler($idElement, $idUser, $returnImpactedElements = FALSE)
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
                        //var_dump($impactedElements); exit();
                        $elementUpdateResult = $elementManager->update($elementCriteria, $elementUpdate, $options);

                        if(!(is_array($elementUpdateResult)))
                        {
                            if($elementUpdateResult === TRUE)
                            {
                                //séparation en deux tableaux
                                $idImpactedElements = array();
                                $sizeImpactedElements = array();

                                foreach($impactedElements as $impactedElement)
                                {
                                    //création d'un tableau contenant uniquement les id des éléments impactés
                                    $idImpactedElements[] = $impactedElement['_id'];

                                    //création d'un tableau contenant uniquement la taille de chaque élément impacté
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
                                        if($returnImpactedElements == TRUE)
                                            return $idImpactedElements;
                                        else return TRUE;
                                    }
                                    else return array('error' => 'Did not manage to update the storage value.');
                                }
                                else return $accountUpdateResult;
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