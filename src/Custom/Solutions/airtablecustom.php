<?php

namespace App\Custom\Solutions;

use App\Solutions\airtable;
use Myddleware\RegleBundle\Classes\rule;
use App\Manager\DocumentManager;

class airtablecustom extends airtable {

	protected array $tableName = array(
								'appdKFUpk2X2Ok8Dc' => 'Contacts',
								'appdKFUpk2X2Ok8Dc' => 'BILAN',         // Aiko PROD Bilan
								'appX0PhUGIkBTcWBE' => 'Aiko Auto Supr',
								'apppq0nb5WI815V57' => 'CONTACTS',		// Aiko PREPROD Reponse
								'apppq0nb5WI815V57' => 'BILAN',			// Aiko PREPROD Bilan
								'app5ustIjI5taRXJS' => 'CONTACTS',		// Mobilisation PROD
								'appP31F11PgaT1f6H' => 'CONTACTS',		// Mobilisation PREPROD
								'appALljzTMc2wjLV1' => 'VSC',			// USC PROD
								'appuC7nsCbe7TxqwK' => 'VSC',			// USC PREPROD
								'appgUF55WW7ApOTPQ' => 'Contacts COMET',		// discord PROD
								'apprA5JofsBBO1Kvj' => 'COUPON',		//  1j1m PREPROD
								'applAYRbr1Af2uJSC' => 'COUPON',		//  1j1m PROD
							);

	protected $modules = array(
								'apppq0nb5WI815V57' => array(
														'CONTACTS' =>	'CONTACTS',
														'BINOMES' =>	'BINOMES',
														'POLE' => 		'POLES',
														'REFERENTS' => 	'REFERENTS',
														'REPONSE' => 	'REPONSE',
														'BILAN' => 	'BILAN',
													),
								'appdKFUpk2X2Ok8Dc' => array(
														'CONTACTS' =>	'CONTACTS',
														'BINOMES' =>	'BINOMES',
														'POLE' => 		'POLES',
														'REFERENTS' => 	'REFERENTS',
														'REPONSE' => 	'REPONSE',
														'BILAN' => 	'BILAN',
													),
								'appX0PhUGIkBTcWBE' =>  array(
														'Aiko Auto Supr' => 'Aiko Auto Supr'
													),
								'app5ustIjI5taRXJS' => array(
														'COUPONS' => 'COUPONS',
														'CONTACTS' => 'CONTACTS',
														'Relation_POLE' => 'Relation POLE',
														'COMPOSANTES' => 'COMPOSANTES',
														'ETABLISSEMENTS' => 'ETABLISSEMENTS',
														'EVENEMENTS' => 'EVENEMENTS',
														'POLES' => 'POLES',
														'UTILISATEURS' => 'UTILISATEURS',
														'PARTICIPATION_RI' => 'PARTICIPATION RI',
														'RECONDUCTION' => 'RECONDUCTION',
														'RENDEZ-VOUS' => 'RENDEZ-VOUS',
														'DOSSIER RECRUTEMENT' => 'DOSSIER RECRUTEMENT'
													),
								'appP31F11PgaT1f6H' => array(
														'COUPONS' => 'COUPONS',
														'CONTACTS' => 'CONTACTS',
														'Relation_POLE' => 'Relation POLE',
														'COMPOSANTES' => 'COMPOSANTES',
														'ETABLISSEMENTS' => 'ETABLISSEMENTS',
														'EVENEMENTS' => 'EVENEMENTS',
														'POLES' => 'POLES',
														'UTILISATEURS' => 'UTILISATEURS',
														'PARTICIPATION_RI' => 'PARTICIPATION RI',
														'RECONDUCTION' => 'RECONDUCTION',
														'RENDEZ-VOUS' => 'RENDEZ-VOUS',
														'DOSSIER RECRUTEMENT' => 'DOSSIER RECRUTEMENT'
													),
								'appALljzTMc2wjLV1' => array(
														'VSC' => 'VSC'
													),
								'appuC7nsCbe7TxqwK' => array(
														'VSC' => 'VSC'
													),
								'appgUF55WW7ApOTPQ' => array(
														'Contacts COMET' => 'Contacts COMET',
													),
													// 1j1m PREPROD
								'apprA5JofsBBO1Kvj' => array(
														'COUPON' => 'COUPON',
														'CONTACT' => 'CONTACT',
														'BINOME' => 'BINOME',
													),
													// 1j1m PROD
								'applAYRbr1Af2uJSC' => array(
														'COUPON' => 'COUPON',
														'CONTACT' => 'CONTACT',
														'BINOME' => 'BINOME',
													),
							);

