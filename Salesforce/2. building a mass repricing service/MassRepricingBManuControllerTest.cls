/**
 * MassRepricingByManufacturerControllerTest was too long so we're using a shorter name.
 * Hopefully this comment will help you track down the full name.
 */
@IsTest
public with sharing class MassRepricingBManuControllerTest {

    @IsTest
    public static void testGetPricebooks() {
        // Given.
        List<Pricebook2> priceBooks = new List<Pricebook2>();
        priceBooks.add( TestDataFactory.getPopulatedPricebook() );
        priceBooks.add(  TestDataFactory.getPopulatedPricebook() );

        // When.
        Test.startTest();
        List<Map<String,Object>> controllerPricebooks = MassRepricingByManufacturerController.getPricebooks();
        Test.stopTest();

        // Then.

        // We're expecting the list of pricebooks we provided and the one returned from the controller to be the same length.
        System.assertEquals( priceBooks.Size(), controllerPricebooks.Size() );
        // As we're providing a dual-listbox ready array, let's check the correct keys exist too.
        System.assert( controllerPricebooks[0].containsKey( 'label' ) && controllerPricebooks[0].containsKey( 'value' ) );
    }

    @IsTest
    public static void testGetManufacturers() {
        // Given.
        List<Map<String,String>> manufacturerDetails = new List<Map<String,String>>();

        Schema.DescribeFieldResult fieldResult = Product2.Manufacturer__c.getDescribe();
        List<Schema.PicklistEntry> ple = fieldResult.getPicklistValues();

        for( Schema.PicklistEntry pickListVal : ple){
            manufacturerDetails.add( new Map<String,String>{
                'label' => pickListVal.getLabel(),
                'value' => pickListVal.getLabel()
            } );
        }
        // When.
        Test.startTest();
        List<Map<String,String>> controllerManufacturers = MassRepricingByManufacturerController.getManufacturers();
        Test.stopTest();

        // Then.
        // We're expecting the list we generated from the metadata and the one from the controller to be the same.
        System.assertEquals( manufacturerDetails, controllerManufacturers );
    }

    @IsTest
    public static void testStartRepricing() {
        // Given.
        // COPY PASTE FOR THE COPY PASTE GOD - OBJECTS FOR THE OBJECT THRONE.
        List<Pricebook2> priceBooks = new List<Pricebook2>();
        priceBooks.add( TestDataFactory.getPopulatedPricebook() );
        priceBooks.add(  TestDataFactory.getPopulatedPricebook() );

        List<String> priceBookIds = new List<Id>();
        for ( Pricebook2 pb2 : priceBooks ) {
            priceBookIds.add( String.valueOf( pb2.Id ) );
        }

        // Our manufacturer's list. Baby Yoda provides.
        List<String> manufacturers = new List<String>{ 'Baby Yoda Industries 1','Baby Yoda Industries 2' };
        Integer percentageChange = 40;

        // When.
        Test.startTest();
        Id batchProcessId = MassRepricingByManufacturerController.startRepricing( priceBookIds, manufacturers, percentageChange );
        Test.stopTest();

        // Then.
        // Get the started job.
        AsyncApexJob job = [ SELECT Id, Status FROM AsyncApexJob WHERE Id = :batchProcessId ];

        // Confirm that the job exists and has a status.
        System.assert( job.Status.length() > 0 );
    }

    @IsTest
    public static void testGetStatus() {
        // Given.
        // COPY PASTE FOR THE COPY PASTE GOD - OBJECTS FOR THE OBJECT THRONE.
        List<Pricebook2> priceBooks = new List<Pricebook2>();
        priceBooks.add( TestDataFactory.getPopulatedPricebook() );
        priceBooks.add(  TestDataFactory.getPopulatedPricebook() );

        List<String> priceBookIds = new List<Id>();
        for ( Pricebook2 pb2 : priceBooks ) {
            priceBookIds.add( String.valueOf( pb2.Id ) );
        }

        // Our manufacturer's list. Baby Yoda provides.
        List<String> manufacturers = new List<String>{ 'Baby Yoda Industries 1','Baby Yoda Industries 2' };
        Integer percentageChange = 40;

        // When.
        Test.startTest();
        Id batchProcessId = MassRepricingByManufacturerController.startRepricing( priceBookIds, manufacturers, percentageChange );
        Test.stopTest();

        // Then.
        // Get the started job.
        AsyncApexJob job = [ SELECT Id, Status FROM AsyncApexJob WHERE Id = :batchProcessId ];
        Map<String,String> statusFromController = MassRepricingByManufacturerController.getStatus( batchProcessId );

        // Confirm that the job status from the query is the same as the one returned from the controller function.
        System.assert( job.Status == statusFromController.get('status') );
    }
}