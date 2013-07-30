<?php
/**
 * Options for the authhiorgserver plugin
 *
 * @author HiOrg Server GmbH <support@hiorg-server.de>
 */


$meta['ov'] = array('string', '_pattern' => '/[a-z]{3,4}/');
$meta['ssourl'] = array('string');
$meta['admin_users'] = array('string');
$meta['group1_name'] = array('string');
$meta['group1_users'] = array('string');
$meta['group2_name'] = array('string');
$meta['group2_users'] = array('string');

$lang['ov'] = "Organisationskuerzel Ihres HiOrg-Servers (kann leer bleiben)";
$lang['ssourl'] = "SSO-Skriptadresse Ihres HiOrg-Server (ohne Parameter)";
$lang['admin_users'] = 'Admins (kommagetrennte Benutzernamen)';
$lang['group1_name'] = 'Bezeichnung eigene Benutzergruppe 1';
$lang['group1_users'] = 'Mitglieder eigener Benutzergruppe 1 (kommagetrennte Benutzernamen)';
$lang['group2_name'] = 'Bezeichnung eigene Benutzergruppe 2';
$lang['group2_users'] = 'Mitglieder eigener Benutzergruppe 2 (kommagetrennte Benutzernamen)';
