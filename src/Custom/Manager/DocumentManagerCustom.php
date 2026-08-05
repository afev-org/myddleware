<?php

namespace App\Custom\Manager;

use App\Solutions\suitecrm;
use App\Manager\ruleManager;
use App\Premium\Manager\DocumentManagerPremium;
use App\Manager\LoadExternalListManager;
use App\Entity\InternalListValue as InternalListValueEntity;

class DocumentManagerCustom extends DocumentManagerPremium
{

	protected $etabComet;
	protected $quartierComet;
	protected $emailCoupon = array();
	protected $toBeCancel = array();

	protected function searchRelateDocumentByStatus($ruleRelationship, $record_id, $status) {
		// Don't check if a relate document is filtered for rule Aiko binome
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '61a930273441b' // Aiko binome
			AND $status = 'Filter'
		) {
			return null;
		}
		return parent::searchRelateDocumentByStatus($ruleRelationship, $record_id, $status);
	}
	
	// Prepare the search fields
	protected function prepareSearchFields($duplicateFields, $target) {
		// Redefine search for Brevo search rules, keeping the email or the SMS even if one of them is empty
		if (
				!empty($this->document_data['rule_id'])
			and	in_array($this->document_data['rule_id'], array('620d3e768e678', '620e5520c62d6', '66165525cb72d', '66579644bba6f')) // Rules contact to Brevo
		) {
			$searchFields = array();
			if (!empty($duplicateFields)) {
				foreach ($duplicateFields as $duplicateField) {
					// Do not search duplicates on an empty field
					if (empty($target[$duplicateField])) {
						continue;
					}
					$searchFields[$duplicateField] = $target[$duplicateField];
				}
			}
			return $searchFields;
		}
		return parent::prepareSearchFields($duplicateFields, $target);
	}

	//function that reads a row from the internallist and trys to find a match in the target
	public function findMatchCrmEtab()
	{
		//to avoid too many choices, this array must have only one element at the end
		$matchingrows = [];

		//we loop through the suiteCrm accounts
		foreach ($this->etabComet as $index => $suiteCrmSchool) {
			//init name as false at the beginning of the loop

			$validName = false;
			$validCity = false;
			
			// Check validity of code postal
			if (
					!empty($this->sourceData['Code_postal'])
				AND !empty($suiteCrmSchool['billing_address_postalcode'])
			) {
				$validPostalCode = ($this->sourceData['Code_postal'] == $suiteCrmSchool['billing_address_postalcode']);
			}
			
			// Check validity of city
			if (
					!empty($this->sourceData['Nom_commune'])
				AND !empty($suiteCrmSchool['billing_address_city'])
			) {
				$cityCompare = similar_text($this->sourceData['Nom_commune'], $suiteCrmSchool['billing_address_city'], $cityPerc);
				if ($cityPerc >= 90) {
					$validCity = true;
				}
			}
			
			//use algorithm to compare similarity of 2 names, threshold is 60% similar
			$namecompare = similar_text($suiteCrmSchool['name'], $this->sourceData['Nom_etablissement'], $perc);
			if ($perc >= 80) {
				$validName = true;
			}
			//to have a match, we need a similar name and at least the same address or postal code
			if (($validName && ($validPostalCode || $validCity))) {
				//we append the array of matches
				$matchingrows[(int)$perc] = $suiteCrmSchool['id'];
			} else {
				// throw new \Exception("Cet établissement n'a pas assez de champs");
			}
		} // end foreach dataSuiteCrm to find school
		return $matchingrows;
	}


	//function that reads a row from the internallist and trys to find a match in the target
	public function findMatchCrmQuartiers()
	{
		//to avoid too many choices, this array must have only one element at the end
		$matchingrows = [];

		//we loop through the suiteCrm quartier
		foreach ($this->quartierComet as $index => $suiteCrmQuartier) {
			// Do not search match if there is already a externalgouvid_c on teh quartier
			if (!empty($suiteCrmQuartier['externalgouvid_c'])) {
				continue;
			}
			$validCity = false;
			$validDepartement = false;
			//init name as false at the beginning of the loop
			$validName = false;
			// Check the city
			$cityCompare = similar_text($suiteCrmQuartier['ville_c'], $this->sourceData['Noms_des_communes_concernees'], $percCity);
			if ($percCity >= 90) {
				$validCity = true;
			}
			
			if (
					!empty($suiteCrmQuartier['departement_c'])
				AND !empty($this->sourceData['DEPARTEMENT'])
				AND $this->sourceData['DEPARTEMENT'] == $suiteCrmQuartier['departement_c']
			) {
				$validDepartement = true;
			}

			//use algorithm to compare similarity of 2 names, threshold is 60% similar
			$nameCompare = similar_text($suiteCrmQuartier['name'], $this->sourceData['Quartier_prioritaire'], $perc);

			if ($perc >= 80) {
				$validName = true;
			}
			//to have a match, we need a similar name and at least the same address or postal code
			if ($validName && ($validCity || $validDepartement)) {
				//we append the array of matches
				$matchingrows[(int)$perc] = $suiteCrmQuartier['id'];
			} else {
				// throw new \Exception("Cet établissement n'a pas assez de champs");
			}
		} // end foreach dataSuiteCrm to find school
		return $matchingrows;
	}
	
	protected function insertDataTable($data, $type): bool {
		if (
				$this->ruleId == '6210fcbe4d654'
			AND $type == 'T'
		) { 	// Sendinblue - email delivered
			// Change parent type if email linked to a coupon
			if (!empty($this->emailCoupon[htmlspecialchars_decode($data['sendinblue_msg_id_c'])])) {
				$data['parent_type'] = 'Leads';
			}
		}
		return parent::insertDataTable($data, $type);
	}
	
	protected function getRelationshipDirection($ruleRelationship) {
		// When we change the relationship on the fly, we have to force the direction of the relationship
		if (
				$this->ruleRelationships[0]['rule_id'] == '6210fcbe4d654'	// Sendinblue - email delivered
			AND	(
					$this->ruleRelationships[0]['field_id'] == '64397bd8c4749'	// Sendinblue - contact search into COMET
				 OR $this->ruleRelationships[0]['field_id'] == '64399ff31f587'	// Sendinblue - coupon search into COMET
			)
		) {
			return '1';
		}
		return parent::getRelationshipDirection($ruleRelationship);
	}
	
	// Connect to the source or target application
    public function connexionSolution($type)
    {
        try {
            if ('source' == $type) {
                $connId = $this->document_data['conn_id_source'];
            } elseif ('target' == $type) {
                $connId = $this->document_data['conn_id_target'];
            } else {
                return false;
            }

            // Get the name of the application
            $sql = 'SELECT solution.name  
		    		FROM connector
						INNER JOIN solution 
							ON solution.id  = connector.sol_id
		    		WHERE connector.id = :connId';
            $stmt = $this->connection->prepare($sql);
            $stmt->bindValue(':connId', $connId);
            $result = $stmt->executeQuery();
            $r = $result->fetchAssociative();
            // Get params connection
            $sql = 'SELECT id, conn_id, name, value
		    		FROM connectorparam 
		    		WHERE conn_id = :connId';
            $stmt = $this->connection->prepare($sql);
            $stmt->bindValue(':connId', $connId);
            $result = $stmt->executeQuery();
            $tab_params = $result->fetchAllAssociative();
            $params = [];
            if (!empty($tab_params)) {
                foreach ($tab_params as $key => $value) {
                    $params[$value['name']] = $value['value'];
                    $params['ids'][$value['name']] = ['id' => $value['id'], 'conn_id' => $value['conn_id']];
                }
            }

            // Connect to the application
            if ('source' == $type) {
                $this->solutionSource = $this->solutionManager->get($r['name']);
                $this->solutionSource->setApi($this->api);
                $loginResult = $this->solutionSource->login($params);
                $c = (($this->solutionSource->connexion_valide) ? true : false);
            } else {
                $this->solutionTarget = $this->solutionManager->get($r['name']);
                $this->solutionTarget->setApi($this->api);
                $loginResult = $this->solutionTarget->login($params);
                $c = (($this->solutionTarget->connexion_valide) ? true : false);
            }
            if (!empty($loginResult['error'])) {
                return $loginResult;
            }

            return $c;
        } catch (\Exception $e) {
            $this->logger->error('Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');

            return false;
        }
    }
}// end define class
