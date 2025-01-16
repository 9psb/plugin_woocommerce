// Get settings for the 9payment gateway from the global wcSettings object
const settings = window.wc.wcSettings.getSetting('9payment_data', {});

// Fallback labels and descriptions if settings are not set
const label = window.wp.htmlEntities.decodeEntities(settings.title) || window.wp.i18n.__('Pay with 9PSB', 'woocommerce-9psb');
const description = window.wp.htmlEntities.decodeEntities(settings.description || 'Secure your transactions with 9PSB.');
const icon = settings.icon || 'https://baastest.9psb.com.ng/gateway/assets/banktitle-D-nB80hl.svg'; // Fallback icon
const paymentFields = settings.payment_fields ? settings.payment_fields.split(',').map(field => field.trim()) : [];

// Content component to display payment method details and input fields
const Content = () => {
    return (
        window.wp.element.createElement(
            'div',
            null,
            [
                // Display the payment method icon
                window.wp.element.createElement('img', {
                    src: icon,
                    alt: '9PSB Payment Icon',
                    style: { width: '300px', height: 'auto' },
                }),
                window.wp.element.createElement('p', null, description),
                ...paymentFields.map((field, index) => (
                    window.wp.element.createElement('div', { key: index },
                        window.wp.element.createElement('label', null, field || 'Field Label'),
                        window.wp.element.createElement('input', {
                            type: 'text',
                            placeholder: `Enter ${field}`,
                            value: '',
                        })
                    )
                ))
            ]
        )
    );
};

// EditContent component to display a placeholder when editing the block
const EditContent = () => {
    return window.wp.htmlEntities.decodeEntities('9PSB Payment Method (Edit Mode)');
};

// The payment method object that registers the block with WooCommerce Blocks
const Block_Gateway = {
    name: '9payment',
    label: label,
    content: Object(window.wp.element.createElement)(Content, null),
    edit: Object(window.wp.element.createElement)(EditContent, null),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports || ['products'],
    },
};

// Register the payment method with WooCommerce Blocks
window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway);
