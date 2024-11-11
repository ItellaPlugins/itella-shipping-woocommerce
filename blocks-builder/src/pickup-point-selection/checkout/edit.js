import { useBlockProps, RichText, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, Disabled } from '@wordpress/components';
import { getSetting } from '@woocommerce/settings';

import { txt } from '../global/text';

export const Edit = ({ attributes, setAttributes }) => {
    const { text } = attributes;
    const blockProps = useBlockProps();
    const options = [
        {
            label: txt.pickup_select_field_default,
            value: ''
        }
    ];
    return (
        <div {...blockProps} style={{ display: 'block' }}>
            <InspectorControls>
                <PanelBody title={txt.block_options}>
                    Options for the block go here.
                </PanelBody>
            </InspectorControls>
            <div>
                <RichText
                    value={text || txt.pickup_block_title}
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
