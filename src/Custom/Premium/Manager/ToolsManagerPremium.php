<?php
namespace App\Custom\Premium\Manager;

use App\Manager\ToolsManager;

class ToolsManagerPremium extends ToolsManager
{
	// Return true if premium package is enabled
	public function isPremium() {
		if (file_exists( __DIR__.'/../licence.php')) {
			include __DIR__.'/../licence.php';
			$limitDate = $this->decryptKey($licenceKey);
			if ($limitDate >= gmdate('Y-m-d')) {
				return true;
			}
		}
		return false;
	}
	
	// Decrypt the licence key
	private function decryptKey($encryptedData) {
		$cipher = "AES-256-CBC";
		$key = 'premiumversionsecretkey';
		$data = base64_decode($encryptedData);
		$ivlen = openssl_cipher_iv_length($cipher);
		$iv = substr($data, 0, $ivlen);
		$encryptedDate = substr($data, $ivlen);
		$decryptedDate = openssl_decrypt($encryptedDate, $cipher, $key, 0, $iv);
		return $decryptedDate;
	}
	
	// Get rule from the group name
	public function getRulesFromGroup($ruleGroup, $force = false)
    {
        try {
			// $rules = array();
            $sqlParams = '	SELECT rule.* 
							FROM ruleorder
								INNER JOIN rule
									ON rule.id = ruleorder.rule_id
									INNER JOIN rulegroup
										ON rulegroup.id = rule.group_id
							WHERE 
									rule.deleted = 0
								AND rulegroup.id = "'.$ruleGroup.'"
								'.(!$force ? ' AND rule.active = 1 ' : '').'
							ORDER BY ruleorder.order ASC';
            $stmt = $this->connection->prepare($sqlParams);
            $result = $stmt->executeQuery();
            return $result->fetchAllAssociative();
        } catch (Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
            return array();
        }
    }
}
