function switchTab(el, tabName) {
    document.querySelectorAll('.settings-tab').forEach(t => {
        t.classList.remove('active', 'text-brand', 'font-semibold');
        t.classList.add('text-gray-500', 'font-medium');
    });
    el.classList.add('active', 'text-brand', 'font-semibold');
    el.classList.remove('text-gray-500', 'font-medium');
    document.querySelectorAll('[id^="tab-"]').forEach(tab => tab.classList.add('hidden'));
    document.getElementById('tab-' + tabName).classList.remove('hidden');
}

document.addEventListener('click', function(e) {
    const menu = document.getElementById('userMenu');
    const trigger = e.target.closest('.cursor-pointer');
    if (menu && !menu.contains(e.target) && !trigger?.contains(e.target)) {
        menu.classList.add('hidden');
    }
});

function generateKey(pattern = "XX-XXXX-X") {
    const charset = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

    const randomChar = () =>
        charset[Math.floor(Math.random() * charset.length)];

    let key = "";

    for (const ch of pattern) {
        key += ch === "X" ? randomChar() : ch;
    }

    return key;
}

window.addEventListener('load', () => {
    const keyPatternInput = document.getElementById('keyPattern');
    const exampleKey = document.getElementById('exampleKey');

    function updateExampleKey() {
        const prefix = document.getElementById('keyPrefix').value || '';
        const pattern = keyPatternInput.value;

        const key = generateKey(pattern);

        const example = `${prefix}-${key}`;
        exampleKey.textContent = example;
    }

    keyPatternInput.addEventListener('input', updateExampleKey);
    document.getElementById('keyPrefix').addEventListener('input', updateExampleKey);

    updateExampleKey();
})