<?php

namespace MediaFigaro\GoogleAnalyticsApi\Controller;

use Google_Service_AnalyticsReporting_DateRange;
use Google_Service_AnalyticsReporting_GetReportsRequest;
use Google_Service_AnalyticsReporting_Metric;
use Google_Service_AnalyticsReporting_ReportRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class DebugController
 * @package MediaFigaro\GoogleAnalyticsApi\Controller
 */
class DebugController extends Controller
{
    /**
     * @Route("{viewId}")
     * @Template()
     */
    public function connectAction($viewId)
    {
        $analyticsService = $this->get('google_analytics_api.api');

        $client = $analyticsService->getClient();

        $analytics = $analyticsService->getAnalytics();

        // demo purpose : dump of the result object

        // Create the DateRange object
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate("30daysAgo");
        $dateRange->setEndDate("today");

        // Create the Metrics object
        $sessions = new Google_Service_AnalyticsReporting_Metric();
        $sessions->setExpression("ga:sessions");
        $sessions->setAlias("sessions");

        // Create the ReportRequest object
        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($viewId);
        $request->setDateRanges($dateRange);
        $request->setMetrics([$sessions]);

        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests([$request]);

        $report = $analytics->reports->batchGet($body);

        // above code included into this helper method :

        $sessions = $analyticsService->getSessionsDateRange($viewId,'30daysAgo','today');
        $bounceRate = $analyticsService->getBounceRateDateRange($viewId,'30daysAgo','today');
        $avgTimeOnPage = $analyticsService->getAvgTimeOnPageDateRange($viewId,'30daysAgo','today');
        $pageViewsPerSession = $analyticsService->getPageviewsPerSessionDateRange($viewId,'30daysAgo','today');
        $percentNewVisits = $analyticsService->getPercentNewVisitsDateRange($viewId,'30daysAgo','today');
        $pageViews = $analyticsService->getPageViewsDateRange($viewId,'30daysAgo','today');
        $avgPageLoadTime = $analyticsService->getAvgPageLoadTimeDateRange($viewId,'30daysAgo','today');

        return [
            'analytics'     =>  $analytics,
            'client'        =>  $client,
            'report'        =>  $report,
            'data'          =>  [
                'sessions'              =>  $sessions,
                'bounce_rate'           =>  $bounceRate,
                'avg_time_on_page'      =>  $avgTimeOnPage,
                'page_view_per_session' =>  $pageViewsPerSession,
                'percent_new_visits'    =>  $percentNewVisits,
                'page_views'            =>  $pageViews,
                'avg_page_load_time'    =>  $avgPageLoadTime
            ]
        ];
    }
}
