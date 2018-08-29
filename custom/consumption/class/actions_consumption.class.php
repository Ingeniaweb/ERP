<?php


class ActionsConsumption
{ 
	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	
	function completeTabsHead($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;
		global $db;
		global $user;
		require_once(DOL_DOCUMENT_ROOT."/custom/consumption/class/consumption.class.php");
		$conso = new Consumption($db);
		$head = $parameters['head'];
		$object=$parameters['object'];
		foreach ($head as $key => $val){
			if ($val[2]=='conso'){				
				$nbmvt=$conso->countconso($object);
				//echo $nbmvt."<br>";
				if($nbmvt > 0 && !strstr($head[$key][1], "badge")){
					$head[$key][1].=' <span class="badge">'. $nbmvt .'</span>';
				}
			}
		}
		return $this->results=$head;
		
	}
}
?>