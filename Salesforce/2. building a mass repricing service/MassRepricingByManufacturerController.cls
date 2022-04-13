public with sharing class MassRepricingByManufacturerController {

    /**
     * Returns a list of mapped pricebook values to be used with a dual listbox element.
     */
    @AuraEnabled( Cacheable = true )
    public static List<Map<String,Object>> getPricebooks(){

        List<Map<String,Object>> pricebookDetails = new List<Map<String,Object>>();
        List<Pricebook2> pricebooks = [ SELECT Id, Name FROM Pricebook2 ];

        for ( Pricebook2 pb2 : pricebooks ) {
            pricebookDetails.add( new Map<String,Object>{
                'label' => pb2.Name,
                'value' => pb2.Id
            } );
        }

        return pricebookDetails;
    }

    /**
     * Returns a list of mapped manufacturer values to be used with a dual listbox element.
     */
    @AuraEnabled( Cacheable = true )
    public static List<Map<String,String>> getManufacturers(){

        List<Map<String,String>> manufacturerDetails = new List<Map<String,String>>();

        Schema.DescribeFieldResult fieldResult = Product2.Manufacturer__c.getDescribe();
        List<Schema.PicklistEntry> ple = fieldResult.getPicklistValues();

        for( Schema.PicklistEntry pickListVal : ple){
            manufacturerDetails.add( new Map<String,String>{
                'label' => pickListVal.getLabel(),
                'value' => pickListVal.getLabel()
            } );
        }

        return manufacturerDetails;
    }

    /**
     * Begins the repricing process by creating a new MassRepricingService object and returns the batchInstanceID or throws an aura exception.
     */
    @AuraEnabled
    public static Id startRepricing( List<String> pricebookIds, List<String> manufacturers, Integer percentageChange ){

        MassRepricingService msr = new MassRepricingService( pricebookIds, manufacturers, percentageChange );

        try {
            msr.startProcessing();
            return msr.batchInstanceId;
        }
        catch( Exception e ){
            throw new AuraHandledException(e.getMessage());
        }

    }

    /**
     * Returns the status of the job running mapped into a String Map.
     */
    @AuraEnabled
    public static Map<String,String> getStatus( Id jobId ){

        Map<String,String> jobStatus = new Map<String,String>();
        AsyncApexJob jobRunning = MassRepricingService.getStatusbyId( jobId );

        jobStatus.put( 'status', jobRunning.Status );
        jobStatus.put( 'totalItems', String.valueOf( jobRunning.TotalJobItems ) );
        jobStatus.put( 'processedItems', String.valueOf( jobRunning.JobItemsProcessed ) );
        jobStatus.put( 'errorItems', String.valueOf( jobRunning.NumberOfErrors ) );

        return jobStatus;
    }

}