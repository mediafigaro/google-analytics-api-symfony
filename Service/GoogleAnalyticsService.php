<?php

namespace MediaFigaro\GoogleAnalyticsApi\Service;

use Google_Client;
use Google_Service_AnalyticsReporting;
use Google_Service_AnalyticsReporting_DateRange;
use Google_Service_AnalyticsReporting_Dimension;
use Google_Service_AnalyticsReporting_DimensionFilter;
use Google_Service_AnalyticsReporting_DimensionFilterClause;
use Google_Service_AnalyticsReporting_GetReportsRequest;
use Google_Service_AnalyticsReporting_Metric;
use Google_Service_AnalyticsReporting_MetricFilter;
use Google_Service_AnalyticsReporting_MetricFilterClause;
use Google_Service_AnalyticsReporting_OrderBy;
use Google_Service_AnalyticsReporting_ReportRequest;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class GoogleAnalyticsService
 * @package MediaFigaro\GoogleAnalyticsApi\Service
 */
class GoogleAnalyticsService {

    /**
     * @var Google_Client
     */
    private $client;
    /**
     * @var Google_Service_AnalyticsReporting
     */
    private $analytics;
    /**
     * @var Google_Service_AnalyticsReporting_Dimension[]
     */
    private $reportingDimensions = null;
    /**
     * @var Google_Service_AnalyticsReporting_Metric[]
     */
    private $reportingMetrics = null;

    /**
     * construct
     */
    public function __construct($keyFileLocation) {

        if (!file_exists($keyFileLocation)) {
            throw new Exception("can't find file key location defined by google_analytics_api.google_analytics_json_key parameter, ex : ../data/analytics/analytics-key.json, defined : ".$keyFileLocation);
        }

        $this->client = new Google_Client();
        $this->client->setApplicationName("GoogleAnalytics");
        $this->client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
        $this->client->setAuthConfig($keyFileLocation);

        $this->analytics = new Google_Service_AnalyticsReporting($this->client);

    }

    /**
     * @return Google_Service_AnalyticsReporting
     */
    public function getAnalytics() {

        return $this->analytics;

    }

    /**
     * @return Google_Client
     */
    public function getClient() {

        return $this->client;

    }

