@IsTest
public with sharing class MassRepricingRestApiV1Test {

    @IsTest
    static void testPostRequest() {
        //Given.

        // COPY PASTE FOR THE COPY PASTE GOD - OBJECTS FOR THE OBJECT THRONE.
        List<Pricebook2> priceBooks = new List<Pricebook2>();
        priceBooks.add( TestDataFactory.getPopulatedPricebook() );
        priceBooks.add(  TestDataFactory.getPopulatedPricebook() );

        List<Id> priceBookIds = new List<Id>();
        for ( Pricebook2 pb2 : priceBooks ) {
            priceBookIds.add( pb2.Id );
        }

        // Our manufacturer's list. Baby Yoda provides.
        List<String> manufacturers = new List<String>{ 'Baby Yoda Industries 1','Baby Yoda Industries 2' };

        Map<ID, PricebookEntry> priceBookEntriesAffected = new Map<ID, PricebookEntry>([ SELECT Id, UnitPrice, Product2.Manufacturer__c FROM PricebookEntry WHERE Pricebook2Id IN :priceBookIds AND Product2.Manufacturer__c IN :manufacturers LIMIT 100 ]);
        Integer percentageChange = 40;
        RestRequest req = new RestRequest();
        RestResponse res = new RestResponse();
        Map<String, Object> reqBody = new Map<String, Object>();
        reqBody.put('pricebookIds', priceBookIds );
        reqBody.put('manufacturers', manufacturers );
        reqBody.put('percentChange', percentageChange );


        // When.
        Test.startTest();

        // Assign our request headers and values.
        req.requestURI = 'https://eu29.salesforce.com/services/apexrest/repricing/v1/init/';
        req.httpMethod = 'POST';
        req.addHeader('Content-Type', 'application/json');
        req.requestBody = Blob.valueOf( JSON.serialize( reqBody ) );

        // Assign the contexts and start repricing.
        RestContext.request = req;
        RestContext.response = res;
        MassRepricingRESTApiV1.startRepricing();
        Test.stopTest();

        // Then.
        Integer responseStatus = res.statusCode;
        Map<String,Object> responseBody = ( Map<String, Object> ) JSON.deserializeUntyped( res.responseBody.toString() );

        // The API has to return 200 and isSuccess true if it runs with correct data.
        System.assertEquals( 200, responseStatus );
        System.assertEquals( true, responseBody.get( 'isSuccess' ) );
    }

    @IsTest
    static void testGetRequest() {
        //Given.

        // COPY PASTE FOR THE COPY PASTE GOD - OBJECTS FOR THE OBJECT THRONE.
        List<Pricebook2> priceBooks = new List<Pricebook2>();
        priceBooks.add( TestDataFactory.getPopulatedPricebook() );
        priceBooks.add(  TestDataFactory.getPopulatedPricebook() );

        List<Id> priceBookIds = new List<Id>();
        for ( Pricebook2 pb2 : priceBooks ) {
            priceBookIds.add( pb2.Id );
        }

        // Our manufacturer's list. Baby Yoda provides.
        List<String> manufacturers = new List<String>{ 'Baby Yoda Industries 1','Baby Yoda Industries 2' };

        Map<ID, PricebookEntry> priceBookEntriesAffected = new Map<ID, PricebookEntry>([ SELECT Id, UnitPrice, Product2.Manufacturer__c FROM PricebookEntry WHERE Pricebook2Id IN :priceBookIds AND Product2.Manufacturer__c IN :manufacturers LIMIT 100 ]);
        Integer percentageChange = 40;
        //Create our Post Request
        RestRequest req = new RestRequest();
        RestResponse res = new RestResponse();
        Map<String, Object> reqBody = new Map<String, Object>();
        reqBody.put('pricebookIds', priceBookIds );
        reqBody.put('manufacturers', manufacturers );
        reqBody.put('percentChange', percentageChange );

        // Create our Get Request
        RestRequest reqGet = new RestRequest();
        RestResponse resGet = new RestResponse();


        // When.
        Test.startTest();

        // Assign our request headers and values for the POST
        req.requestURI = 'https://eu29.salesforce.com/services/apexrest/repricing/v1/init/';
        req.httpMethod = 'POST';
        req.addHeader('Content-Type', 'application/json');
        req.requestBody = Blob.valueOf( JSON.serialize( reqBody ) );

        // Assign the contexts and start repricing ( POST )
        RestContext.request = req;
        RestContext.response = res;
        MassRepricingRESTApiV1.startRepricing();

        // Process our values from the POST to do a GET
        Map<String,Object> responseBody = ( Map<String, Object> ) JSON.deserializeUntyped( res.responseBody.toString() );
        Id responseJobId = Id.valueOf( String.valueOf( responseBody.get( 'jobId' ) ) );

        // Add values to our GET request.
        reqGet.requestURI = 'https://eu29.salesforce.com/services/apexrest/repricing/v1/check/' + responseJobId;
        reqGet.httpMethod = 'GET';
        RestContext.request = reqGet;
        RestContext.response = resGet;
        MassRepricingRESTApiV1.getStatus();
        Test.stopTest();

        // Then.
        Integer responseStatus = resGet.statusCode;
        Map<String,Object> responseBodyGet = ( Map<String, Object> ) JSON.deserializeUntyped( resGet.responseBody.toString() );

        // The API has to return 200 and isSuccess true if it runs with correct data.
        System.assertEquals( 200, responseStatus );
        System.assertEquals( true, responseBodyGet.get( 'isSuccess' ) );
    }
}