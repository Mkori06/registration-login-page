<?php
/**
 * Protected User Dashboard
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

// Authentication Guard
require_login();

$user_id = (int)$_SESSION['user_id'];

// Handle Profile Updates & Password Changes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Invalid security token (CSRF).');
        header('Location: dashboard.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // 1. Update Profile Info
    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $bio       = trim($_POST['bio'] ?? '');

        if (mb_strlen($full_name) < 2 || mb_strlen($full_name) > 100) {
            set_flash('error', 'Full Name must be between 2 and 100 characters.');
            header('Location: dashboard.php');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Please provide a valid email address.');
            header('Location: dashboard.php');
            exit;
        }

        try {
            // Check if email is already taken by someone else
            $check_email = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
            $check_email->execute(['email' => $email, 'id' => $user_id]);
            if ($check_email->fetch()) {
                set_flash('error', 'This email address is already in use by another account.');
                header('Location: dashboard.php');
                exit;
            }

            // Update user record
            $update_stmt = $pdo->prepare('
                UPDATE users 
                SET full_name = :full_name, email = :email, bio = :bio 
                WHERE id = :id
            ');
            $update_stmt->execute([
                'full_name' => $full_name,
                'email'     => $email,
                'bio'       => $bio,
                'id'        => $user_id,
            ]);

            // Update session cache
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email']     = $email;
            $_SESSION['bio']       = $bio;

            set_flash('success', 'Your profile details have been updated successfully!');
            header('Location: dashboard.php');
            exit;

        } catch (PDOException $e) {
            error_log('Profile Update Error: ' . $e->getMessage());
            set_flash('error', 'Could not update profile. Please try again.');
            header('Location: dashboard.php');
            exit;
        }
    }

    // 2. Change Password
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (strlen($new_password) < 8) {
            set_flash('error', 'The new password must be at least 8 characters long.');
            header('Location: dashboard.php');
            exit;
        }

        if ($new_password !== $confirm_password) {
            set_flash('error', 'New passwords do not match.');
            header('Location: dashboard.php');
            exit;
        }

        try {
            // Fetch existing password hash
            $pwd_stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id');
            $pwd_stmt->execute(['id' => $user_id]);
            $current_hash = $pwd_stmt->fetchColumn();

            if (!$current_hash || !password_verify($current_password, $current_hash)) {
                set_flash('error', 'Incorrect current password. Please try again.');
                header('Location: dashboard.php');
                exit;
            }

            // Hash new password
            $new_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
            $update_pwd = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
            $update_pwd->execute(['password' => $new_hash, 'id' => $user_id]);

            set_flash('success', 'Your password has been changed securely!');
            header('Location: dashboard.php');
            exit;

        } catch (PDOException $e) {
            error_log('Password Change Error: ' . $e->getMessage());
            set_flash('error', 'Could not change password. Please try again.');
            header('Location: dashboard.php');
            exit;
        }
    }
}

// Fetch fresh user data from database
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

if (!$user) {
    // If user no longer exists, force logout
    header('Location: logout.php');
    exit;
}

// Dynamic initials
$words = explode(' ', trim($user['full_name']));
$initials = '';
foreach ($words as $w) {
    if (!empty($w)) {
        $initials .= mb_strtoupper(mb_substr($w, 0, 1));
        if (mb_strlen($initials) >= 2) break;
    }
}
if (empty($initials)) $initials = 'U';

// Greeting based on server time
$hour = (int)date('H');
if ($hour < 12) {
    $greeting = "Good morning";
} elseif ($hour < 18) {
    $greeting = "Good afternoon";
} else {
    $greeting = "Good evening";
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard &bull; <?= e($user['full_name']) ?></title>
    
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

    <div class="dashboard-wrapper">
        
        <!-- Top Navigation Bar -->
        <header class="dashboard-nav">
            <div class="brand-badge" style="margin-bottom: 0;">
                <i class="fa-solid fa-shield-halved brand-icon"></i>
                <span class="brand-name">AuraAuth</span>
            </div>

            <div class="nav-user-preview">
                <div class="avatar-badge"><?= e($initials) ?></div>
                <div class="nav-user-info">
                    <div class="user-name"><?= e($user['full_name']) ?></div>
                    <div class="user-handle">@<?= e($user['username']) ?></div>
                </div>
                <a href="logout.php" class="btn-logout" id="btn-logout" title="Sign out of your session">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Sign Out</span>
                </a>
            </div>
        </header>

        <!-- Flash Message Banner -->
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

        <!-- Dashboard Grid Layout -->
        <div class="dashboard-grid">

            <!-- Sidebar / Profile Card -->
            <aside class="glass-card profile-card">
                <div class="profile-header">
                    <div class="avatar-badge lg"><?= e($initials) ?></div>
                    <h2 class="profile-name"><?= e($user['full_name']) ?></h2>
                    <p class="profile-username">@<?= e($user['username']) ?></p>
                    <span class="profile-badge badge-verified">
                        <i class="fa-solid fa-shield-check"></i> <?= strtoupper(e($user['role'])) ?>
                    </span>
                </div>

                <p class="profile-bio">
                    "<?= e($user['bio'] ?: 'Welcome to my profile!') ?>"
                </p>

                <div class="profile-stats-list">
                    <div class="stat-item">
                        <span class="stat-label">
                            <i class="fa-regular fa-envelope"></i> Email:
                        </span>
                        <span class="stat-value" title="<?= e($user['email']) ?>">
                            <?= e($user['email']) ?>
                        </span>
                    </div>

                    <div class="stat-item">
                        <span class="stat-label">
                            <i class="fa-regular fa-calendar-check"></i> Member Since:
                        </span>
                        <span class="stat-value">
                            <?= date('M j, Y', strtotime($user['created_at'])) ?>
                        </span>
                    </div>

                    <div class="stat-item">
                        <span class="stat-label">
                            <i class="fa-regular fa-clock"></i> Last Login:
                        </span>
                        <span class="stat-value">
                            <?= $user['last_login'] ? date('M j, g:i A', strtotime($user['last_login'])) : 'First Session' ?>
                        </span>
                    </div>

                    <div class="stat-item">
                        <span class="stat-label">
                            <i class="fa-solid fa-lock"></i> Security:
                        </span>
                        <span class="stat-value" style="color: #34d399;">
                            Bcrypt (Cost 12)
                        </span>
                    </div>
                </div>
            </aside>

            <!-- Main Content Card with Navigation Tabs -->
            <section class="glass-card dashboard-content-card">
                <div class="dash-tabs" role="tablist">
                    <button type="button" class="dash-tab-btn active" data-panel="panel-overview" id="tab-overview">
                        <i class="fa-solid fa-chart-pie"></i>
                        <span>Overview</span>
                    </button>
                    <button type="button" class="dash-tab-btn" data-panel="panel-edit-profile" id="tab-edit-profile">
                        <i class="fa-solid fa-user-pen"></i>
                        <span>Edit Profile</span>
                    </button>
                    <button type="button" class="dash-tab-btn" data-panel="panel-change-password" id="tab-change-password">
                        <i class="fa-solid fa-key"></i>
                        <span>Security</span>
                    </button>
                </div>

                <!-- Panel 1: Overview -->
                <div class="dash-panel active" id="panel-overview">
                    <h1 class="section-title">
                        <span>👋 <?= $greeting ?>, <?= e(explode(' ', $user['full_name'])[0]) ?>!</span>
                    </h1>
                    <p class="section-desc">You are safely authenticated in your secure private session.</p>

                    <div class="feature-cards-grid">
                        <div class="feature-mini-card">
                            <div class="mini-card-icon icon-purple">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <span class="mini-card-title">Session Protection</span>
                            <span class="mini-card-value">Active &bull; HttpOnly</span>
                        </div>

                        <div class="feature-mini-card">
                            <div class="mini-card-icon icon-cyan">
                                <i class="fa-solid fa-database"></i>
                            </div>
                            <span class="mini-card-title">Database Engine</span>
                            <span class="mini-card-value">PDO Prepared</span>
                        </div>

                        <div class="feature-mini-card">
                            <div class="mini-card-icon icon-emerald">
                                <i class="fa-solid fa-user-check"></i>
                            </div>
                            <span class="mini-card-title">Account State</span>
                            <span class="mini-card-value">Verified &bull; Active</span>
                        </div>
                    </div>

                    <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.07); border-radius: var(--radius-sm); padding: 20px;">
                        <h3 style="font-size: 1rem; margin-bottom: 10px; color: #ffffff; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-circle-check" style="color: #10b981;"></i> Security Features Activated
                        </h3>
                        <ul style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.8; padding-left: 20px;">
                            <li><strong>Password Hashing:</strong> Passwords are protected using standard bcrypt cryptographic hashing with automatic salt generation.</li>
                            <li><strong>SQL Injection Prevention:</strong> 100% of queries use PDO parameterized prepared statements.</li>
                            <li><strong>CSRF Token Protection:</strong> Every POST request is verified against a cryptographically secure session token.</li>
                            <li><strong>Session Fixation Defense:</strong> Session IDs are regenerated upon authentication.</li>
                            <li><strong>XSS Protection:</strong> Output is sanitized against cross-site scripting attacks using ENT_QUOTES.</li>
                        </ul>
                    </div>
                </div>

                <!-- Panel 2: Edit Profile -->
                <div class="dash-panel" id="panel-edit-profile">
                    <h2 class="section-title">Edit Profile Information</h2>
                    <p class="section-desc">Update your personal details visible on your profile.</p>

                    <form action="dashboard.php" method="POST" id="form-update-profile">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_profile">

                        <div class="form-group">
                            <label for="edit-fullname" class="form-label">Full Name</label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-id-card input-icon"></i>
                                <input type="text" 
                                       id="edit-fullname" 
                                       name="full_name" 
                                       class="form-control" 
                                       value="<?= e($user['full_name']) ?>" 
                                       required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="edit-email" class="form-label">Email Address</label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-envelope input-icon"></i>
                                <input type="email" 
                                       id="edit-email" 
                                       name="email" 
                                       class="form-control" 
                                       value="<?= e($user['email']) ?>" 
                                       required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="edit-bio" class="form-label">Bio / Status</label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-pen-nib input-icon"></i>
                                <input type="text" 
                                       id="edit-bio" 
                                       name="bio" 
                                       class="form-control" 
                                       value="<?= e($user['bio']) ?>" 
                                       placeholder="Tell us a little about yourself...">
                            </div>
                        </div>

                        <button type="submit" class="btn-primary" style="margin-top: 10px;" id="btn-save-profile">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save Profile Changes</span>
                        </button>
                    </form>
                </div>

                <!-- Panel 3: Change Password -->
                <div class="dash-panel" id="panel-change-password">
                    <h2 class="section-title">Update Password</h2>
                    <p class="section-desc">Ensure your account is using a long, random password for security.</p>

                    <form action="dashboard.php" method="POST" id="form-change-password">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="change_password">

                        <div class="form-group">
                            <label for="current_password" class="form-label">Current Password</label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-lock input-icon"></i>
                                <input type="password" 
                                       id="current_password" 
                                       name="current_password" 
                                       class="form-control has-toggle" 
                                       placeholder="••••••••" 
                                       required>
                                <button type="button" class="password-toggle" data-for="current_password" aria-label="Toggle password visibility">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="new_password" class="form-label">New Password</label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-key input-icon"></i>
                                <input type="password" 
                                       id="new_password" 
                                       name="new_password" 
                                       class="form-control has-toggle" 
                                       placeholder="At least 8 characters" 
                                       required>
                                <button type="button" class="password-toggle" data-for="new_password" aria-label="Toggle password visibility">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-check-double input-icon"></i>
                                <input type="password" 
                                       id="confirm_new_password" 
                                       name="confirm_password" 
                                       class="form-control has-toggle" 
                                       placeholder="Repeat new password" 
                                       required>
                                <button type="button" class="password-toggle" data-for="confirm_new_password" aria-label="Toggle password visibility">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary" style="margin-top: 10px;" id="btn-update-password">
                            <i class="fa-solid fa-shield-keyhole"></i>
                            <span>Update Password</span>
                        </button>
                    </form>
                </div>

            </section>
        </div>

    </div>

    <!-- Custom Script -->
    <script src="assets/js/main.js"></script>
</body>
</html>