    /**
     * getDataDateRangeMetricsDimensions
     *
     * simple helper & wrapper of Google Api Client
     *
     * @param $viewId
     * @param $dateStart
     * @param $dateEnd
     * @param array $metrics
     * @param array $dimensions
     * @param array $sorting ( = [ ['fields']=>['sessions','bounceRate',..] , 'order'=>'descending' ] )
     * @param array $filterMetric ( = [ ['metric_name']=>['sessions'] , 'operator'=>'LESS_THAN' , 'comparison_value'=>'100' ] )
     * @param array $filterDimension ( = [ ['dimension_name']=>['sourceMedium'] , 'operator'=>'EXACT' , 'expressions'=>['my_campaign'] ] )
     * @return mixed
     *
     * @link https://developers.google.com/analytics/devguides/reporting/core/dimsmets
     * @link https://ga-dev-tools.appspot.com/query-explorer/
     * @link https://developers.google.com/analytics/devguides/reporting/core/v4/quickstart/web-php
     * @link https://developers.google.com/analytics/devguides/reporting/core/v4/samples
     * @link https://github.com/google/google-api-php-client
     *
     */
    public function getDataDateRangeMetricsDimensions($viewId,$dateStart,$dateEnd,$metrics='sessions',$dimensions=null,$sorting=null,$filterMetric=null,$filterDimension=null) {

        // Create the DateRange object
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($dateStart);
        $dateRange->setEndDate($dateEnd);

        if (isset($metrics) && $metrics && !is_array($metrics)) {
            $metrics = [$metrics];
        }

        if (isset($metrics) && $metrics && is_array($metrics)) {

            $this->reportingMetrics = [];

            foreach ($metrics as $metric) {

                // Create the Metrics object
                $reportingMetrics = new Google_Service_AnalyticsReporting_Metric();
                $reportingMetrics->setExpression("ga:$metric");
                $reportingMetrics->setAlias("$metric");

                if (!in_array($reportingMetrics,$this->reportingMetrics))
                    $this->reportingMetrics[] = $reportingMetrics;

            }

        }

        if (isset($dimensions) && $dimensions && !is_array($dimensions)) {
            $dimensions = [$dimensions];
        }

        if (isset($dimensions) && $dimensions && is_array($dimensions)) {

            $this->reportingDimensions = [];

            foreach ($dimensions as $dimension) {

                // Create the segment(s) dimension.
                $reportingDimensions = new Google_Service_AnalyticsReporting_Dimension();
                $reportingDimensions->setName("ga:$dimension");

                if (!in_array($reportingDimensions,$this->reportingDimensions))
                    $this->reportingDimensions[] = $reportingDimensions;

            }
        }

        // Create the ReportRequest object
        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($viewId);
        $request->setDateRanges($dateRange);

        // add dimensions
        if (isset($this->reportingDimensions) && is_array($this->reportingDimensions))
            $request->setDimensions($this->reportingDimensions);

        // add metrics
        if (isset($this->reportingMetrics) && is_array($this->reportingMetrics))
            $request->setMetrics($this->reportingMetrics);

        // sorting
        // @link https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet#SortOrder

        if (isset($sorting) && is_array($sorting)) {

            $orderBy = new Google_Service_AnalyticsReporting_OrderBy();

            if (isset($sorting['fields']) && is_array($sorting['fields'])) {

                $fields = $sorting['fields'];

                foreach ($fields as $sortingFieldName) {

                    $orderBy->setFieldName("ga:$sortingFieldName");

                }

                if (isset($sorting['order'])) {

                    $order = $sorting['order'];

                    $orderBy->setSortOrder($order);

                }

            }

            $request->setOrderBys($orderBy);

        }

        // metric filter (simple wrapper)
        // @link https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet#metricfilter

        if (isset($filterMetric) && is_array($filterMetric)) {

            if (isset($filterMetric['metric_name']) && isset($filterMetric['operator']) && isset($filterMetric['comparison_value'])) {

                // Create the DimensionFilter.
                $metricFilter = new Google_Service_AnalyticsReporting_MetricFilter();
                $metricFilter->setMetricName('ga:'.$filterMetric['metric_name']);
                $metricFilter->setOperator($filterMetric['operator']);
                $metricFilter->setComparisonValue($filterMetric['comparison_value']);

                // Create the DimensionFilterClauses
                $metricFilterClause = new Google_Service_AnalyticsReporting_MetricFilterClause();
                $metricFilterClause->setFilters([$metricFilter]);

                // add to request
                $request->setMetricFilterClauses($metricFilterClause);

            }

        }



        // dimension filter (simple wrapper)
        // @link https://developers.google.com/analytics/devguides/reporting/core/v3/reference#filters

        if (isset($filterDimension) && is_array($filterDimension)) {

            if (isset($filterDimension['dimension_name']) && isset($filterDimension['operator']) && isset($filterDimension['expressions'])) {

                if (!is_array($filterDimension['expressions'])) {
                    $filterDimension['expressions'] = [ $filterDimension['expressions'] ];
                }

                // Create the DimensionFilter.
                $dimensionFilter = new Google_Service_AnalyticsReporting_DimensionFilter();
                $dimensionFilter->setDimensionName('ga:'.$filterDimension['dimension_name']);
                $dimensionFilter->setOperator($filterDimension['operator']);
                $dimensionFilter->setExpressions($filterDimension['expressions']);

                // Create the DimensionFilterClauses
                $dimensionFilterClause = new Google_Service_AnalyticsReporting_DimensionFilterClause();
                $dimensionFilterClause->setFilters(array($dimensionFilter));

                // add to request
                $request->setDimensionFilterClauses(array($dimensionFilterClause));

            }

        }

        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests([$request]);

        $reports = $this->analytics->reports->batchGet($body);

        $data = [];

        foreach ($reports->getReports()[0]->getData()->getRows() as $row) {

            // arrays
            $dimensionsArray = $row->getDimensions();
            $valuesArray = $row->getMetrics()[0]->getValues();

            $dimensionsKeyValue = [];

            if (isset($dimensionsArray)) {

                $i=0;

                foreach ($dimensionsArray as $k => $v) {
                    $dimensionsKeyValue[$dimensions[$i]] = $v;
                    $i++;
                }

            }

            $metricsKeyValue = [];

            if (isset($metrics)) {

                $i = 0;

                foreach ($metrics as $k => $v) {
                    $metricsKeyValue[$metrics[$i]] = $valuesArray[$i];
                    $i++;
                }

            }

            $data[] = [
                'metrics'       =>  $metricsKeyValue,
                'dimensions'    =>  $dimensionsKeyValue
            ];

        }

        return $data;

    }

