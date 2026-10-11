import assert from 'node:assert/strict';
import test from 'node:test';
import { translator } from '../../resources/js/lib/platform-i18n.ts';

await test('workspace locale selects Bengali labels', () => {
    assert.equal(translator('bn')('save'), 'সংরক্ষণ');
    assert.equal(translator('bn')('qualified'), 'যোগ্য');
});
await test('English labels and unknown-key fallback remain strings', () => {
    assert.equal(translator('en')('save'), 'Save');
    assert.equal(
        translator('bn')('industry-specific-key'),
        'industry-specific-key',
    );
});
await test('prototype keys cannot escape the dictionary lookup', () => {
    assert.equal(translator('en')('constructor'), 'constructor');
    assert.equal(translator('bn')('__proto__'), '__proto__');
});
await test('CRM workflow labels are localized', () => {
    assert.equal(translator('bn')('convert'), 'কাস্টমার বানান');
    assert.equal(translator('bn')('timeline'), 'কাজের ইতিহাস');
    assert.equal(translator('en')('customers'), 'Customers');
});
