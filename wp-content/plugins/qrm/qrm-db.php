<?php
abstract class WPQRM_Model {

	static $primary_key = 'id';

	private static function _table() {
		global $wpdb;
		$tablename = strtolower( get_called_class() );
		$tablename = str_replace( 'wpqrm_model_', $wpdb->prefix . 'qrm_', $tablename );
		return $tablename;
	}
	private static function _fetch_sql( $value ) {
		global $wpdb;
		$sql = sprintf( 'SELECT * FROM %s WHERE %s = %%s', self::_table(), static::$primary_key );
		return $wpdb->prepare( $sql, $value );
	}
	private static function _fix ($data){
		
		$arr = (array)$data;
		//Removes any objects or arrays from the array
		foreach ($arr as $key => $value) {
			if (gettype($value) == "array" || gettype($value) == "object" ) {
				unset($arr[$key]);
			}
		}
		return $arr;
	}
	static function get( $value ) {
		global $wpdb;
		return $wpdb->get_row( self::_fetch_sql( $value ) );
	}

	static function insert( $data ) {
		global $wpdb;
		$wpdb->insert( self::_table(), self::_fix($data) );
	}
	static function replace( $data ) {
		global $wpdb;
//		$wpdb->show_errors();
		$wpdb->replace( self::_table(), self::_fix($data) );
//		$wpdb->print_error();
	}
	static function update( $data, $where ) {
		global $wpdb;
		$wpdb->update( self::_table(), self::_fix($data), $where );
	}
	static function delete( $value ) {
		global $wpdb;
		$sql = sprintf( 'DELETE FROM %s WHERE %s = %%s', self::_table(), static::$primary_key );
		return $wpdb->query( $wpdb->prepare( $sql, $value ) );
	}
	static function insert_id() {
		global $wpdb;
		return $wpdb->insert_id;
	}
	static function time_to_date( $time ) {
		return gmdate( 'Y-m-d H:i:s', $time );
	}
	static function now() {
		return self::time_to_date( time() );
	}
	static function date_to_time( $date ) {
		return strtotime( $date . ' GMT' );
	}
}

class WPQRM_Model_Controls extends WPQRM_Model{

	static function deleteRiskControls($riskID){
		global $wpdb;
		$sql = sprintf( 'DELETE FROM %s WHERE riskID = %%s', $wpdb->prefix . 'qrm_controls', static::$primary_key );
		return $wpdb->query( $wpdb->prepare( $sql, $riskID ) );
	}
}
class WPQRM_Model_Mitplan extends WPQRM_Model{

	static function deleteRiskMitPlan($riskID){
		global $wpdb;
		$sql = sprintf( 'DELETE FROM %s WHERE riskID = %%s', $wpdb->prefix . 'qrm_mitplan', static::$primary_key );
		return $wpdb->query( $wpdb->prepare( $sql, $riskID ) );
	}
}
class WPQRM_Model_Respplan extends WPQRM_Model{

	static function deleteRiskRespPlan($riskID){
		global $wpdb;
		$sql = sprintf( 'DELETE FROM %s WHERE riskID = %%s', $wpdb->prefix . 'qrm_respplan', static::$primary_key );
		return $wpdb->query( $wpdb->prepare( $sql, $riskID ) );
	}
}
class WPQRM_Model_Review extends WPQRM_Model{
	
