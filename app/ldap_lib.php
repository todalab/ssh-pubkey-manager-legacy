<?php

function GetUserDN($ad, $samaccountname, $basedn)
{
    $attributes = ['dn'];
    $result = ldap_search($ad, $basedn, "(uid={$samaccountname})", $attributes);

    if ($result === false) {
        return '';
    }
    $entries = ldap_get_entries($ad, $result);

    if ($entries['count'] > 0) {
        return $entries[0]['dn'];
    }
    return '';
}

function GetGroupDN($ad, $samaccountname, $basedn)
{
    $attributes = ['dn'];
    $result = ldap_search($ad, $basedn, "(cn={$samaccountname})", $attributes);

    if ($result === false) {
        return '';
    }
    $entries = ldap_get_entries($ad, $result);

    if ($entries['count'] > 0) {
        return $entries[0]['dn'];
    }
    return '';
}

function CheckGroupEx($ad, $userdn, $groupdn)
{
    $attributes = ['memberof'];
    $result = ldap_read($ad, $userdn, '(objectClass=*)', $attributes);

    if ($result === false) {
        return false;
    }
    $entries = ldap_get_entries($ad, $result);

    if ($entries['count'] <= 0) {
        return false;
    }

    if (empty($entries[0]['memberof'])) {
        return false;
    }

    for ($i = 0; $i < $entries[0]['memberof']['count']; $i++) {
        if ($entries[0]['memberof'][$i] === $groupdn) {
            return true;
        }

        if (CheckGroupEx($ad, $entries[0]['memberof'][$i], $groupdn)) {
            return true;
        }
    }

    return false;
}

function GetGroupMembers($ad, $samaccountname, $basedn)
{
    $attributes = ['memberUid'];
    $result = ldap_search($ad, $basedn, "(cn={$samaccountname})", $attributes);

    if ($result === false) {
        return [];
    }
    $entries = ldap_get_entries($ad, $result);

    if ($entries['count'] > 0) {
        $ret = $entries[0]['memberuid'];
        unset($ret['count']);
        return $ret;
    }
    return [];
}
