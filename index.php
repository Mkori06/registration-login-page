<?php
/**
 * Main Authentication Portal (Login & Registration)
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

// Redirect logged in users directly to dashboard
require_guest();

// Active tab determination (default: 'login')
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'register' ? 'register' : 'login';

// Retrieve and clear flash message
$flash = get_flash();

// Sticky inputs for registration form
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Secure and modern PHP registration and login system with responsive glassmorphism UI.">
    <title>AuraAuth &bull; Secure Registration &amp; Login</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Ambient Glowing Background Elements -->
    <div class="bg-ambient">
        <div class="ambient-orb orb-1"></div>
        <div class="ambient-orb orb-2"></div>
        <div class="ambient-orb orb-3"></div>
    </div>
    <div class="bg-grid"></div>

    <main class="main-wrapper">
        <div class="auth-container">
            
            <!-- Brand Badge -->
            <div class="brand-header">
                <div class="brand-badge">
                    <i class="fa-solid fa-shield-halved brand-icon"></i>
                    <span class="brand-name">AuraAuth</span>
                </div>
                <p class="brand-tagline">Secure Authentication &amp; User Portal</p>
            </div>

            <!-- Glassmorphism Card -->
            <div class="glass-card">

                <!-- Sliding Tab Switcher -->
                <nav class="auth-tabs" aria-label="Authentication Selector">
                    <button type="button" 
                            class="tab-btn <?= $active_tab === 'login' ? 'active' : '' ?>" 
                            data-target="login"
                            id="tab-btn-login">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        <span>Sign In</span>
                    </button>
                    <button type="button" 
                            class="tab-btn <?= $active_tab === 'register' ? 'active' : '' ?>" 
                            data-target="register"
                            id="tab-btn-register">
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Create Account</span>
                    </button>
                </nav>

                <!-- Dynamic Flash Alerts -->
                <?php if ($flash): ?>
                    <div class="alert-box alert-<?= e($flash['type']) ?>" role="alert">
                        <?php if ($flash['type'] === 'success'): ?>
                            <i class="fa-solid fa-circle-check alert-icon"></i>
                        <?php elseif ($flash['type'] === 'error'): ?>
                            <i class="fa-solid fa-circle-exclamation alert-icon"></i>
                        <?php else: ?>
                            <i class="fa-solid fa-circle-info alert-icon"></i>
                        <?php endif; ?>
                        <div><?= $flash['message'] ?></div>
                    </div>
                <?php endif; ?>

                <!-- ========================================== -->
                <!-- SIGN IN FORM                               -->
                <!-- ========================================== -->
                <div id="login-form-wrapper" class="auth-form <?= $active_tab === 'login' ? 'active' : '' ?>">
                    <form action="login.php" method="POST" id="form-login" novalidate>
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <label for="login_id" class="form-label">
                                <span>Username or Email</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-envelope input-icon"></i>
                                <input type="text" 
                                       id="login_id" 
                                       name="login_id" 
                                       class="form-control" 
                                       placeholder="john_doe or john@example.com" 
                                       required 
                                       autocomplete="username">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="login_password" class="form-label">
                                <span>Password</span>
                                <a href="javascript:void(0)" onclick="alert('Demo notice: For security reasons, please contact system admin or register a new test account.');">Forgot password?</a>
                            </label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-lock input-icon"></i>
                                <input type="password" 
                                       id="login_password" 
                                       name="password" 
                                       class="form-control has-toggle" 
                                       placeholder="••••••••" 
                                       required 
                                       autocomplete="current-password">
                                <button type="button" class="password-toggle" data-for="login_password" aria-label="Toggle password visibility">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" id="remember" name="remember" class="custom-checkbox">
                            <label for="remember" class="check-label">Remember my session for 30 days</label>
                        </div>

                        <button type="submit" class="btn-primary" id="btn-submit-login">
                            <span>Sign In</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </form>
                </div>

                <!-- ========================================== -->
                <!-- REGISTRATION FORM                          -->
                <!-- ========================================== -->
                <div id="register-form-wrapper" class="auth-form <?= $active_tab === 'register' ? 'active' : '' ?>">
                    <form action="register.php" method="POST" id="form-register" novalidate>
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <label for="reg-fullname" class="form-label">Full Name</label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-id-card input-icon"></i>
                                <input type="text" 
                                       id="reg-fullname" 
                                       name="full_name" 
                                       class="form-control" 
                                       placeholder="e.g. Jane Cooper" 
                                       value="<?= e($old['full_name'] ?? '') ?>" 
                                       required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="reg-username" class="form-label">Username</label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-at input-icon"></i>
                                <input type="text" 
                                       id="reg-username" 
                                       name="username" 
                                       class="form-control" 
                                       placeholder="letters, numbers, underscore" 
                                       value="<?= e($old['username'] ?? '') ?>" 
                                       required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="reg-email" class="form-label">Email Address</label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-envelope input-icon"></i>
                                <input type="email" 
                                       id="reg-email" 
                                       name="email" 
                                       class="form-control" 
                                       placeholder="name@company.com" 
                                       value="<?= e($old['email'] ?? '') ?>" 
                                       required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="reg-password" class="form-label">Create Password</label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-lock input-icon"></i>
                                <input type="password" 
                                       id="reg-password" 
                                       name="password" 
                                       class="form-control has-toggle" 
                                       placeholder="Minimum 8 characters" 
                                       required>
                                <button type="button" class="password-toggle" data-for="reg-password" aria-label="Toggle password visibility">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>

                            <!-- Live Password Strength Meter -->
                            <div class="password-strength-wrap" id="reg-strength-wrap">
                                <div class="strength-meta">
                                    <span>Password Strength:</span>
                                    <span class="strength-label" id="reg-strength-label">Weak</span>
                                </div>
                                <div class="strength-bar-bg">
                                    <div class="strength-bar-fill" id="reg-strength-fill"></div>
                                </div>
                                <ul class="strength-checklist">
                                    <li class="checklist-item" id="check-length">
                                        <i class="fa-regular fa-circle-dot"></i> 8+ characters
                                    </li>
                                    <li class="checklist-item" id="check-upper">
                                        <i class="fa-regular fa-circle-dot"></i> Uppercase letter
                                    </li>
                                    <li class="checklist-item" id="check-number">
                                        <i class="fa-regular fa-circle-dot"></i> Number (0-9)
                                    </li>
                                    <li class="checklist-item" id="check-special">
                                        <i class="fa-regular fa-circle-dot"></i> Special symbol
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="reg-confirm-password" class="form-label">
                                <span>Confirm Password</span>
                                <span id="reg-match-status" style="font-size: 0.78rem;"></span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-check-double input-icon"></i>
                                <input type="password" 
                                       id="reg-confirm-password" 
                                       name="confirm_password" 
                                       class="form-control has-toggle" 
                                       placeholder="Repeat your password" 
                                       required>
                                <button type="button" class="password-toggle" data-for="reg-confirm-password" aria-label="Toggle password visibility">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary" id="btn-submit-register">
                            <span>Create Free Account</span>
                            <i class="fa-solid fa-sparkles"></i>
                        </button>
                    </form>
                </div>

            </div>

            <!-- Footer Info -->
            <footer class="auth-footer">
                <p>
                    <i class="fa-solid fa-lock" style="margin-right: 4px; color: #10b981;"></i> 
                    Protected with 256-Bit Strong Encryption &bull; PHP <?= phpversion() ?>
                </p>
            </footer>

        </div>
    </main>

    <!-- Custom Script -->
    <script src="assets/js/main.js"></script>
</body>
</html>
