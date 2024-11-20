import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';

import { Block } from './front';
import metadata from './block.json';
import './front.scss';

registerCheckoutBlock({
   metadata,
   component: Block 
});
