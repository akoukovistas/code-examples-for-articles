import { LightningElement, track, wire, api } from 'lwc';
import getPricebooks from '@salesforce/apex/MassRepricingByManufacturerController.getPricebooks';
import getManufacturers from '@salesforce/apex/MassRepricingByManufacturerController.getManufacturers';
import startRepricing from '@salesforce/apex/MassRepricingByManufacturerController.startRepricing';
import getStatus from '@salesforce/apex/MassRepricingByManufacturerController.getStatus';
import { ShowToastEvent } from 'lightning/platformShowToastEvent'
export default class MassRepricingByManufacturer extends LightningElement {

    // Public properties
    @api isPricebook = false;
    @api recordId;

    // Listbox values
    @track pricebookOptions = [];
    @track pricebookValues = [];
    @track manufacturerOptions = [];
    @track manufacturerValues = [];
    @track percentageValue;

    // Mass Repricing values
    @track repricingJobId;
    @track jobStatus = [];
    @track jobPercentageComplete = 0;
    @track progressBarHeading = 'Re-pricing in progress';
    @track progressBarHeadingVariant = 'slds-text-heading_medium';

    // Render-altering variables.
    @track isTab = true;
    @track error;
    @track jobStarted = false;
    @track isJobFinished = false;
    @track showForm = true;
    @track hasErrors = false;

    /**
     * Fires when the component loads and initializes values.
     */
    connectedCallback() {

        // If it is included on a pricebook page, mark tab as false.
        this.recordId ? this.isPricebook = true : false;
        if ( this.isPricebook ) {
            this.isTab = false;
            // Also add the record ID to the pricebookValues array.
            this.pricebookValues[0] = this.recordId;
        }

        // If it's a tab, then populate the pricebooks.
        if (this.isTab) {
            this.populateListbox('Pricebook2');
        }

        this.populateListbox('Manufacturers');
    }

    /**
     * Fires when the start processing button is clicked.
     * Triggers the mass repricing if the values are ok.
     */
    startProcess() {
        if ( this.validateValues() ) {
            // Start the repricing process.
            startRepricing(
                {
                    'pricebookIds' : this.pricebookValues,
                    'manufacturers' : this.manufacturerValues,
                    'percentageChange' : this.percentageValue
                } )
                .then(result => {
                    this.repricingJobId = JSON.parse(JSON.stringify(result));
                    this.jobStarted = true;
                    this.showForm = false;
                    this.updateJobProgress();
                })
                .catch(error => {
                    this.error = error.message;
                    this.fireToast('error');
                });
        } else {
            this.fireToast('error');
        }
    }

    /**
     * Constantly updates the job status & percentage every 3 seconds until it reaches 100%.
     * After that, it calls showSummary and stops the interval.
     */
    updateJobProgress() {
        let updateTimer = setInterval(()=> {
            this.updateStatus();
            if ( this.jobPercentageComplete === 100 ) {
                this.showSummary();
                clearInterval( updateTimer );
            }
        }, 3000);
    }

    /**
     * Grabs the job status from the controller and calculates the percentage.
     */
    updateStatus() {
        getStatus(
            {
                'jobId' : this.repricingJobId
            } )
            .then(result => {
                this.jobStatus = result ? result : [];
                this.jobPercentageComplete = this.jobStatus.totalItems > 0 ? this.jobStatus.processedItems / this.jobStatus.totalItems * 100 : 0;
                // Let it run but check if it becomes complete and we still have 0 batches, mark it as complete.
                if( this.jobStatus.status == 'Completed' ){
                    this.jobPercentageComplete = 100;
                }
            })
            .catch(error => {
                this.error = error.message;
                this.fireToast('error');
            });
    }

    /**
     * After the processing is finished, show the summary.
     */
    showSummary() {

        this.isJobFinished = true;

        // If there are any error items.
        if ( this.jobStatus.errorItems > 0 ) {
            this.hasErrors = true;
            this.progressBarHeading = 'Re-pricing failed.';
            this.progressBarHeadingVariant = 'slds-text-heading_medium  slds-text-color_error';
        } else {
            this.progressBarHeading = 'Success';
            this.progressBarHeadingVariant = 'slds-text-heading_medium  slds-text-color_success';
        }

    }

