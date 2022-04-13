@RestResource(urlMapping='/repricing/v1/*')
global with sharing class MassRepricingRESTApiV1 {

    // Set our endpoint paths.
    static final string INIT_ENDPOINT_PATH = 'init';
    static final string STATUS_ENDPOINT_PATH = 'check';

    @HttpGet
    global static void getStatus() {

        // Init our request and response values.
        RestRequest request = RestContext.request;
        Map<String, Object> responseData = new Map<String, Object>();
        responseData.put( 'isSuccess', false );
        responseData.put( 'error', 'Unknown error detected, please panic.' );
        Integer statusCode = 401;

        // Check which get route was called
        if ( request.requestURI.contains( STATUS_ENDPOINT_PATH ) ) {
            //Grab the jobId from the URL
            String jobId = request.requestURI.substring( request.requestURI.lastIndexOf( STATUS_ENDPOINT_PATH + '/' ) + STATUS_ENDPOINT_PATH.length() + 1 );
            AsyncApexJob jobRunning = MassRepricingService.getStatusbyId( jobId );

            // If we actually got a job back.
            if ( jobRunning != null ) {

                responseData.put( 'isDone', jobRunning.Status == 'Completed' ? true : false );
                responseData.put( 'isSuccess', jobRunning.NumberOfErrors == 0 ? true : false );
                responseData.put( 'batchesProcessed', jobRunning.JobItemsProcessed );
                responseData.put( 'batchesFailed', jobRunning.NumberOfErrors );
                responseData.put( 'batchesTotal', jobRunning.TotalJobItems );
                statusCode = 200;
                responseData.put( 'isSuccess', true );

            } else {
                responseData.put( 'error', 'No job with an ID of ' + jobId + ' was found' );
            }

        } else {
            // If the request URL did not include any known endpoints.
            responseData.put( 'error', 'Unknown endpoint detected, please confirm your endpoint URL' );
        }

        // Send back the response after JSON serializing the response body.
        RestContext.response.addHeader('Content-Type', 'application/json');
        RestContext.response.statusCode = statusCode;
        RestContext.response.responseBody = Blob.valueOf( JSON.serialize( responseData ) );

    }

    @HttpPost
    global static void startRepricing() {

        // Init our request and response values.
        RestRequest request = RestContext.request;

        Map<String, Object> responseData = new Map<string, object>();
        responseData.put( 'isSuccess', false );
        responseData.put( 'error', 'Unknown error detected, please panic.' );
        Integer statusCode = 401;

        // Check which repricing route was called.
        if ( request.requestURI.contains( INIT_ENDPOINT_PATH ) ) {
            // Map our request body to objects.
            Map<String, Object> reqBodyMapped = ( Map<String, Object> ) JSON.deserializeUntyped( request.requestBody.toString() );

            // Convert the pricebookIds object list into a String list.
            List<Object> tempList = (List<Object>) reqBodyMapped.get('pricebookIds');
            List<String> pricebookIds = new List<String> ();

            for( Object temp : tempList ) {
                pricebookIds.add(  String.valueOf( temp ) );
            }

            // Convert the manufacturer object list into a String list.
            tempList.clear();
            tempList = (List<Object>) reqBodyMapped.get('manufacturers');
            List<String> manufacturerNames = new List<string> ();

            for( Object temp : tempList ) {
                manufacturerNames.add( String.valueOf( temp ) );
            }

            Integer percentChange = Integer.valueOf( reqBodyMapped.get( 'percentChange' ) );

            // Create a mass repricing service object.
            MassRepricingService mrs = new MassRepricingService( pricebookIds, manufacturerNames, percentChange );

            // If it's legit start executing, otherwise return a "useful" error message.
            if ( mrs.validatePropertyValues() ) {

                mrs.startProcessing();
                responseData.put( 'jobId', mrs.batchInstanceId );
                responseData.put( 'isSuccess', true );
                responseData.remove( 'error' );
                statusCode = 200;

            } else {
                responseData.put( 'error', 'Value validation failed, please check your JSON request and try again.' );
            }
        } else {
            // If the request URL did not include any known endpoints.
            responseData.put( 'error', 'Unknown endpoint detected, please confirm your endpoint URL' );
        }

        // Send back the response after JSON serializing the response body.
        RestContext.response.addHeader('Content-Type', 'application/json');
        RestContext.response.statusCode = statusCode;
        RestContext.response.responseBody = Blob.valueOf( JSON.serialize( responseData ) );
    }
}