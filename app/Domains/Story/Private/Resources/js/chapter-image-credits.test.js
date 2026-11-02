import { describe, it, expect } from 'vitest';
import { buildLabel, countPendingUploads } from './chapter-image-credits.js';

const labels = {
    create: { one: 'C1 :total/:count/:balance', other: 'C* :total/:count/:balance' },
    edit: { one: 'E1 :count/:balance', other: 'E* :count/:balance' },
    insufficient: ' — solde insuffisant',
};

function fixture({ mode = 'advanced', files = [] } = {}) {
    const root = document.createElement('div');
    root.innerHTML = `<input name="mode" value="${mode}">`;
    files.forEach((n, i) => {
        const input = document.createElement('input');
        input.type = 'file';
        input.name = `blocks[b${i}][file]`;
        root.appendChild(input);
        if (n) {
            const dt = new DataTransfer();
            for (let k = 0; k < n; k++) dt.items.add(new File(['x'], `f${k}.png`, { type: 'image/png' }));
            input.files = dt.files;
        }
    });
    return root;
}

describe('countPendingUploads', () => {
    it('counts only file inputs that hold a file', () => {
        expect(countPendingUploads(fixture({ files: [1, 0, 1] }))).toBe(2);
    });

    it('ignores file inputs that are not block image fields', () => {
        const root = fixture({ files: [1] });
        const other = document.createElement('input');
        other.type = 'file';
        other.name = 'avatar';
        const dt = new DataTransfer();
        dt.items.add(new File(['x'], 'a.png'));
        other.files = dt.files;
        root.appendChild(other);
        expect(countPendingUploads(root)).toBe(1);
    });

    it('returns 0 outside advanced mode', () => {
        expect(countPendingUploads(fixture({ mode: 'simple', files: [1, 1] }))).toBe(0);
    });

    it('drops when an input is cleared', () => {
        const root = fixture({ files: [1, 1] });
        root.querySelector('input[type=file]').value = '';
        expect(countPendingUploads(root)).toBe(1);
    });

    it('drops when the block node is removed', () => {
        const root = fixture({ files: [1, 1] });
        root.querySelector('input[type=file]').remove();
        expect(countPendingUploads(root)).toBe(1);
    });
});

describe('buildLabel', () => {
    it('includes the creation credit on the create form', () => {
        expect(buildLabel({ count: 2, balance: 5, creationCost: 1 }, labels)).toBe('C* 3/2/5');
        expect(buildLabel({ count: 0, balance: 5, creationCost: 1 }, labels)).toBe('C1 1/0/5');
    });

    it('uses the edit wording without creation cost', () => {
        expect(buildLabel({ count: 1, balance: 5, creationCost: 0 }, labels)).toBe('E1 1/5');
        expect(buildLabel({ count: 3, balance: 5, creationCost: 0 }, labels)).toBe('E* 3/5');
    });

    it('states the shortfall in text when the total exceeds the balance', () => {
        expect(buildLabel({ count: 2, balance: 2, creationCost: 1 }, labels)).toContain('solde insuffisant');
        expect(buildLabel({ count: 2, balance: 3, creationCost: 1 }, labels)).not.toContain('solde insuffisant');
        expect(buildLabel({ count: 3, balance: 2, creationCost: 0 }, labels)).toContain('solde insuffisant');
    });
});
