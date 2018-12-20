Google Analytics API v4 Symfony bundle
======================================

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/da6423cf-b198-402a-8d23-e82e7833f9f6/big.png)](https://insight.sensiolabs.com/projects/da6423cf-b198-402a-8d23-e82e7833f9f6)

[![Latest Stable Version](https://poser.pugx.org/mediafigaro/google-analytics-api-symfony/v/stable)](https://packagist.org/packages/mediafigaro/google-analytics-api-symfony)
[![Total Downloads](https://poser.pugx.org/mediafigaro/google-analytics-api-symfony/downloads)](https://packagist.org/packages/mediafigaro/google-analytics-api-symfony)
[![Latest Unstable Version](https://poser.pugx.org/mediafigaro/google-analytics-api-symfony/v/unstable)](https://packagist.org/packages/mediafigaro/google-analytics-api-symfony)
[![License](https://poser.pugx.org/mediafigaro/google-analytics-api-symfony/license)](https://packagist.org/packages/mediafigaro/google-analytics-api-symfony)
[![Monthly Downloads](https://poser.pugx.org/mediafigaro/google-analytics-api-symfony/d/monthly)](https://packagist.org/packages/mediafigaro/google-analytics-api-symfony)
[![Daily Downloads](https://poser.pugx.org/mediafigaro/google-analytics-api-symfony/d/daily)](https://packagist.org/packages/mediafigaro/google-analytics-api-symfony)
[![composer.lock](https://poser.pugx.org/mediafigaro/google-analytics-api-symfony/composerlock)](https://packagist.org/packages/mediafigaro/google-analytics-api-symfony)

# use

At MEDIA.figaro http://media.figaro.fr, the advertising department of the french newspaper Le Figaro and part of the Figaro Group (CCM Benchmark), we use this bundle to monitor our digital platforms with Google Analytics. 

It's a simple package that wraps the Google Analytics API version 4, and that gives you all the information to go straight to the point of getting some main metrics from GA.

To be able to use it, you have to setup a project on Google Console for Google Analytics, get the json key, then configure this package by setting the path for it. You'll have to add the developer email defined into the Google Console to the GA views to authorize it, otherwise the view won't be accessible through the API. 

You can use the debug routes to go live and test a profile (ex id : 111111111, here with [Docker](https://www.docker.com/)) :

http://symfony.dev/app_dev.php/analytics-api/111111111 

![debug](doc/debug.png)

# installation

    composer require mediafigaro/google-analytics-api-symfony
    
add to /app/AppKernel.php :

    $bundles = [
        ...
        new MediaFigaro\GoogleAnalyticsApi\GoogleAnalyticsApi(),
    ];

# configuration

    google_analytics_api.google_analytics_json_key

Set the relative path for your json key (set it on your server, better not into your repository) from execution path, ex: /data/analytics/analytics-27cef1a4c0fd.json.

/app/config/parameters.yml

    google_analytics_json_key: "../data/analytics/analytics-27cef1a4c0fd.json"

/app/config/config.yml

    google_analytics_api:
        google_analytics_json_key: "%google_analytics_json_key%"
        
# Google API key

Generate the json file from https://console.developers.google.com/start/api?id=analyticsreporting.googleapis.com&credential=client_key by creating a project, check the documentation : https://developers.google.com/analytics/devguides/reporting/core/v4/quickstart/service-php.

# Google Analytics API v4

List of metrics for report building with search engine : https://developers.google.com/analytics/devguides/reporting/core/dimsmets eg. ga:sessions, ga:visits, ga:bounceRate ...

Objects : https://github.com/google/google-api-php-client-services/tree/master/AnalyticsReporting

(example : ReportData object : https://github.com/google/google-api-php-client-services/blob/master/AnalyticsReporting/ReportData.php)

Samples : https://developers.google.com/analytics/devguides/reporting/core/v4/samples

# debug

Add the debug routes for development purposes :

/app/config/routing_dev.yml

    _google_analytics_api:
        resource: "@GoogleAnalyticsApi/Resources/config/routing_dev.yml"

http://symfony.dev/app_dev.php/analytics-api/000000000 

000000000 = profile id that you can find in the analytics URL, p000000000 :

https://analytics.google.com/analytics/web/?hl=en&pli=1#management/Settings/a222222222w1111111111p000000000/%3Fm.page%3DPropertySettings/

Result of this debug page :

![debug](doc/debug.png)

# errors

In that 403 error case, follow the link and authorize the API v4.

    ...
        "message": "Google Analytics Reporting API has not been used in project xxxxxx-xxxxxx-000000 
        before or it is disabled. Enable it by visiting 
        https://console.developers.google.com/apis/api/analyticsreporting.googleapis.com/overview?project=xxxxxx-xxxxxx-000000 
        then retry. If you enabled this API recently, wait a few minutes for the action to propagate 
        to our systems and retry.",
        "domain": "global",
        "reason": "forbidden"
    }
    ],
    "status": "PERMISSION_DENIED"

# example

Call the service :

    $analyticsService = $this->get('google_analytics_api.api');
    $analytics = $analyticsService->getAnalytics();
    
Use the method helpers to get the main metrics within a date range :
    
    $viewId = '000000000'; // set your view id
    
    // get some metrics (last 30 days, date format is yyyy-mm-dd)
    $sessions = $analyticsService->getSessionsDateRange($viewId,'30daysAgo','today');
    $bounceRate = $analyticsService->getBounceRateDateRange($viewId,'30daysAgo','today');
    $avgTimeOnPage = $analyticsService->getAvgTimeOnPageDateRange($viewId,'30daysAgo','today');
    $pageViewsPerSession = $analyticsService->getPageviewsPerSessionDateRange($viewId,'30daysAgo','today');
    $percentNewVisits = $analyticsService->getPercentNewVisitsDateRange($viewId,'30daysAgo','today');
    $pageViews = $analyticsService->getPageViewsDateRange($viewId,'30daysAgo','today');
    $avgPageLoadTime = $analyticsService->getAvgPageLoadTimeDateRange($viewId,'30daysAgo','today');

# contribution

You are welcome to contribute to this small Google Analytics v4 wrapper, to create more helpers or more.

# more tools

Try the Symfony Debug Toolbar Git : https://github.com/kendrick-k/symfony-debug-toolbar-git and the docker Service Oriented Architecture for Symfony : https://github.com/mediafigaro/docker-symfony.

# tutorial

French [tutorial](https://www.supinfo.com/articles/single/2423-symfony-27-integration-google-analytics) by Jérémy PERCHE, SUPINFO student.