    /**
     * @param $viewId
     * @param $dateStart
     * @param $dateEnd
     * @return mixed
     *
     * https://ga-dev-tools.appspot.com/query-explorer/
     *
     */
    private function getDataDateRange($viewId,$dateStart,$dateEnd,$metric) {

        // Create the DateRange object
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($dateStart);
        $dateRange->setEndDate($dateEnd);

        // Create the Metrics object
        $sessions = new Google_Service_AnalyticsReporting_Metric();
        $sessions->setExpression("ga:$metric");
        $sessions->setAlias("$metric");

        if (isset($dimensions) && is_array($dimensions)) {

            $this->reportingDimensions = [];

            foreach ($dimensions as $dimension) {

                // Create the segment dimension.
                $reportingDimensions = new Google_Service_AnalyticsReporting_Dimension();
                $reportingDimensions->setName("ga:$dimension");

                $this->reportingDimensions[] = $reportingDimensions;

            }
        }

        // Create the ReportRequest object
        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($viewId);
        $request->setDateRanges($dateRange);

        // add dimensions
        if (isset($this->reportingDimensions) && is_array($this->reportingDimensions))
            $request->setDimensions($this->reportingDimensions);

        $request->setMetrics([$sessions]);

        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests([$request]);

        $report = $this->analytics->reports->batchGet($body);

        $result = $report->getReports()[0]
            ->getData()
            ->getTotals()[0]
            ->getValues()[0]
        ;

        return $result;

    }

    /**
     * @param $viewId
     * @param $dateStart
     * @param $dateEnd
     * @return mixed
     */
    public function getSessionsDateRange($viewId,$dateStart,$dateEnd) {
        return $this->getDataDateRange($viewId,$dateStart,$dateEnd,'sessions');
    }

    /**
     * @param $viewId
     * @param $dateStart
     * @param $dateEnd
     * @return mixed
     */
    public function getBounceRateDateRange($viewId,$dateStart,$dateEnd) {
        return $this->getDataDateRange($viewId,$dateStart,$dateEnd,'bounceRate');
    }

    /**
     * @param $viewId
     * @param $dateStart
     * @param $dateEnd
     * @return mixed
     */
    public function getAvgTimeOnPageDateRange($viewId,$dateStart,$dateEnd) {
        return $this->getDataDateRange($viewId,$dateStart,$dateEnd,'avgTimeOnPage');
    }

    /**
     * @param $viewId
     * @param $dateStart
     * @param $dateEnd
     * @return mixed
     */
    public function getPageviewsPerSessionDateRange($viewId,$dateStart,$dateEnd) {
        return $this->getDataDateRange($viewId,$dateStart,$dateEnd,'pageviewsPerSession');
    }

    /**
     * @param $viewId
     * @param $dateStart
     * @param $dateEnd
     * @return mixed
     */
    public function getPercentNewVisitsDateRange($viewId,$dateStart,$dateEnd) {
        return $this->getDataDateRange($viewId,$dateStart,$dateEnd,'percentNewVisits');
    }

    /**
     * @param $viewId
     * @param $dateStart
     * @param $dateEnd
     * @return mixed
     */
    public function getPageViewsDateRange($viewId,$dateStart,$dateEnd) {
        return $this->getDataDateRange($viewId,$dateStart,$dateEnd,'pageviews');
    }

    /**
     * @param $viewId
     * @param $dateStart
     * @param $dateEnd
     * @return mixed
     */
    public function getAvgPageLoadTimeDateRange($viewId,$dateStart,$dateEnd) {
        return $this->getDataDateRange($viewId,$dateStart,$dateEnd,'avgPageLoadTime');
    }

}
