<?php

class WPML_TM_Jobs_Summary_Report_Process_Factory {

	/**
	 * @return WPML_TM_Jobs_Summary_Report_Process
	 */
	public function create_weekly_report() {

		$summary_report = $this->get_summary_report( WPML_TM_Jobs_Summary::WEEKLY_REPORT );

		return new WPML_TM_Jobs_Summary_Report_Process(
			$this->get_template(),
			new WPML_TM_Jobs_Weekly_Summary_Report_Model(),
			$summary_report->get_jobs()
		);
	}

	/**
	 * @return WPML_TM_Jobs_Summary_Report_Process
	 */
	public function create_daily_report() {
		$summary_report = $this->get_summary_report( WPML_TM_Jobs_Summary::DAILY_REPORT );

		return new WPML_TM_Jobs_Summary_Report_Process(
			$this->get_template(),
			new WPML_TM_Jobs_Daily_Summary_Report_Model(),
			$summary_report->get_jobs()
		);
	}

	/**
	 * @param string $frequency
	 *
	 * @return WPML_TM_Jobs_Summary_Report
	 */
	private function get_summary_report( $frequency ) {
		global $sitepress, $wpdb;

		return new WPML_TM_Jobs_Summary_Report(
			new WPML_Translation_Jobs_Collection( $wpdb, array() ),
			new WPML_TM_String( false, $sitepress, $wpdb ),
			new WPML_TM_Post( false, $sitepress, $wpdb ),
			$frequency,
			new WPML_Translation_Element_Factory( $sitepress )
		);
	}

	/**
	 * @return WPML_TM_Jobs_Summary_Report_View
	 */
	private function get_template() {
		$template_service_factory = new WPML_TM_Email_Twig_Template_Factory();
		return new WPML_TM_Jobs_Summary_Report_View( $template_service_factory->create() );
	}
}