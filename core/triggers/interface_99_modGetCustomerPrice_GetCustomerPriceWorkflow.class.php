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
		if ($action == 'LINEORDER_INSERT' || $action == 'LINEORDER_UPDATE' ||
			$action == 'LINEPROPAL_INSERT' || $action == 'LINEPROPAL_UPDATE' ||
			$action == 'LINEBILL_INSERT' || $action == 'LINEBILL_UPDATE') {
			
			dol_include_once('/comm/propal/class/propal.class.php');
			dol_include_once('/commande/class/commande.class.php');
			dol_include_once('/compta/facture/class/facture.class.php');
			
			if(!empty($object->fk_product)) {
				$prix = $this->_getLastPriceForCustomer($object, 'invoice');
				if(empty($prix)) $prix = $this->_getLastPriceForCustomer($object, 'order');
				if(empty($prix)) $prix = $this->_getLastPriceForCustomer($object, 'proposal');
				if(empty($prix)) $prix = 0;
			}
			
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
			}
			
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->rowid);
		}
		
		return 0;
	}

	function _getLastPriceForCustomer(&$objectLine, $type) {
		// Subselect to get soc id
		$subSelect = array();
		$subSelect['facturedet'] = "SELECT f.fk_soc FROM ".MAIN_DB_PREFIX."facture f WHERE f.rowid = ".$objectLine->fk_facture;
		$subSelect['commandedet'] = "SELECT c.fk_soc FROM ".MAIN_DB_PREFIX."commande c WHERE c.rowid = ".$objectLine->fk_commande;
		$subSelect['propaldet'] = "SELECT c.fk_soc FROM ".MAIN_DB_PREFIX."propal p WHERE p.rowid = ".$objectLine->fk_propal;
		
		$sql = array();
		$sql['invoice'] = "SELECT subprice
					FROM ".MAIN_DB_PREFIX."facturedet fd
					LEFT JOIN ".MAIN_DB_PREFIX."facture f ON fd.fk_facture = f.rowid
					WHERE fd.fk_product = ".$objectLine->fk_product."
					AND f.fk_soc = (".$subSelect[$objectLine->element].")
					AND f.fk_statut > 0
					AND f.datef >= TIMESTAMPADD(YEAR, -1, NOW())
					ORDER BY f.datef DESC
					LIMIT 1";
		$sql['order'] = "SELECT subprice
					FROM ".MAIN_DB_PREFIX."commandedet cd
					LEFT JOIN ".MAIN_DB_PREFIX."commande c ON cd.fk_commande = c.rowid
					WHERE cd.fk_product = ".$objectLine->fk_product."
					AND c.fk_soc = (".$subSelect[$objectLine->element].")
					AND c.fk_statut > 0
					AND c.date_commande >= TIMESTAMPADD(YEAR, -1, NOW())
					ORDER BY c.date_commande DESC
					LIMIT 1";
		$sql['proposal'] = "SELECT subprice
					FROM ".MAIN_DB_PREFIX."propaldet pd
					LEFT JOIN ".MAIN_DB_PREFIX."propal p ON pd.fk_propal = p.rowid
					WHERE pd.fk_product = ".$objectLine->fk_product."
					AND p.fk_soc = (".$subSelect[$objectLine->element].")
					AND p.fk_statut > 0
					AND p.datep >= TIMESTAMPADD(YEAR, -1, NOW())
					ORDER BY p.datep DESC
					LIMIT 1";
		
		$resql = $this->db->query($sql[$type]);
		if($resql) {
			$obj = $this->db->fetch_object($resql);
			$prix = $obj->subprice;
			return $prix;
		}
		
		return false;
	}
}
