<?php
/**
 * Created by PhpStorm.
 * User: Crocell
 * Date: 12/06/14
 * Time: 22:23
 */

function findSharesForElement($idElement, $idUser)
{
    $idElement = new MongoId($idElement);
    $idUser = new MongoId($idUser);

    //vérification de l'existence de l'élément
    $elementManager = new ElementManager();

    $element = $elementManager->findById($idElement);

    if(array_key_exists('error', $element))
        return $element;

    if($element['idOwner'] != $idUser)
        return array('error' => 'You need to be the owner of the file to get sharing information on it.');

    $rightManager = new RightManager();

    $rightCriteria = array(
        'state' => (int)1,
        'idElement' => $idElement
    );

    $rights = $rightManager->find($rightCriteria);

    $userManager = new UserManager();
    $refRightManager = new RefRightManager();

    $userFieldsToReturn = array('email' => TRUE);
    $refRightFieldsToReturn = array('code' => TRUE);
    $user = $refRight = NULL;

    foreach($rights as  $key => $right)
    {
        $user = $userManager->findById($right['idUser'], $userFieldsToReturn);
        $refRight = $refRightManager->findById($right['idRefRight'], $refRightFieldsToReturn);

        if(array_key_exists('error', $user) || array_key_exists('error', $refRight))
            unset($rights[$key]);

        unset($rights[$key]['idUser']);
        unset($rights[$key]['idRefRight']);
        $rights[$key]['user'] = $user;
        $rights[$key]['refRight'] = $refRight;
    }

    if(empty($rights))
        return array('error' => 'No righ found for the element');
    else
        return $rights;
}

/**
 * Partage (lecture ou lecture et écriture) d'un élément avec un autre utilisateur
 * @author Alban Truc
 * @param string|MongoId $idElement
 * @param string|MongoId $idOwner
 * @param string $recipientEmail
 * @param string $refRightCode
 * @param bool $sendMail
 * @since 12/06/2014
 * @return array|bool
 */

function shareWithUser($idElement, $idOwner, $recipientEmail, $refRightCode, $sendMail = FALSE)
{
    $idElement = new MongoId($idElement);
    $idOwner = new MongoId($idOwner);

    $elementManager = new ElementManager();

    $elementCriteria = array(
        'state' => (int)1,
        '_id' => $idElement
    );

    $element = $elementManager->findOne($elementCriteria);

    if(is_array($element) && !(array_key_exists('error', $element)))
    {
        /*
         * vérification que l'idOwner en param de la fonction est le même que celui de l'element, la gestion des partages
         * n'étant dans cette version qu'accessible au propriétaire de l'élément
         */
        if($idOwner == $element['idOwner'])
        {
            //vérification que l'email indiquée appartient bien à un utilisateur inscrit
            $userCriteria = array(
                'state' => (int)1,
                'email' => $recipientEmail
            );

            $userManager = new UserManager();
            $recipientUser = $userManager->findOne($userCriteria);

            if(is_array($recipientUser) && !(array_key_exists('error', $recipientUser)))
            {
                if($recipientUser['_id'] != $idOwner)
                {
                    //récupérer l'id du refRight
                    $refRightCriteria = array(
                        'state' => (int)1,
                        'code' => $refRightCode
                    );

                    $refRightManager = new RefRightManager();
                    $refRight = $refRightManager->findOne($refRightCriteria, array('_id' => TRUE));

                    if(is_array($refRight) && !(array_key_exists('error', $refRight)))
                    {
                        $rightList = array();

                        $refRightId = $refRight['_id'];

                        $newRight = array(
                            'state' => (int)1,
                            'idUser' => $recipientUser['_id'],
                            'idElement' => $idElement,
                            'idRefRight' => $refRightId
                        );

                        $rightList[] = $newRight;

                        /*
                         * vérification qu'il ne s'agit pas d'un dossier vide (inutile de chercher à copier le droit
                         * pour d'éventuels contenus sinon)
                         */
                        $isNonEmptyFolder = isFolder($element['idRefElement'], TRUE);

                        if(is_bool($isNonEmptyFolder))
                        {
                            if($isNonEmptyFolder == TRUE)
                            {
                                //récupération des éléments contenus dans le dossier
                                $folderPath = $element['serverPath'].$element['name'].'/';

                                $elementsInFolderCriteria = array(
                                    'state' => 1,
                                    'idOwner' => $idOwner,
                                    'serverPath' => new MongoRegex("/^$folderPath/i")
                                );

                                $elementsInFolder = $elementManager->find($elementsInFolderCriteria);

                                if(is_array($elementsInFolder) && !(array_key_exists('error', $elementsInFolder)))
                                {
                                    foreach($elementsInFolder as $elementInFolder)
                                    {
                                        $rightCopy = $newRight;
                                        $rightCopy['idElement'] = $elementInFolder['_id'];
                                        $rightList[] = $rightCopy;
                                    }
                                }
                                else return $elementsInFolder;
                            }
                        }
                        else return $isNonEmptyFolder;

                        /*
                         * Insertion ou mise à jour du droit en base. De fait cette fonction est utilisé pour la création
                         * et la mise à jour de droit.
                         */
                        $rightManager = new RightManager();

                        $rightCriteria = array(
                            'state' => (int)1,
                            'idUser' => $recipientUser['_id']
                        );

                        $options = array(
                            'upsert' => TRUE
                        );

                        foreach($rightList as $right)
                        {
                            $rightCriteria['idElement'] = $right['idElement'];
                            $rightManager->update($rightCriteria, array('$set' => $right), $options);
                        }

                        return TRUE;
                        //@todo envoyer un mail
                    }
                    else return $refRight;
                }
                else return array('error' => 'You cannot share an element with yourself');
            }
            else return $recipientUser;
        }
        else return array('error' => 'You are not the owner of this element, you cannot share it.');
    }
    else return $element;
}

