<?php
namespace App\Custom\Manager;

use App\Premium\Manager\ToolsManagerPremium;

class ToolsManagerCustom extends ToolsManagerPremium {

	// Add contact type parameter in the list
	protected array $ruleParam = array('datereference','bidirectional','fieldId','mode','duplicate_fields','limit','delete', 'fieldDateRef', 'fieldId', 'targetFieldId','contactType','recordType','deletionField','deletion','anneeScolaire');

	// Allow more relationships
	public function beforeRuleEditViewRender($data) {
	
		// Add bidirectional rule for Mobilisation - Participation RI
		if ($data['regleId'] == '6281633dcddf1') { // Mobilisation - Participation RI -> comet
				$data['rule_params'][] = array(
									'id' => 'bidirectional',
									'name' => 'bidirectional',
									'required' => false,
									'type' => 'option',
									'label' => 'create_rule.step3.params.sync',
									'option' => array('627153382dc34' => 'Mobilisation - Participations RI'));
		}
		
		if ($data['regleId'] == '627153382dc34') {	// Mobilisation - Participations RI
				$data['rule_params'][] = array(
									'id' => 'bidirectional',
									'name' => 'bidirectional',
									'required' => false,
									'type' => 'option',
									'label' => 'create_rule.step3.params.sync',
									'option' => array('6281633dcddf1' => 'Mobilisation - Participation RI -> comet'));
		}

		return $data;
	}

} 