    protected $FieldsDuplicate = array(
        'CONTACTS' => array('fldXhleTPZRv0zBbd','fldpSrPxUtj0apDA6'),
        'BINOMES' => array('fldpdbxLe9B1H2i2J'),
        'POLE' => array('fldxWO5Cs8t9z7ZP8'),
        'REFERENTS' => array('fldLt1pZEcUxKlTpH'),
        'COMPOSANTES' => array('fld0FmpZqG5wJFrCP'),
		'PARTICIPATION_RI' => array('fldL4qph2Lg65xKjz', 'fldtIpKCdlbykhkm5'),
		'Relation_POLE' => array('fldNHqlGf5PJhYCMN', 'fldWsjwPo27DVlYMy', 'fldaLPQ8EbyOpk61X'),
		'VSC' => array('fldTpnnN8XfbLHADM')
        );

	protected $fieldsDuplicatePreprod = array(
        'CONTACTS' => array('fld9XGHFATCBdkoD8','fld9r01QCAuHTzVXV'),
        'BINOMES' => array('fldBTw0xZ3m7UN5uE'),
        'POLE' => array('fldJC9yod2efMSMh3'),
        'REFERENTS' => array('fldX9mSLp6FDX6GRC'),
        'COMPOSANTES' => array('fldKeVBi8NgdsPJZE'),
		'PARTICIPATION_RI' => array('fldvDZBAKSrNOH2Go', 'flddhYWVVsmf3rCJU'),
		'Relation_POLE' => array('fldxgZxZXc0q08U9C', 'fldG1SI869ikEvg9n','fldUko2rmiJv8uooM'),
		'VSC' => array('fldAeyp1OXzWdrn9i')
        );
	
