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
	// protected $doNotOverrideStatus = false;
	
	/* // No history for Aiko rules to not surcharge the API
	protected function getDocumentHistory($searchFields) {
		if (
				strpos($this->ruleName, 'aiko') !== false
			AND !empty($searchFields['id'])					// Only history, we keep search duplicate
		) {		
			return false;			
		}		
		return parent::getDocumentHistory($searchFields);
	} */
	
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

	protected function beforeStatusChange($new_status) {	
		// On annule la relation pôle - contact (user) si le contact (user) a été filtré
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '5cfa78d49c536' // Rule User - Pôle
		) {
			// On tente 3 fois l'envoi (attempt > 1) car la relation pourrait être lues avant le user et être annulé par erreur
			if (
					strpos($this->message, 'No data for the field user_id.') !== false
				and strpos($this->message, 'in the rule REEC - Users.') !== false
			) {
				if($this->attempt > 1) {
					$new_status = 'Error_expected';
					$this->message .= utf8_decode('Le contact (user) lié à ce pôle est absent de la platforme REEC, probablement filtré car inactif. Le lien contact - pôle ne sera donc pas créé dans REEC. Ce transfert de données est annulé. ');
				// Keep the document open until the second try
				} else {
					$new_status = 'Predecessor_OK';
					$this->message .= utf8_decode('Le contact (user) lié à ce pôle est absent de la platforme REEC, probablement filtré car inactif. En attente d un second essai. ');
					// Add a try as the standard increment attemp only for error or close document
					$this->attempt++;
				}
			}
		}

		// On annule la relation pôle - contact (engagé) si le contact (engagé) a été filtré
		if (
				!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '5d081bd3e1234' // Rule User - Pôle
		) {
			if (
				strpos($this->message, 'No data for the field record_id.') !== false
				and strpos($this->message, 'in the rule REEC - Engagé.') !== false
			) {
				if ($this->attempt > 1) {
					$new_status = 'Error_expected';
					$this->message .= utf8_decode('Le contact lié à ce pôle est absent de la platforme REEC ou n\'est pas un contact de type engagé. Le lien contact - pôle ne sera donc pas créé dans REEC. Ce transfert de données est annulé. ');
				// Keep the document open until the second try
				} else {
					$new_status = 'Predecessor_OK';
					$this->message .= utf8_decode('Le contact lié à ce pôle est absent de la platforme REEC ou n\'est pas un contact de type engagé. En attente d un second essai. ');
					// Add a try as the standard increment attemp only for error or close document
					$this->attempt++;
				}
			}
		}
		
		// On annule la relation pôle - contact (engagé) si le contact (jeune accompagné) a été filtré ou n'est pas un jeune accompagné
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '63a325156cd50' // REEC - Composante - Jeune accompag
		) {
			if (
				strpos($this->message, 'No data for the field contact_id.') !== false
				and strpos($this->message, 'in the rule REEC - Jeune accompagn') !== false
			) {
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact lié à ce pôle est absent de la platforme REEC ou n\'est pas un contact de type jeune accompagne. Le lien contact - composante ne sera donc pas créé dans REEC. Ce transfert de données est annulé. ');
			}
		}

		// On annule la relation pôle - contact (université) si le contact (université) a été filtré
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '5d163d3c1d837' // Rule Contact partenaire - Pôle
		) {
			if (
				strpos($this->message, 'No data for the field record_id.') !== false
				and strpos($this->message, 'in the rule REEC - Contact partenaire.') !== false
			) {
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact lié à ce pôle est absent de la platforme REEC ou n\'est pas un contact de type contact université. Le lien contact - pôle ne sera donc pas créé dans REEC. Ce transfert de données est annulé. ');
			}
		}

		// We cancel the relation pôle - contact partenaire if he has been filtered
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '62743060350ed' // Esp Rep - Contact repérant - Pôle
		) {
			if (
				strpos($this->message, 'No data for the field record_id.') !== false
				and strpos($this->message, 'Esp Rep - Contact rep') !== false
			) {
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact lié à ce pôle est absent de la platforme l\'epace repérant ou n\'est pas un contact de type contact partenaire. Le lien contact - pôle ne sera donc pas créé dans REEC. Ce transfert de données est annulé. ');
			}
		}

		// We cancel the relation Contact repérant - Pôle if he has been filtered
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '62743060350ed' // Esp Rep - Contact repérant - Pôle
		) {
			if (
				strpos($this->message, 'No data for the field record_id.') !== false
				and strpos($this->message, 'in the rule Esp Rep - Contacts rep') !== false
			) {
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact lié à ce pôle est absent de la platforme epace repérant ou n\'est pas un contact de type contact repérant. Le lien contact - pôle ne sera donc pas créé dans l\'espace repérant. Ce transfert de données est annulé. ');
			}
		}

		// If we don't found the contact (COMET) in the coupon (REEC), we cancel the data transfer. 
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '6273b3b11c63e' // Esp Rep - Relation Contacts Coupons
			and $new_status == 'Not_found'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Le mentoré n\a pas été trouvé sur un coupon dans l\'epace repérant. Ce transfert de données est annulé. ');
		}

		// If we don't found the coupon (REEC) corresponding to the contact (COMET), we cancel the data transfer. 
		if (
			!empty($this->document_data['rule_id'])
			and	in_array($this->document_data['rule_id'], array('6274428910b18', '62744b95de96f')) // Esp Rep - Fiche évaluation fin vers Esp Rep
			and $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Le mentoré n\a pas été trouvé sur un coupon dans l\'epace repérant. Ce transfert de données est annulé. ');
		}
		
		// We cancel the document from COMET to aiko if it does not comes from aiko
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '6493f82a6102a' // 	Aiko - Suivi Mentorat vers Aiko
			and $new_status == 'Error_checking'
		) {
			if (
				strpos($this->message, 'The document is a creation but the rule mode is UPDATE ONLY.') !== false
			) {
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le suivi mentorat provient de la COMET mais n\'est pas supposé aller vers Aiko. ');
			}
		}
		/* if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '5cffd54c8842b' // Rule Formation - Engagé
		) {
			if (	
				(	
						strpos($this->message, 'No data for the field fp_events_contactscontacts_idb.') !== false
					AND strpos($this->message, 'in the rule Engagé.') !== false	
				)
				OR (
						strpos($this->message, 'No data for the field fp_events_contactsfp_events_ida.') !== false
					AND strpos($this->message, 'in the 	Formation session.') !== false	
				)
			) {
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact de cette formation est absent de la platforme REEC. Le lien Formation - Contact ne sera donc pas créé dans REEC. Ce transfert de données est annulé. '); 
			}
		}
		
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '5d08e425e49ea' // Rule Formation - pôle
		) {
			if (
					strpos($this->message, 'No data for the field record_id.') !== false
				AND strpos($this->message, 'in the rule Formation.') !== false	
			) {	
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('La formation est absente de la platforme REEC, il s\'agit probablement d\'une formation filtrée car de type réunion. Le lien Formation - pôle ne sera donc pas créé dans REEC. Ce transfert de données est annulé. '); 
			}
		} */

		// If we don't found the coupon (REEC) corresponding to the contact (COMET), we cancel the data transfer. 
		if (
			!empty($this->document_data['rule_id'])
			and	in_array($this->document_data['rule_id'], array('628cdd961b093')) // Esp Rep - Coupon - Pôles
			and $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Le coupon de la relation pole - coupon n\'a pas été trouvé. Il s\'agit probablement d\'un coupon non mentoré. Ce transfert de données est annulé. ');
		}

		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '5d163d3c1d837' // Rule Contact partenaire - Pôle
		) {
			if (
				strpos($this->message, 'No data for the field record_id.') !== false
				and strpos($this->message, 'in the rule REEC - Contact partenaire.') !== false
			) {
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le contact partenaire est absent de la platforme REEC, il s\'agit probablement d\'une composante sans adresse email. Le lien Contact composante - pôle ne sera donc pas créé dans REEC. Ce transfert de données est annulé. ');
			}
		}

		// On annule tous les transferts de données en relate ko pour la règle composante - Contact partenaire
		// En effet des la majorité des relations accounts_contacts ne sont pas des composante - Contact partenaire
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '5f20b113356e1' // Rule Composante - Contact partenaire
			and $new_status == 'Error_transformed'
			and strpos($this->message, 'lookup') !== false
		) {
			// Relationship could be created after the contact, to we don't cancel teh document at the first atempt
			if($this->attempt > 1 ) {
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('La relation ne concerne probablement pas une composante composante et un contact partenaire. Ce transfert de données est annulé. ');
			// Keep the document open until the second try
			} else {
				$new_status = 'Relate_OK';
				$this->message .= utf8_decode('La relation ne concerne probablement pas une composante composante et un contact partenaire. En attente d un second essai. ');
				// Add a try as the standard increment attemp only for error or close document
				$this->attempt++;
			}
		}

		// On annule tous les transferts de données en relate ko pour la règle composante - Contact partenaire
		// En effet des la majorité des relations accounts_contacts ne sont pas des composante - Contact partenaire
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '62790c7db0a87' // Esp Rep - Composante - Contact partenaire
			and $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('La relation ne concerne probablement pas une composante et un contact partenaire. Ce transfert de données est annulé. ');
		}


		// On annule tous les transferts de données en relate ko pour la règle composante - Engagé
		// En effet une partie des relations accounts_contacts ne sont pas des composante - Engagé
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '5f8486295b5a7' // Rule composante - Engagé
			and $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('La relation ne concerne probablement pas une composante et un contact partenaire. Ce transfert de données est annulé. ');
		}

		// Si on est sur une suppression d'une composante, le document est souvent filtré car la composante supprimé n'a plus d'établissment supérieur lié
		// La suppression est alors annulée. On souhaite supprimer quand même la données si elle a été envoyée par Myddleware
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '5ce362b962b63' // Rule composante
			and	$this->document_data['type'] == 'D' // Delete
			and $new_status == 'Filter'
		) {
			$new_status = 'Filter_OK';
			$this->message .= utf8_decode('Aucun filtrage appliqué sur la suppression d une composante. Cette composante doit réellement être supprimée dans REEC même si elle n a plus d établissement supérieur dans la COMET. ');
		}

		// No error if the coupon doesn't exist in REEC (no update in this case)
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '62739b419755f' // Esp Rep - Coupons vers Esp Rep
			and $new_status == 'Relate_KO'
		) {
			if (
				strpos($this->message, 'No data for the field Myddleware_element_id.') !== false
				and strpos($this->message, ' in the rule REEC - Coupons vers comet.') !== false
			) {
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le coupon n\existe pas dans de la platforme epace repérant, la mise à jour est donc interrompue. ');
			}
		}
		
		// We cancel the relation Coupon - Pôle if he has been filtered
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '626931ebbff78' // Mobilisation - Relations pôles Coupons
		) {			
			if (
					strpos($this->message, 'No data for the field record_id.') !== false
				AND strpos($this->message, ' in the rule Mobilisation - Coupons') !== false	
			) {				
				$new_status = 'Error_expected';
				$this->message .= utf8_decode('Le coupon lié à ce pôle est absent de Airtable. Il s\'agit probablement d\'un coupon d\'un type filtré. Le lien coupon - pôle ne sera donc pas créé dans Airtable. Ce transfert de données est annulé. '); 
			}
		}
		
		// No update from REEC to COMET if the contact hasn't been create from COMET
		// Relate KO happens when a contact is created manually into REEC of if the contact is only a user in COMET not a contact
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '60b0b881235e8' // REEC - Engagé vers COMET
			and $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Le contact REEC n\existe pas dans la COMET, la mise à jour est donc interrompue. ');
		}

		/************************************************/
		/************         AIKO         **************/
		/************************************************/
		// If relate_ko and binôme status is annule then we cancel the data transfer
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '61a930273441b' // Rule Aiko binome
			and $new_status == 'Relate_KO'
			and $this->sourceData['statut_c'] == 'annule'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Le statut du binôme est annulé. Ce transfert de données est annulé. ');
		}

		// If relate_OK and binôme status is one of these status : termine;annule;accompagnement_termine
		// And if the document type is a creation then we cancel the data transfer
		// However if it is an update we keep the document to set the new status in Airtable (and generate a deletion during the next call)
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '61a930273441b' // Rule Aiko binome
			and $new_status == 'Predecessor_OK'
			and in_array($this->sourceData['statut_c'], array('termine', 'annule', 'accompagnement_termine'))
			and	$this->documentType == 'C' // Creation
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Le statut du binôme est annulé ou terminé et le document genère une création donc on annule l envoi vers Airtable. ');
		}

		// If relate_ko on rule Aiko binome - pole then we cancel the data transfer
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '61a93469599ae' // Rule Aiko binome - pole
			and $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Les anciens binômes et les binômes annulés ne sont pas envoyés dans Airtable, la relation pôle tombe logiquement en relate_KO. Ce transfert de données est annulé. ');
		}

		// If relate_ko on rule Aiko contact - pole then we cancel the data transfer
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '61a9329e6d6f2' // Rule Aiko contact - pole
			and $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Les contacts partenaires ne sont pas envoyés dans Airtable, la relation pôle tombe logiquement en relate_KO. Ce transfert de données est annulé. ');
		}

		// No suivi update if the suivi deosn't exist anymore
		if (
				!empty($this->document_data)
			AND	$this->document_data['rule_id'] == '6493f82a6102a'	// Aiko - Suivi Mentorat vers Aiko
			AND $new_status == 'Error_checking'
			AND strpos($this->message, '404  returned') !== false
		) {		
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Le suivi a été supprimé dans Aiko. Ce transfert de données est annulé. ');
		}
		
		// Cancel if the doc is related KO and the email linked to a user (afev.org)
		if (
				!empty($this->document_data['rule_id'])
			AND	$this->document_data['rule_id'] == '6210fcbe4d654' // Sendinblue - email delivered
			AND $new_status == 'Relate_KO'
		) {
			if (strpos($this->sourceData['email'], '@afev.org') !== false) {
				$this->message .= utf8_decode('L\email n\'appartient pas à un contact dans la COMET mais à un salarié (domaine afev.org). Ce transfert de données est annulé. ');
				$new_status = 'Error_expected';
			} elseif (strpos($this->sourceData['subject'], 'Contact SuiteCRM -') !== false) {
				$this->message .= utf8_decode('L\email est une notification pour un salarié. Ce transfert de données est annulé. ');
				$new_status = 'Error_expected';
			}
		}

		// Cancel if the doc is related KO and the email linked to a user (afev.org)
		if (
				!empty($this->toBeCancel[$this->id])
			AND	$this->document_data['rule_id'] == '6210fcbe4d654' // Sendinblue - email delivered
			AND $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('L\email n\'a pas été trouvé dans les contacts et les coupons de la COMET. Ce transfert de données est annulé. ');
		}
				
		// In case a data has already been deleted in Airtable, Myddleware won't be able to process the check, so we cancel the deletion
		if (
				!empty($this->document_data)
			AND	in_array($this->document_data['conn_id_target'], array(4,8)) // Airtable connectors
			AND $new_status == 'Error_checking'
			AND	$this->documentType == 'D' // Deletion
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('L\'enregistrement est certainement déjà supprimé dans Airtable. Ce transfert de données est annulé. ');
		}
		
		// Do not create a contact into USC
		if (
				!empty($this->document_data)
			AND	$this->document_data['rule_id'] == '643eb15eb70ea'	// Mobilisation - Contact vers USC
			AND $new_status == 'Error_checking'
			AND	$this->documentType == 'C' // Creation
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('La COMET ne peut pas créer de contact dans USC. Ce transfert de données est annulé. ');
		}
		
		// If relate_ko on rule Aiko contact - pole then we cancel the data transfer
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '65b11d4176f7d' // Aiko - Bilan vers Airtable
			and $new_status == 'Relate_KO'
		) {
			$new_status = 'Error_expected';
			$this->message .= utf8_decode('Le bilan n\'a pas été créé dans le COMET par Airtable. Impossible de le mettre à jour dans Airtable. Ce transfert de données est annulé. ');
		}
		return $new_status;
	}

	public function updateStatus($new_status, $workflow = false)
	{
		/* // If the status has been forced during a standard process, we stop the next status change (done by the standard)
		if ($this->doNotOverrideStatus) {
			$this->doNotOverrideStatus = false;
			return null;
		} */
		
		// Add error expected status
		$this->globalStatus['Error_expected'] = 'Cancel';

		// Cancel data transfert as the rule Aiko - Suppression generates document into other rules
		if (
			!empty($this->document_data['rule_id'])
			and	$this->document_data['rule_id'] == '61bb49a310715' // Aiko - Suppression
			and	$new_status == 'Predecessor_OK'
		) {
			$new_status = 'Cancel';
		}

		// Generate a document for the rule REEC - Users custom each time a document is generated for a user
		if (
				!empty($this->document_data['rule_id'])
			and !empty($this->document_data['source_id'])
			and	$this->document_data['rule_id'] == '5cf98651a17f3' // REEC - Users
			and	$new_status == 'Ready_to_send'
		) {
			$this->generateDocument('63e1007614977', $this->document_data['source_id']);	// REEC - Users custom
		}
		
		// Generate Pole and account relationship for creation only but before the duplicate search. 
		// The contact could already exist thanks to the REEC user rule. So we need to check if this is a creation document before the duplicate search.
		if (
				!empty($this->document_data['rule_id'])
			AND $this->document_data['rule_id'] == '5ce3621156127' //Engagés
			AND $new_status == 'Transformed'
			AND $this->documentType == 'C'
		) {
			// Si un engagé est envoyé dans REEC, on recherche également son pôle
			// En effet quand un engagé est reconduit, on n'enverra pas son pôle qui est une données qui a été créée dans le passé 
			$this->generateDocument('5d081bd3e1234', $this->document_data['source_id'], 'record_id', false); // Engagé - pole
			// Si un engagé est envoyé dans REEC, on recherche également sa composante
			// En effet quand un engagé est envoyé dans REEC, il a peut être filtré avant et la relation avec la composante est donc filtrée aussi
			// On force donc la relance de la relation composante - Engagé à chaque fois qu'un engagé est modifié	
			$this->generateDocument('5f8486295b5a7', $this->document_data['source_id'], 'contact_id', false); // Composante - Engagé
		}

		$updateStatus = parent::updateStatus($new_status);

		
		return $updateStatus;
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
