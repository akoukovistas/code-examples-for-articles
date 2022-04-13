@isTest
public class QuoteServiceTest {

    @isTest
    static void testValidation(){
        // Given.

        /**
         *  Create two accounts and opportunities so we can test the validation. They need to have different pricebooks.
         */
        Account newAccount = TestDataFactory.getNewAccount();
        insert newAccount;
        Pricebook2 newPricebook = TestDataFactory.getPopulatedPricebook();
        Opportunity newOpportunity = TestDataFactory.getNewOpportunity( 'Test Case Opportunity', newAccount.Id, newPricebook.Id, 'prospecting' );
        insert newOpportunity;

        Account targetAccount = TestDataFactory.getNewAccount();
        insert targetAccount;
        Pricebook2 targetPricebook = TestDataFactory.getPopulatedPricebook();
        Opportunity targetOpportunity = TestDataFactory.getNewOpportunity( 'Test Case Opportunity', targetAccount.Id, targetPricebook.Id, 'prospecting' );
        insert targetOpportunity;

        Quote newQuote = TestDataFactory.getNewQuote( newOpportunity.Id );

        Boolean exceptionThrown = false;

        // When.
        Test.startTest();
        insert newQuote;
        QuoteService qs = new QuoteService();
        Test.stopTest();

        // Then.

        // Try copying and catch the exception that should be returned.
        try {
            qs.copyQuote(newQuote.Id, targetOpportunity.Id);
        }
        catch( Exception e ) {
            exceptionThrown = true;
        }

        System.assertEquals(true, exceptionThrown);

    }

    @isTest
    static void testCopy(){
        // Given.

        /**
         *  Create two accounts and opportunities so we can test the validation. They need to have the same pricebook.
         */
        Account newAccount = TestDataFactory.getNewAccount();
        insert newAccount;
        Pricebook2 newPricebook = TestDataFactory.getPopulatedPricebook();
        Opportunity newOpportunity = TestDataFactory.getNewOpportunity( 'Test Case Opportunity', newAccount.Id, newPricebook.Id, 'prospecting' );
        insert newOpportunity;

        Account targetAccount = TestDataFactory.getNewAccount();
        insert targetAccount;
        Opportunity targetOpportunity = TestDataFactory.getNewOpportunity( 'Test Case Opportunity', targetAccount.Id, newPricebook.Id, 'prospecting' );
        insert targetOpportunity;

        Quote newQuote = TestDataFactory.getNewQuote( newOpportunity.Id );
        newQuote.PriceBook2Id = newPricebook.Id;
        Test.startTest();
        insert newQuote;
        List<QuoteLineItem> quoteLineItems = TestDataFactory.getQuoteLineItems( newPricebook, newQuote );
        insert quoteLineItems;

        // When.

        QuoteService qs = new QuoteService();
        qs.copyQuote(newQuote.Id, targetOpportunity.Id);
        Test.stopTest();

        // Then.

        // Select the last quote from the target opportunity along with the Line Items.
        Quote targetQuote = [
            SELECT Id, Name, PriceBook2Id, Description, (
                SELECT Id
                FROM QuoteLineItems
                )
            FROM Quote
            WHERE OpportunityId = :targetOpportunity.Id
            ORDER BY Id DESC
            LIMIT 1
            ];

        // Check if the copy happened by checking some values.

        System.assert( newQuote.Description == targetQuote.Description );
        System.assert( quoteLineItems.size() == targetQuote.QuoteLineItems.size() );

    }

}