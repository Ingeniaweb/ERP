<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2014      Charles-Fr Benke     <charles.fr@benke.fr>
 * Copyright (C) 2015      Jean-François Ferry  <jfefe@aternatik.fr>
 * Copyright (C) 2016      Ferran Marcet        <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *  \file       htdocs/societe/index.php
 *  \ingroup    societe
 *  \brief      Home page for third parties area
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
if (! empty($conf->commande->enabled))
    require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->commande->enabled))
    require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
if (! empty($conf->tax->enabled))
    require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';

$langs->load("companies");
$tipo_tercero=$_GET['leftmenu']; //Variable tomada de la URL para determinar el tipo de usuario
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;

// Security check
$result=restrictedArea($user,'societe',0,'','','','');

$thirdparty_static = new Societe($db);

    /*Start here*/
    $langs->loadLangs(array('compta', 'bills'));
    if (! empty($conf->commande->enabled))
        $langs->load("orders");
    $action=GETPOST('action', 'alpha');
    $bid=GETPOST('bid', 'int');
    // Security check
    $socid='';
    if ($user->societe_id > 0)
    {
        $action = '';
        $socid = $user->societe_id;
    }
    $max=3;
    $hookmanager->initHooks(array('invoiceindex'));
    $now=dol_now();
    $facturestatic=new Facture($db);
    $facturesupplierstatic=new FactureFournisseur($db);
    $form = new Form($db);
    $formfile = new FormFile($db);
    $thirdpartystatic = new Societe($db);



    /*Fin here*/


/*
 * View
 */

$transAreaType = $langs->trans("ThirdPartiesArea");
$helpurl='EN:Module_Third_Parties|FR:Module_Tiers|ES:M&oacute;dulo_Terceros';

llxHeader("",$langs->trans("ThirdParties"),$helpurl);
$linkback='';
if($_GET['leftmenu']=="proveedores" || $_GET['mainmenu'] == "proveedores"){
    print load_fiche_titre("Proveedores",$linkback,'title_companies.png');
}
else{
    print load_fiche_titre("Clientes",$linkback,'title_companies.png');
}

//print '<table border="0" width="100%" class="notopnoleftnoright">';
//print '<tr><td valign="top" width="30%" class="notopnoleft">';
print '<div class="fichecenter"><div class="fichethirdleft">';


if (! empty($conf->global->MAIN_SEARCH_FORM_ON_HOME_AREAS))     // This is useless due to the global search combo
{
    // Search thirdparty
    if (! empty($conf->societe->enabled) && $user->rights->societe->lire)
    {
        $listofsearchfields['search_thirdparty']=array('text'=>'ThirdParty');
    }
    // Search contact/address
    if (! empty($conf->societe->enabled) && $user->rights->societe->lire)
    {
        $listofsearchfields['search_contact']=array('text'=>'Contact');
    }

    if (count($listofsearchfields))
    {
        print '<form method="post" action="'.DOL_URL_ROOT.'/core/search.php">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<table class="noborder nohover centpercent">';
        $i=0;
        foreach($listofsearchfields as $key => $value)
        {
            if ($i == 0) print '<tr class="liste_titre"><th colspan="3">'.$langs->trans("Search").'</th></tr>';
            print '<tr '.$bc[false].'>';
            print '<td class="nowrap"><label for="'.$key.'">'.$langs->trans($value["text"]).'</label></td><td><input type="text" class="flat inputsearch" name="'.$key.'" id="'.$key.'" size="18"></td>';
            if ($i == 0) print '<td rowspan="'.count($listofsearchfields).'"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td>';
            print '</tr>';
            $i++;
        }
        print '</table>';
        print '</form>';
        print '<br>';
    }
}


/*
 * Statistics area
 */

