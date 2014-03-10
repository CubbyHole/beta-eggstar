<?php
/**
 * Created by Notepad++.
 * User: Alban Truc
 * Date: 31/01/14
 * Time: 12:52
 */
interface AccountManagerInterface
{
    /**
     * - Retrouver un account par son ID. Inclut le refPlan de l'account dans le retour.
     * - Gestion des erreurs
     * @author Alban Truc
     * @param $id String|MongoId Identifiant unique de l'account à trouver
     * @since 02/2014
     * @return FALSE|array contenant le résultat de la requête
     */

    function findById($id);

    /**
     * - Insère un nouveau compte en base.
     * - Gestion des exceptions MongoCursor: http://www.php.net/manual/en/class.mongocursorexception.php
     * - Gestion des erreurs
     * - On n'insert pas de nouveau refPlan, ceux-ci sont déjà définis en base.
     * @author Alban Truc
     * @param $account array
     * @since 02/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    function createAccount($account);

    /**
     * - Supprime un compte à partir de son ID
     * - Gestion des exceptions MongoCursor: http://www.php.net/manual/en/class.mongocursorexception.php
     * - Gestion des erreurs
     * @author Alban Truc
     * @param MongoID|String $id
     * @since 23/02/2014
     * @return TRUE|array contenant le message d'erreur dans un indexe 'error'
     */

    function removeAccount($id);
}
?>