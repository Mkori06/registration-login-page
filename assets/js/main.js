/**
 * AuraAuth - Dynamic Interactions & Form Validation
 */

document.addEventListener('DOMContentLoaded', () => {
    initAuthTabs();
    initPasswordToggles();
    initPasswordStrength();
    initPasswordMatch();
    initDashboardTabs();
});

/**
 * Handle switching between Login and Registration tabs
 */
function initAuthTabs() {
    const tabBtns = document.querySelectorAll('.auth-tabs .tab-btn');
    const loginForm = document.getElementById('login-form-wrapper');
    const registerForm = document.getElementById('register-form-wrapper');

    if (!tabBtns.length || !loginForm || !registerForm) return;

    // Check URL parameters for active tab
    const urlParams = new URLSearchParams(window.location.search);
    const requestedTab = urlParams.get('tab');
    if (requestedTab === 'register') {
        switchTab('register');
    }

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-target');
            switchTab(target);
        });
    });

    function switchTab(target) {
        tabBtns.forEach(b => b.classList.remove('active'));
        const activeBtn = document.querySelector(`.tab-btn[data-target="${target}"]`);
        if (activeBtn) activeBtn.classList.add('active');

        if (target === 'register') {
            loginForm.classList.remove('active');
            registerForm.classList.add('active');
            window.history.replaceState(null, '', '?tab=register');
        } else {
            registerForm.classList.remove('active');
            loginForm.classList.add('active');
            window.history.replaceState(null, '', '?tab=login');
        }
    }
}

/**
 * Password Visibility Toggle
 */
function initPasswordToggles() {
    const toggles = document.querySelectorAll('.password-toggle');
    toggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = toggle.getAttribute('data-for');
            const input = document.getElementById(targetId);
            const icon = toggle.querySelector('i');

            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
}

/**
 * Live Password Strength Checker
 */
function initPasswordStrength() {
    const passwordInput = document.getElementById('reg-password');
    const strengthWrap = document.getElementById('reg-strength-wrap');
    const fill = document.getElementById('reg-strength-fill');
    const label = document.getElementById('reg-strength-label');

    const checkLength = document.getElementById('check-length');
    const checkUpper = document.getElementById('check-upper');
    const checkNumber = document.getElementById('check-number');
    const checkSpecial = document.getElementById('check-special');

    if (!passwordInput || !strengthWrap || !fill || !label) return;

    passwordInput.addEventListener('input', () => {
        const val = passwordInput.value;
        if (!val) {
            strengthWrap.classList.remove('active');
            return;
        }

        strengthWrap.classList.add('active');

        // Test rules
        const hasLength = val.length >= 8;
        const hasUpper = /[A-Z]/.test(val);
        const hasNumber = /[0-9]/.test(val);
        const hasSpecial = /[^A-Za-z0-9]/.test(val);

        updateCheckItem(checkLength, hasLength);
        updateCheckItem(checkUpper, hasUpper);
        updateCheckItem(checkNumber, hasNumber);
        updateCheckItem(checkSpecial, hasSpecial);

        let score = 0;
        if (hasLength) score++;
        if (hasUpper) score++;
        if (hasNumber) score++;
        if (hasSpecial) score++;

        // Strength levels
        if (score <= 1) {
            fill.style.width = '25%';
            fill.style.backgroundColor = '#ef4444'; // Red
            label.textContent = 'Weak';
            label.style.color = '#ef4444';
        } else if (score === 2) {
            fill.style.width = '50%';
            fill.style.backgroundColor = '#f59e0b'; // Amber
            label.textContent = 'Fair';
            label.style.color = '#f59e0b';
        } else if (score === 3) {
            fill.style.width = '75%';
            fill.style.backgroundColor = '#3b82f6'; // Blue
            label.textContent = 'Good';
            label.style.color = '#3b82f6';
        } else {
            fill.style.width = '100%';
            fill.style.backgroundColor = '#10b981'; // Emerald
            label.textContent = 'Strong & Secure';
            label.style.color = '#10b981';
        }
    });

    function updateCheckItem(element, isValid) {
        if (!element) return;
        const icon = element.querySelector('i');
        if (isValid) {
            element.classList.add('valid');
            if (icon) {
                icon.classList.remove('fa-circle-dot');
                icon.classList.add('fa-circle-check');
            }
        } else {
            element.classList.remove('valid');
            if (icon) {
                icon.classList.remove('fa-circle-check');
                icon.classList.add('fa-circle-dot');
            }
        }
    }
}

/**
 * Password Confirmation Match Validation
 */
function initPasswordMatch() {
    const password = document.getElementById('reg-password');
    const confirm = document.getElementById('reg-confirm-password');
    const matchStatus = document.getElementById('reg-match-status');

    if (!password || !confirm || !matchStatus) return;

    function checkMatch() {
        if (!confirm.value) {
            matchStatus.textContent = '';
            return;
        }
        if (password.value === confirm.value) {
            matchStatus.textContent = '✓ Passwords match';
            matchStatus.style.color = '#10b981';
        } else {
            matchStatus.textContent = '✕ Passwords do not match';
            matchStatus.style.color = '#ef4444';
        }
    }

    password.addEventListener('input', checkMatch);
    confirm.addEventListener('input', checkMatch);
}

/**
 * User Dashboard Tab Navigation
 */
function initDashboardTabs() {
    const dashTabs = document.querySelectorAll('.dash-tab-btn');
    const panels = document.querySelectorAll('.dash-panel');

    if (!dashTabs.length || !panels.length) return;

    dashTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetPanelId = tab.getAttribute('data-panel');

            dashTabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));

            tab.classList.add('active');
            const targetPanel = document.getElementById(targetPanelId);
            if (targetPanel) {
                targetPanel.classList.add('active');
            }
        });
    });
}
