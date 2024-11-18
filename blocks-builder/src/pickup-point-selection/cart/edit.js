import { useBlockProps } from '@wordpress/block-editor';

import { getTxt } from '../global/text';

export const Edit = ({ attributes, setAtrributes }) => {
    const blockProps = useBlockProps();
    return (
        <div {...blockProps} style={{ display: 'block' }}>
            <div className={'wc-block-components-totals-wrapper'}>
                <span clallName={'wc-block-components-totals-item'}>{getTxt('cart_pickup_info')}</span>
            </div>
        </div>
    );
};

export const Save = () => {
    return <div { ...useBlockProps.save() } />;    
};
