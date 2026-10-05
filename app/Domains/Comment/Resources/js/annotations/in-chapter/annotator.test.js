import { describe, it, expect } from 'vitest';
import { annotatorName } from './annotator.js';

describe('annotatorName', () => {
    it('is the profile display name', () => {
        expect(annotatorName({ display_name: 'Lecteur' }, 'Esperluette disparue')).toBe('Lecteur');
    });

    it('falls back to the unknown user for an anonymised profile', () => {
        expect(annotatorName(null, 'Esperluette disparue')).toBe('Esperluette disparue');
        expect(annotatorName(undefined, 'Esperluette disparue')).toBe('Esperluette disparue');
        expect(annotatorName({ display_name: '' }, 'Esperluette disparue')).toBe('Esperluette disparue');
        expect(annotatorName({ display_name: null }, 'Esperluette disparue')).toBe('Esperluette disparue');
    });
});
