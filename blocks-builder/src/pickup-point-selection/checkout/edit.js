import { useBlockProps, RichText, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, Disabled } from '@wordpress/components';
import { getSetting } from '@woocommerce/settings';

import { getTxt } from '../global/text';

export const Edit = ({ attributes, setAttributes }) => {
    const { text } = attributes;
    const blockProps = useBlockProps();
    const options = [
        {
            label: getTxt('pickup_select_field_default'),
            value: ''
        }
    ];
    return (
        <div {...blockProps} style={{ display: 'block' }}>
            <InspectorControls>
                <PanelBody title={getTxt('block_options')}>
                    Options for the block go here.
                </PanelBody>
            </InspectorControls>
            <div>
                <RichText
                    value={text || getTxt('pickup_block_title')}
                    onChange={(value) => setAttributes({ text: value })}
                />
            </div>
            <div>
                <Disabled>
                    <SelectControl options={options} />
                </Disabled>
            </div>
        </div>
    );
};

export const Save = ({ attributes }) => {
    const { text } = attributes;
    return (
        <div {...useBlockProps.save()}>
            <RichText.Content value={text} />
        </div>
    );
};
