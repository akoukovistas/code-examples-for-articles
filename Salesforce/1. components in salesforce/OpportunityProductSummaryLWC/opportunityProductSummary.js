import { LightningElement, api, wire, track } from 'lwc';
import getProducts from '@salesforce/apex/OpportunityProductSummaryController.getProducts';
import { ShowToastEvent } from 'lightning/platformShowToastEvent'
import CURRENCY from '@salesforce/i18n/currency';

const columns = [
    { label: 'Manufacturer', fieldName: 'manufacturerName' },
    { label: 'Product Name', fieldName: 'productName' },
    { label: 'Unit Price', fieldName: 'unitPrice', type: 'currency' },
    { label: 'Quantity', fieldName: 'quantity', type:'number' },
    { label: 'Total price', fieldName: 'totalPrice', type: 'currency' },
    { label: 'Date added', fieldName: 'dateAdded', type: 'date' },
];


export default class OpportunityProductSummary extends LightningElement {
    @api recordId;
    currency = CURRENCY;
    @track columns = columns;
    @track productFamilies = [];
    @track error;
    @track hasData = false;
    @track showEmpty = true;

    // Get our products from the apex and wire them into a function.
    @wire( getProducts, { opportunityId: '$recordId' })
    wiredProducts( { error, data } ) {
        // if we have data.
        if( data ){

            // No error.
            this.error = undefined;
            //Has data.
            this.hasData = true;
            // Don't show the empty template.
            this.showEmpty = false;
            // Assign the returned products to an array.
            let returnedProducts = data.products;
            // Get the product families from the returned data by complexifying the array so we can use it in templates.
            this.productFamilies = this.complexifyArray( data.families );

            // Loop through the families.
            this.productFamilies.forEach( element => {
                // Assign the filtered array of products to a temporary data variable.
                let tempData = returnedProducts.filter( e => e.Product2.Family === element.name );
                // Add products to the dataset by converting the product data into usable table data.
                element.dataset = this.populateTableData( tempData );
                // Calculate the total price and save it.
                element.totalprice = this.calculateTotalPrice( element.dataset );
            });

            // Check if we have empty families.
            let emptyFamily = returnedProducts.filter( e => e.Product2.Family === undefined );
            if ( emptyFamily.length > 0 ) {

                // Make a dataset for the empty family products.
                let dataset = this.populateTableData( emptyFamily );
                // Push a new line to the array.
                this.productFamilies.push( {
                    name: 'None',
                    id: this.productFamilies.length + 1,
                    dataset: dataset,
                    totalPrice: this.calculateTotalPrice( dataset ),
                } );
            }

        } else if ( error ) {
            // Has error.
            this.error = error;
            // Show an error toast.
            const event = new ShowToastEvent({
                title: 'Product Loading Error',
                message: this.error,
                variant: 'error',
            });
            this.dispatchEvent( event );
        }
    }

    // Turns a one-dimensional JS array into a complex one for LWC by assigning Ids to each element.
    complexifyArray( initialArray ){
        let complexArray = [];

        initialArray.forEach( function( element , index ){
            complexArray.push( {
                name: element,
                id: index
            } );
        } );
        return complexArray;
    }

    // Convert product data into an object accepted by the data table and insert it into an array.
    populateTableData( incomingData ) {
        let tempData = [];

        incomingData.forEach( datum => {
            tempData.push( {
                // Es lint did not allow ?? so we're going with this visual cancer.
                manufacturerName: datum.Product2.Manufacturer__c !== undefined ? datum.Product2.Manufacturer__c : '',
                productName: datum.Product2.Name !== undefined ? datum.Product2.Name : '',
                unitPrice: datum.UnitPrice !== undefined ? datum.UnitPrice : 0,
                quantity: datum.Quantity !== undefined ? datum.Quantity : 0,
                totalPrice: datum.TotalPrice !== undefined ? datum.TotalPrice : 0,
                dateAdded: datum.ServiceDate !== undefined ? datum.ServiceDate : Date.now(),
            } );
        });

        return tempData;
    }

    // Use reduce on the product data to calculate the total price.
    calculateTotalPrice( productSet ) {
        return productSet.reduce( ( total, item ) => {
            return total + item.totalPrice;
        }, 0 )
    }
}