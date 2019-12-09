<?php

namespace WPML\TM\Container;

class Config {

	static public function getDelegated() {
		return [
			'\WPML_Translation_Job_Factory'    => 'wpml_tm_load_job_factory',
			\WPML_TM_ATE_Job_Repository::class => 'wpml_tm_get_ate_jobs_repository',
		];
	}

	static public function getSharedClasses() {
		return [
			'\WPML_TM_AMS_API',
			'\WPML_TM_ATE_API',
			'\WPML_TM_ATE_AMS_Endpoints',
			'\WPML_TM_ATE_Authentication',
			'\WPML_TM_AMS_ATE_Console_Section',
			'\WPML_TM_Admin_Sections',
			'\WPML_Translator_Records',
			'\WPML_Translator_Admin_Records',
			'\WPML_Translation_Manager_Records',
			'\WPML_TM_MCS_ATE_Strings',
			'\WPML_TM_AMS_Users',
			'\WPML_TM_AMS_Translator_Activation_Records',
			'\WPML_TM_REST_AMS_Clients',
			'\WPML_TM_AMS_Check_Website_ID',
			'\WPML_Translation_Job_Factory',
			\WPML\TM\ATE\Log\Storage::class,
		];
	}
}
