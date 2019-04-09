<?php
/* Copyright (C) 2013 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file	   htdocs/core/triggers/interface_90_all_Demo.class.php
 *  \ingroup	core
 *  \brief	  Fichier de demo de personalisation des actions du workflow
 *  \remarks	Son propre fichier d'actions peut etre cree par recopie de celui-ci:
 *			  - Le nom du fichier doit etre: interface_99_modMymodule_Mytrigger.class.php
 *										   ou: interface_99_all_Mytrigger.class.php
 *			  - Le fichier doit rester stocke dans core/triggers
 *			  - Le nom de la classe doit etre InterfaceMytrigger
 *			  - Le nom de la methode constructeur doit etre InterfaceMytrigger
 *			  - Le nom de la propriete name doit etre Mytrigger
 */


/**
 *  Class of triggers for Mantis module
 */

class InterfaceGetCustomerPriceWorkflow
{
	var $db;

	/**
	 *   Constructor
	 *
	 *   @param		DoliDB		$db	  Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i','',get_class($this));
		$this->family = "ATM";
		$this->description = "Trigger du module de récupération de prix client";
		$this->version = 'dolibarr';			// 'development', 'experimental', 'dolibarr' or version
		$this->picto = 'technic';
	}


	/**
	 *   Return name of trigger file
	 *
	 *   @return	 string	  Name of trigger file
	 */
	function getName()
	{
		return $this->name;
	}

	/**
	 *   Return description of trigger file
	 *
	 *   @return	 string	  Description of trigger file
	 */
	function getDesc()
	{
		return $this->description;
	}

