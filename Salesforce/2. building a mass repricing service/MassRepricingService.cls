/**
 * This class is used essentially as an adaptor for mass updating price services.
 * For now it is only updating products based on manufacturers but more can be added.
 */
public with sharing class MassRepricingService {

    private List<Id> pricebookIdsAffected;
    private List<String> manufacturersAffected;
    private Integer percentageChange;
    public Id batchInstanceId {get; set;}

    /**
     * Create our four constructors. Only two are currently meant to be used but we add the rest for completion.
     */

    // Main constructor to be used that can accept our values as they are intented to be.
    public MassRepricingService( List<Id> pricebookIdsAffected, List<String> manufacturersAffected, Integer percentageChange ) {
        this.pricebookIdsAffected = pricebookIdsAffected;
        this.manufacturersAffected = manufacturersAffected;
        this.percentageChange = percentageChange;
    }

    // If the pricebook Ids are sent as a list of strings. Used mostly to avoid a conversion to IDs, open to errors and sadness.
    public MassRepricingService( List<String> pricebookIdsAffected, List<String> manufacturersAffected, Integer percentageChange ) {
        this.pricebookIdsAffected = new List<Id>();

        // If we are passed a list of strings instead of Ids, convert and then resume normally.
        for ( String pricebookIdAffected : pricebookIdsAffected ) {
            this.pricebookIdsAffected.add( Id.valueOf( pricebookIdAffected ) );
        }

        this.manufacturersAffected = manufacturersAffected;
        this.percentageChange = percentageChange;
    }

    // If a singular pricebook ID is sent
    public MassRepricingService( Id pricebookIdsAffected, List<String> manufacturersAffected, Integer percentageChange ) {
        this.pricebookIdsAffected = new List<Id> { pricebookIdsAffected };
        this.manufacturersAffected = manufacturersAffected;
        this.percentageChange = percentageChange;
    }

    // If a singular pricebook and manufacturer is sent.
    public MassRepricingService( Id pricebookIdsAffected, String manufacturersAffected, Integer percentageChange ) {
        this.pricebookIdsAffected = new List<Id> { pricebookIdsAffected };
        this.manufacturersAffected = new List<String> { manufacturersAffected };
        this.percentageChange = percentageChange;
    }

    // If a singular manufacturer is sent.
    public MassRepricingService( List<Id> pricebookIdsAffected, String manufacturersAffected, Integer percentageChange ) {
        this.pricebookIdsAffected = pricebookIdsAffected;
        this.manufacturersAffected = new List<String> { manufacturersAffected };
        this.percentageChange = percentageChange;
    }

    /**
     * Used to validate that the values passsed to the constructor can be used.
     *
     * More complex validation can be added here if necessary.
     */
    public Boolean validatePropertyValues() {

        Boolean usable = false;

        // If we have items in the manufacturers and pricebooks and the percentage change is not 0 or less than -99.
        if ( this.manufacturersAffected.size() > 0 &&
        this.pricebookIdsAffected.size() > 0 &&
        this.percentageChange <> 0 &&
        this.percentageChange > -99 ) {
            usable = true;
        }

        return usable;
    }

    // This is the main method essentially, it calls the batch apex.
    public void startProcessing() {

        if ( this.validatePropertyValues() ){

            UpdateProductPricesByManufacturer manufacturerPriceUpdate =
            new UpdateProductPricesByManufacturer(
                this.pricebookIdsAffected,
                this.manufacturersAffected,
                this.percentageChange );
                this.batchInstanceId = Database.executeBatch( manufacturerPriceUpdate, 20 );

        } else {

            // Shortcut for the front-end.
            throw new AuraHandledException( 'Validation failed, please check your selected values and try again' );

        }
    }

    // Get the status
    public AsyncApexJob getStatus() {

        AsyncApexJob jobRunning =
            [
                SELECT ID,JobItemsProcessed,NumberOfErrors,Status,TotalJobItems
                FROM AsyncApexJob
                WHERE ID = :this.batchInstanceId
            ];

        return jobRunning;
    }

    // Get the status by Id
    public static AsyncApexJob getStatusbyId( ID jobId ) {

        AsyncApexJob jobRunning =
            [
                SELECT ID,JobItemsProcessed,NumberOfErrors,Status,TotalJobItems
                FROM AsyncApexJob
                WHERE ID = :jobId
            ];

        return jobRunning;
    }

}