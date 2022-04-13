@IsTest
public with sharing class UpdateProductPricesByManufacturerTest {

    /**
     * Test the batch apex with correct test data and
     * compare the final prices from the Job with the old ones calculated with the percentage change
     */
    @isTest
    static void runTheBatchWithCorrectData(){
        // Given.

        // Make a list of pricebooks from the data factory and add their Ids to a list.
        List<Pricebook2> priceBooks = new List<Pricebook2>();
        priceBooks.add( TestDataFactory.getPopulatedPricebook() );
        priceBooks.add(  TestDataFactory.getPopulatedPricebook() );

        List<Id> priceBookIds = new List<Id>();
        for ( Pricebook2 pb2 : priceBooks ) {
            priceBookIds.add(pb2.Id);
        }

        // Our manufacturer's list. Baby Yoda provides.
        List<String> manufacturers = new List<String>{ 'Baby Yoda Industries 1','Baby Yoda Industries 2' };
        Map<ID, PricebookEntry> priceBookEntriesAffected = new Map<ID, PricebookEntry>([ SELECT Id, UnitPrice, Product2.Manufacturer__c FROM PricebookEntry WHERE Pricebook2Id IN :priceBookIds AND Product2.Manufacturer__c IN :manufacturers LIMIT 100 ]);
        Integer percentageChange = 40;

        // When.
        Test.startTest();
        UpdateProductPricesByManufacturer uppbm = new UpdateProductPricesByManufacturer( priceBookIds, manufacturers, percentageChange );
        Id batchId = Database.executeBatch( uppbm, 20 );
        Test.stopTest();

        // Then.

        // Confirm that the prices after the batch process match the initial prices adusted by the percentage.
        List<PricebookEntry> priceBookEntries = [ SELECT Id, UnitPrice, Product2.Manufacturer__c FROM PricebookEntry WHERE Pricebook2Id IN :priceBookIds AND Product2.Manufacturer__c IN :manufacturers LIMIT 100 ];
        for ( PricebookEntry pbe : priceBookEntries ) {
            if ( priceBookEntriesAffected.containsKey( pbe.Id ) ) {
                Decimal oldPrice =  priceBookEntriesAffected.get( pbe.Id ).UnitPrice;
                oldPrice = oldPrice + ( oldPrice * percentageChange)  / 100;
                System.assertEquals( pbe.UnitPrice, oldPrice );
            }

        }
    }
}

