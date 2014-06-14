<?php
/**
 * Created by PhpStorm.
 * User: Crocell
 * Date: 21/05/14
 * Time: 09:58
 */

$projectRoot = $_SERVER['DOCUMENT_ROOT'].'/eggstar/v2';
require_once $projectRoot.'/required.php';

define('PATH', $_SERVER['DOCUMENT_ROOT'].'/Nestbox');

/**
 * Crée un objet DirectoryIterator
 * @see http://www.php.net/manual/en/class.directoryiterator.php
 * @author Alban Truc
 * @param string $path
 * @since 22/05/2014
 * @return array|DirectoryIterator
 */

function createDirectoryIterator($path)
{
    $path = utf8_decode($path);

    if(is_dir($path) || is_file($path))
    {
        $directoryIterator = new DirectoryIterator($path);
        return $directoryIterator;
    }
    else return array('error' => 'Directory or file not found.');
}

/**
 * Renomme un élément sur le système de fichier
 * @author Alban Truc
 * @param string $idUser
 * @param string $elementPath
 * @param string $oldName
 * @param string $newName
 * @since 22/05/2014
 * @return array|bool
 */

function renameFSElement($idUser, $elementPath, $oldName, $newName)
{
    $elementPath = utf8_decode($elementPath);
    $oldName = utf8_decode($oldName);
    $newName = utf8_decode($newName);

    if(!(is_string($idUser)))
        $idUser = (string)$idUser;

    $fileServerPath = PATH.$idUser.$elementPath;

    $oldCompletePath = $fileServerPath.$oldName;
    $newCompletePath = $fileServerPath.$newName;

    //L'élément à renommer existe et le nom voulu n'est pas déjà pris
    if (file_exists($oldCompletePath) && !(file_exists($newCompletePath)))
    {
        $directoryIterator = createDirectoryIterator($fileServerPath);

        if ($directoryIterator instanceof DirectoryIterator)
        {
            if ($directoryIterator->current()->isWritable())
            {
                //@link http://www.php.net/manual/fr/function.rename.php
                $renameSuccessful = rename($oldCompletePath, $newCompletePath);

                if ($renameSuccessful)
                    return TRUE;
                else return array('error' => 'Could not rename element '.$oldName.' in '.$elementPath);
            }
            else return array('error' => 'You need write access on '.$elementPath);
        }
        else return $directoryIterator; //message d'erreur
    }
    else return array('error' => 'Invalid source element or name of element already taken');
}

/**
 * Copie un élément sur le serveur de fichier
 * @author Alban Truc
 * @param MongoId|string $idUser
 * @param string $sourcePath
 * @param string $sourceName
 * @param string $destinationPath
 * @param string $destinationName
 * @since 12/06/2014
 * @return array|bool
 */

function copyFSElement($idUser, $sourcePath, $sourceName, $destinationPath, $destinationName )
{
    $elementSourceName = utf8_decode($sourceName);
    $sourcePath = utf8_decode($sourcePath);
    $elementDestinationName = utf8_decode($destinationName);
    $destinationPath = utf8_decode($destinationPath);

    if(!(is_string($idUser)))
        $idUser = (string)$idUser;

    $sourceFSPath = PATH.$idUser.$sourcePath;
    $destinationFSPath = PATH.$idUser.$destinationPath;

    $sourceCompletePath = $sourceFSPath.$elementSourceName;
    $destinationCompletePath = $destinationFSPath.$elementDestinationName;

    //le dossier et élément source existent
    if(file_exists($sourceCompletePath))
    {
        //le dossier de destination n'existe pas?
        if(!(file_exists($destinationFSPath)))
        {
            //on le crée {@link http://fr2.php.net/manual/fr/function.mkdir.php}
            $mkdirSuccessful = mkdir($destinationFSPath, 0777, TRUE);

            if($mkdirSuccessful != TRUE)
                return array('error' => 'Destination did not exist. We tried to create it but it failed');
        }

        //le nom de l'élément de destination est-il disponible?
        if(file_exists($destinationCompletePath))
            return array('error' => 'Element already exists for this name in destination');
        else
        {
            if(is_file($sourceCompletePath))
                //@link http://www.php.net/manual/fr/function.copy.php
                copy($sourceCompletePath, $destinationCompletePath);
            elseif(is_dir($sourceCompletePath))
            {
                $sourceCompletePath = "\"".$sourceCompletePath."\"";
                $destinationCompletePath = "\"".$destinationCompletePath."\"";;
                shell_exec("cp -r -a $sourceCompletePath $destinationCompletePath 2>&1");
            }
        }
        return TRUE;
    }
    else return array('error' => 'Source element not found');
}

/**
 * Déplacer un élément sur le serveur de fichier
 * @author Alban Truc
 * @param string|MongoId $idUser
 * @param string $sourcePath
 * @param string $sourceName
 * @param string $destinationPath
 * @param string $destinationName
 * @since 12/06/2014
 * @return array|bool
 */

