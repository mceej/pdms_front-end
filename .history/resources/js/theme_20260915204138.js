// resources/js/theme.js
import { definePreset } from '@primevue/themes';
import Aura from '@primevue/themes/aura';

export const DSWDPreset = definePreset(Aura, {
    semantic: {
        primary: {
            50: '#eef4fd',
            100: '#d7e6fa',
            200: '#aecdf5',
            300: '#7fb0ee',
            400: '#3f7fdb',
            500: '#0649b9', // matches your header gradient end
            600: '#073a91', // matches your header gradient start
            700: '#062f76',
            800: '#052557',
            900: '#041b3d',
            950: '#02101f',
        },
    },
    components: {
        button: {
            colorScheme: {
                light: {
                    root: {
                        primary: {
                            background: '{primary.600}',
                            hoverBackground: '{primary.700}',
                        },
                    },
                },
            },
        },
        progressbar: {
            colorScheme: {
                light: {
                    root: {
                        background: '#e4ebf2',
                    },
                    value: {
                        background: '#2588d2',
                    },
                },
            },
        },
    },
});