	static function _fix($review){
		WPQRM_Model_ReviewRisks::deleteReviewRisks($review->id);
		WPQRM_Model_ReviewComments::deleteReviewComments($review->id);
		WPQRM_Model_ReviewRiskComments::deleteReviewRiskComments($review->id);
		
		global $wpdb;
		$sql = sprintf( 'DELETE FROM %s WHERE reviewID = %%s', $wpdb->prefix . 'qrm_reviewcomments', static::$primary_key );
		$wpdb->query( $wpdb->prepare( $sql, $reviewID ) );
		
		foreach ($review->risks as $risk){
			$o = new stdObject();
			$o->riskID = $risk;
			$o->reviewID = $review->id;
			WPQRM_Model_ReviewRisks::replace($o);
		}
		foreach ($review->comments as $comment){
			$o = new stdObject();
			$o->reviewID = $review->id;
			$o->commentID = $comment->comment_id;
			WPQRM_Model_ReviewComments::replace($o);
		}
		foreach ($review->riskComments as $riskcomment){
			$riskcomment->reviewID = $review->id;
			WPQRM_Model_ReviewRiskComments::replace($riskcomment);
		}
		
		unset($review->risks);
		unset($review->risksComments);
		unset($review->comments);
		unset($review->attachments);
	}

	
	static function replace($review){
		self::_fix($review);
		parent::replace($review);
	}
}
class WPQRM_Model_ReviewComments extends WPQRM_Model{

	static function deleteReviewComments($reviewID){
		global $wpdb;
		$sql = sprintf( 'DELETE FROM %s WHERE reviewID = %%s', $wpdb->prefix . 'qrm_reviewcomments', static::$primary_key );
		return $wpdb->query( $wpdb->prepare( $sql, $reviewID ) );
	}
}
class WPQRM_Model_ReviewRiskComments extends WPQRM_Model{

	static function deleteReviewRiskComments($reviewID){
		global $wpdb;
		$sql = sprintf( 'DELETE FROM %s WHERE reviewID = %%s', $wpdb->prefix . 'qrm_reviewriskcomments', static::$primary_key );
		return $wpdb->query( $wpdb->prepare( $sql, $reviewID ) );
	}
}
class WPQRM_Model_ReviewRisks extends WPQRM_Model{

	static function deleteReviewRisks($reviewID){
		global $wpdb;
		$sql = sprintf( 'DELETE FROM %s WHERE reviewID = %%s', $wpdb->prefix . 'qrm_reviewrisks', static::$primary_key );
		return $wpdb->query( $wpdb->prepare( $sql, $reviewID ) );
	}
}
class WPQRM_Model_Risk extends WPQRM_Model {
	static $primary_key = 'id';
	
