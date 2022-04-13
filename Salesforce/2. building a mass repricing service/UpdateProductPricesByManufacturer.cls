global class UpdateProductPricesByManufacturer implements Database.Batchable<sObject>, Database.Stateful {

    static final Integer MAXIMUM_DISCOUNT = -99;
    private List<Id> pricebookIdsList;
    private List<String> manufacturersList;
    private Integer percentageChange;

    public UpdateProductPricesByManufacturer(  List<Id> pricebookIdsList, List<String> manufacturersList, Integer percentageChange ) {
        this.pricebookIdsList = pricebookIdsList;
        this.manufacturersList = manufacturersList;
        this.percentageChange = percentageChange;
    }

    // instance member to retain state across transactions
    global Integer succesfulRecords = 0;
    global Integer failedRecords = 0;

    global Database.QueryLocator start( Database.BatchableContext bc ) {

        // Get the entries where the manufacturer is part of the list and they are not using the standard PB prices.
        String query = 'SELECT ID, Product2.Manufacturer__c, UnitPrice FROM PricebookEntry WHERE Product2.Manufacturer__c IN :manufacturersList AND Pricebook2Id IN :pricebookIdsList AND UseStandardPrice = false';
        return Database.getQueryLocator( query );
    }

    global void execute( Database.BatchableContext bc, List<PricebookEntry> scope ){

        // Process each batch of records.
        for (PricebookEntry pbe : scope) {

            // 99 problems but a discount aint one. - On a serious note, if they discount more than the maximum cap it at that.
            if( this.percentageChange < MAXIMUM_DISCOUNT ){
                this.percentageChange = MAXIMUM_DISCOUNT;
            }

            // Calculate the new unit price.
            pbe.UnitPrice = pbe.UnitPrice + ( pbe.UnitPrice * this.percentageChange ) / 100;
        }

        update scope;
    }

    global void finish( Database.BatchableContext bc ){
        // Silence is golden.
    }
}