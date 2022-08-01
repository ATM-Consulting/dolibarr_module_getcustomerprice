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
$newToken = function_exists('newToken') ? newToken() : $_SESSION['newtoken'];
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

if($action == 'save_multicompany_shared_conf')
{
    $multicompanypriceshare = GETPOST('multicompany-customerprice','array');

    if(!empty($multicompanypriceshare))
    {
        foreach ($multicompanypriceshare as $entityId => $shared)
        {

            //'MULTICOMPANY_'.strtoupper($element).'_SHARING_ENABLED
            if(is_array($shared)){
                $shared = array_map('intval', $shared);

                $dao = new DaoMulticompany($db);
                if($dao->fetch($entityId)>0)
                {
                    $dao->options['sharings']['customerprice']  = $shared;
                    if($dao->update($entityId, $user)<1)
                    {
                        setEventMessage('Error');
                    }
                }
            }
        }
    }

}

/*
 * View
 */
$extrajs = $extracss = array();
if(!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) )
{
    $extrajs = array(
        '/multicompany/inc/multiselect/js/ui.multiselect.js',
    );
    $extracss = array(
        '/multicompany/inc/multiselect/css/ui.multiselect.css',
    );
}

llxHeader('',$langs->trans("GetCustomerPriceSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?token="'.$newToken.'>'.$langs->trans("BackToModuleList").'</a>';
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
print '<input type="hidden" name="token" value="'.$newToken.'">';
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
print '<input type="hidden" name="token" value="'.$newToken.'">';
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

// Price by quantity
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("UsePriceByQty").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="300">';
print ajax_constantonoff('GETCUSTOMERPRICE_PRICE_BY_QTY');
print '</td></tr>';

// Price min
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$form->textwithpicto($langs->trans("UsePriceMinAsDiscountPrice"), $langs->trans('UsePriceMinAsDiscountPrice_tooltip')).'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="300">';
print ajax_constantonoff('GETCUSTOMERPRICE_ADAPT_PRICE_FROM_SOURCE');
print '</td></tr>';


// Forcer l'application des prix même pour des documents créé à partir d'autre documents


$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$form->textwithpicto($langs->trans("ForceCustomerPriceOnDocumentWithOrigin"), $langs->trans('ForceCustomerPriceOnDocumentWithOrigin_tooltip')).'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="300">';
print ajax_constantonoff('GETCUSTOMERPRICE_NO_CONTROL_ORIGIN');
print '</td></tr>';


print '</table>';



/**
 * FILTER BY CATEGORY
 */

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
print '<input type="hidden" name="token" value="'.$newToken.'">';
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
	print '<a href="'.$url.$_SERVER['PHP_SELF'].'?action=delete&id='.$res->rowid.'&token='.$newToken.'">'.img_delete().'</a>';
	print '</td></tr>';
}

print '</table>';



if(!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) )
{

    print '<br><br>';

    //var_dump($mc);
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
    print '<input type="hidden" name="token" value="'.$newToken.'">';
    print '<input type="hidden" name="action" value="save_multicompany_shared_conf">';

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Multicompany").'</td>'."\n";
    print '<td align="center" ></td>';
    print '</tr>';

    $element = 'customerprice';
    $moduleSharingEnabled = 'MULTICOMPANY_'.strtoupper($element).'_SHARING_ENABLED';



    print '<tr class="oddeven" >';
    print '<td align="left" >';
    print $langs->trans("ActivateSharing");
    print '</td>';
    print '<td align="center" >';
    print ajax_constantonoff($moduleSharingEnabled, array(),0);
    print '</td>';
    print '</tr>';


    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("MulticompanyConfiguration").'</td>'."\n";
    print '<td align="center" >'.$langs->trans("ShareWith").'</td>';
    print '</tr>';

    $m=new ActionsMulticompany($db);

    $dao = new DaoMulticompany($db);
    $dao->getEntities();

    if (is_array($dao->entities))
    {

        foreach($dao->entities as $entitie)
        {

            if(intval($conf->entity) === 1 || intval($conf->entity) === intval($entitie->id))
            {

                print '<tr class="oddeven" >';
                print '<td align="left" >';
                print $entitie->name.' <em>('.$entitie->label.')</em> ';
               //
                print '</td>';
                print '<td align="center" >';
                print _multiselect_entities('multicompany-customerprice['.$entitie->id.']', $entitie,'',$element);
                print '</td>';
                print '</tr>';
            }

        }


        print '<tr>';
        print '<td colspan="2" style="text-align:right;" >';
        print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
        print '</td>';
        print '</tr>';
    }
    print '</table>';

    print '</form>';


    $langs->loadLangs(array( 'languages', 'multicompany@multicompany'));

    print '<script type="text/javascript">';
    print '$(document).ready(function () {';

    print '     $.extend($.ui.multiselect.locale, {';
    print '         addAll:\''.$langs->transnoentities("AddAll").'\',';
    print '         removeAll:\''.$langs->transnoentities("RemoveAll").'\',';
    print '         itemsCount:\''.$langs->transnoentities("ItemsCount").'\'';
    print '    });';


    print '    $(function(){';
    print '        $(".multiselect").multiselect({sortable: false, searchable: false});';
    print '    });';
    print '});';
    print '</script>';
}


// Footer
llxFooter();
// Close database handler
$db->close();


/**
 *	Return multiselect list of entities.
 *
 *	@param	string	$htmlname	Name of select
 *	@param	DaoMulticompany	$current	Current entity to manage
 *	@param	string	$option		Option
 *	@return	string
 */
function _multiselect_entities($htmlname, $current, $option='',$sharingElement = '')
{
    global $conf, $langs, $db;

    $dao = new DaoMulticompany($db);
    $dao->getEntities();

    $sharingElement = !empty($sharingElement)?$sharingElement:$htmlname;

    $return = '<select id="'.$htmlname.'" class="multiselect" multiple="multiple" name="'.$htmlname.'[]" '.$option.'>';
    if (is_array($dao->entities))
    {
        foreach ($dao->entities as $entity)
        {
            if (is_object($current) && $current->id != $entity->id && $entity->active == 1)
            {
                $return.= '<option value="'.$entity->id.'" ';
                if (is_array($current->options['sharings'][$sharingElement]) && in_array($entity->id, $current->options['sharings'][$sharingElement]))
                {
                    $return.= 'selected="selected"';
                }
                $return.= '>';
                $return.= $entity->label;
                if (empty($entity->visible))
                {
                    $return.= ' ('.$langs->trans('Hidden').')';
                }
                $return.= '</option>';
            }
        }
    }
    $return.= '</select>';

    return $return;
}