	static function _fix($data){
		
		try {
		
		$data->primcatID = $data->primcat->id;
		$data->seccatID = $data->seccat->id;

		$data->description = str_replace( "<p>", "", $data->description );
		$data->description = str_replace( "</p>", "<br/><br/>", $data->description );
		
		$data->cause = str_replace( "<p>", "", $data->cause );
		$data->cause = str_replace( "</p>", "<br/><br/>", $data->cause );
		
		$data->consequence = str_replace( "<p>", "", $data->consequence );
		$data->consequence = str_replace( "</p>", "<br/><br/>", $data->consequence );
		
		$data->mitigation->mitPlanSummary = str_replace( "<p>", "", $data->mitigation->mitPlanSummary );
		$data->mitigation->mitPlanSummaryUpdate = str_replace( "<p>", "", $data->mitigation->mitPlanSummaryUpdate );
		
		$data->response->respPlanSummary = str_replace( "<p>", "", $data->response->respPlanSummary );
		$data->response->respPlanSummaryUpdate = str_replace( "<p>", "", $data->response->respPlanSummaryUpdate );
		
		$data->mitigation->mitPlanSummary = str_replace( "</p>", "<br/><br/>", $data->mitigation->mitPlanSummary );
		$data->mitigation->mitPlanSummaryUpdate = str_replace( "</p>", "<br/><br/>", $data->mitigation->mitPlanSummaryUpdate );
		
		$data->response->respPlanSummary = str_replace( "</p>", "<br/><br/>", $data->response->respPlanSummary );
		$data->response->respPlanSummaryUpdate = str_replace( "</p>", "<br/><br/>", $data->response->respPlanSummaryUpdate );
		
		WPQRM_Model_Controls::deleteRiskControls($data->id);
		foreach ($data->controls as $control){
			$control->riskID = $data->id;
			WPQRM_Model_Controls::replace($control);	
		}
		
		WPQRM_Model_Mitplan::deleteRiskMitPlan($data->id);
		foreach ($data->mitigation->mitPlan as $plan){
			$plan->riskID = $data->id;
			WPQRM_Model_Mitplan::replace($plan);
		}		
		
		WPQRM_Model_Respplan::deleteRiskRespPlan($data->id);
		foreach ($data->response->respPlan as $plan){
			$plan->riskID = $data->id;
			WPQRM_Model_Respplan::replace($plan);
		}		
		if ($data->impCost != 1) $data->impCost = 0;
		if ($data->impRep != 1) $data->impRep = 0;
		if ($data->impSafety != 1) $data->impSafety = 0;
		if ($data->impSpec != 1) $data->impSpec = 0;
		if ($data->impTime != 1) $data->impTime = 0;
		if ($data->impEnviron != 1) $data->impEnviron = 0;
		
		if ($data->treatAvoid != 1) $data->treatAvoid = 0;
		if ($data->treatMinimise != 1) $data->treatMinimise = 0;
		if ($data->treatRetention != 1) $data->treatRetention = 0;
		if ($data->treatTransfer != 1) $data->treatTransfer = 0;
		if ($data->treated != 1) $data->treated = 0;

		if ($data->useCalContingency != 1) $data->useCalContingency = 0;
		if ($data->useCalProb != 1) $data->useCalProb = 0;

		if ($data->primcatID < 1) $data->primcatID = 0;
		if ($data->seccatID < 1) $data->seccatID = 0;
		if ($data->summaryRisk != 1) $data->summaryRisk = 0;
		
		$data->primCatName = WPQRM_Model_Category::get($data->primcatID)->title;
		$data->secCatName = WPQRM_Model_Category::get($data->seccatID)->title;

		$data->mitPlanSummary = $data->mitigation->mitPlanSummary;
		$data->mitPlanSummaryUpdate = $data->mitigation->mitPlanSummaryUpdate;
		
		$data->respPlanSummary = $data->response->respPlanSummary;
		$data->respPlanSummaryUpdate = $data->response->respPlanSummaryUpdate;
		
		$data->auditIdentDate = $data->auditIdent->auditDate;
		$data->auditIdentComment = $data->auditIdent->auditComment;
		$data->auditIdentPersonID = $data->auditIdent->auditPerson;
		
		$data->auditIdentRevDate = $data->auditIdentRev->auditDate;
		$data->auditIdentRevComment = $data->auditIdentRev->auditComment;
		$data->auditIdentRevPersonID = $data->auditIdentRev->auditPerson;
		
		$data->auditIdentAppDate = $data->auditIdentApp->auditDate;
		$data->auditIdentAppComment = $data->auditIdentApp->auditComment;
		$data->auditIdentAppPersonID = $data->auditIdentApp->auditPerson;
		
		$data->auditEvalDate = $data->auditEval->auditDate;
		$data->auditEvalComment = $data->auditEval->auditComment;
		$data->auditEvalPersonID = $data->auditEval->auditPerson;
		
		$data->auditEvalRevDate = $data->auditEvalRev->auditDate;
		$data->auditEvalRevComment = $data->auditEvalRev->auditComment;
		$data->auditEvalRevPersonID = $data->auditEvalRev->auditPerson;
		
		$data->auditEvalAppDate = $data->auditEvalApp->auditDate;
		$data->auditEvalAppComment = $data->auditEvalApp->auditComment;
		$data->auditEvalAppPersonID = $data->auditEvalApp->auditPerson;
		
		$data->auditMitDate = $data->auditMit->auditDate;
		$data->auditMitComment = $data->auditMit->auditComment;
		$data->auditMitPersonID = $data->auditMit->auditPerson;
		
		$data->auditMitRevDate = $data->auditMitRev->auditDate;
		$data->auditMitRevComment = $data->auditMitRev->auditComment;
		$data->auditMitRevPersonID = $data->auditMitRev->auditPerson;
		
		$data->auditMitAppDate = $data->auditMitApp->auditDate;
		$data->auditMitAppComment = $data->auditMitApp->auditComment;
		$data->auditMitAppPersonID = $data->auditMitApp->auditPerson;
		
		//Get the project
		
		$project = WPQRM_Model_Project::get( $data->projectID);
		
		$data->tolString = $project->tolString;
		$data->maxProb = $project->maxProb;
		$data->maxImpact = $project->maxImpact;
		
		// Get the preferred user display
		
		$p = get_option("qrm_displayUser");
		
		$man = WP_User::get_data_by("id", $data->manager);
		$own = WP_User::get_data_by("id", $data->owner);
	
				
		switch ($p){
			case 'userdisplayname':
				$data->managerName = $man->display_name;
				$data->ownerName = $own->display_name;
				break;
			case 'userlogin':
				$data->managerName = $man->user_login;
				$data->ownerName = $own->user_login;
				break;
			case 'usernicename':
				$data->managerName = $man->user_nicename;
				$data->ownerName = $own->user_nicename;
				break;
			case 'useremail':
				$data->managerName = $man->user_email;
				$data->ownerName = $own->user_email;
				break;
			case 'usernickname':
				$data->managerName = get_user_meta($data_manager, "nickname", true);
				$data->ownerName = get_user_meta($data_owner, "nickname", true);
				break;
			case 'userfirstname':
				$data->managerName = get_user_meta($data_manager, "first_name", true);
				$data->ownerName = get_user_meta($data_owner, "$first_name", true);
				break;
			case 'userlastname':
				$data->managerName = get_user_meta($data_manager, "last_name", true);
				$data->ownerName = get_user_meta($data_owner, "last_name", true);
				break;
		}
		
		unset($data->x);
		unset($data->x1);
		unset($data->y);
		unset($data->y1);
		unset($data->primcat);
		unset($data->seccat);
		} catch (Exception $e){
			echo $e->getMessage();
		}
		return $data;
	}
	static function insert($data){
		$data = self::_fix($data);
		parent::insert($data);
	}
	static function update($data){
		$data = self::_fix($data);
		parent::update($data);
	}
	static function replace($data){
		global $wpdb;
		parent::replace(self::_fix($data));
	}
	