$third = array(
        'customer' => 0,
        'prospect' => 0,
        'supplier' => 0,
        'other' =>0
);
$total=0;
if($_GET['leftmenu']=="proveedores" || $_GET['mainmenu'] == "proveedores"){
    $sql = "SELECT s.rowid, s.client, s.fournisseur";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
    if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= ' WHERE s.entity IN ('.getEntity('societe').')';
    if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    if ($socid) $sql.= " AND s.rowid = ".$socid;
    $sql.= " AND (s.fournisseur = 1 OR s.client <> 0)";    // client=0, fournisseur=0 must be visible
}  
if($_GET['mainmenu'] == "companies" || $_GET['leftmenu'] == "thirdparties"){
    $sql = "SELECT s.rowid, s.client, s.fournisseur";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
    if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= ' WHERE s.entity IN ('.getEntity('societe').')';
    if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    if ($socid) $sql.= " AND s.rowid = ".$socid;
    $sql.= " AND (s.fournisseur <> 1 OR s.client = 0)";     // client=0, fournisseur=0 must be visible
}                                                                                             
//print $sql;
$result = $db->query($sql);
if ($result)
{
    while ($objp = $db->fetch_object($result))
    {
        $found=0;
        if (! empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS_STATS) && ($objp->client == 2 || $objp->client == 3)) { $found=1; $third['prospect']++; }
        if (! empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS_STATS) && ($objp->client == 1 || $objp->client == 3)) { $found=1; $third['customer']++; }
        if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_STATS) && $objp->fournisseur) { $found=1; $third['supplier']++; }
        if (! empty($conf->societe->enabled) && $objp->client == 0 && $objp->fournisseur == 0) { $found=1; $third['other']++; }
        if ($found) $total++;
    }
}
else dol_print_error($db);

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder nohover" width="100%">'."\n";
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").'</th></tr>';
if (! empty($conf->use_javascript_ajax) && ((round($third['prospect'])?1:0)+(round($third['customer'])?1:0)+(round($third['supplier'])?1:0)+(round($third['other'])?1:0) >= 2))
{
    print '<tr><td align="center" colspan="2">';
    $dataseries=array();
    if($_GET['leftmenu']=="companies" || $_GET['mainmenu'] == "companies" || $_GET['leftmenu'] == "thirdparties"){
        if (! empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS_STATS))     $dataseries[]=array($langs->trans("Prospects"), round($third['prospect']));
        if (! empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS_STATS))     $dataseries[]=array($langs->trans("Customers"), round($third['customer']));
        //if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_STATS)) $dataseries[]=array($langs->trans("Suppliers"), round($third['supplier']));
        //if (! empty($conf->societe->enabled)) $dataseries[]=array($langs->trans("Others"), round($third['other']));        
    }
    else{
        //if (! empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS_STATS))     $dataseries[]=array($langs->trans("Prospects"), round($third['prospect']));
        //if (! empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS_STATS))     $dataseries[]=array($langs->trans("Customers"), round($third['customer']));
        if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_STATS)) $dataseries[]=array($langs->trans("Suppliers"), round($third['supplier']));
        //if (! empty($conf->societe->enabled)) $dataseries[]=array($langs->trans("Others"), round($third['other']));        
    }

    include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
    $dolgraph = new DolGraph();
    $dolgraph->SetData($dataseries);
    $dolgraph->setShowLegend(1);
    $dolgraph->setShowPercent(1);
    $dolgraph->SetType(array('pie'));
    $dolgraph->setWidth('100%');
    $dolgraph->draw('idgraphthirdparties');
    print $dolgraph->show();
    print '</td></tr>'."\n";
}
else
{
    if (! empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS_STATS))
    {
        $statstring = "<tr>";
        $statstring.= '<td><a href="'.DOL_URL_ROOT.'/societe/list.php?type=p">'.$langs->trans("Prospects").'</a></td><td align="right">'.round($third['prospect']).'</td>';
        $statstring.= "</tr>";
    }
    if (! empty($conf->societe->enabled) && $user->rights->societe->lire && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS_STATS))
    {
        $statstring.= "<tr>";
        $statstring.= '<td><a href="'.DOL_URL_ROOT.'/societe/list.php?type=c">'.$langs->trans("Customers").'</a></td><td align="right">'.round($third['customer']).'</td>';
        $statstring.= "</tr>";
    }
    if (! empty($conf->fournisseur->enabled) && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_STATS) && $user->rights->fournisseur->lire)
    {
        $statstring2 = "<tr>";
        $statstring2.= '<td><a href="'.DOL_URL_ROOT.'/societe/list.php?type=f">'.$langs->trans("Suppliers").'</a></td><td align="right">'.round($third['supplier']).'</td>';
        $statstring2.= "</tr>";
    }
    print $statstring;
    print $statstring2;
}
/*
print '<tr class="liste_total"><td>'.$langs->trans("UniqueThirdParties").'</td><td align="right">';
print $total;
print '</td></tr>';*/
print '</table>';
print '</div>';

if (! empty($conf->categorie->enabled) && ! empty($conf->global->CATEGORY_GRAPHSTATS_ON_THIRDPARTIES))
{
    require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
    $elementtype = 'societe';

    print '<br>';

    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder nohover" width="100%">';
    print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Categories").'</th></tr>';
    print '<tr '.$bc[0].'><td align="center" colspan="2">';
    $sql = "SELECT c.label, count(*) as nb";
    $sql.= " FROM ".MAIN_DB_PREFIX."categorie_societe as cs";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON cs.fk_categorie = c.rowid";
    $sql.= " WHERE c.type = 2";
    if (! is_numeric($conf->global->CATEGORY_GRAPHSTATS_ON_THIRDPARTIES)) $sql.= " AND c.label like '".$db->escape($conf->global->CATEGORY_GRAPHSTATS_ON_THIRDPARTIES)."'";
    $sql.= " AND c.entity IN (".getEntity('category').")";
    $sql.= " GROUP BY c.label";
    $total=0;
    $result = $db->query($sql);
    if ($result)
    {
        $num = $db->num_rows($result);
        $i=0;
        if (! empty($conf->use_javascript_ajax) )
        {
            $dataseries=array();
            $rest=0;
            $nbmax=10;

            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                if ($i < $nbmax)
                {
                    $dataseries[]=array($obj->label, round($obj->nb));
                }
                else
                {
                    $rest+=$obj->nb;
                }
                $total+=$obj->nb;
                $i++;
            }
            if ($i > $nbmax)
            {
                $dataseries[]=array($langs->trans("Other"), round($rest));
            }
            include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
            $dolgraph = new DolGraph();
            $dolgraph->SetData($dataseries);
            $dolgraph->setShowLegend(1);
            $dolgraph->setShowPercent(1);
            $dolgraph->SetType(array('pie'));
            $dolgraph->setWidth('100%');
            $dolgraph->draw('idgraphcateg');
            print $dolgraph->show();
        }
        else
        {
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);

                print '<tr class="oddeven"><td>'.$obj->label.'</td><td>'.$obj->nb.'</td></tr>';
                $total+=$obj->nb;
                $i++;
            }
        }
    }
    print '</td></tr>';
    print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td align="right">';
    print $total;
    print '</td></tr>';
    print '</table>';
    print '</div>';
}

//print '</td><td valign="top" width="70%" class="notopnoleftnoright">';
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * Last third parties modified
 */
if($_GET['leftmenu']=="proveedores" || $_GET['mainmenu'] == "proveedores"){
    $max=15;
    $sql = "SELECT s.rowid, s.nom as name, s.client, s.fournisseur";
    $sql.= ", s.code_client";
    $sql.= ", s.code_fournisseur";
    $sql.= ", s.logo";
    $sql.= ", s.canvas, s.tms as datem, s.status as status";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
    if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= ' WHERE s.code_fournisseur is not NULL and s.entity IN ('.getEntity('societe').')';
    if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    if ($socid) $sql.= " AND s.rowid = ".$socid;
    if (! $user->rights->fournisseur->lire) $sql.=" AND (s.fournisseur != 1 OR s.client != 0)";
    $sql.= $db->order("s.tms","DESC");
    $sql.= $db->plimit($max,0);

}
else{
    $max=15;
    $sql = "SELECT s.rowid, s.nom as name, s.client, s.fournisseur";
    $sql.= ", s.code_client";
    $sql.= ", s.code_fournisseur";
    $sql.= ", s.logo";
    $sql.= ", s.canvas, s.tms as datem, s.status as status";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
    if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= ' WHERE s.code_client is not NULL and s.entity IN ('.getEntity('societe').')';
    if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    if ($socid) $sql.= " AND s.rowid = ".$socid;
    if (! $user->rights->fournisseur->lire) $sql.=" AND (s.fournisseur != 0 OR s.client != 1)";
    $sql.= $db->order("s.tms","DESC");
    $sql.= $db->plimit($max,0);
}