	/**
	 *   Return version of trigger file
	 *
	 *   @return	 string	  Version of trigger file
	 */
	function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("Development");
		elseif ($this->version == 'experimental') return $langs->trans("Experimental");
		elseif ($this->version == 'dolibarr') return DOL_VERSION;
		elseif ($this->version) return $this->version;
		else return $langs->trans("Unknown");
	}

	function run_trigger($action,$object,$user,$langs,$conf)
	{
		global $conf;

		/*echo '<pre>';
		print_r($_REQUEST);
		echo '<pre>';
		exit;*/

		if ((float)DOL_VERSION <= 3.4) {
			$test_bool = (!empty($_REQUEST['addline'])  && !empty($_REQUEST['mode']));
		} else {
			$test_bool = ((!empty($_REQUEST['addline_predefined']) || !empty($_REQUEST['addline_libre'])  || !empty($_REQUEST['prod_entry_mode'])));
		}

		if (($action == 'LINEPROPAL_INSERT' || $action == 'LINEORDER_INSERT' || $action == 'LINEBILL_INSERT')
			&& !empty($object->fk_product) && $test_bool) {

			dol_include_once('/comm/propal/class/propal.class.php');
			dol_include_once('/commande/class/commande.class.php');
			dol_include_once('/compta/facture/class/facture.class.php');

			$langs->load('getcustomerprice@getcustomerprice');

			$TInfos = $this->_getLastPriceForCustomer($object);

			if($TInfos !== -1 && (!empty($TInfos['prix']) || !empty($conf->global->GETCUSTOMERPRICE_ALLOW_GET_PRICE_0))) {
				// Fonctionnement spécifique si on est sur une ligne d'avoir
				if($object->element == 'facturedet') {
					$f = new Facture($object->db);
					$f->fetch($object->fk_facture);
					if($f->type == 2) {
						$TInfos['prix'] *= -1;
					}
				}

				$tabprice=calcul_price_total(
					$object->qty,
					(strpos($conf->global->GETCUSTOMERPRICE_WHATTOGET,'price') !== false) ? $TInfos['prix'] : $object->subprice,
					(strpos($conf->global->GETCUSTOMERPRICE_WHATTOGET,'discount') !== false) ? $TInfos['remise_percent'] : $object->remise_percent,
					$object->tva_tx,
					$object->localtax1_tx,
					$object->localtax2_tx,
					0,
					'HT',
					$object->info_bits,
					$object->product_type
				);

				$total_ht  = $tabprice[0];
				$total_tva = $tabprice[1];
				$total_ttc = $tabprice[2];
				$total_localtax1=$tabprice[9];
				$total_localtax2=$tabprice[10];
				$pu_ht  = $tabprice[3];
				$pu_tva = $tabprice[4];
				$pu_ttc = $tabprice[5];

				$object->remise_percent = (strpos($conf->global->GETCUSTOMERPRICE_WHATTOGET,'discount') !== false) ? $TInfos['remise_percent'] : $object->remise_percent;
				$object->subprice = $pu_ht;
				$object->total_ht = $total_ht;
				$object->total_tva = $total_tva;
				$object->total_ttc = $total_ttc;
				$object->total_localtax1 = $total_localtax1;
				$object->total_localtax2 = $total_localtax2;

				$price = $pu_ht;
				$remise = 0;
				if ($object->remise_percent> 0)
				{
					$remise = round(($price * $object->remise_percent/ 100), 2);
					$price = $price - $remise;
				}

				$object->price = $price;
				$object->remise = $remise;

				if($object->element == 'facturedet') $object->update($user);
				else {
					if((float)DOL_VERSION > 4.0) $object->update($user); // Bug en 7.0 si on spécifie pas $user... (test sur la version obligatoire car avant 5.0 premier param = $notrigger)
					else $object->update();
				}

				setEventMessage($langs->trans('CustomerPriceFrom'.$TInfos['sourcetype'], $TInfos['source']->getNomUrl()), 'warnings');

				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->rowid);
				return 1;
			}

			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->rowid);
		}

		return 0;
	}

	function _getLastPriceForCustomer(&$objectLine, $socid=0) {
		global $conf, $db;

		// Define filter for where to search
		$searchIn = array();
		if($conf->global->GETCUSTOMERPRICE_SEARCH_IN_PROPOSAL) $searchIn[] = 'proposal';
		if($conf->global->GETCUSTOMERPRICE_SEARCH_IN_ORDER) $searchIn[] = 'order';
		if($conf->global->GETCUSTOMERPRICE_SEARCH_IN_INVOICE) $searchIn[] = 'invoice';
		if(empty($searchIn)) return -3;

		// Define filter on date
		$filterDate = array();
		$filterDate['nofilter'] = '1970-01-01';
		$filterDate['thisyear'] = 'MAKEDATE(EXTRACT(YEAR FROM NOW()), 1)';
		$filterDate['lastyear'] = 'TIMESTAMPADD(YEAR, -1, NOW())';
		$whDate = !empty($filterDate[$conf->global->GETCUSTOMERPRICE_DATEFROM]) ? $filterDate[$conf->global->GETCUSTOMERPRICE_DATEFROM] : $filterDate['thisyear'];

		// Subselect definition to get soc id
		$subSelect = array();
		$subSelect['FactureLigne'] = empty($socid) ? "SELECT f.fk_soc FROM ".MAIN_DB_PREFIX."facture f WHERE f.rowid = ".$objectLine->fk_facture : $socid;
		$subSelect['OrderLine'] = empty($socid) ? "SELECT c.fk_soc FROM ".MAIN_DB_PREFIX."commande c WHERE c.rowid = ".$objectLine->fk_commande : $socid;
		$subSelect['PropaleLigne'] = empty($socid) ? "SELECT p.fk_soc FROM ".MAIN_DB_PREFIX."propal p WHERE p.rowid = ".$objectLine->fk_propal : $socid;

		// Subselect definition to get filtered categories
		$subSelectCatFilter = "SELECT cat1.fk_categorie_societe FROM ".MAIN_DB_PREFIX."categorie_customerprice as cat1";

		$globalSelect = "o.rowid, o.fk_soc, od.subprice, od.remise_percent, od.qty, ";
		$globalWhere = " od.fk_product = ".$objectLine->fk_product;

		$field_categorie_societe = ((float)DOL_VERSION >= 3.8) ? 'fk_soc' : 'fk_societe';

		// On regarde si la société testée est dans une catégorie.
		$query = "SELECT fk_categorie ";
		$query.= " FROM ".MAIN_DB_PREFIX."categorie_societe";
		$query.= " WHERE ".$field_categorie_societe." = (".$subSelect[get_class($objectLine)].")";
		$query.= " AND fk_categorie IN (".$subSelectCatFilter.")";

		$resquery = $db->query($query);

		// $socHasACategory :
		// > 0 si la société est dans une catégorie
		// = 0 si la société n'est pas dans une catégorie
		$socHasACategory = $resquery->num_rows;

		// On filtre par catégorie si la constante est à 1 ET si la société est dans une catégorie.
		if($conf->global->GETCUSTOMERPRICE_FILTER_THIRD_PARTY_CATEGORY && $socHasACategory > 0){
			$globalWhere .= " AND o.fk_soc IN (SELECT cat.".$field_categorie_societe."
											   FROM ".MAIN_DB_PREFIX."categorie_societe as cat
											   WHERE cat.fk_categorie IN (".$subSelectCatFilter.")
											   AND cat.fk_categorie IN (SELECT cat2.fk_categorie
											   							FROM ".MAIN_DB_PREFIX."categorie_societe as cat2
											   							WHERE cat2.".$field_categorie_societe." = (".$subSelect[get_class($objectLine)].")))";
		}
		else{
			$globalWhere .= " AND o.fk_soc = (".$subSelect[get_class($objectLine)].")";
		}

		$globalWhere .= " AND o.fk_statut > 0";
		if($conf->global->GETCUSTOMERPRICE_PRICE_BY_QTY) $globalWhere .= " AND od.qty <= ".$objectLine->qty;
		$globalOrder = " ORDER BY qty DESC, date DESC
						 LIMIT 1";

		// Select definition to get last price for customer
		$sql = array();
		$sql['invoice'] = "SELECT ".$globalSelect."o.datef as date, 'Facture' as type
					FROM ".MAIN_DB_PREFIX."facturedet od
					LEFT JOIN ".MAIN_DB_PREFIX."facture o ON od.fk_facture = o.rowid
					WHERE ".$globalWhere."
					AND o.datef >= ".$whDate."
					".$globalOrder;
		$sql['order'] = "SELECT ".$globalSelect."o.date_commande as date, 'Commande' as type
					FROM ".MAIN_DB_PREFIX."commandedet od
					LEFT JOIN ".MAIN_DB_PREFIX."commande o ON od.fk_commande = o.rowid
					WHERE ".$globalWhere."
					AND o.date_commande >= ".$whDate."
					".$globalOrder;
		$sql['proposal'] = "SELECT ".$globalSelect."o.datep as date, 'Propal' as type
					FROM ".MAIN_DB_PREFIX."propaldet od
					LEFT JOIN ".MAIN_DB_PREFIX."propal o ON od.fk_propal = o.rowid
					WHERE ".$globalWhere."
					AND o.datep >= ".$whDate."
					".$globalOrder;

		//exit($sql['proposal']);

		$sqlToUse = array();
		foreach($sql as $type => $query) {
			if(in_array($type, $searchIn)) {
				$sqlToUse[] = '('.$query.')';
			}
		}

		$sqlFinal = implode(' UNION ', $sqlToUse);
		$sqlFinal.= ' ORDER BY date DESC LIMIT 1';

		//exit($sqlFinal);

		$resql = $this->db->query($sqlFinal);
		//echo $sqlFinal;
		if($resql) {
			$obj = $this->db->fetch_object($resql);
			$prix = $obj->subprice;
			$remise_percent = $obj->remise_percent;
			$fk_soc = $obj->fk_soc;
			$class = $obj->type;
			$rowid = $obj->rowid;

			if(!empty($prix)) {
				// Load object the price is coming from
				$o = new $class($this->db);
				$o->fetch($rowid);

				// Load product
				$product = new Product($this->db);
				$product->fetch($objectLine->fk_product);

				// Load customer
				$customer = new Societe($this->db);
				$customer->fetch($fk_soc);

				// Check if last price is not less than min price
				$price_min = $product->price_min;
				if (!empty($conf->global->PRODUIT_MULTIPRICES) && !empty($customer->price_level))
					$price_min = $product->multiprices_min[$customer->price_level];

				if (!empty($price_min) && (price2num($prix)*(1-price2num($objectLine->remise_percent)/100) < price2num($price_min))) return -2;

				return array(
					'prix' => price2num($prix)
					,'remise_percent' => price2num($remise_percent)
					,'sourcetype' => $class
					,'source' => &$o
				);
			}
		}

		return -1;
	}
}
