import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';

import { Block } from './front';
import metadata from './block.json';

registerCheckoutBlock({
   metadata,
   component: Block 
});
