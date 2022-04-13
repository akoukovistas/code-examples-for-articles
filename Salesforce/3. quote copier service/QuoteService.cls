/**
* @author Alexander Koukovistas
* @date 21/01/2020
*
* @description Service that handles the duplication of a quote between two Opportunities if they are using the same PriceBook.
*/
public with sharing class QuoteService {


    public Quote copyQuote( ID sourceQuoteId, ID targetOpportunityId ){

        /**
        *   Do our initial DB queries to populate our variables.
        *   We copy over the info from the old quote into the newQuote,
        *   nullify the ID so we can insert and update the target Opportunity.
        */
        Quote newQuote = [
            SELECT Id,
            OpportunityId,
            Opportunity.Pricebook2Id,
            Description,
            (
                SELECT Product2Id, PricebookEntryId, UnitPrice, Quantity
                FROM QuoteLineItems
            )
            FROM Quote
            WHERE Id = :sourceQuoteId
        ];

        Id targetPricebookID = [
            SELECT Pricebook2Id
            FROM Opportunity
            WHERE Id = :targetOpportunityId
        ].Pricebook2Id;

        /**
        *   Check if all of our validation is True so we can continue with the copy,
        *   or if it's false and throw an exception.
        */
        if ( newQuote.Opportunity.Pricebook2Id  != null &&
            targetPricebookID != null &&
            newQuote.Opportunity.Pricebook2Id == targetPricebookID   ) {

            /**
            *   Init our variables after the validation has passed.
            *
            *   Then make a collection of QuoteLineItems and create a new one to be inserted.
            */

            newQuote.Id = null;
            newQuote.Pricebook2Id = targetPricebookID;
            newQuote.OpportunityId = targetOpportunityId;
            List<QuoteLineItem> sourceQuoteLineItems = newQuote.QuoteLineItems;
			List<QuoteLineItem> newQuoteLineItems = new List<QuoteLineItem>();

            insert newQuote;

            /**
            *   Loop through the existing QuoteLineItems and create a collection with their data
            *   and the newQuote ID that now exists after the insert.
            */
            for( QuoteLineItem quoteLineItem : sourceQuoteLineItems ){
                newQuoteLineItems.add(
                new QuoteLineItem(
                	QuoteId = newQuote.Id,
                    PricebookEntryId = quoteLineItem.PricebookEntryId,
                    Quantity = quoteLineItem.Quantity,
                    UnitPrice = quoteLineItem.UnitPrice
                ));
            }

            insert newQuoteLineItems;

            return newQuote;

        } else {
            throw new CustomException();
        }

    }
}

