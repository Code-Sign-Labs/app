const generatePasswordBtn = document.getElementById('generatePasswordBtn');
const passwordInput = document.getElementById('password');
const rpasswordInput = document.getElementById('rpassword');
const generatedPasswordDisplay = document.getElementById('generated-password');
const passwordValidationBox = document.getElementById('password-validation');

let hideTimeout = null;

function generatePassword() {
    const lowercase = 'abcdefghijklmnopqrstuvwxyz';
    const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const numbers = '0123456789';
    const special = '!@#$%^&*()-_=+[]{}<>?';

    let charset = lowercase + numbers;
    if (window.REQUIRE_UPPERCASE) charset += uppercase;
    if (window.REQUIRE_SPECIAL) charset += special;

    let password = '';

    if (window.REQUIRE_UPPERCASE) {
        password += uppercase[Math.floor(Math.random() * uppercase.length)];
    }
    if (window.REQUIRE_SPECIAL) {
        password += special[Math.floor(Math.random() * special.length)];
    }
    if(window.REQUIRE_NUMBER) {
        password += numbers[Math.floor(Math.random() * numbers.length)];
    }

    while (password.length < window.MIN_LENGTH * 2) {
        password += charset[Math.floor(Math.random() * charset.length)];
    }

    password = password.split('').sort(() => Math.random() - 0.5).join('');
    return password;
}

function validatePassword(pass) {
    const errors = [];

    if (pass.length < window.MIN_LENGTH) {
        errors.push(`Password must be at least ${window.MIN_LENGTH} characters long`);
    }
    if (window.REQUIRE_UPPERCASE && !/[A-Z]/.test(pass)) {
        errors.push(`Password must contain an uppercase letter`);
    }
    if (window.REQUIRE_SPECIAL && !/[!@#$%^&*()_=+\[\]{}<>?-]/.test(pass)) {
        errors.push(`Password must contain a special character`);
    }
    if (window.REQUIRE_NUMBER && !/[0-9]/.test(pass)) {
        errors.push(`Password must contain a digit`);
    }

    return errors;
}

function showValidation(errors) {
    passwordValidationBox.innerHTML = '';

    if (errors.length === 0) {
        passwordValidationBox.innerHTML = `<span class="text-success">Password is valid</span>`;
        return;
    }

    errors.forEach(err => {
        const div = document.createElement('div');
        div.classList.add('text-danger');
        div.textContent = err;
        passwordValidationBox.appendChild(div);
    });
}

function showGeneratedPassword(pass) {
    generatedPasswordDisplay.textContent = pass;
    generatedPasswordDisplay.style.display = 'inline';
    generatedPasswordDisplay.classList.remove('fade-out');
    generatedPasswordDisplay.classList.add('pulse');

    if (hideTimeout) clearTimeout(hideTimeout);

    hideTimeout = setTimeout(() => {
        generatedPasswordDisplay.classList.remove('pulse');
        generatedPasswordDisplay.classList.add('fade-out');
        setTimeout(() => {
            generatedPasswordDisplay.style.display = 'none';
        }, 1000);
    }, 5000);
}

passwordInput.addEventListener('input', () => {
    const errors = validatePassword(passwordInput.value);
    showValidation(errors);
});

generatePasswordBtn.addEventListener('click', () => {
    const pass = generatePassword();

    passwordInput.value = pass;
    rpasswordInput.value = pass;

    const errors = validatePassword(pass);
    showValidation(errors);

    showGeneratedPassword(pass);
    // set clipboard
    window.navigator.clipboard.writeText(pass);
});