/**
 * Payvessel WooCommerce Blocks Checkout
 */
(function() {
    'use strict';

    const { registerPaymentMethod } = wc.wcBlocksRegistry;
    const { getSetting } = wc.wcSettings;
    const { createElement } = window.React;
    const { decodeEntities } = wp.htmlEntities;

    const settings = getSetting('payvessel_data', {});
    
    const defaultLabel = 'Payvessel';
    const label = decodeEntities(settings.title) || defaultLabel;

    /**
     * Content component
     */
    const Content = () => {
        return createElement(
            'div',
            { className: 'payvessel-block-content' },
            decodeEntities(settings.description || '')
        );
    };

    /**
     * Label component with logo
     */
    const Label = (props) => {
        const { PaymentMethodLabel } = props.components;
        
        return createElement(
            'span',
            { className: 'payvessel-block-label' },
            createElement('img', {
                src: settings.logo_url,
                alt: label,
                style: { 
                    height: '24px', 
                    marginRight: '10px',
                    verticalAlign: 'middle'
                }
            }),
            createElement(PaymentMethodLabel, { text: label })
        );
    };

    /**
     * Payment method configuration
     */
    const payvesselPaymentMethod = {
        name: 'payvessel',
        label: createElement(Label, null),
        content: createElement(Content, null),
        edit: createElement(Content, null),
        canMakePayment: () => true,
        ariaLabel: label,
        supports: {
            features: settings.supports || [],
        },
    };

    // Register the payment method
    registerPaymentMethod(payvesselPaymentMethod);
})();
