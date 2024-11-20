import { registerBlockType } from '@wordpress/blocks';

import { iconBox } from '../global/icons';
import metadata from './block.json';
import { Edit } from './edit';

registerBlockType(metadata, {
    icon: iconBox,
    edit: Edit,
    attributes: {
    }
});
