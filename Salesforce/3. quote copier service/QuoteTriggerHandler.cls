/**
* @author Alexander Koukovistas
* @date 2020
*
* @description Trigger handler for Quote sObjects. Currently only applies to beforeInsert statements.
*/
public with sharing class QuoteTriggerHandler extends TriggerHandler {

    /**
        Set some constants for the quote name formatting.
     */
    static final String QUOTE_NAME_PREFIX = 'Qt';
    static final String QUOTE_NAME_SEPARATOR = ' - ';
    static final String QUOTE_NAME_TEMPLATE = '{0}' + QUOTE_NAME_SEPARATOR + '{1}';

    /**
     *  Logic that fires before new Quotes are inserted.
     *  We generate their names based on a template {Prefix}{Relative Sequential Order No} - {Opportunity Name}.
     */
    public override void beforeInsert() {

        /*
        *   Init our cheeky values.
        */
        List<Quote> newQuotes = (List<Quote>) Trigger.new;
        Set<Id> opportunityIds = new Set<Id>();


        /*
        *   Grab all the related opportunity IDs into a set we could use for SOQL. We want this to be unique.
        */
        for( Quote newQuote : newQuotes ) {
            opportunityIds.add(newQuote.OpportunityId);
        }

        /*
        * Map the related opportunities and also select the name of the last quote so we can make use of it during the name generation.
        */
        Map<ID, Opportunity> relatedOpportunities =
        new Map<ID, Opportunity>([
            SELECT Id, Name, (
                SELECT Name
                FROM Quotes
                ORDER BY Id DESC
                LIMIT 1)
            FROM Opportunity
            WHERE Id
            IN :opportunityIds]);
        Map<ID, String> lastQuoteNameIndex = new Map<ID,String>();

        // Loop through our available Quotes.
         for ( Quote newQuote : newQuotes ) {

            String lastQuoteName = '';

            if ( relatedOpportunities.get( newQuote.OpportunityId ).Quotes.isEmpty() ){
                if ( lastQuoteNameIndex.containsKey( newQuote.OpportunityId ) ) {
                    lastQuoteName = lastQuoteNameIndex.get( newQuote.OpportunityId );
                }
            } else {
                lastQuoteName = relatedOpportunities.get( newQuote.OpportunityId ).Quotes[0].Name;
            }

            /**
             *  Create the name based on our template and values.
             */
             newQuote.Name = String.format(
                 QUOTE_NAME_TEMPLATE,
                 new List<String> {
                    generateQuoteNumber( lastQuoteName ),
                    relatedOpportunities.get( newQuote.OpportunityId ).Name
                }
            );

             if ( ! relatedOpportunities.get( newQuote.OpportunityId ).Quotes.isEmpty() ){
                relatedOpportunities.get( newQuote.OpportunityId ).Quotes[0].Name = newQuote.Name;
             } else {
                 lastQuoteNameIndex.put( newQuote.OpportunityId , newQuote.Name );
             }

         }
    }

    /**
     * Generate a quote number dynamicaly based on the count of the existing quotes for an opportunity.
     *
     * @param  ID opportunityId the ID of the opportunity this quote is attached to.
     * @param  String lastQuoteName the name of the last quote for the opportunity.
     * @return String the complete quote number to be used with the name.
     */
    private String generateQuoteNumber( String lastQuoteName ) {
        Integer currentNumber = 0;

        /**
         * Do a basic check if the lastQuoteName follows the template and if it does split it and cast it to an int.
         */
        if( lastQuoteName.contains( QUOTE_NAME_PREFIX ) && lastQuoteName.contains( QUOTE_NAME_SEPARATOR ) ){
            currentNumber = integer.valueOf( lastQuoteName.split( QUOTE_NAME_SEPARATOR )[0].split( QUOTE_NAME_PREFIX)[1] ) + 1 ;
        } else {
            currentNumber ++;
        }

        return QUOTE_NAME_PREFIX + currentNumber;
    }
}