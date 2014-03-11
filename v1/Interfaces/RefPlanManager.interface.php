<?php
/**
 * Created by Notepad++.
 * User: Alban Truc
 * Date: 31/01/14
 * Time: 12:52
 */

interface RefPlanManagerInterface
{
    /**
     * - Retrouver un refPlan par son ID.
     * - Gestion des erreurs.
     * @author Alban Truc
     * @param string|MongoId $id Identifiant unique du refPlan à trouver
     * @since 02/2014
     * @return array contenant le résultat de la requête ou le message d'erreur
     */

    function findById($id);
}
?>