	static function delete($data){
		$data = self::_fix($data);
		parent::delete($data);
	}
}
class WPQRM_Model_ProjectOwners extends WPQRM_Model{
	static function deleteProjectRiskOwners($projectID){
		global $wpdb;
		$sql = sprintf( 'DELETE FROM %s WHERE projectID = %%s', $wpdb->prefix . 'qrm_projectowners', static::$primary_key );
		return $wpdb->query( $wpdb->prepare( $sql, $projectID ) );
	}
	
	static function replace($data){
		parent::replace($data);
	}
}
class WPQRM_Model_ProjectManagers extends WPQRM_Model{
	static function deleteProjectRiskManagers($projectID){
		global $wpdb;
		$sql = sprintf( 'DELETE FROM %s WHERE projectID = %%s', $wpdb->prefix . 'qrm_projectmanagers', static::$primary_key );
		return $wpdb->query( $wpdb->prepare( $sql, $projectID ) );
	}
	
	static function replace($data){
		parent::replace($data);
	}
}
class WPQRM_Model_ProjectUsers extends WPQRM_Model{
	
	static function deleteProjectRiskUsers($projectID){
		global $wpdb;
		$sql = sprintf( 'DELETE FROM %s WHERE projectID = %%s', $wpdb->prefix . 'qrm_projectusers', static::$primary_key );
		return $wpdb->query( $wpdb->prepare( $sql, $projectID ) );
	}
	
	static function replace($data){
		parent::replace($data);
	}
}
class WPQRM_Model_Audit extends WPQRM_Model{}
class WPQRM_Model_Category extends WPQRM_Model{
	static function deleteProjectCategories($projectID){
		global $wpdb;
		$sql = sprintf( 'DELETE FROM %s WHERE projectID = %%s', $wpdb->prefix . 'qrm_category', static::$primary_key );
		return $wpdb->query( $wpdb->prepare( $sql, $projectID ) );
	}
	