function moveFSElement($idUser, $sourcePath, $sourceName, $destinationPath, $destinationName)
{
    $sourcePath = utf8_decode($sourcePath);
    $elementSourceName = utf8_decode($sourceName);
    $destinationPath = utf8_decode($destinationPath);
    $elementDestinationName = utf8_decode($destinationName);

    if(!(is_string($idUser)))
        $idUser = (string)$idUser;

    $sourceFSPath = PATH.$idUser.$sourcePath;
    $destinationFSPath = PATH.$idUser.$destinationPath;

    $sourceCompletePath = $sourceFSPath.$elementSourceName;
    $destinationCompletePath = $destinationFSPath.$elementDestinationName;

    //le dossier et élément source existent
    if(file_exists($sourceCompletePath))
    {
        //le dossier de destination n'existe pas?
        if(!(file_exists($destinationFSPath)))
        {
            //on le crée {@link http://fr2.php.net/manual/fr/function.mkdir.php}
            $mkdirSuccessful = mkdir($destinationFSPath, 0777, TRUE);

            if($mkdirSuccessful != TRUE)
                return array('error' => 'Destination did not exist. We tried to create it but it failed');
        }

        //le nom de l'élément de destination est-il disponible?
        if(file_exists($destinationCompletePath))
            return array('error' => 'Element already exists for this name in destination');
        else
            //@link http://www.php.net/manual/fr/function.rename.php
            $renameSuccessful = rename($sourceCompletePath, $destinationCompletePath);

        if($renameSuccessful != TRUE)
            return array('error' => 'Move was not done successfully on file server.');
        else return TRUE;
    }
    else return array('error' => 'Source element not found');
}

/**
 * Créer un dossier vide sur le serveur de fichier
 * @author Alban Truc
 * @param string|MongoId $idUser
 * @param string $path
 * @param string $name
 * @since 12/06/2014
 * @return array|bool
 */

function createFSDirectory($idUser, $path, $name)
{
    $path = utf8_decode($path);
    $name = utf8_decode($name);

    if(!(is_string($idUser)))
        $idUser = (string)$idUser;

    $FSPath = PATH.$idUser.$path;
    $completeFSPath = $FSPath.$name;

    //le dossier devant contenir notre nouveau dossier n'existe pas?
    if(!(file_exists($FSPath)))
    {
        //on le crée {@link http://fr2.php.net/manual/fr/function.mkdir.php}
        $mkdirSuccessful = mkdir($FSPath, 0777, TRUE);

        if($mkdirSuccessful != TRUE)
            return array('error' => 'Destination does not exist. We tried to create it but it failed');
    }

    $mkdirSuccessful = mkdir($completeFSPath, 0777, TRUE);

    if($mkdirSuccessful == TRUE)
        return TRUE;
    else
        return array('error' => 'We could not create the new directory.');
}

/**
 * Déplace un élément dans un dossier corbeille
 * @author Alban Truc
 * @param $idUser
 * @param $sourcePath
 * @param $elementName
 * @since 12/06/2014
 * @return array|bool
 */

function moveToTrash($idUser, $sourcePath, $elementName)
{
    $sourcePath = utf8_decode($sourcePath);
    $elementName = utf8_decode($elementName);

    if(!(is_string($idUser)))
        $idUser = (string)$idUser;

    $sourceFSPath = PATH.$idUser.$sourcePath;
    $sourceCompletePath = $sourceFSPath.$elementName;

    $destinationFSPath = PATH.$idUser.'/Trash-'.$idUser.'/';
    $destinationCompletePath = $destinationFSPath.$elementName;

    //le dossier et élément source existent
    if(file_exists($sourceCompletePath))
    {
        //le dossier de destination n'existe pas?
        if(!(file_exists($destinationFSPath)))
        {
            //on le crée {@link http://fr2.php.net/manual/fr/function.mkdir.php}
            $mkdirSuccessful = mkdir($destinationFSPath, 0777, TRUE);

            if($mkdirSuccessful != TRUE)
                return array('error' => 'Destination did not exist. We tried to create it but it failed');
        }

        //le nom de l'élément de destination est-il disponible?
        if(file_exists($destinationCompletePath))
            $destinationCompletePath = pathinfo($destinationCompletePath, PATHINFO_FILENAME)
                .time()
                .pathinfo($destinationCompletePath, PATHINFO_EXTENSION);

        //@link http://www.php.net/manual/fr/function.rename.php
        $renameSuccessful = rename($sourceCompletePath, $destinationCompletePath);

        if($renameSuccessful != TRUE)
            return array('error' => 'Move was not done successfully on file server.');
        else return TRUE;
    }
    else return array('error' => 'Source element not found');
}
