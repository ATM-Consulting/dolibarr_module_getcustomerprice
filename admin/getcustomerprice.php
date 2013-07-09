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
	if (dolibarr_set_const($db, $code, 1, 'chaine', 0, '', 0) > 0)
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
print '</tr>';

/*
 * Formulaire parametres divers
 */

// Use cookie
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("EnableCookieLogin").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
print ajax_constantonoff('MULTICOMPANY_COOKIE_ENABLED', '', 0);
print '</td></tr>';

// Login page combobox activation
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("HideLoginCombobox").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
print ajax_constantonoff('MULTICOMPANY_HIDE_LOGIN_COMBOBOX', '', 0);
print '</td></tr>';

// Enable global sharings
if (! empty($conf->societe->enabled) || ! empty($conf->product->enabled) || ! empty($conf->service->enabled) || ! empty($conf->categorie->enabled))
{
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("EnableGlobalSharings").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	$input = array(
			'alert' => array(
					'set' => array(
							'info' => true,
							'yesButton' => $langs->trans('Ok'),
							'title' => $langs->trans('GlobalSharings'),
							'content' => img_info().' '.$langs->trans('GlobalSharingsInfo')
					)
			),
			'showhide' => array(
					'shareproduct',
					'sharethirdparty',
					'sharecategory'
			),
			'hide' => array(
					'shareproduct',
					'sharestock',
					'sharethirdparty',
					'shareagenda',
					'sharecategory'
			),
			'del' => array(
					'MULTICOMPANY_PRODUCT_SHARING_ENABLED',
					'MULTICOMPANY_STOCK_SHARING_ENABLED',
					'MULTICOMPANY_SOCIETE_SHARING_ENABLED',
					'MULTICOMPANY_AGENDA_SHARING_ENABLED',
					'MULTICOMPANY_CATEGORY_SHARING_ENABLED'
			)
	);
	print ajax_constantonoff('MULTICOMPANY_SHARINGS_ENABLED', $input, 0);
	print '</td></tr>';
}

// Share thirparties and contacts
if (! empty($conf->societe->enabled))
{
	$var=!$var;
	print '<tr id="sharethirdparty" '.$bc[$var].(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) ? ' style="display:none;"' : '').'>';
	print '<td>'.$langs->trans("ShareThirdpartiesAndContacts").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	$input = array(
			'showhide' => array(
					'shareagenda'
			),
			'del' => array(
					'MULTICOMPANY_AGENDA_SHARING_ENABLED'
			)
	);
	print ajax_constantonoff('MULTICOMPANY_SOCIETE_SHARING_ENABLED', $input, 0);
	print '</td></tr>';
}

// Share agenda
if (! empty($conf->agenda->enabled) && ! empty($conf->societe->enabled))
{
	$var=!$var;
	$display=(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) || empty($conf->global->MULTICOMPANY_SOCIETE_SHARING_ENABLED) ? ' style="display:none;"' : '');
	print '<tr id="shareagenda" '.$bc[$var].$display.'>';
	print '<td>'.$langs->trans("ShareAgenda").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	print ajax_constantonoff('MULTICOMPANY_AGENDA_SHARING_ENABLED', '', 0);
	print '</td></tr>';
}

// Share products/services
if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))
{
	$var=!$var;
	print '<tr id="shareproduct" '.$bc[$var].(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) ? ' style="display:none;"' : '').'>';
	print '<td>'.$langs->trans("ShareProductsAndServices").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	$input = array(
			'showhide' => array(
					'sharestock'
			),
			'del' => array(
					'MULTICOMPANY_STOCK_SHARING_ENABLED'
			)
	);
	print ajax_constantonoff('MULTICOMPANY_PRODUCT_SHARING_ENABLED', $input, 0);
	print '</td></tr>';
}

// Share stock
if (! empty($conf->stock->enabled) && (! empty($conf->product->enabled) || ! empty($conf->service->enabled)))
{
	$var=!$var;
	$display=(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) || empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED) ? ' style="display:none;"' : '');
	print '<tr id="sharestock" '.$bc[$var].$display.'>';
	print '<td>'.$langs->trans("ShareStock").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	print ajax_constantonoff('MULTICOMPANY_STOCK_SHARING_ENABLED', '', 0);
	print '</td></tr>';
}

// Share categories
if (! empty($conf->categorie->enabled))
{
	$var=!$var;
	print '<tr id="sharecategory" '.$bc[$var].(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) ? ' style="display:none;"' : '').'>';
	print '<td>'.$langs->trans("ShareCategories").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	print ajax_constantonoff('MULTICOMPANY_CATEGORY_SHARING_ENABLED', '', 0);
	print '</td></tr>';
}


/* Mode de gestion des droits :
 * Mode Off : mode Off : pyramidale. Les droits et les groupes sont gérés dans chaque entité : les utilisateurs appartiennent au groupe de l'entity pour obtenir leurs droits
 * Mode On : mode On : transversale : Les groupes ne peuvent appartenir qu'a l'entity = 0 et c'est l'utilisateur qui appartient à tel ou tel entity
 */
/*
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("GroupModeTransversal").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
print ajax_constantonoff('MULTICOMPANY_TRANSVERSE_MODE');
print '</td></tr>';
*/
print '</table>';

// Footer
llxFooter();
// Close database handler
$db->close();
