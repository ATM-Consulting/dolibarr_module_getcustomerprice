<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *  \file       htdocs/core/triggers/interface_90_all_Demo.class.php
 *  \ingroup    core
 *  \brief      Fichier de demo de personalisation des actions du workflow
 *  \remarks    Son propre fichier d'actions peut etre cree par recopie de celui-ci:
 *              - Le nom du fichier doit etre: interface_99_modMymodule_Mytrigger.class.php
 *				                           ou: interface_99_all_Mytrigger.class.php
 *              - Le fichier doit rester stocke dans core/triggers
 *              - Le nom de la classe doit etre InterfaceMytrigger
 *              - Le nom de la methode constructeur doit etre InterfaceMytrigger
 *              - Le nom de la propriete name doit etre Mytrigger
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
     *   @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    
        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "ATM";
        $this->description = "Trigger du module de récupération de prix client";
        $this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'technic';
    }
    
    
    /**
     *   Return name of trigger file
     *
     *   @return     string      Name of trigger file
     */
    function getName()
    {
        return $this->name;
    }
    
    /**
     *   Return description of trigger file
     *
     *   @return     string      Description of trigger file
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *
     *   @return     string      Version of trigger file
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
		// TODO : add message if price is from a document rather than the product price
		if (($action == 'LINEPROPAL_INSERT' || $action == 'LINEORDER_INSERT' || $action == 'LINEBILL_INSERT')
			&& !empty($object->fk_product)) {
			dol_include_once('/comm/propal/class/propal.class.php');
			dol_include_once('/commande/class/commande.class.php');
			dol_include_once('/compta/facture/class/facture.class.php');
			
			$prix = $this->_getLastPriceForCustomer($object);
			
			if($prix > 0) {
				$tabprice=calcul_price_total(
					$object->qty,
					$prix,
					$object->remise_percent,
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
				
				$object->subprice = $pu_ht;
				$object->total_ht = $total_ht;
				$object->total_tva = $total_tva;
				$object->total_ttc = $total_ttc;
				$object->total_localtax1 = $total_localtax1;
				$object->total_localtax2 = $total_localtax2;
				
				if($object->element == 'facturedet') $object->update($user, true);
				else $object->update(true);
				
				return 1;
			}
			
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->rowid);
		}
		
		return 0;
	}

	function _getLastPriceForCustomer(&$objectLine) {
		// Subselect definition to get soc id
		$subSelect = array();
		$subSelect['FactureLigne'] = "SELECT f.fk_soc FROM ".MAIN_DB_PREFIX."facture f WHERE f.rowid = ".$objectLine->fk_facture;
		$subSelect['OrderLine'] = "SELECT c.fk_soc FROM ".MAIN_DB_PREFIX."commande c WHERE c.rowid = ".$objectLine->fk_commande;
		$subSelect['PropaleLigne'] = "SELECT c.fk_soc FROM ".MAIN_DB_PREFIX."propal p WHERE p.rowid = ".$objectLine->fk_propal;
		
		$filterDate = array(); // TODO : define in config date filter
		$filterDate['thisyear'] = 'MAKEDATE(EXTRACT(YEAR FROM NOW()), 1)';
		$filterDate['lastyear'] = 'TIMESTAMPADD(YEAR, -1, NOW())';
		
		// Select definition to get last price for customer
		$sql = array();
		$sql['invoice'] = "SELECT f.fk_soc, fd.subprice, f.datef as date
					FROM ".MAIN_DB_PREFIX."facturedet fd
					LEFT JOIN ".MAIN_DB_PREFIX."facture f ON fd.fk_facture = f.rowid
					WHERE fd.fk_product = ".$objectLine->fk_product."
					AND f.fk_soc = (".$subSelect[get_class($objectLine)].")
					AND f.fk_statut > 0
					AND f.datef >= ".$filterDate['thisyear']."
					ORDER BY date DESC
					LIMIT 1";
		$sql['order'] = "SELECT c.fk_soc, cd.subprice, c.date_commande as date
					FROM ".MAIN_DB_PREFIX."commandedet cd
					LEFT JOIN ".MAIN_DB_PREFIX."commande c ON cd.fk_commande = c.rowid
					WHERE cd.fk_product = ".$objectLine->fk_product."
					AND c.fk_soc = (".$subSelect[get_class($objectLine)].")
					AND c.fk_statut > 0
					AND c.date_commande >= ".$filterDate['thisyear']."
					ORDER BY date DESC
					LIMIT 1";
		$sql['proposal'] = "SELECT p.fk_soc, pd.subprice, p.datep as date
					FROM ".MAIN_DB_PREFIX."propaldet pd
					LEFT JOIN ".MAIN_DB_PREFIX."propal p ON pd.fk_propal = p.rowid
					WHERE pd.fk_product = ".$objectLine->fk_product."
					AND p.fk_soc = (".$subSelect[get_class($objectLine)].")
					AND p.fk_statut > 0
					AND p.datep >= ".$filterDate['thisyear']."
					ORDER BY date DESC
					LIMIT 1";
		
		$sqlToUse = array();
		foreach($sql as $type => $query) {
			if(in_array($type, array('invoice', 'order', 'proposal'))) { // TODO : define in config where to search
				$sqlToUse[] = '('.$query.')';
			}
		}
		
		$sqlFinal = implode(' UNION ', $sqlToUse);
		$sqlFinal.= ' ORDER BY date DESC LIMIT 1';
		//echo $sqlFinal;
		
		$prix = 0;
		$resql = $this->db->query($sqlFinal);
		if($resql) {
			$obj = $this->db->fetch_object($resql);
			$prix = $obj->subprice;
			$fk_soc = $obj->fk_soc;
		}
		
		if(!empty($prix)) {
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
			
			return $prix;
		}
		
		return -1;
	}
}