    /**
     * Used to grab the values from the form inputs.
     *
     * @param {*} event The onchange event fired by the component.
     */
    handleFormInputChange( event ){
        if( event.target.name == 'pricebooks' ){
            this.pricebookValues = event.detail.value;
        }
        else if( event.target.name == 'manufacturers' ){
            this.manufacturerValues = event.detail.value;
        }
        else if( event.target.name == 'percentage' ){
            this.percentageValue = event.target.value;
        }
    }

    /**
     * Used to populate the dual listboxes.
     *
     * @param {*} itemType String that dictates which listbox we're populating.
     */
    populateListbox( itemType ) {
        let items = [];

        if ( itemType === 'Pricebook2' ) {

            // Grab the pricebooks.
            getPricebooks()
                .then( result => {
                    items = JSON.parse( JSON.stringify( result ) );
                    this.pricebookOptions = items;
                })
                .catch( error => {
                    this.error = error.message;
                    this.fireToast( 'error' );
                });

        } else if ( itemType === 'Manufacturers' ) {

            // Grab the manufacturers.
            getManufacturers()
                .then( result => {
                    items = JSON.parse( JSON.stringify( result ) );
                    this.manufacturerOptions = items;
                })
                .catch( error => {
                    this.error = error.message;
                    this.fireToast( 'error' );
                });

        }
    }

    /**
     * Validate the form values before they are sent to the controller.
     */
    validateValues() {
        let goodValues = true;

        if( this.pricebookValues < 1 ) {
            goodValues = false;
            this.error += 'Error validating your selected Pricebooks. \n';
        }

        if( this.manufacturerValues < 1 ) {
            goodValues = false;
            this.error += '\n Error validating your selected Manufacturers. \n';
        }

        if( this.percentageValue == 0 || this.percentageValue < -99 ) {
            goodValues = false;
            this.error += '\n Percentage cannot be ' + this.percentageValue + '. \n';
        }

        return goodValues;
    }

    /**
     * Toaster.
     *
     * @param {*} toastType The type of toast we're firing.
     */
    fireToast( toastType ) {
        let toastTitle;
        let toastMessage;
        let toastVariant;

        switch( toastType ) {

            case 'error':
                toastMessage = this.error;
                toastTitle = 'An error has occurred';
                toastVariant = 'error'
                break;
            default:
                toastMessage = 'An irregular toast has been triggered.';
                toastTitle = 'A Toast';
                toastVariant = 'info'
                break;
        }
        // Show an error toast if it fails validation
        const event = new ShowToastEvent({
            title: toastTitle,
            message: toastMessage,
            variant: toastVariant,
        });
        this.dispatchEvent( event );
        this.error = '';
    }

    /**
     * Refresh view handling for pricebook.
     * Sends an event captured by the wrapping Aura component.
     * Also resets this component.
     */
    fireRefreshView(){
        this.resetComponent();
        this.dispatchEvent(new CustomEvent('refreshPricebook'));

    }

    /**
     * Cheeky reset component action.
     * Resets all values to the default ones so the component can be re-used without refreshing the browser.
     */
    resetComponent() {

        // Component values
        this.pricebookValues = [];
        this.manufacturerOptions = [];
        this.percentageValue = null;
        this.repricingJobId = null;
        this.jobStatus = [];
        this.jobPercentageComplete = 0;
        this.progressBarHeading = 'Re-pricing in progress';
        this.progressBarHeadingVariant = 'slds-text-heading_medium';
        this.error = null;

        // Re-run our init before we draw the first screen again.
        this.connectedCallback();

        // Rendering values.
        this.showForm = true;
        this.hasErrors = false;
        this.isJobFinished = false;
        this.jobStarted = false;
    }
}