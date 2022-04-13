({
    handleCopy : function( cmp, recordId, quoteId, eventSource ) {

        // Prepare the data to send to our Apex controller.
        var copyQuoteAction = cmp.get( "c.handleCopyApex" );
        copyQuoteAction.setParams({
            "sourceQuoteID": quoteId,
            "targetOpportunityId": recordId
        });

        // Call the Apex controller to trigger the copy of the quote.
        copyQuoteAction.setCallback( this, function( response ) {
            var state = response.getState();
            if ( state === "SUCCESS" ) {

                // Grab the response and redirect to the new Quote that has been created from the copy.
                var newQuote = response.getReturnValue();
                var navService = cmp.find( "navService" );
                var pageReference = {
                    "type": "standard__recordPage",
                    "attributes": {
                        "recordId": newQuote.Id,
                        "objectApiName": "Quote",
                        "actionName": "view"
                    }
                };

                navService.navigate( pageReference );

            } else if ( state === "ERROR" ) {
                var errors = response.getError();

                // Check if we have any errors from the callback, set a message in either case and run the required handling.
                if ( errors ) {

                    let errorMessages = [];

                    for( var i = 0; i < errors.length; i++ ) {
                        errorMessages[i] = errors[i].message;
                    }
                    cmp.set( 'v.errorMessages', errorMessages );

                } else {
                    cmp.set( 'v.errorMessages', ['An unknown error has occured, please contact your administrator'] );
                }

                this.handleShowError( cmp, eventSource );

            }
        });

        $A.enqueueAction( copyQuoteAction );
    },

    handleShowError : function( cmp, eventSource ) {
        eventSource.set( 'v.disabled', true );
        cmp.set('v.showSpinner', false );
        cmp.set( 'v.hideErrorPanel', false );
    },

    handleHideError : function( cmp, eventSource ) {
        cmp.set('v.showSpinner', false );
        eventSource.set('v.disabled',false);
    }
})