	// Redefine read function
	public function readData($param): array {
		$result = parent::readData($param);

		// if the rule id is 6491a6a34b732, we handle the conversion of the emoji to a format that will be fully compatible with the database encoding which is utf8_general_ci
		//Todo: We will have to change the id on myddleware prod !!!

		$aikoEmoji = false;
		$simulationParams = false;
		$readParams = false;
		$moduleReponse = isset($param['module']) && !empty($param['module']) && $param['module'] == 'REPONSE';

		if (isset($_POST["params"])) {
			if (!empty($_POST["params"])) {
				if (!empty($_POST["params"][1]["value"])) {
					if ($_POST["params"][1]["name"] === "bidirectional") {
						$ruleId = $_POST["params"][2]["value"];
					} else {
						$ruleId = $_POST["params"][1]["value"];
					}
					$simulationParams = true;
				}
			}
		}

		if (isset($param["rule"])) {
			if (!empty($param["rule"])) {
				if (!empty($param["rule"]["id"])) {
					$ruleId = $param["rule"]["id"];
					$readParams = true;
				}
			}
		}

		// if (simulation params or read params) and module reponse then aiko emoji is true
		if (($simulationParams || $readParams) && $moduleReponse && $ruleId === '6491a6a34b732') {
			$aikoEmoji = true;
		}

		if ($aikoEmoji) {
			if (!empty($result['values'])) {
				foreach ($result['values'] as $docId => $values) {
					$notationField = 'fldqr1DNrnnwmlXuV'; // PROD
					if (isset($values['fldC7m6zch8Cz6KWQ'])) {
						$notationField = 'fldC7m6zch8Cz6KWQ'; // PREPROD
					}

					if (!empty($values[$notationField])) {
						switch ($values[$notationField]) {
							case '😡':
								$result['values'][$docId][$notationField] = 1;
								break;
							case '🙁':
								$result['values'][$docId][$notationField] = 2;
								break;
							case '😐':
								$result['values'][$docId][$notationField] = 3;
								break;
							case '🙂':
								$result['values'][$docId][$notationField] = 4;
								break;
							case '😍':
								$result['values'][$docId][$notationField] = 5;
								break;
							default:
								$result['values'][$docId][$notationField] = '';
						}
					}

					$ratingField = 'fldS4eJtB1hJVUgcb'; // PROD
					if (isset($values['fld4KzcfmV2P8F3E6'])) {
						$ratingField = 'fld4KzcfmV2P8F3E6'; // PREPROD
					}
					if (!empty($values[$ratingField])) {
						switch ($values[$ratingField]) {
							case '⭐️':
								$result['values'][$docId][$ratingField] = 1;
								break;
							case '⭐️⭐️':
								$result['values'][$docId][$ratingField] = 2;
								break;
							case '⭐️⭐️⭐️':
								$result['values'][$docId][$ratingField] = 3;
								break;
							case '⭐️⭐️⭐️⭐️':
								$result['values'][$docId][$ratingField] = 4;
								break;
							case '⭐️⭐️⭐️⭐️⭐️':
								$result['values'][$docId][$ratingField] = 5;
								break;
							default:
								$result['values'][$docId][$ratingField] = '';
						}
					}
				}
			}
		}



		// If we send an update to Airtable but if the data doesn't exist anymore into Airtable, we change the upadet to a creation
		if (isset($param["rule"]) && isset($param["document"]) && isset($param["call_type"])) {
			if (
				!empty($param['rule'])
				and	in_array($param['rule']['conn_id_target'], array(4, 8, 12))
				and $param['document']['type'] == 'U'
				and $param['call_type'] == 'history'
				// Excluded rules
				and !in_array($param['rule']['id'], array('6493f82a6102a'))  //	Aiko - Suivi Mentorat vers Aiko
				and !empty($result['error'])
				and (strpos($result['error'], '404 Not Found')
					or strpos($result['error'], '404  returned')	// Airtable has changed the error message
				)
			) {
				$this->setDocumentManager();
				$paramDoc['id_doc_myddleware'] = $param['document']['id'];
				$paramDoc['jobId'] = $param['jobId'];
				$documentManager->setParam($paramDoc);
				// Add a log
				$documentManager->generateDocLog('W', 'La donnee a ete supprimee dans Airtable. Le type de document passe donc de Update a Create. ');
				$documentManager->updateType('C');
				// Clear the error
				$result['error'] = '';
			}
		}
		return $result;
	}
	
	// Check data before create
    protected function checkDataBeforeCreate($param, $data, $idDoc)
    {
		$data = parent::checkDataBeforeCreate($param, $data, $idDoc);
		// If the etab sup is missing then we remove the field from the call
		if ($param['rule']['id'] == '6267e9c106873') { // Mobilisation - Composantes
			if (
					array_key_exists('fldBQBCfr1ZgVJmE3', $data)
				AND	empty($data['fldBQBCfr1ZgVJmE3'])
			) {	// Etbalissement sup
				unset($data['fldBQBCfr1ZgVJmE3']);
			}
		}
		
		if ($param['rule']['id'] == '61a930273441b') { // Aiko binome
			if (
					array_key_exists('fldqGYsTr5EylIi2f', $data)
				AND	empty($data['fldqGYsTr5EylIi2f'])
			) {	// if referent empty we remove it from the data sent
				unset($data['fldqGYsTr5EylIi2f']);
			}
		}
		
		if ($param['rule']['id'] == '625fcd2ed442f') { 	// Mobilisation - Coupons
			// Execute this block if the status has to be filtered,
			if (
				// PREPROD
				(
						isset($data['fldohGMXZZOWhxN2o'])
					AND	 in_array($data['fldohGMXZZOWhxN2o'], ['refus_non_eligible', 'inscription_attente', 'contrat_attente_validation'])
				)
				// PROD
				OR (
						isset($data['fldEI7AEhSDfynvFz'])
					AND	 in_array($data['fldEI7AEhSDfynvFz'], ['refus_non_eligible', 'inscription_attente', 'contrat_attente_validation'])
				)
			) {
				if (!isset($this->documentManager)) {
					$chlidEntityManager = clone $this->entityManager;
					$chlidEntityManager->clear();
					$this->documentManager = new DocumentManager(
						$this->logger, 
						$this->connection, 
						$chlidEntityManager,
						$this->formulaManager
					);
				}
				// Update the document status to 'Filter'
				$paramDoc['id_doc_myddleware'] =  $idDoc;
				$paramDoc['jobId'] = $param['jobId'];
				$this->documentManager->setParam($paramDoc);
				$value['id'] = $data['target_id'];
				$value['error'] = 'Le document est filtr� car le statut du coupon est '.(isset($data['fldohGMXZZOWhxN2o']) ? $data['fldohGMXZZOWhxN2o'] : $data['fldEI7AEhSDfynvFz']).'. ';		
				$this->updateDocumentStatus($idDoc, $value, $param, 'Filter');
				return null;
			}

			if (
					array_key_exists('fldY9MAvfDHSHtJKT', $data)
				AND	empty($data['fldY9MAvfDHSHtJKT'])
			) {	// if referent empty we remove it from the data sent
				unset($data['fldY9MAvfDHSHtJKT']);
			}
		}

		
		if ($param['rule']['id'] == '6493f82a6102a') { // 	Aiko - Suivi Mentorat vers Aiko
			throw new \Exception('No possible to create a suivi in Airtable. ');
		}

        return $data;
    }