/**
 * Permet de désactiver les droits d'un élément pour un user, gestion récursive pour les dossiers.
 * @author Harry Bellod & Alban Truc
 * @param string|MongoId $idElement  id de l'élément qu'on veut désactiver
 * @param string|MongoId $idUser  id de l'utilisateur concerné
 * @param string|MongoId $idOwner  id du propriétaire de l'élément
 * @since 15/06/2014
 * @return bool|array contenant un message d'erreur
 */

function disableShareRights($idElement, $idUser, $idOwner)
{
    $idElement = new MongoId($idElement);
    $idUser = new MongoId($idUser);
    $idOwner = new MongoId($idOwner);

    $elementManager = new ElementPdoManager();
    $refElementManager = new RefElementPdoManager();
    $rightPdoManager = new RightPdoManager();

    $element = $elementManager->findById($idElement);
    $refElement = $refElementManager->findById($element['idRefElement']);
    $idRefElement = $refElement['_id'];

    /** @var  $isFolder => bool, true si l'élément est bien un dossier, sinon false */
    $isFolder = isFolder($idRefElement);

    if(is_bool($isFolder) && $isFolder == TRUE)
    {
        $serverPath = $element['serverPath'].$element['name'].'/';

        //récupération des éléments contenus dans le dossier
        $seekElementsInFolder = array(
            'state' => (int)1,
            'serverPath' => new MongoRegex("/^$serverPath/i"),
            'idOwner' => $idOwner
        );

        //liste des éléments contenus dans le dossier
        $elementsInFolder = $elementManager->find($seekElementsInFolder);
        foreach($elementsInFolder as $subElement)
        {
            $rightCriteria = array(
                'state' => (int) 1,
                'idElement' => new MongoId($subElement['_id']),
                'idUser' => $idUser
            );

            $rightUpdate = array(
                '$set' => array( 'state' => (int) 0)
            );

            //pour chaque élément on désactive le droit qui lui était affecté
            $disableElementsInFolder = $rightPdoManager->update($rightCriteria, $rightUpdate);
            if(is_bool($disableElementsInFolder) && $disableElementsInFolder != TRUE)
                return array('error' => 'No match found.');
        }
    }

    $rightCriteria = array(
        'state' => (int) 1,
        'idElement' => $idElement,
        'idUser' => $idUser
    );
    $rightUpdate = array(
        '$set' => array( 'state' => (int) 0)
    );
    //désactivation de l'élément parent
    $disableParent = $rightPdoManager->update($rightCriteria, $rightUpdate);
    if(is_bool($disableParent) && $disableParent != TRUE)
        return array('error' => 'No match found.');
}