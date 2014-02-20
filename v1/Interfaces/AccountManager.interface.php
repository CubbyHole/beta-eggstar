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
     * @author Alban Truc
     * @param $id string ou objet MongoId
     * @since 01/2014
     * @return mixed
     */

    function findById($id);

    /**
     * @author Alban Truc
     * @param $account array
     * @since 02/2014
     * @return mixed
     */

    function createAccount($account);
}
?>