<?php
/** 
 * Create a template of all virtual machines in an enterprise and delete old backups
 **/

// Include the Abiquo API (github.com/tonybaloney/Abiquo-PHP)
require_once ( 'api/ConnectorException.class.php');
require_once ( 'api/Connector.interface.php');
require_once ( 'api/Abiquo.class.php' ) ;

$abiquo = new Abiquo ("abiquoserver.com", "username", "password");
$prefix = "BACKUP_"; // Prefix all instances with this text
$exclude_vm_list = array( "WEB_01", "BACKUP_02" ) ; // Don't backup machines with these names
$retention = 7 ; // number of days to keep
// Get VDC list for my enterprise 

foreach ($abiquo->GetLocations() as $vdc) {
	// get the virtual appliances
	foreach ($abiquo->GetAppliances($vdc['clusterLocation']) as $app) { 
		// get the virtual machines in this appliance
		foreach ($abiquo->GetVirtualMachines($vdc['clusterLocation'],$app['applianceId']) as $vm){
			if (!in_array($vm['vmName'],$exclude_vm_list)) { 
				try { 	
					$backup_title = $prefix.date("d_m");
					$abiquo->AbiquoSnapshotVm($vdc['clusterLocation'],$app['applianceId'],$vm['vmId'],$backup_title);
				} catch ( ConnectorException $cex ) {
					echo "Failed to backup $vm[vmName] with error : ".$cex->GetConnectorErrorMessage()."\n";
				}
			}
		}
	}
	// go and delete backups older than x days
	foreach ($abiquo->GetTemplates($vdc['clusterLocation']) as $template){
		if (mktime(0,0,0,0,-$retention) < strtotime($template['creationDate']) && substr($template['name'],0,strlen($prefix)) == $prefix){
			$abiquo->AbiquoDeleteTemplate($template['repoId'],$template['id']);
		}
	}
}

?>
