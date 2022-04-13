@IsTest
public with sharing class MassRepricingServiceTest {

    /**
     * Check that our validation is working
     */
    @IsTest
    static void failValidation(){

        Pricebook2 pricebook = TestDataFactory.getPopulatedPricebook();
        // Our manufacturer's list. Baby Yoda provides.
        String manufacturer = 'Baby Yoda Industries 1';
        Integer percentageChange = 0;


        // When.
        Test.startTest();
        // We're overloading the single value constructor here.
        MassRepricingService mrs = new MassRepricingService( pricebook.Id, manufacturer, percentageChange );
        Test.stopTest();

        // Then.
        System.assertEquals( false, mrs.validatePropertyValues() );
    }

    @IsTest
    static void overloadMainConstructorAndConfirmExecution() {
        // COPY PASTE FOR THE COPY PASTE GOD - OBJECTS FOR THE OBJECT THRONE.
        List<Pricebook2> priceBooks = new List<Pricebook2>();
        priceBooks.add( TestDataFactory.getPopulatedPricebook() );
        priceBooks.add(  TestDataFactory.getPopulatedPricebook() );

        List<Id> priceBookIds = new List<Id>();
        for ( Pricebook2 pb2 : priceBooks ) {
            priceBookIds.add(pb2.Id);
        }

        // Our manufacturer's list. Baby Yoda provides.
        List<String> manufacturers = new List<String>{ 'Baby Yoda Industries 1','Baby Yoda Industries 2' };
        Integer percentageChange = 40;

        // When.
        Test.startTest();
        MassRepricingService mrs = new MassRepricingService( priceBookIds, manufacturers, percentageChange );
        mrs.startProcessing();
        Test.stopTest();

        // Then.
        AsyncApexJob job = mrs.getStatus();

        // Confirm that the job is complete.
        System.assert( job.Status == 'Completed' );
        System.assertEquals( job.TotalJobItems, job.JobItemsProcessed );
    }
}