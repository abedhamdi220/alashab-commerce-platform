import { useState, useEffect, useRef } from 'react';

export const useDebounce = (value, delay, immediate = false) => {
    const [debouncedValue, setDebouncedValue] = useState(value); //[cite: 9]
    const firstRender = useRef(true);

    useEffect(() => {
        if (immediate && firstRender.current) {
            setDebouncedValue(value);
            firstRender.current = false;
            return;
        }

        const handler = setTimeout(() => {
            setDebouncedValue(value); //[cite: 9]
        }, delay);

        return () => clearTimeout(handler); //[cite: 9]
    }, [value, delay, immediate]); //[cite: 9]

    return debouncedValue; //[cite: 9]
};
