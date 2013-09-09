<?php
/**
 * Options for the authhiorgserver plugin
 *
 * @author HiOrg Server GmbH <support@hiorg-server.de>
 */


$meta['ov'] = array('string', '_pattern' => '/([a-z]{3,4})?/', '_cautionList' => array('plugin____authhiorgserver____ov' => 'danger'));
$meta['ssourl'] = array('string', '_cautionList' => array('plugin____authhiorgserver____ssourl' => 'danger'));
$meta['admin_users'] = array('string', '_cautionList' => array('plugin____authhiorgserver____admin_users' => 'danger'));
$meta['group1_name'] = array('string');
$meta['group1_users'] = array('string');
$meta['group2_name'] = array('string');
$meta['group2_users'] = array('string');
$meta['syncname'] = array('multichoice','_choices' => array('all','vname','vona','vn'));