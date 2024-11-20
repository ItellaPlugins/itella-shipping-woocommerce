import { useEffect, useState } from '@wordpress/element';

export const compareObjects = ( obj1, obj2 ) => {
   return JSON.stringify(obj1) === JSON.stringify(obj2);
}

export const useDebounce = ( cb, delay ) => {
   const [debounceValue, setDebounceValue] = useState(cb);
   useEffect(() => {
      const handler = setTimeout(() => {
         setDebounceValue(cb);
      }, delay);

      return () => {
         clearTimeout(handler);
      };
   }, [cb, delay]);
   return debounceValue;
};
