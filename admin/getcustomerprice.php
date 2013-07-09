<?php

$res=@include("../../main.inc.php");						// For root directory
if (! $res) $res=@include("../../../main.inc.php");			// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("admin");
$langs->load('getcustomerprice@getcustomerprice');

// Security check
if (! $user->admin) accessforbidden();

$action=GETPOST('action');

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
	'thisyear' => $langs->trans('DateFromThisYear')
	,'lastyear' => $langs->trans('DateFromLastYear')
);
print '<td align="center" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_GETCUSTOMERPRICE_DATEFROM">';
print $form->selectarray('GETCUSTOMERPRICE_DATEFROM', $dateFrom, $conf->global->GETCUSTOMERPRICE_DATEFROM);
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

// Footer
llxFooter();
// Close database handler
$db->close();
