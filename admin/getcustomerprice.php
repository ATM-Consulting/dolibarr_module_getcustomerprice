<?php

$res=@include("../../main.inc.php");						// For root directory
if (! $res) $res=@include("../../../main.inc.php");			// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("admin");
$langs->load('getcustomerprice@getcustomerprice');

global $db;

// Security check
if (! $user->admin) accessforbidden();

$action=GETPOST('action');
$id=GETPOST('id');

/*
 * Action
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_set_const($db, $code, GETPOST($code), 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}
	
if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if($action == "add_GETCUSTOMERPRICE_FILTER_THIRD_PARTY_CATEGORY"){
	
	if(!empty($_REQUEST['categorie']) && $_REQUEST['categorie'] > 0 && GETCUSTOMERPRICE_FILTER_THIRD_PARTY_CATEGORY){
		$db->query('INSERT INTO '.MAIN_DB_PREFIX.'categorie_customerprice (fk_categorie_societe) VALUES ('.$_REQUEST['categorie'].')');
	}
}

if($action == "delete"){
	
	if(!empty($id)){
		$db->query('DELETE FROM '.MAIN_DB_PREFIX.'categorie_customerprice WHERE rowid = '.$id);
	}
}

/*
 * View
 */

llxHeader('',$langs->trans("GetCustomerPriceSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("GetCustomerPriceSetup"),$linkback,'getcustomerprice@getcustomerprice');

print '<br>';

$form=new Form($db);
$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";


// Search in document from
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("SeachInDocumentFrom").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

$dateFrom = array(
	'nofilter' => $langs->trans('NoDateFilter')
	,'thisyear' => $langs->trans('DateFromThisYear')
	,'lastyear' => $langs->trans('DateFromLastYear')
);
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_GETCUSTOMERPRICE_DATEFROM">';
print $form->selectarray('GETCUSTOMERPRICE_DATEFROM', $dateFrom, $conf->global->GETCUSTOMERPRICE_DATEFROM);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

// Get price, price + discount, discount
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("WhatToGet").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

$whattoget = array(
	'price' => $langs->trans('GetPrice')
	,'discount' => $langs->trans('GetDiscount')
	,'price|discount' => $langs->trans('GetPriceAndDiscount')
);

print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_GETCUSTOMERPRICE_WHATTOGET">';
print $form->selectarray('GETCUSTOMERPRICE_WHATTOGET', $whattoget, $conf->global->GETCUSTOMERPRICE_WHATTOGET);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

// Search in propal
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("SearchInProposal").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="300">';
print ajax_constantonoff('GETCUSTOMERPRICE_SEARCH_IN_PROPOSAL');
print '</td></tr>';

// Search in order
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("SearchInOrder").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="300">';
print ajax_constantonoff('GETCUSTOMERPRICE_SEARCH_IN_ORDER');
print '</td></tr>';

// Search in invoice
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("SearchInInvoice").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="300">';
print ajax_constantonoff('GETCUSTOMERPRICE_SEARCH_IN_INVOICE');
print '</td></tr>';

print '</table>';

print '<br><br>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("CategoryFilter").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

// Filter In category
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FilterByThirdPartyCategory").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="300">';
print ajax_constantonoff('GETCUSTOMERPRICE_FILTER_THIRD_PARTY_CATEGORY');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("AddThirdPartyCategoryInFilter").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

dol_include_once('/categories/class/categorie.class.php');

print '<td align="center" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="add_GETCUSTOMERPRICE_FILTER_THIRD_PARTY_CATEGORY">';
print $form->select_all_categories(2,'','categorie');
print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
print '</form>';
print '</td></tr>';

print '</table>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("CategoryToFilter").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Action").'</td>'."\n";
print '</tr>';

$resql = $db->query('SELECT cc.rowid, c.label, c.description FROM '.MAIN_DB_PREFIX.'categorie_customerprice as cc LEFT OUTER JOIN '.MAIN_DB_PREFIX.'categorie as c ON (cc.fk_categorie_societe = c.rowid)');

while($res = $db->fetch_object($resql)){
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td align="center" width="30">';
	print $res->label;
	print '</td>';
	print '<td align="center" width="40">';
	print $res->description;
	print '</td>';
	print '<td align="center" width="30">';
	print '<a href="'.$url.$_SERVER['PHP_SELF'].'?action=delete&id='.$res->rowid.'">'.img_delete().'</a>';
	print '</td></tr>';	
}

print '</table>';

// Footer
llxFooter();
// Close database handler
$db->close();