	static function replace($data){
		$var = '$$hashKey';
		unset ($data->$var);
		parent::replace($data);
	}
}
class WPQRM_Model_Objective extends WPQRM_Model{
	static function deleteProjectObjectives($projectID){
		global $wpdb;
		$sql = sprintf( 'DELETE FROM %s WHERE projectID = %%s', $wpdb->prefix . 'qrm_objective', static::$primary_key );
		return $wpdb->query( $wpdb->prepare( $sql, $projectID ) );
	}
	
	static function replace($data){
		$var = '$$hashKey';
		unset ($data->$var);
		$var = '$$treeLevel';
		unset ($data->$var);
		parent::replace($data);
	}
}
class WPQRM_Model_IncidentRisks extends WPQRM_Model{
	static function deleteIncidentRisks($incidentID){
		global $wpdb;
		$sql = sprintf( 'DELETE FROM %s WHERE incidentID = %%s', $wpdb->prefix . 'qrm_incidentrisks', static::$primary_key );
		return $wpdb->query( $wpdb->prepare( $sql, $projectID ) );
	}

	static function replace($data){
		parent::replace($data);
	}
}
class WPQRM_Model_Incident extends WPQRM_Model{
	static function _fix($data){

		WPQRM_Model_IncidentRisks::deleteIncidentRisks($data->incidentID);
		foreach ($data->risks as $risk){
			$o = new stdClass();
			$o->riskID = $risk;
			$o->incidentID = $data->id;
				WPQRM_Model_IncidentRisks::replace($o);
		}
		$data->incidentDate = $data->date;
		unset($data->date);
		return $data;
	}
	static function replace($data){
		global $wpdb;
		parent::replace(self::_fix($data));
	}
}
class WPQRM_Model_Project extends WPQRM_Model{
	static function _fix($data){
		$data->tolString = $data->matrix->tolString;
		$data->maxProb = $data->matrix->maxProb;
		$data->maxImpact = $data->matrix->maxImpact;
		$data->probVal1 = $data->matrix->probVal1;
		$data->probVal2 = $data->matrix->probVal2;
		$data->probVal3 = $data->matrix->probVal3;
		$data->probVal4 = $data->matrix->probVal4;
		$data->probVal5 = $data->matrix->probVal5;
		$data->probVal6 = $data->matrix->probVal6;
		$data->probVal7 = $data->matrix->probVal7;
		$data->probVal8 = $data->matrix->probVal8;
		
		WPQRM_Model_Category::deleteProjectCategories($data->id);
		foreach ($data->categories as $cat){
			if ($cat->projectID == $data->id){
				WPQRM_Model_Category::replace($cat);
			}
		}
		WPQRM_Model_Objective::deleteProjectObjectives($data->id);
		foreach ($data->objectives as $obj){
			if ($obj->projectID == $data->id){
				WPQRM_Model_Objective::replace($obj);
			}
		}
		
		WPQRM_Model_ProjectOwners::deleteProjectRiskOwners($data->id);
		foreach ($data->ownersID as $ID){
			$o = new stdClass();
			$o->projectID = $data->id;
			$o->ownerID = $ID;
			WPQRM_Model_ProjectOwners::replace($o);
		}		
		
		WPQRM_Model_ProjectManagers::deleteProjectRiskManagers($data->id);
		foreach ($data->managersID as $ID){
			$o = new stdClass();
			$o->projectID = $data->id;
			$o->managerID = $ID;
			WPQRM_Model_ProjectManagers::replace($o);
		}		
		
		WPQRM_Model_ProjectUsers::deleteProjectRiskUsers($data->id);
		foreach ($data->usersID as $ID){
			$o = new stdClass();
			$o->projectID = $data->id;
			$o->userID = $ID;
			WPQRM_Model_ProjectUsers::replace($o);
		}
		$var = '$$treeLevel';
		unset($data->$var);
		return $data;
	}
	static function replace($data){
		global $wpdb;
		parent::replace(self::_fix($data));
	}
}