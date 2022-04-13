({
    doInit: function( cmp ) {

        // Run the controller function to get the pricebookId.
        var pricebookIdAction = cmp.get('c.getPricebookId');
        pricebookIdAction.setParams({
            "opportunityId": cmp.get( 'v.recordId' )
        });

        pricebookIdAction.setCallback( this, function( response ) {
            var state = response.getState();
            if ( state === "SUCCESS" ) {

                // Grab the response and redirect to the new Quote that has been created from the copy.
                cmp.set('v.priceBookId', response.getReturnValue() );
                cmp.set( 'v.showSpinner', false );

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

                cmp.set( 'v.showSpinner', false );
                this.handleShowError( cmp, eventSource );

            }
        });

        $A.enqueueAction( pricebookIdAction );

    },

    handleCopy : function( component, event, helper ) {

        // Set our values.
        var recordId = component.get( 'v.recordId' );
        var quoteLookup = component.find( 'strikeQuoteLookup' );
        let button = event.getSource();

        // Disable the button and fire the spinner.
        button.set( 'v.disabled',true );
        component.set( 'v.showSpinner', true );

        // Check if a quote exists.
        if (  quoteLookup.get( 'v.value' )  ) {

            // Hide any visible errors.
            quoteLookup.hideError();

            // Call the helper to process the copy.
            helper.handleCopy( component, recordId, quoteLookup.get( 'v.value' ), button );

        } else {

            // Stop the spinner and re-enable the button while showing an error on the lookup.
            helper.handleHideError( component, button );
            quoteLookup.showError( 'Please select a quote' );

        }


    },
})