	   // Check data before update
	protected function checkDataBeforeUpdate($param, $data, $idDoc)
	{
		$data = parent::checkDataBeforeUpdate($param, $data, $idDoc);
		
		if ($idDoc == 'error') {
			throw new \Exception('Error unknown. ');
		}
		// If the etab sup is missing then we remove the field from the call
		if ($param['rule']['id'] == '6267e9c106873') { // Mobilisation - Composantes
			if (
					array_key_exists('fldBQBCfr1ZgVJmE3', $data)
				AND	empty($data['fldBQBCfr1ZgVJmE3'])
			) {	// Etbalissement sup
				unset($data['fldBQBCfr1ZgVJmE3']);
			}
		}
	
		if ($param['rule']['id'] == '61a930273441b') { // Aiko binome
			if (
					array_key_exists('fldqGYsTr5EylIi2f', $data)
				AND	empty($data['fldqGYsTr5EylIi2f'])
			) {	// if referent empty we remove it from the data sent
				unset($data['fldqGYsTr5EylIi2f']);
			}
		}
	
		if ($param['rule']['id'] == '625fcd2ed442f') { // Mobilisation - Coupons
			if (!isset($this->documentManager)) {
				$chlidEntityManager = clone $this->entityManager;
				$chlidEntityManager->clear();
				$this->documentManager = new DocumentManager(
					$this->logger, 
					$this->connection, 
					$chlidEntityManager,
					$this->formulaManager
				);
			}
			 // Update the email if the email is modified even if teh status should be filtered
			if (
				// PREPROD
				(
						isset($data['fldUfChKmCxvSBEqb'])
					AND	$data['fldUfChKmCxvSBEqb'] != $param['dataHistory'][$idDoc]['fldUfChKmCxvSBEqb']
				)
				// PROD
				OR (
						isset($data['fldaG35rEvmO9rm3m'])
					AND	$data['fldaG35rEvmO9rm3m'] != $param['dataHistory'][$idDoc]['fldaG35rEvmO9rm3m']
				)
			) {
				// Update the data array with only the target ID and the new email
				// PREPROD 
				if (isset($data['fldUfChKmCxvSBEqb'])) {
					$data = array(
						'target_id' => $data['target_id'],
						'fldUfChKmCxvSBEqb' => $data['fldUfChKmCxvSBEqb'],
					);
				// PROD
				} else {
					$data = array(
						'target_id' => $data['target_id'],
						'fldaG35rEvmO9rm3m' => $data['fldaG35rEvmO9rm3m'],
					);
				}
				$paramDoc['id_doc_myddleware'] = $idDoc;
				$paramDoc['jobId'] = $param['jobId'];
				$this->documentManager->setParam($paramDoc);
				$this->documentManager->updateDocumentData($idDoc, $data, 'T', true);
			} 
			// Execute this block if the status has to be filtered, but the email has not been modified
			else if (
				// PREPROD
				(
						isset($data['fldohGMXZZOWhxN2o'])
					AND	in_array($data['fldohGMXZZOWhxN2o'], ['refus_non_eligible', 'inscription_attente', 'contrat_attente_validation'])
				)
				// PROD
				OR (
						isset($data['fldEI7AEhSDfynvFz'])
					AND	in_array($data['fldEI7AEhSDfynvFz'], ['refus_non_eligible', 'inscription_attente', 'contrat_attente_validation']) 
				)
			) {
				// Update the document status to 'Filter'
				$paramDoc['id_doc_myddleware'] =  $idDoc;
				$paramDoc['jobId'] = $param['jobId'];
				$this->documentManager->setParam($paramDoc);
				$value['id'] = $data['target_id'];
				$value['error'] = 'Le document est filtr� car le statut du coupon est '.(isset($data['fldohGMXZZOWhxN2o']) ? $data['fldohGMXZZOWhxN2o'] : $data['fldEI7AEhSDfynvFz']).'. ';		
				$this->updateDocumentStatus($idDoc, $value, $param, 'Filter');
				return null; 
			}
		}	
		return $data;
	}
	
	    
	function getFieldsDuplicate($module)
    {
		if ($_ENV['AFEV_ENV'] == 'PREPROD') {
			if (isset($this->fieldsDuplicatePreprod[$module])) {
				return $this->fieldsDuplicatePreprod[$module];
			} elseif (isset($this->fieldsDuplicatePreprod['default'])) {
				return $this->fieldsDuplicatePreprod['default'];
			}
		}
        return parent::getFieldsDuplicate($module);
    }

