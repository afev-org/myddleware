<?php

namespace App\Custom\Premium\Manager;

use App\Manager\DocumentManager;
use App\Manager\RuleManager;
use App\Entity\Workflow;
use App\Entity\WorkflowLog;
use App\Entity\WorkflowAction;
use App\Entity\Document;
use App\Entity\Job;

class DocumentManagerPremium extends DocumentManager
{	
	public function setParam($param, $clear = false, $clearRule = true)
    {
		if ($clearRule) {
			$this->ruleWorkflows = [];
			$this->variables = [];
		}
		if (!empty($param['ruleWorkflows'])) {
			$this->ruleWorkflows = $param['ruleWorkflows'];
		}
		if (!empty($param['variables'])) {
			$this->variables = $param['variables'];
		}
		return parent::setParam($param, $clear, $clearRule);
	}
	
	public function runWorkflow($rerun=false) {	
		try {
			// Check if at least on workflow exist for the rule
			if (!empty($this->ruleWorkflows)) {
				$targetFields = false;
				$historyFields = false;
				// Can be empty depending on the context of the workflow call
				if (empty($this->sourceData)) {
					$this->sourceData = $this->getDocumentData('S');
				}
				// Add all source data in variables
				if (!empty($this->sourceData)) {
					foreach($this->sourceData as $key => $value) {
						$fieldName = 'source_'.$key;
						$$fieldName = $value;
					}
				}
				
				// Include variables used in the formula
				$status = $this->status;
				$documentType = $this->documentType;
				$attempt = $this->attempt;
				$message = $this->message;
				$typeError = $this->typeError;
				
				// Execute every workflow of the rule
				foreach ($this->ruleWorkflows as $ruleWorkflow) {
					// Add target fields if requested and if not already calculated
					if (
							strpos($ruleWorkflow['condition'], 'target_') !== false
						AND !$targetFields
					) {
						$targetFields = true;
						$target = $this->getDocumentData('T');
						// Add all source data in variables
						if (!empty($target)) {
							foreach($target as $key => $value) {
								$fieldName = 'target_'.$key;
								$$fieldName = $value;
							}
						}
					}
					// Add history fields if requested and if not already calculated
					if (
							strpos($ruleWorkflow['condition'], 'history_') !== false
						AND !$historyFields
					) {
						$historyFields = true;
						$history = $this->getDocumentData('H');
						// Add all source data in variables
						if (!empty($history)) {
							foreach($history as $key => $value) {
								$fieldName = 'history_'.$key;
								$$fieldName = $value;
							}
						}
					}
					
					// Check the condition 
					$this->formulaManager->init($ruleWorkflow['condition']); // mise en place de la règle dans la classe
					$this->formulaManager->generateFormule(); // Genère la nouvelle formule à la forme PhP
					$f = $this->formulaManager->execFormule();
					eval('$condition = ('.$f.'?1:0);');
					// Execute the action if the condition is met
					if ($condition == 1) {
						try {
							// Execute all actions 
							if (!empty($ruleWorkflow['actions'])) {
								// Call each actions
								foreach($ruleWorkflow['actions'] as $action) {
									// Check if the action has already been executed for the current document 
									// Only if attempt > 0, if it is the first attempt then the action has never been executed
									if (
											$this->attempt > 0
										 OR $rerun
									) {
										// Search action for the current document
										$workflowLogEntity = $this->entityManager->getRepository(WorkflowLog::class)
																->findOneBy([
																			'triggerDocument' => $this->id,
																			'action' => $action['id'],
																			]
																		);
										// If the current action has been found for the current document, we don't execute the current action
										if (
												!empty($workflowLogEntity)
											AND $workflowLogEntity->getStatus() == 'Success'
										) {
											// GenerateDocument can be empty depending the action 
											if (!empty($workflowLogEntity->getGenerateDocument())) {
												$this->docIdRefError = $workflowLogEntity->getGenerateDocument()->getId();
											}
											$this->generateDocLog('W','Action ' . $action['id'] . ' already executed for this document. ');
											continue;
										}
									}

									// Execute action depending of the function in the workflow
									$arguments = $this->setWorkflowNotificationArguments($action);
									switch ($action['action']) {
										case 'generateDocument':
											// Set default value if empty
											$searchField = (!empty($arguments['searchField']) ? $arguments['searchField'] : 'id');
											$searchValue = ((!empty($arguments['searchValue']) AND !empty($this->sourceData[$arguments['searchValue']])) ? $this->sourceData[$arguments['searchValue']] : '');
											$this->generateDocument($arguments['ruleId'],$searchValue, $searchField ,$arguments['rerun'], $action);
											break;
										case 'sendNotification':
                                            $workflowStatus = 'Success';
                                            $error = '';
                                            // Method sendMessage throws an exception if it fails
                                            $this->tools->sendMessage($arguments['to'],$arguments['subject'],$arguments['message']);
											$this->createWorkflowLog($action, $workflowStatus, $error);
											break;
										case 'updateStatus':
                                            $workflowStatus = 'Success';
                                            $error = '';
                                            $this->typeError = 'W';
                                            $this->message = 'Status change using workflow. ';
                                            $this->updateStatus($arguments['status'], true);
											$this->createWorkflowLog($action, $workflowStatus, $error);
											break;
                                        case 'changeData':
                                            $workflowStatus = 'Success';
                                            $error = '';
                                            $this->typeError = 'W';
                                            $this->message = 'Change fields values. ';
											if (!empty($arguments['fields'])) {
												$this->updateDocumentData($this->id, $arguments['fields'], 'T');
											}
											$this->createWorkflowLog($action, $workflowStatus, $error);
											break;
                                        case 'transformDocument':
                                            $workflowStatus = 'Success';
                                            $error = '';
                                            $this->typeError = 'W';
											// Avoid infinite loop by skipping the call to the workflow after changing the status
                                            $this->workflowAction = true;
                                            // Set back the parameter $this->transformError to false 
                                            // because it could have been set to true by the current action
                                            $this->transformError = false;
                                            $this->transformDocument();
                                            $this->message = 'Document transformed using workflow. ';
                                            $this->createWorkflowLog($action, $workflowStatus, $error);
                                            break;
										case 'rerun':
                                            $workflowStatus = 'Success';
                                            $error = '';
                                            $this->typeError = 'W';
											// Avoid infinite loop by skipping the call to the workflow after changing the status
                                            $this->workflowAction = true;
											$rule = new RuleManager($this->logger, $this->connection, $this->entityManager, $this->parameterBagInterface, $this->formulaManager, $this->solutionManager, clone $this);
											$rule->setRule($this->ruleId);
											$rule->setJobId($this->jobId);
											$errors = $rule->actionDocument($this->id, 'rerun');
                                            $this->message = 'Document rerun using workflow. ';
                                            $this->createWorkflowLog($action, $workflowStatus, $error);
                                            break;
										default:
										   throw new \Exception('Function '.key($action).' unknown.');
									}
								}
							}
						} catch (\Exception $e) {
							$this->logger->error($this->id.' - Failed to run the workflow '.$ruleWorkflow['name'].' : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
							$this->generateDocLog('E','Failed to run the workflow '.$ruleWorkflow['name'].' : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
							$this->setWorkflowError(true);
						}
					}
				}
			}
		} catch (\Exception $e) {
            $this->logger->error($this->id.' - Failed to run all workflows : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
			$this->generateDocLog('E','Failed to run all workflows : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }
	}
	
	protected function setWorkflowNotificationArguments($action) {
		$arguments = unserialize($action['arguments']);
		// In case of notification, we add informations on the message
		if ($action['action'] == 'sendNotification') {
			$arguments['message'] .= '<br><br>Document ID : '.$this->id.'<br><br>';
			if (!empty($this->sourceData)) {
				$arguments['message'] .= 'Detail of the document : <br>';
				foreach($this->sourceData as $key => $value) {
					$arguments['message'] .= $key.' : '.$value.'<br>';
				}
			}
			$arguments['message'] .= '<br>Best regards<br>Myddleware';
			// We transform the string to array in case there are several recipients
			$arguments['to'] = explode(',',$arguments['to']);
		}
		return $arguments;
	}

	// Generate a document using the rule id and search parameters
	protected function generateDocument($ruleId, $searchValue = null, $searchField = 'id', $rerun = true, $action = null)
	{
		try {
			// Instantiate the rule
			$rule = new RuleManager($this->logger, $this->connection, $this->entityManager, $this->parameterBagInterface, $this->formulaManager, $this->solutionManager, clone $this);
			$rule->setRule($ruleId);
			$rule->setJobId($this->jobId);

			if (empty($searchValue)) {
				$searchValue = $this->sourceId;
			}

			// Generate the documents depending on the search parameter
			$documents = $rule->generateDocuments($searchValue, true, '', $searchField);
			if (!empty($documents->error)) {
				throw new \Exception($documents->error);
			}
			// Run documents
			if (
				!empty($documents)
				and $rerun
			) {
				foreach ($documents as $doc) {
					$errors = $rule->actionDocument($doc->id, 'rerun');
					// Check errors
					if (!empty($errors)) {
						$this->message .=  'Document ' . $doc->id . ' in error (rule ' . $ruleId . '  : ' . $errors[0] . '. ';
					}
					// Generate the workflow log for each document if it has been generated by a workflow
					if (!empty($action['id'])) {
						$error = '';
						if (!empty($errors)) {
							$error = $this->message; 
							$status = 'Error';
						} else {
							$status = 'Success';
						}
						$this->createWorkflowLog($action, $status, $error, $doc->id);
					}
				}
			}
		} catch (\Exception $e) {
			$this->logger->error($this->id.' - Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )');
			$this->generateDocLog('E',$this->message);
		}
	}

	// Create a workflow log
	protected function createWorkflowLog($action, $status, $error=null, $generateDocumentId=null) {
		try {
			// Generate the workflow log
			$workflowLog = new WorkflowLog();
			// Set the current document
			$triggerDocumentEntity = $this->entityManager->getRepository(Document::class)->find($this->id);
			$workflowLog->setTriggerDocument($triggerDocumentEntity);
			// Set the current action
			$workflowActionEntity = $this->entityManager->getRepository(WorkflowAction::class)->find($action['id']);
			$workflowLog->setAction($workflowActionEntity); 
			// Set the generated document if the action has generated a document
			if (!empty($generateDocumentId)) {
				$generateDocumentEntity = $this->entityManager->getRepository(Document::class)->find($generateDocumentId);
				$this->docIdRefError = $generateDocumentId;
				$workflowLog->setGenerateDocument($generateDocumentEntity);
			}
			// Set the workflow
			$workflowEntity = $this->entityManager->getRepository(Workflow::class)->find($action['workflow_id']);
			$workflowLog->setWorkflow($workflowEntity);
			// Set the job
			$jobEntity = $this->entityManager->getRepository(Job::class)->find($this->jobId);
			$workflowLog->setJob($jobEntity);
			// Set the creation date
			$workflowLog->setDateCreated(new \DateTime());
			// Set the status depending on the error message
			if (!empty($errors)) {
				$workflowLog->setMessage($error); 
				$workflowLog->setStatus($status);
			} else {
				$workflowLog->setStatus('Success');;
			}
			$this->entityManager->persist($workflowLog);
			$this->entityManager->flush();
			// Generate a document log.
			$this->generateDocLog('S','Action '.$action['action'].' : '.$action['name'].' executed. '.(!empty($generateDocumentId) ? 'The document '.$generateDocumentId.' has been generated. ' : ''));
		} catch (\Exception $e) {
			$this->logger->error($this->id.' - Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )');
			$this->generateDocLog('E','Error : ' . $e->getMessage() . ' ' . $e->getFile() . ' Line : ( ' . $e->getLine() . ' )');
		}
	}	
	
	// Unset the lock on the rule
	protected function setWorkflowError($flag) {
		try {
			// Get the rule details
			$documentEntity = $this->entityManager->getRepository(Document::class)->find($this->id);
			// If read lock empty, we set the lock with the job id
			if (!empty($documentEntity)) {
				$documentEntity->setWorkflowError($flag);
				$this->entityManager->persist($documentEntity);
				$this->entityManager->flush();
				$this->workflowError = true;
				return true;
			}	
        } catch (Exception $e) {
            $this->logger->error('Failed set the flag workflow error to '.$flag.' to the document '.$this->id.' : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )');
        }
		return false;
	}

}