//print $sql;
$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);

    $i = 0;

    if ($num > 0)
    {
        $transRecordedType = $langs->trans("LastModifiedThirdParties",$max);

        print "\n<!-- last thirdparties modified -->\n";
        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder" width="100%">';
        if($_GET['leftmenu']=="companies" || $_GET['mainmenu'] == "companies"){ $transRecordedType="Ultimos clientes modificados";}
        else{$transRecordedType="Ultimos proveedores modificados";}
        print '<tr class="liste_titre"><th colspan="2">'.$transRecordedType.'</th>';
        print '<th>&nbsp;</th>';
        print '<th align="right">'.$langs->trans('Status').'</th>';
        print '</tr>'."\n";

        while ($i < $num)
        {
            $objp = $db->fetch_object($result);

            $thirdparty_static->id=$objp->rowid;
            $thirdparty_static->name=$objp->name;
            $thirdparty_static->client=$objp->client;
            $thirdparty_static->fournisseur=$objp->fournisseur;
            $thirdparty_static->logo = $objp->logo;
            $thirdparty_static->datem=$db->jdate($objp->datem);
            $thirdparty_static->status=$objp->status;
            $thirdparty_static->code_client = $objp->code_client;
            $thirdparty_static->code_fournisseur = $objp->code_fournisseur;
            $thirdparty_static->canvas=$objp->canvas;

            print '<tr class="oddeven">';
            // Name
            print '<td class="nowrap">';
            print $thirdparty_static->getNomUrl(1);
            print "</td>\n";
            // Type
            print '<td align="center">';
            if ($thirdparty_static->client==1 || $thirdparty_static->client==3)
            {
                $thirdparty_static->name=$langs->trans("Customer");
                print $thirdparty_static->getNomUrl(0,'customer',0,1);
            }
            if ($thirdparty_static->client == 3 && empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) print " / ";
            if (($thirdparty_static->client==2 || $thirdparty_static->client==3) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
            {
                $thirdparty_static->name=$langs->trans("Prospect");
                print $thirdparty_static->getNomUrl(0,'prospect',0,1);
            }
            if (! empty($conf->fournisseur->enabled) && $thirdparty_static->fournisseur)
            {
                if ($thirdparty_static->client) print " / ";
                $thirdparty_static->name=$langs->trans("Supplier");
                print $thirdparty_static->getNomUrl(0,'supplier',0,1);
            }
            print '</td>';
            // Last modified date
            print '<td align="right">';
            print dol_print_date($thirdparty_static->datem,'day');
            print "</td>";
            print '<td align="right" class="nowrap">';
            print $thirdparty_static->getLibStatut(3);
            print "</td>";
            print "</tr>\n";
            $i++;
        }

        $db->free($result);

        print "</table>\n";
        print '</div>';
        print "<!-- End last thirdparties modified -->\n";
    }
    if($_GET['leftmenu']=="proveedores" || $_GET['mainmenu'] == "proveedores"){

        /**
         * Draft suppliers invoices
         */
        if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->facture->lire)
        {
            $sql  = "SELECT f.ref, f.rowid, f.total_ht, f.total_tva, f.total_ttc, f.type, f.ref_supplier";
            $sql.= ", s.nom as name";
            $sql.= ", s.rowid as socid, s.email";
            $sql.= ", s.code_fournisseur, s.code_compta_fournisseur";
            $sql.= ", cc.rowid as country_id, cc.code as country_code";
            $sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f, ".MAIN_DB_PREFIX."societe as s LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = s.fk_pays";
            if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql.= " WHERE s.rowid = f.fk_soc AND f.fk_statut = 0";
            $sql.= " AND f.entity = ".$conf->entity;
            if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
            if ($socid) $sql.= " AND f.fk_soc = ".$socid;
            // Add where from hooks
            $parameters=array();
            $reshook=$hookmanager->executeHooks('printFieldListWhereSupplierDraft',$parameters);
            $sql.=$hookmanager->resPrint;
            $resql = $db->query($sql);
            if ( $resql )
            {
                $num = $db->num_rows($resql);
                print '<table class="noborder" width="100%">';
                print '<tr class="liste_titre">';
                print '<th colspan="3">'.$langs->trans("SuppliersDraftInvoices").($num?' <span class="badge">'.$num.'</span>':'').'</th></tr>';
                if ($num)
                {
                    $companystatic=new Societe($db);
                    $i = 0;
                    $tot_ttc = 0;
                    while ($i < $num)
                    {
                        $obj = $db->fetch_object($resql);
                        $facturesupplierstatic->ref=$obj->ref;
                        $facturesupplierstatic->id=$obj->rowid;
                        $facturesupplierstatic->total_ht=$obj->total_ht;
                        $facturesupplierstatic->total_tva=$obj->total_tva;
                        $facturesupplierstatic->total_ttc=$obj->total_ttc;
                        $facturesupplierstatic->ref_supplier=$obj->ref_supplier;
                        $facturesupplierstatic->type=$obj->type;
                        $companystatic->id=$obj->socid;
                        $companystatic->name=$obj->name;
                        $companystatic->email=$obj->email;
                        $companystatic->country_id=$obj->country_id;
                        $companystatic->country_code=$obj->country_code;
                        $companystatic->fournisseur = 1;
                        $companystatic->code_client = $obj->code_client;
                        $companystatic->code_fournisseur = $obj->code_fournisseur;
                        $companystatic->code_compta = $obj->code_compta;
                        $companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
                        print '<tr class="oddeven"><td class="nowrap">';
                        print $facturesupplierstatic->getNomUrl(1,'',16);
                        print '</td>';
                        print '<td>';
                        print $companystatic->getNomUrl(1,'supplier',16);
                        print '</td>';
                        print '<td align="right">'.price($obj->total_ttc).'</td>';
                        print '</tr>';
                        $tot_ttc+=$obj->total_ttc;
                        $i++;
                    }
                    print '<tr class="liste_total"><td align="left">'.$langs->trans("Total").'</td>';
                    print '<td colspan="2" align="right">'.price($tot_ttc).'</td>';
                    print '</tr>';
                }
                else
                {
                    print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoInvoice").'</td></tr>';
                }
                print "</table><br>";
                $db->free($resql);
            }
            else
            {
                dol_print_error($db);
            }
        }  
        // Last modified supplier invoices
        if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->facture->lire)
        {
            $langs->load("boxes");
            $facstatic=new FactureFournisseur($db);
            $sql = "SELECT ff.rowid, ff.ref, ff.fk_statut, ff.libelle, ff.total_ht, ff.total_tva, ff.total_ttc, ff.tms, ff.paye";
            $sql.= ", s.nom as name";
            $sql.= ", s.rowid as socid";
            $sql.= ", s.code_fournisseur, s.code_compta_fournisseur";
            $sql.= ", SUM(pf.amount) as am";
            $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture_fourn as ff";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf on ff.rowid=pf.fk_facturefourn";
            if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql.= " WHERE s.rowid = ff.fk_soc";
            $sql.= " AND ff.entity = ".$conf->entity;
            if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
            if ($socid) $sql.= " AND ff.fk_soc = ".$socid;
            // Add where from hooks
            $parameters=array();
            $reshook=$hookmanager->executeHooks('printFieldListWhereSupplierLastModified',$parameters);
            $sql.=$hookmanager->resPrint;
            $sql.= " GROUP BY ff.rowid, ff.ref, ff.fk_statut, ff.libelle, ff.total_ht, ff.tva, ff.total_tva, ff.total_ttc, ff.tms, ff.paye,";
            $sql.= " s.nom, s.rowid, s.code_fournisseur, s.code_compta_fournisseur";
            $sql.= " ORDER BY ff.tms DESC ";
            $sql.= $db->plimit($max, 0);
            $resql=$db->query($sql);
            if ($resql)
            {
                $var=false;
                $num = $db->num_rows($resql);
                print '<table class="noborder" width="100%">';
                print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("BoxTitleLastSupplierBills",$max).'</th>';
                if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<th align="right">'.$langs->trans("AmountHT").'</th>';
                print '<th align="right">'.$langs->trans("AmountTTC").'</th>';
                print '<th align="right">'.$langs->trans("DateModificationShort").'</th>';
                print '<th width="16">&nbsp;</th>';
                print "</tr>\n";
                if ($num)
                {
                    $i = 0;
                    $total = $total_ttc = $totalam = 0;
                    while ($i < $num)
                    {
                        $obj = $db->fetch_object($resql);
                        $facstatic->ref=$obj->ref;
                        $facstatic->id = $obj->rowid;
                        $facstatic->total_ht = $obj->total_ht;
                        $facstatic->total_tva = $obj->total_tva;
                        $facstatic->total_ttc = $obj->total_ttc;
                        $thirdpartystatic->id=$obj->socid;
                        $thirdpartystatic->name=$obj->name;
                        $thirdpartystatic->fournisseur=1;
                        //$thirdpartystatic->code_client = $obj->code_client;
                        $thirdpartystatic->code_fournisseur = $obj->code_fournisseur;
                        //$thirdpartystatic->code_compta = $obj->code_compta;
                        $thirdpartystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
                        print '<tr class="oddeven"><td>';
                        print $facstatic->getNomUrl(1,'');
                        print '</td>';
                        print '<td>';
                        print $thirdpartystatic->getNomUrl(1,'supplier',44);
                        print '</td>';
                        if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.price($obj->total_ht).'</td>';
                        print '<td align="right">'.price($obj->total_ttc).'</td>';
                        print '<td align="right">'.dol_print_date($db->jdate($obj->tms),'day').'</td>';
                        print '<td>'.$facstatic->LibStatut($obj->paye,$obj->fk_statut,3).'</td>';
                        print '</tr>';
                        $total += $obj->total_ht;
                        $total_ttc +=  $obj->total_ttc;
                        $totalam +=  $obj->am;
                        $i++;
                        $var = !$var;
                    }
                }
                else
                {
                    $colspan=5;
                    if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) $colspan++;
                    print '<tr class="oddeven"><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoInvoice").'</td></tr>';
                }
                print '</table><br>';
            }
            else
            {
                dol_print_error($db);
            }
        }
        /*
         * Unpayed supplier invoices
         */
        if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->facture->lire)
        {
            $facstatic=new FactureFournisseur($db);
            $sql = "SELECT ff.rowid, ff.ref, ff.fk_statut, ff.libelle, ff.total_ht, ff.total_tva, ff.total_ttc, ff.paye";
            $sql.= ", ff.date_lim_reglement";
            $sql.= ", s.nom as name";
            $sql.= ", s.rowid as socid, s.email";
            $sql.= ", s.code_client, s.code_compta";
            $sql.= ", s.code_fournisseur, s.code_compta_fournisseur";
            $sql.= ", sum(pf.amount) as am";
            $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture_fourn as ff";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf on ff.rowid=pf.fk_facturefourn";
            if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql.= " WHERE s.rowid = ff.fk_soc";
            $sql.= " AND ff.entity = ".$conf->entity;
            $sql.= " AND ff.paye = 0";
            $sql.= " AND ff.fk_statut = 1";
            if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
            if ($socid) $sql.= " AND ff.fk_soc = ".$socid;
            // Add where from hooks
            $parameters=array();
            $reshook=$hookmanager->executeHooks('printFieldListWhereSupplierUnpaid',$parameters);
            $sql.=$hookmanager->resPrint;
            $sql.= " GROUP BY ff.rowid, ff.ref, ff.fk_statut, ff.libelle, ff.total_ht, ff.tva, ff.total_tva, ff.total_ttc, ff.paye, ff.date_lim_reglement,";
            $sql.= " s.nom, s.rowid, s.email, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur";
            $sql.= " ORDER BY ff.date_lim_reglement ASC";
            $resql=$db->query($sql);
            if ($resql)
            {
                $num = $db->num_rows($resql);
                print '<div class="div-table-responsive-no-min">';
                print '<table class="noborder" width="100%">';
                print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("BillsSuppliersUnpaid",$num).' <a href="'.DOL_URL_ROOT.'/fourn/facture/impayees.php"><span class="badge">'.$num.'</span></a></th>';
                print '<th align="right">'.$langs->trans("DateDue").'</th>';
                if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<th align="right">'.$langs->trans("AmountHT").'</th>';
                print '<th align="right">'.$langs->trans("AmountTTC").'</th>';
                print '<th align="right">'.$langs->trans("Paid").'</th>';
                print '<th width="16">&nbsp;</th>';
                print "</tr>\n";
                $societestatic = new Societe($db);
                if ($num)
                {
                    $i = 0;
                    $total = $total_ttc = $totalam = 0;
                    while ($i < $num)
                    {
                        $obj = $db->fetch_object($resql);
                        $facstatic->ref=$obj->ref;
                        $facstatic->id = $obj->rowid;
                        $facstatic->total_ht = $obj->total_ht;
                        $facstatic->total_tva = $obj->total_tva;
                        $facstatic->total_ttc = $obj->total_ttc;
                        $societestatic->id=$obj->socid;
                        $societestatic->name=$obj->name;
                        $societestatic->email=$obj->email;
                        $societestatic->client=0;
                        $societestatic->fournisseur=1;
                        $societestatic->code_client = $obj->code_client;
                        $societestatic->code_fournisseur = $obj->code_fournisseur;
                        $societestatic->code_compta = $obj->code_compta;
                        $societestatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
                        print '<tr class="oddeven"><td>';
                        print $facstatic->getNomUrl(1,'');
                        print '</td>';
                        print '<td>'.$societestatic->getNomUrl(1, 'supplier', 44).'</td>';
                        print '<td align="right">'.dol_print_date($db->jdate($obj->date_lim_reglement),'day').'</td>';
                        if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.price($obj->total_ht).'</td>';
                        print '<td align="right">'.price($obj->total_ttc).'</td>';
                        print '<td align="right">'.price($obj->am).'</td>';
                        print '<td>'.$facstatic->LibStatut($obj->paye,$obj->fk_statut,3).'</td>';
                        print '</tr>';
                        $total += $obj->total_ht;
                        $total_ttc +=  $obj->total_ttc;
                        $totalam +=  $obj->am;
                        $i++;
                    }
                    print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").' &nbsp; <font style="font-weight: normal">('.$langs->trans("RemainderToPay").': '.price($total_ttc-$totalam).')</font> </td>';
                    print '<td>&nbsp;</td>';
                    if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.price($total).'</td>';
                    print '<td align="right">'.price($total_ttc).'</td>';
                    print '<td align="right">'.price($totalam).'</td>';
                    print '<td>&nbsp;</td>';
                    print '</tr>';
                }
                else
                {
                    $colspan=6;
                    if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) $colspan++;
                    print '<tr class="oddeven"><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoInvoice").'</td></tr>';
                }
                print '</table></div><br>';
            }
            else
            {
                dol_print_error($db);
            }
        }        

    }//Fin IF provee
    else{
        /**
         * Draft customers invoices
         */
        if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
        {
            $sql = "SELECT f.facnumber";
            $sql.= ", f.rowid, f.total as total_ht, f.tva as total_tva, f.total_ttc, f.ref_client";
            $sql.= ", f.type";
            $sql.= ", s.nom as name";
            $sql.= ", s.rowid as socid, s.email";
            $sql.= ", s.code_client, s.code_compta, s.code_fournisseur, s.code_compta_fournisseur";
            if (!$user->rights->societe->client->voir && !$socid) $sql.= ", sc.fk_soc, sc.fk_user ";
            $sql.= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";
            if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql.= " WHERE s.rowid = f.fk_soc AND f.fk_statut = 0";
            $sql.= " AND f.entity = ".$conf->entity;
            if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
            if ($socid)
            {
                $sql .= " AND f.fk_soc = $socid";
            }
            // Add where from hooks
            $parameters=array();
            $reshook=$hookmanager->executeHooks('printFieldListWhereCustomerDraft',$parameters);
            $sql.=$hookmanager->resPrint;
            $resql = $db->query($sql);
            if ( $resql )
            {
                $var = false;
                $num = $db->num_rows($resql);
                print '<table class="noborder" width="100%">';
                print '<tr class="liste_titre">';
                print '<th colspan="3">'.$langs->trans("CustomersDraftInvoices").($num?' <span class="badge">'.$num.'</span>':'').'</th></tr>';
                if ($num)
                {
                    $companystatic=new Societe($db);
                    $i = 0;
                    $tot_ttc = 0;
                    while ($i < $num)
                    {
                        $obj = $db->fetch_object($resql);
                        $facturestatic->ref=$obj->facnumber;
                        $facturestatic->id=$obj->rowid;
                        $facturestatic->total_ht=$obj->total_ht;
                        $facturestatic->total_tva=$obj->total_tva;
                        $facturestatic->total_ttc=$obj->total_ttc;
                        $facturestatic->ref_client=$obj->ref_client;
                        $facturestatic->type=$obj->type;
                        $companystatic->id=$obj->socid;
                        $companystatic->name=$obj->name;
                        $companystatic->email=$obj->email;
                        $companystatic->client = 1;
                        $companystatic->code_client = $obj->code_client;
                        $companystatic->code_fournisseur = $obj->code_fournisseur;
                        $companystatic->code_compta = $obj->code_compta;
                        $companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
                        print '<tr class="oddeven"><td class="nowrap">';
                        print $facturestatic->getNomUrl(1,'');
                        print '</td>';
                        print '<td class="nowrap">';
                        print $companystatic->getNomUrl(1,'customer',16);
                        print '</td>';
                        print '<td align="right" class="nowrap">'.price($obj->total_ttc).'</td>';
                        print '</tr>';
                        $tot_ttc+=$obj->total_ttc;
                        $i++;
                    }
                    print '<tr class="liste_total"><td align="left">'.$langs->trans("Total").'</td>';
                    print '<td colspan="2" align="right">'.price($tot_ttc).'</td>';
                    print '</tr>';
                }
                else
                {
                    print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoInvoice").'</td></tr>';
                }
                print "</table><br>";
                $db->free($resql);
            }
            else
            {
                dol_print_error($db);
            }
        }
        if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
        {
            $langs->load("boxes");
            $facstatic=new Facture($db);
            $sql = "SELECT f.rowid, f.facnumber, f.fk_statut, f.type, f.total as total_ht, f.tva as total_tva, f.total_ttc, f.paye, f.tms";
            $sql.= ", f.date_lim_reglement as datelimite";
            $sql.= ", s.nom as name";
            $sql.= ", s.rowid as socid";
            $sql.= ", s.code_client, s.code_compta, s.email";
            $sql.= ", cc.rowid as country_id, cc.code as country_code";
            $sql.= ", sum(pf.amount) as am";
            $sql.= " FROM ".MAIN_DB_PREFIX."societe as s LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = s.fk_pays, ".MAIN_DB_PREFIX."facture as f";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid=pf.fk_facture";
            if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql.= " WHERE s.rowid = f.fk_soc";
            $sql.= " AND f.entity = ".$conf->entity;
            if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
            if ($socid) $sql.= " AND f.fk_soc = ".$socid;
            // Add where from hooks
            $parameters=array();
            $reshook=$hookmanager->executeHooks('printFieldListWhereCustomerLastModified',$parameters);
            $sql.=$hookmanager->resPrint;
            $sql.= " GROUP BY f.rowid, f.facnumber, f.fk_statut, f.type, f.total, f.tva, f.total_ttc, f.paye, f.tms, f.date_lim_reglement,";
            $sql.= " s.nom, s.rowid, s.code_client, s.code_compta, s.email,";
            $sql.= " cc.rowid, cc.code";
            $sql.= " ORDER BY f.tms DESC ";
            $sql.= $db->plimit($max, 0);
            $resql = $db->query($sql);
            if ($resql)
            {
                $var=false;
                $num = $db->num_rows($resql);
                $i = 0;
                print '<table class="noborder" width="100%">';
                print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("BoxTitleLastCustomerBills",$max).'</th>';
                if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<th align="right">'.$langs->trans("AmountHT").'</th>';
                print '<th align="right">'.$langs->trans("AmountTTC").'</th>';
                print '<th align="right">'.$langs->trans("DateModificationShort").'</th>';
                print '<th width="16">&nbsp;</th>';
                print '</tr>';
                if ($num)
                {
                    $total_ttc = $totalam = $total = 0;
                    while ($i < $num && $i < $conf->liste_limit)
                    {
                        $obj = $db->fetch_object($resql);
                        $facturestatic->ref=$obj->facnumber;
                        $facturestatic->id=$obj->rowid;
                        $facturestatic->total_ht=$obj->total_ht;
                        $facturestatic->total_tva=$obj->total_tva;
                        $facturestatic->total_ttc=$obj->total_ttc;
                        $facturestatic->statut = $obj->fk_statut;
                        $facturestatic->date_lim_reglement = $db->jdate($obj->datelimite);
                        $facturestatic->type=$obj->type;
                        $thirdpartystatic->id=$obj->socid;
                        $thirdpartystatic->name=$obj->name;
                        $thirdpartystatic->email=$obj->email;
                        $thirdpartystatic->country_id=$obj->country_id;
                        $thirdpartystatic->country_code=$obj->country_code;
                        $thirdpartystatic->email=$obj->email;
                        $thirdpartystatic->client=1;
                        $thirdpartystatic->code_client = $obj->code_client;
                        //$thirdpartystatic->code_fournisseur = $obj->code_fournisseur;
                        $thirdpartystatic->code_compta = $obj->code_compta;
                        //$thirdpartystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
                        print '<tr class="oddeven">';
                        print '<td class="nowrap">';
                        print '<table class="nobordernopadding"><tr class="nocellnopadd">';
                        print '<td width="110" class="nobordernopadding nowrap">';
                        print $facturestatic->getNomUrl(1,'');
                        print '</td>';
                        print '<td width="20" class="nobordernopadding nowrap">';
                        if ($facturestatic->hasDelay()) {
                            print img_warning($langs->trans("Late"));
                        }
                        print '</td>';
                        print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
                        $filename=dol_sanitizeFileName($obj->facnumber);
                        $filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($obj->facnumber);
                        $urlsource=$_SERVER['PHP_SELF'].'?facid='.$obj->rowid;
                        print $formfile->getDocumentsLink($facturestatic->element, $filename, $filedir);
                        print '</td></tr></table>';
                        print '</td>';
                        print '<td align="left">';
                        print $thirdpartystatic->getNomUrl(1,'customer',44);
                        print '</td>';
                        if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.price($obj->total_ht).'</td>';
                        print '<td align="right">'.price($obj->total_ttc).'</td>';
                        print '<td align="right">'.dol_print_date($db->jdate($obj->tms),'day').'</td>';
                        print '<td>'.$facstatic->LibStatut($obj->paye,$obj->fk_statut,3,$obj->am).'</td>';
                        print '</tr>';
                        $total_ttc +=  $obj->total_ttc;
                        $total += $obj->total_ht;
                        $totalam +=  $obj->am;
                        $i++;
                    }
                }
                else
                {
                    $colspan=5;
                    if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) $colspan++;
                    print '<tr class="oddeven"><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoInvoice").'</td></tr>';
                }
                print '</table><br>';
                $db->free($resql);
            }
            else
            {
                dol_print_error($db);
            }
        } 
        /*
         * Customers orders to be billed
         */
        if (! empty($conf->facture->enabled) && ! empty($conf->commande->enabled) && $user->rights->commande->lire && empty($conf->global->WORKFLOW_DISABLE_CREATE_INVOICE_FROM_ORDER))
        {
            $commandestatic=new Commande($db);
            $langs->load("orders");
            $sql = "SELECT sum(f.total) as tot_fht, sum(f.total_ttc) as tot_fttc";
            $sql.= ", s.nom as name, s.email";
            $sql.= ", s.rowid as socid";
            $sql.= ", s.code_client, s.code_compta";
            $sql.= ", c.rowid, c.ref, c.facture, c.fk_statut, c.total_ht, c.tva as total_tva, c.total_ttc,";
            $sql.= " cc.rowid as country_id, cc.code as country_code";
            $sql.= " FROM ".MAIN_DB_PREFIX."societe as s LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = s.fk_pays";
            if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql.= ", ".MAIN_DB_PREFIX."commande as c";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON el.fk_source = c.rowid AND el.sourcetype = 'commande'";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture AS f ON el.fk_target = f.rowid AND el.targettype = 'facture'";
            $sql.= " WHERE c.fk_soc = s.rowid";
            $sql.= " AND c.entity = ".$conf->entity;
            if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
            if ($socid) $sql.= " AND c.fk_soc = ".$socid;
            $sql.= " AND c.fk_statut = 3";
            $sql.= " AND c.facture = 0";
            // Add where from hooks
            $parameters=array();
            $reshook=$hookmanager->executeHooks('printFieldListWhereCustomerOrderToBill',$parameters);
            $sql.=$hookmanager->resPrint;
            $sql.= " GROUP BY s.nom, s.rowid, s.email, s.code_client, s.code_compta, c.rowid, c.ref, c.facture, c.fk_statut, c.tva, c.total_ht, c.total_ttc";
            $resql = $db->query($sql);
            if ( $resql )
            {
                $num = $db->num_rows($resql);
                if ($num)
                {
                    $i = 0;
                    print '<table class="noborder" width="100%">';
                    print "<tr class=\"liste_titre\">";
                    print '<th colspan="2">'.$langs->trans("OrdersDeliveredToBill").' <a href="'.DOL_URL_ROOT.'/commande/list.php?viewstatut=3&amp;billed=0"><span class="badge">'.$num.'</span></a></th>';
                    if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<th align="right">'.$langs->trans("AmountHT").'</th>';
                    print '<th align="right">'.$langs->trans("AmountTTC").'</th>';
                    print '<th align="right">'.$langs->trans("ToBill").'</th>';
                    print '<th align="center" width="16">&nbsp;</th>';
                    print '</tr>';
                    $tot_ht=$tot_ttc=$tot_tobill=0;
                    $societestatic = new Societe($db);
                    while ($i < $num)
                    {
                        $obj = $db->fetch_object($resql);
                        $societestatic->id=$obj->socid;
                        $societestatic->name=$obj->name;
                        $societestatic->email=$obj->email;
                        $societestatic->country_id=$obj->country_id;
                        $societestatic->country_code=$obj->country_code;
                        $societestatic->client=1;
                        $societestatic->code_client = $obj->code_client;
                        //$societestatic->code_fournisseur = $obj->code_fournisseur;
                        $societestatic->code_compta = $obj->code_compta;
                        //$societestatic->code_fournisseur = $obj->code_fournisseur;
                        $commandestatic->id=$obj->rowid;
                        $commandestatic->ref=$obj->ref;
                        print '<tr class="oddeven">';
                        print '<td class="nowrap">';
                        print '<table class="nobordernopadding"><tr class="nocellnopadd">';
                        print '<td width="110" class="nobordernopadding nowrap">';
                        print $commandestatic->getNomUrl(1);
                        print '</td>';
                        print '<td width="20" class="nobordernopadding nowrap">';
                        print '&nbsp;';
                        print '</td>';
                        print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
                        $filename=dol_sanitizeFileName($obj->ref);
                        $filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($obj->ref);
                        $urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
                        print $formfile->getDocumentsLink($commandestatic->element, $filename, $filedir);
                        print '</td></tr></table>';
                        print '</td>';
                        print '<td align="left">';
                        print $societestatic->getNomUrl(1,'customer',44);
                        print '</td>';
                        if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.price($obj->total_ht).'</td>';
                        print '<td align="right">'.price($obj->total_ttc).'</td>';
                        print '<td align="right">'.price($obj->total_ttc-$obj->tot_fttc).'</td>';
                        print '<td>'.$commandestatic->LibStatut($obj->fk_statut,$obj->facture,3).'</td>';
                        print '</tr>';
                        $tot_ht += $obj->total_ht;
                        $tot_ttc += $obj->total_ttc;
                        //print "x".$tot_ttc."z".$obj->tot_fttc;
                        $tot_tobill += ($obj->total_ttc-$obj->tot_fttc);
                        $i++;
                    }
                    print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").' &nbsp; <font style="font-weight: normal">('.$langs->trans("RemainderToBill").': '.price($tot_tobill).')</font> </td>';
                    if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.price($tot_ht).'</td>';
                    print '<td align="right">'.price($tot_ttc).'</td>';
                    print '<td align="right">'.price($tot_tobill).'</td>';
                    print '<td>&nbsp;</td>';
                    print '</tr>';
                    print '</table><br>';
                }
                $db->free($resql);
            }
            else
            {
                dol_print_error($db);
            }
        }
        /*
         * Unpaid customers invoices
         */
        if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
        {
            $facstatic=new Facture($db);
            $sql = "SELECT f.rowid, f.facnumber, f.fk_statut, f.datef, f.type, f.total as total_ht, f.tva as total_tva, f.total_ttc, f.paye, f.tms";
            $sql.= ", f.date_lim_reglement as datelimite";
            $sql.= ", s.nom as name";
            $sql.= ", s.rowid as socid, s.email";
            $sql.= ", s.code_client, s.code_compta";
            $sql.= ", cc.rowid as country_id, cc.code as country_code";
            $sql.= ", sum(pf.amount) as am";
            $sql.= " FROM ".MAIN_DB_PREFIX."societe as s LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = s.fk_pays,".MAIN_DB_PREFIX."facture as f";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid=pf.fk_facture";
            if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql.= " WHERE s.rowid = f.fk_soc AND f.paye = 0 AND f.fk_statut = 1";
            $sql.= " AND f.entity = ".$conf->entity;
            if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
            if ($socid) $sql.= " AND f.fk_soc = ".$socid;
            // Add where from hooks
            $parameters=array();
            $reshook=$hookmanager->executeHooks('printFieldListWhereCustomerUnpaid',$parameters);
            $sql.=$hookmanager->resPrint;
            $sql.= " GROUP BY f.rowid, f.facnumber, f.fk_statut, f.datef, f.type, f.total, f.tva, f.total_ttc, f.paye, f.tms, f.date_lim_reglement,";
            $sql.= " s.nom, s.rowid, s.email, s.code_client, s.code_compta, cc.rowid, cc.code";
            $sql.= " ORDER BY f.datef ASC, f.facnumber ASC";
            $resql = $db->query($sql);
            if ($resql)
            {
                $num = $db->num_rows($resql);
                $i = 0;
                print '<div class="div-table-responsive-no-min">';
                print '<table class="noborder" width="100%">';
                print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("BillsCustomersUnpaid",$num).' <a href="'.DOL_URL_ROOT.'/compta/facture/list.php?search_status=1"><span class="badge">'.$num.'</span></a></th>';
                print '<th align="right">'.$langs->trans("DateDue").'</th>';
                if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<th align="right">'.$langs->trans("AmountHT").'</th>';
                print '<th align="right">'.$langs->trans("AmountTTC").'</th>';
                print '<th align="right">'.$langs->trans("Received").'</th>';
                print '<th width="16">&nbsp;</th>';
                print '</tr>';
                if ($num)
                {
                    $societestatic = new Societe($db);
                    $total_ttc = $totalam = $total = 0;
                    while ($i < $num && $i < $conf->liste_limit)
                    {
                        $obj = $db->fetch_object($resql);
                        $facturestatic->ref=$obj->facnumber;
                        $facturestatic->id=$obj->rowid;
                        $facturestatic->total_ht=$obj->total_ht;
                        $facturestatic->total_tva=$obj->total_tva;
                        $facturestatic->total_ttc=$obj->total_ttc;
                        $facturestatic->type=$obj->type;
                        $facturestatic->statut = $obj->fk_statut;
                        $facturestatic->date_lim_reglement = $db->jdate($obj->datelimite);
                        $societestatic->id=$obj->socid;
                        $societestatic->name=$obj->name;
                        $societestatic->email=$obj->email;
                        $societestatic->country_id=$obj->country_id;
                        $societestatic->country_code=$obj->country_code;
                        $societestatic->client=1;
                        $societestatic->code_client = $obj->code_client;
                        $societestatic->code_fournisseur = $obj->code_fournisseur;
                        $societestatic->code_compta = $obj->code_compta;
                        $societestatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
                        print '<tr class="oddeven">';
                        print '<td class="nowrap">';
                        print '<table class="nobordernopadding"><tr class="nocellnopadd">';
                        print '<td width="110" class="nobordernopadding nowrap">';
                        print $facturestatic->getNomUrl(1,'');
                        print '</td>';
                        print '<td width="20" class="nobordernopadding nowrap">';
                        if ($facturestatic->hasDelay()) {
                            print img_warning($langs->trans("Late"));
                        }
                        print '</td>';
                        print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
                        $filename=dol_sanitizeFileName($obj->facnumber);
                        $filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($obj->facnumber);
                        $urlsource=$_SERVER['PHP_SELF'].'?facid='.$obj->rowid;
                        print $formfile->getDocumentsLink($facturestatic->element, $filename, $filedir);
                        print '</td></tr></table>';
                        print '</td>';
                        print '<td align="left">' ;
                        print $societestatic->getNomUrl(1,'customer',44);
                        print '</td>';
                        print '<td align="right">'.dol_print_date($db->jdate($obj->datelimite),'day').'</td>';
                        if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.price($obj->total_ht).'</td>';
                        print '<td align="right">'.price($obj->total_ttc).'</td>';
                        print '<td align="right">'.price($obj->am).'</td>';
                        print '<td>'.$facstatic->LibStatut($obj->paye,$obj->fk_statut,3,$obj->am).'</td>';
                        print '</tr>';
                        $total_ttc +=  $obj->total_ttc;
                        $total += $obj->total_ht;
                        $totalam +=  $obj->am;
                        $i++;
                    }
                    print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").' &nbsp; <font style="font-weight: normal">('.$langs->trans("RemainderToTake").': '.price($total_ttc-$totalam).')</font> </td>';
                    print '<td>&nbsp;</td>';
                    if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td align="right">'.price($total).'</td>';
                    print '<td align="right">'.price($total_ttc).'</td>';
                    print '<td align="right">'.price($totalam).'</td>';
                    print '<td>&nbsp;</td>';
                    print '</tr>';
                }
                else
                {
                    $colspan=6;
                    if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) $colspan++;
                    print '<tr class="oddeven"><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoInvoice").'</td></tr>';
                }
                print '</table></div><br>';
                $db->free($resql);
            }
            else
            {
                dol_print_error($db);
            }
        }

    }//Fin Else



}
else
{
    dol_print_error($db);
}

//print '</td></tr></table>';
print '</div></div></div>';

llxFooter();

$db->close();
?>