	// Redefine updateDocumentStatus standard function
	protected function updateDocumentStatus($idDoc, $value, $param, $forceStatus = null): array {
		// Make an integromat call if call OK to Mobilisation - Contacts webservice
		if (
				!empty($param['ruleId'])
			AND	in_array($param['ruleId'], array('64f5e0543cb6c')) // Mobilisation - Relations p�les Contact
			AND $value['id'] != '-1'
		) {
			try {
				$targetId = (!empty($param['data'][$idDoc]['fldWsjwPo27DVlYMy']) ? $param['data'][$idDoc]['fldWsjwPo27DVlYMy'] : $param['data'][$idDoc]['fldG1SI869ikEvg9n']);
				if (empty($targetId)) {
					throw new \Exception('No target id found in the parent document (rule Mobilisation - Contacts webservice ');
				}
					
				// Get the COMET contact ID
				$sqlParams = "SELECT * FROM document where rule_id = '6303832f0a0b7' and target_id = :targetId";
				$stmt = $this->getConn()->prepare($sqlParams);
				// Check PROD field then PREPROD field
				$stmt->bindValue(':targetId', $targetId);
				$stmt->execute();
				$result = $stmt->executeQuery();
                $document = $result->fetchAssociative();
				if (empty($document['source_id'])) {
					throw new \Exception('No source id found on the document. ');
				}

				// Make call
				$return['contactId'] = $document['source_id'];
				$json = json_encode($return);
				$url = 'https://hook.eu1.make.com/lwh71b78maxb9o4mjswavrzs2fye5pxk'; // nouvelle URL
				$curl = curl_init($url);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
				curl_setopt($curl, CURLOPT_TIMEOUT, 300);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
				$response = curl_exec($curl);
				curl_close($curl);
				// Erro if call not accpeted
				if (
						empty($response)
					 OR strpos($response, 'Accepted') === false
				) {
					$value['error'] = (empty($value['error']) ? 'No response from make. '.$return['contactId'] : $value['error'].'No response from make. ' );
					$value['id'] = '-1';
					$forceStatus = 'Error_sending';
				}
			} catch (\Exception $e) {
				$value['error'] = 'Failed to call make : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
				$value['id'] = '-1';
				$forceStatus = 'Error_sending';
			}
		}
		return parent::updateDocumentStatus($idDoc, $value, $param, $forceStatus);
	}
}
