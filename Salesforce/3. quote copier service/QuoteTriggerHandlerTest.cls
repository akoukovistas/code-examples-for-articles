@isTest
public class QuoteTriggerHandlerTest {

    static final string EXPECTED_QUOTE_NAME_PATTERN = '^Qt\\d*\\s-\\s.*';

    @isTest
    static void testNameGenerator(){
        // Given.
        Account newAccount = TestDataFactory.getNewAccount();
        Insert newAccount;

        Pricebook2 newPricebook = TestDataFactory.getPopulatedPricebook();

        Opportunity newOpportunity = TestDataFactory.getNewOpportunity( 'Test Case Opportunity', newAccount.Id, newPricebook.Id, 'prospecting' );
        insert newOpportunity;

        Quote newQuote = TestDataFactory.getNewQuote( newOpportunity.Id );

        // When.
        Test.startTest();
        Insert newQuote;
        Test.stopTest();

        // Then.
        Quote testedQuote = [
            SELECT Id, Name
            FROM Quote
            WHERE Id = :newQuote.Id
            ];

        // Check if the testedQuote's name is matching our naming pattern.
        System.assert( Pattern.matches( EXPECTED_QUOTE_NAME_PATTERN, testedQuote.Name));
    }
}