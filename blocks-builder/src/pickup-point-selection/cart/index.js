import { registerBlockType } from '@wordpress/blocks';

import { iconBox } from '../global/icons';
import metadata from './block.json';
import { Edit, Save } from './edit';

registerBlockType(metadata, {
    icon: iconBox,
    edit: Edit,
    save: Save
});
