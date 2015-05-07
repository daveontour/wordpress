<?php

class Project{
	public $id;
	public $title;
	public $description;
	public $projectCode;
	public $riskIndex;
	public $parent_id;
	public $categories;
	public $projectRiskManager;
	public $useAdvancedConsequences;
	public $useAdvancedLiklihood;
	public $ownersID;
	public $managersID;
	public $tolSting;
	public $maxImpact;
	public $maxProb;
	public $objectives;
	public $inheritParentObjectives;
	public $riskCategories;
	public $inheritParentCategories;
}

class Risk {
	
	public $startDate;
	public $endDate;
	
	public $consequences;
	public $causes;
	public $description;
	public $title;
	
	public $riskOwner;    //metadata
	public $riskManager;  //metadata
	public $riskManager2;
	
	public $probInt;     //metadata
	public $impactInt;   //metadata
	public $probDouble;
	public $impactDouble;	
	public $probReal;
	public $tolerance;   //metadata
	public $costImpact;
	
	public $probIntPost;     //metadata
	public $impactIntPost;   //metadata
	public $probDoublePost;
	public $impactDoublePost;
	public $probRealPost;
	public $tolerancePost;  //metadata
	public $costImpactPost;
	
	public $calcContingencyCost;
	public $estimatedContingencyCost;
	
	public $calcRemediationCost;
	public $estimatedRemediationCost;
	
	public $primCategory;  //metadata
	public $secCategory;   //metadata
	
	public $bTreated = FALSE;
	
	public $bTreatAvoidence = FALSE;
	public $bTreatTransfer = FALSE;
	public $bTreatMinimisation = FALSE;
	public $bTreatAccept = FALSE;
	
	public $bImpSafety = FALSE;
	public $bImpCost = FALSE;
	public $bImpTime = FALSE;
	public $bImpSpec = FALSE;
	public $bImpEnviron = FALSE;
	
	public $mitigationPlanID;
	
	public $comments;
	public $attachments;
	public $objectives;
	
	
	public static function postSave($post_id) {
		;
	}
	
}
class SmallRisk {
	public $title;
	public $id;
	public $owner;
	public $manager;
	public $description;
	public $currentTolerance;
	public $currentProb;
	public $currentImpact;
}
class Data {
	public $data;
}
